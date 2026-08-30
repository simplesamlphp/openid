<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Utils;

use DateInterval;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
use SimpleSAML\OpenID\Exceptions\FetchException;
use Throwable;

/**
 * @see \SimpleSAML\Test\OpenID\Utils\ArtifactFetcherTest
 */
class ArtifactFetcher
{
    /**
     * How much of an artifact reaches the log when artifact logging is on. Enough to recognise a document
     * by, far short of what a remote endpoint could otherwise write into this deployment's log.
     */
    public const DEFAULT_MAX_LOGGED_ARTIFACT_LENGTH = 2048;


    protected readonly int $maxLoggedArtifactLength;


    /**
     * @param bool $logArtifacts Whether a fetched or cached artifact appears in the debug log at all.
     *        Off by default: what is fetched comes from a destination that some other party named, so it is
     *        that party rather than this deployment who would be deciding what its log fills up with, and a
     *        body carrying newlines can put lines into the log that look like the deployment's own. Turn it
     *        on while debugging a fetch, where the destinations are ones the deployment configured.
     * @param int $maxLoggedArtifactLength How much of an artifact is logged once $logArtifacts is on.
     *        Values below one are raised to one; there is deliberately no way to ask for no limit.
     */
    public function __construct(
        protected readonly HttpClientDecorator $httpClientDecorator,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly bool $logArtifacts = false,
        int $maxLoggedArtifactLength = self::DEFAULT_MAX_LOGGED_ARTIFACT_LENGTH,
    ) {
        $this->maxLoggedArtifactLength = max(1, $maxLoggedArtifactLength);
    }


    /**
     * What an artifact contributes to a log context: nothing at all unless logging it was asked for, and
     * never more than the configured length once it was.
     *
     * @return array<string, mixed>
     */
    protected function artifactLogContext(mixed $artifact): array
    {
        if (!$this->logArtifacts) {
            return [];
        }

        // Only a string is something the length cap can bound, so nothing else is logged at all. The one
        // call site that can reach here with another type already reports what that type was, which is the
        // useful part of a cached value that should have been a string and was not.
        if (!is_string($artifact)) {
            return [];
        }

        $value = $this->artifactForLog($artifact);

        // Anything other than the artifact itself is reported as such, which covers a value the cap cut
        // short and equally one whose unencodable bytes were replaced. Either way what is logged is not
        // what was fetched, and a reader has to be able to tell.
        if ($value === $artifact) {
            return ['artifact' => $value];
        }

        return [
            'artifact' => $value,
            'artifactLength' => strlen($artifact),
            'artifactTruncated' => true,
        ];
    }


    /**
     * An artifact cut to the configured length and left as something a log formatter can encode.
     *
     * Cutting on a character boundary is not enough on its own: what was fetched is bytes chosen elsewhere
     * and need not have been valid UTF-8 to begin with. A record a JSON formatter refuses would turn a
     * successful fetch into a logging failure, so whatever is left invalid is replaced rather than passed on.
     */
    protected function artifactForLog(string $artifact): string
    {
        $repaired = mb_convert_encoding(
            mb_strcut($artifact, 0, $this->maxLoggedArtifactLength, 'UTF-8'),
            'UTF-8',
            'UTF-8',
        );

        // Cut again, because repairing can grow the string: a deployment that has set the substitute
        // character to U+FFFD spends three bytes on every one-byte sequence it replaces, which would put
        // the result back over the limit the first cut brought it under.
        return mb_strcut($repaired, 0, $this->maxLoggedArtifactLength, 'UTF-8');
    }


    public function fromCacheAsString(string $keyElement, string ...$keyElements): ?string
    {
        if (is_null($this->cacheDecorator)) {
            $this->logger?->debug(
                'Cache instance not available, skipping cache query.',
                ['keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return null;
        }

        try {
            $artifact = $this->cacheDecorator->get(null, $keyElement, ...$keyElements);
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error trying to get artifact from cache: ' . $throwable->getMessage(),
                ['keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return null;
        }

        if (is_null($artifact)) {
            $this->logger?->debug(
                'Artifact not found in cache.',
                ['keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return null;
        }

        if (is_string($artifact)) {
            $this->logger?->debug(
                'Artifact found in cache, returning.',
                $this->artifactLogContext($artifact) +
                ['keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return $artifact;
        }

        $this->logger?->warning(
            'Unexpected value for cached artifact (expected string).',
            $this->artifactLogContext($artifact) +
            ['artifactType' => get_debug_type($artifact), 'keyElement' => $keyElement, 'keyElements' => $keyElements],
        );

        return null;
    }


    /**
     * @param array<string, mixed> $options See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param ?int $maxSizeBytes Overrides the configured maximum response body size for this single request.
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException When the destination, or one of the
     *         redirect hops taken towards it, is not one the deployment permits.
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetwork(
        string $uri,
        HttpMethodsEnum $httpMethodsEnum = HttpMethodsEnum::GET,
        array $options = [],
        ?int $maxSizeBytes = null,
    ): ResponseInterface {
        $this->logger?->debug('Fetching artifact on network from URI.', ['uri' => $uri]);
        try {
            $response = $this->httpClientDecorator->request($httpMethodsEnum, $uri, $options, $maxSizeBytes);
        } catch (DestinationPolicyException $destinationPolicyException) {
            // A refused destination is a policy decision rather than a transport failure, and a caller that
            // reports the two differently needs it to stay recognizable instead of becoming a fetch error.
            $this->logger?->error($destinationPolicyException->getMessage(), ['uri' => $uri]);

            throw $destinationPolicyException;
        } catch (Throwable $throwable) {
            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $throwable->getMessage(),
            );
            $this->logger?->error($message);
            throw new FetchException($message, (int)$throwable->getCode(), $throwable);
        }

        $this->logger?->debug('Artifact fetched on network from URI, returning HTTP response.', ['uri' => $uri]);

        return $response;
    }


    /**
     * Request options that stop a single request from running longer than the given number of seconds.
     *
     * @see \SimpleSAML\OpenID\Decorators\HttpClientDecorator::timeoutCeilingOptions()
     * @return array<string,mixed>
     */
    public function timeoutCeilingOptions(float $deadlineTimestamp): array
    {
        return $this->httpClientDecorator->timeoutCeilingOptions($deadlineTimestamp);
    }


    /**
     * Read a response body into a string, up to the maximum size allowed by the HTTP client decorator.
     *
     * @param ?int $maxSizeBytes Overrides the configured maximum for this single read.
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function readResponseBodyAsString(ResponseInterface $response, ?int $maxSizeBytes = null): string
    {
        try {
            return $this->httpClientDecorator->readResponseBodyAsString($response, $maxSizeBytes);
        } catch (Throwable $throwable) {
            $message = 'Error reading HTTP response body. Error was: ' . $throwable->getMessage();
            $this->logger?->error($message);
            throw new FetchException($message, (int)$throwable->getCode(), $throwable);
        }
    }


    /**
     * @param ?int $maxSizeBytes Overrides the configured maximum response body size for this single fetch.
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetworkAsString(string $uri, ?int $maxSizeBytes = null): string
    {
        $this->logger?->debug('Fetching artifact on network from URI (as string).', ['uri' => $uri]);

        $artifact = $this->readResponseBodyAsString(
            $this->fromNetwork($uri, HttpMethodsEnum::GET, [], $maxSizeBytes),
            $maxSizeBytes,
        );

        $this->logger?->debug(
            'Fetched artifact on network from URI as string.',
            $this->artifactLogContext($artifact) + ['uri' => $uri],
        );

        return $artifact;
    }


    public function cacheIt(string $artifact, int|DateInterval $ttl, string $keyElement, string ...$keyElements): void
    {
        if (is_null($this->cacheDecorator)) {
            $this->logger?->debug(
                'Cache instance not available, skipping caching.',
                $this->artifactLogContext($artifact) +
                ['ttl' => $ttl, 'keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return;
        }

        try {
            $this->cacheDecorator->set(
                $artifact,
                $ttl,
                $keyElement,
                ...$keyElements,
            );
            $this->logger?->debug(
                'Artifact saved to cache.',
                $this->artifactLogContext($artifact) +
                ['ttl' => $ttl, 'keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error saving artifact to cache: ' . $throwable->getMessage(),
                $this->artifactLogContext($artifact) +
                ['ttl' => $ttl, 'keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
        }
    }
}
