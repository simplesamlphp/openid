<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Network;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;

/**
 * Applies a destination policy to every request a Guzzle client makes, and pins what it validated.
 *
 * Belongs below Guzzle's redirect middleware, which is where `HandlerStack::push()` puts it: a validated first
 * hop that redirects inward is the whole attack, so each hop has to be validated and pinned in its own right
 * rather than the original request standing in for the chain.
 *
 * @see \SimpleSAML\Test\OpenID\Network\DestinationGuardMiddlewareTest
 */
class DestinationGuardMiddleware
{
    /**
     * Whether the loss of pinning has already been reported, so that a deployment which can not pin gets one
     * warning rather than one per request.
     */
    protected bool $hasReportedUnavailablePinning = false;


    public function __construct(
        protected readonly DestinationPolicy $destinationPolicy,
        protected readonly AddressPinner $addressPinner = new AddressPinner(),
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * @param callable(\Psr\Http\Message\RequestInterface, array<mixed>): \GuzzleHttp\Promise\PromiseInterface $handler
     * @return callable(\Psr\Http\Message\RequestInterface, array<mixed>): \GuzzleHttp\Promise\PromiseInterface
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            $this->assertRequestDoesNotOverrideTheDestination($options);

            $destination = $this->destinationPolicy->validateUri($request->getUri());

            return $handler($request, $this->withPinnedAddresses($destination, $options));
        };
    }


    /**
     * Refuse a request carrying a cURL option that would take the destination out of the policy's hands.
     *
     * These are a different matter from the options that only void a pin: CURLOPT_URL replaces the address
     * that was just validated, and CURLOPT_FOLLOWLOCATION hands redirect following to libcurl, underneath
     * this middleware, so the hops it takes are never seen here at all. Neither leaves anything to weaken
     * the guarantee about - there would simply be no guarantee - so they are refused whatever the pinning
     * mode says.
     *
     * Guzzle 8 rejects both on its own. Guzzle 7 passes them through to the handler, so the policy has to.
     *
     * @param array<mixed> $options
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function assertRequestDoesNotOverrideTheDestination(array $options): void
    {
        $curlOptions = $options[RequestOptions::CURL] ?? null;

        if (!is_array($curlOptions)) {
            return;
        }

        foreach ($this->destinationOverridingCurlOptions() as $name => $option) {
            if (empty($curlOptions[$option])) {
                continue;
            }

            throw new DestinationPolicyException(
                sprintf(
                    'Outbound request refused: the cURL option %s decides where the request goes, or which ' .
                    'redirects it follows, below the destination policy, leaving nothing for the policy to ' .
                    'check.',
                    $name,
                ),
            );
        }
    }


    /**
     * Named one by one and behind defined(), since the cURL extension is not a requirement of this library.
     *
     * @return array<string, int>
     */
    protected function destinationOverridingCurlOptions(): array
    {
        $options = [];

        if (defined('CURLOPT_URL')) {
            $options['CURLOPT_URL'] = CURLOPT_URL;
        }

        if (defined('CURLOPT_FOLLOWLOCATION')) {
            $options['CURLOPT_FOLLOWLOCATION'] = CURLOPT_FOLLOWLOCATION;
        }

        return $options;
    }


    /**
     * @param array<mixed> $options
     * @return array<mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function withPinnedAddresses(ValidatedDestination $destination, array $options): array
    {
        $addressPinningMode = $this->destinationPolicy->getAddressPinningMode();

        if ($addressPinningMode === AddressPinningModeEnum::Disabled) {
            return $options;
        }

        // A destination with no second resolution ahead of it has nothing to pin, and nothing to report
        // either: an address written into the URI can not turn into another one, and an allowed host was
        // deliberately exempted from the address check. Anything else is a name that will be resolved again,
        // so failing to pin it is a loss rather than an absence.
        if ($destination->isHostLiteralAddress || $destination->isHostAllowListed) {
            return $options;
        }

        if ($destination->isPinnable() && $this->addressPinner->isSupported($options)) {
            return $this->addressPinner->pin($options, $destination);
        }

        if ($addressPinningMode === AddressPinningModeEnum::Required) {
            throw new DestinationPolicyException(
                sprintf(
                    'Outbound request to host %s refused: the addresses it was validated against can not be ' .
                    'pinned to the connection, so the host would be resolved a second time. Address pinning ' .
                    'is required by configuration.',
                    $destination->host,
                ),
            );
        }

        $this->reportUnavailablePinning($destination);

        return $options;
    }


    /**
     * Report that the request is going out on the weaker guarantee: the destination was validated, but the
     * connection resolves the host again, so a name whose answer changes between the two can still be
     * followed inward.
     */
    protected function reportUnavailablePinning(ValidatedDestination $destination): void
    {
        if ($this->hasReportedUnavailablePinning) {
            $this->logger?->debug(
                'Outbound destination validated without pinning the address.',
                ['host' => $destination->host],
            );

            return;
        }

        $this->hasReportedUnavailablePinning = true;

        $this->logger?->warning(
            'Outbound destinations are being validated without pinning the validated address, because the ' .
            'requests are not made through the cURL handler. The host is resolved again when the connection ' .
            'is made, so a destination whose DNS answer changes between the check and the connection can ' .
            'still be reached. Install the cURL extension, or set the address pinning mode to required to ' .
            'refuse such requests instead. This is reported once.',
            ['host' => $destination->host],
        );
    }
}
