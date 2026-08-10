<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Network;

use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;

/**
 * Where this deployment is willing to send outbound requests.
 *
 * Every destination the library fetches - federation endpoints, `jwks_uri`, `signed_jwks_uri`, `request_uri`,
 * Status List Tokens - can be named by a party other than the deployment operator, which is what makes an
 * unrestricted fetcher a way to reach the deployment's own network from the outside. Non-public destinations
 * are therefore refused by default, and a deployment that legitimately fetches from an internal address says
 * so explicitly through the allowed hosts and ranges.
 *
 * The policy is deliberately usable on its own, without a request in hand, so that a destination can be
 * refused when it is registered rather than only when it is first fetched.
 *
 * Applying this to requests is the job of the middleware from {@see middleware()}, which revalidates every
 * redirect hop and pins what it validated.
 *
 * A caveat worth passing on: SSRF defence at the application layer is leaky by nature, and an egress firewall
 * or proxy remains the stronger control. This raises the bar; it does not make fetching arbitrary URLs safe.
 *
 * @see \SimpleSAML\Test\OpenID\Network\DestinationPolicyTest
 */
class DestinationPolicy
{
    /**
     * Every destination the library fetches is https by specification, and redirects are already restricted
     * to https, so an outbound request over plain http is a deployment choice rather than a default.
     */
    public const DEFAULT_ALLOWED_SCHEMES = ['https'];

    /**
     * @var array<string,int> The port a scheme connects on when the URI does not name one.
     */
    protected const DEFAULT_PORTS = [
        'https' => 443,
        'http' => 80,
    ];


    /** @var list<string> */
    protected readonly array $allowedSchemes;

    /** @var list<string> */
    protected readonly array $allowedHosts;

    /** @var list<string> */
    protected readonly array $allowedCidrs;

    protected readonly AddressResolver $addressResolver;

    protected ?DestinationGuardMiddleware $middleware = null;


    /**
     * @param list<string> $allowedSchemes URI schemes an outbound request may use.
     * @param list<string> $allowedHosts Hosts this deployment declares legitimate whatever they resolve to,
     *        for an internal destination that a range can not describe (a name resolved outside DNS, or one
     *        whose address is not fixed). Note that allowing a host means trusting whoever controls that name
     *        with where the request goes, so keep the list to destinations the deployment operates itself.
     *        Compared case-insensitively; an address written here allows that address as a literal host.
     * @param list<string> $allowedCidrs Ranges to treat as permitted alongside the public ones, as CIDR. Use
     *        the narrowest range that covers the destination ("10.1.2.3/32" rather than "10.0.0.0/8"). An
     *        IPv4 range also covers the v4-mapped and NAT64 spellings of the addresses in it.
     * @param \SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum $addressPinningMode How strictly to insist
     *        on connecting to the address that was validated. See the enum for what each mode costs.
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException On unusable configuration, rather than
     *         letting a range that can never match pass for a working exemption.
     */
    public function __construct(
        array $allowedSchemes = self::DEFAULT_ALLOWED_SCHEMES,
        array $allowedHosts = [],
        array $allowedCidrs = [],
        protected readonly AddressPinningModeEnum $addressPinningMode = AddressPinningModeEnum::Preferred,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly AddressValidator $addressValidator = new AddressValidator(),
        ?AddressResolver $addressResolver = null,
    ) {
        $this->addressResolver = $addressResolver ?? new AddressResolver($this->addressValidator);
        $this->allowedSchemes = $this->prepareAllowedSchemes($allowedSchemes);
        $this->allowedHosts = $this->prepareAllowedHosts($allowedHosts);
        $this->allowedCidrs = $this->prepareAllowedCidrs($allowedCidrs);
    }


    /**
     * @return list<string>
     */
    public function getAllowedSchemes(): array
    {
        return $this->allowedSchemes;
    }


    /**
     * @return list<string>
     */
    public function getAllowedHosts(): array
    {
        return $this->allowedHosts;
    }


    /**
     * @return list<string>
     */
    public function getAllowedCidrs(): array
    {
        return $this->allowedCidrs;
    }


    public function getAddressPinningMode(): AddressPinningModeEnum
    {
        return $this->addressPinningMode;
    }


    /**
     * The middleware that applies this policy to a Guzzle client.
     *
     * Push it onto a handler stack (`$stack->push($policy->middleware())`) to guard a client this library did
     * not build. Pushing puts it below the redirect middleware, which is where it has to sit so that it runs
     * again for every hop instead of only for the original request.
     *
     * @param ?\SimpleSAML\OpenID\Network\AddressPinner $addressPinner What the middleware may assume about
     *        the transport it is being attached to. One policy can guard several clients, and pinning is a
     *        property of the client rather than of the policy, so a caller that knows something about a
     *        particular transport says so here and gets a middleware of its own.
     */
    public function middleware(?AddressPinner $addressPinner = null): DestinationGuardMiddleware
    {
        if (!is_null($addressPinner)) {
            return new DestinationGuardMiddleware($this, $addressPinner, $this->logger);
        }

        return $this->middleware ??= new DestinationGuardMiddleware($this, logger: $this->logger);
    }


    /**
     * Whether a destination may be fetched, without raising anything.
     *
     * Meant for the places that already have their own way of reporting a rejected destination, such as
     * refusing a client registration that names one.
     */
    public function isUriAllowed(string|UriInterface $uri): bool
    {
        try {
            $this->validateUri($uri);
        } catch (DestinationPolicyException) {
            return false;
        }

        return true;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    public function assertUriIsAllowed(string|UriInterface $uri): void
    {
        $this->validateUri($uri);
    }


    /**
     * Check a destination and report what it resolved to.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    public function validateUri(string|UriInterface $uri): ValidatedDestination
    {
        $uri = $this->toUri($uri);

        $this->assertSchemeIsAllowed($uri);
        $this->assertNoCredentials($uri);

        $host = $this->hostFrom($uri);
        $port = $this->portFrom($uri);
        // Kept alongside the compared form, since a pin has to be made for the spelling the request uses.
        $requestHost = $this->requestHostFrom($uri);

        if (in_array($host, $this->allowedHosts, true)) {
            $this->logger?->debug(
                'Outbound destination host is explicitly allowed, so its addresses are not checked.',
                ['host' => $host],
            );

            return new ValidatedDestination($host, $port, [], isHostAllowListed: true);
        }

        // A host that is already an address has nothing to resolve, and nothing that could resolve to
        // something else later, so it is judged as it stands.
        if ($this->addressValidator->isAddress($host)) {
            $this->assertAddressIsAllowed($host, $host);

            return new ValidatedDestination($host, $port, [$host], isHostLiteralAddress: true);
        }

        $addresses = $this->addressResolver->resolve($host);

        if ($addresses === []) {
            throw new DestinationPolicyException(
                sprintf(
                    'Outbound request to host %s refused: the host could not be resolved to any address.',
                    $host,
                ),
            );
        }

        // Every address, not just the first: a host that answers with one permitted and one internal address
        // would otherwise be a matter of which one the connection happens to pick.
        foreach ($addresses as $address) {
            $this->assertAddressIsAllowed($address, $host);
        }

        return new ValidatedDestination($host, $port, $addresses, requestHost: $requestHost);
    }


    /**
     * Whether an outbound request may be routed to a given address.
     */
    public function isAddressAllowed(string $address): bool
    {
        if ($this->addressValidator->isPublic($address)) {
            return true;
        }

        return $this->addressValidator->matchesAnyCidr($address, $this->allowedCidrs);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function assertAddressIsAllowed(string $address, string $host): void
    {
        if ($this->isAddressAllowed($address)) {
            return;
        }

        $reason = ($address === $host) ?
        sprintf('host %s is not a public address', $host) :
        sprintf('host %s resolves to %s, which is not a public address', $host, $address);

        throw new DestinationPolicyException(
            sprintf(
                'Outbound request refused: %s. Allow the host or the address range explicitly if this ' .
                'destination is legitimate.',
                $reason,
            ),
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function assertSchemeIsAllowed(UriInterface $uri): void
    {
        $scheme = strtolower($uri->getScheme());

        if (in_array($scheme, $this->allowedSchemes, true)) {
            return;
        }

        throw new DestinationPolicyException(
            sprintf(
                'Outbound request refused: scheme "%s" is not among the allowed schemes (%s).',
                $scheme,
                implode(', ', $this->allowedSchemes),
            ),
        );
    }


    /**
     * Credentials in the URI are refused rather than stripped: nothing this library fetches uses them, and
     * they are a way to make a destination read as one host while the request goes to another.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function assertNoCredentials(UriInterface $uri): void
    {
        if ($uri->getUserInfo() === '') {
            return;
        }

        throw new DestinationPolicyException('Outbound request refused: the destination URI carries credentials.');
    }


    /**
     * The host to judge, with the brackets around an IPv6 literal removed and a trailing root label dropped,
     * so that one address or name has one spelling here.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function hostFrom(UriInterface $uri): string
    {
        $host = $this->normalizeHost($uri->getHost());

        if ($host === '') {
            throw new DestinationPolicyException('Outbound request refused: the destination URI has no host.');
        }

        // A bracketed host is an address literal by definition, so one that is not an address (a zone index,
        // say, or anything else the brackets were used to smuggle) is refused instead of going to DNS.
        if (str_starts_with($uri->getHost(), '[') && !$this->addressValidator->isAddress($host)) {
            throw new DestinationPolicyException(
                sprintf('Outbound request refused: "%s" is not a valid address literal.', $uri->getHost()),
            );
        }

        return $host;
    }


    /**
     * The port the connection will use, or null when it can not be worked out.
     *
     * A pin is made for one host and port pair, so a port that is not a real one has to read as unknown
     * rather than end up in an entry that silently matches nothing.
     */
    protected function portFrom(UriInterface $uri): ?int
    {
        $port = $uri->getPort() ?? (self::DEFAULT_PORTS[strtolower($uri->getScheme())] ?? null);

        return (is_null($port) || $port < 1 || $port > 65535) ? null : $port;
    }


    /**
     * The host as the request writes it, differing from the compared form only in that a trailing root label
     * is left on. A client keys its resolver cache on this spelling, so it is the one a pin has to name.
     */
    protected function requestHostFrom(UriInterface $uri): string
    {
        return $this->unbracketHost(strtolower(trim($uri->getHost())));
    }


    protected function normalizeHost(string $host): string
    {
        // "example.org." and "example.org" are the same name; only one of them should have to be allowed.
        return rtrim($this->unbracketHost(strtolower(trim($host))), '.');
    }


    protected function unbracketHost(string $host): string
    {
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            return substr($host, 1, -1);
        }

        return $host;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function toUri(string|UriInterface $uri): UriInterface
    {
        try {
            return Utils::uriFor($uri);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new DestinationPolicyException(
                'Outbound request refused: the destination is not a usable URI. Error was: ' .
                $invalidArgumentException->getMessage(),
                $invalidArgumentException->getCode(),
                $invalidArgumentException,
            );
        }
    }


    /**
     * @param list<string> $allowedSchemes
     * @return list<string>
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function prepareAllowedSchemes(array $allowedSchemes): array
    {
        $preparedSchemes = array_values(array_unique(array_map(
            fn(string $scheme): string => strtolower(trim($scheme)),
            $allowedSchemes,
        )));

        if (in_array('', $preparedSchemes, true) || $preparedSchemes === []) {
            throw new DestinationPolicyException('Destination policy needs at least one allowed URI scheme.');
        }

        return $preparedSchemes;
    }


    /**
     * @param list<string> $allowedHosts
     * @return list<string>
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function prepareAllowedHosts(array $allowedHosts): array
    {
        $preparedHosts = array_values(array_unique(array_map(
            $this->normalizeHost(...),
            $allowedHosts,
        )));

        if (in_array('', $preparedHosts, true)) {
            throw new DestinationPolicyException('Destination policy was given an empty allowed host.');
        }

        return $preparedHosts;
    }


    /**
     * @param list<string> $allowedCidrs
     * @return list<string>
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException
     */
    protected function prepareAllowedCidrs(array $allowedCidrs): array
    {
        $preparedCidrs = array_values(array_unique(array_map(
            trim(...),
            $allowedCidrs,
        )));

        foreach ($preparedCidrs as $cidr) {
            if ($this->addressValidator->isValidCidr($cidr)) {
                continue;
            }

            throw new DestinationPolicyException(
                sprintf(
                    'Destination policy was given "%s" as an allowed range, which is not a valid CIDR.',
                    $cidr,
                ),
            );
        }

        return $preparedCidrs;
    }
}
