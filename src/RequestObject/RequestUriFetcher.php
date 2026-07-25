<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\RequestObject;

use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

/**
 * @see \SimpleSAML\Test\OpenID\RequestObject\RequestUriFetcherTest
 */
class RequestUriFetcher
{
    public function __construct(
        protected readonly ArtifactFetcher $artifactFetcher,
    ) {
    }


    /**
     * Fetch a Request Object from the request_uri URL.
     * Enforces that the URI has https scheme, handles timeout,
     * and reads response body stream up to a maximum byte size.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fetch(string $uri, int $timeout = 5, ?int $maxSizeBytes = null): string
    {
        if (!str_starts_with(strtolower($uri), 'https://')) {
            throw new FetchException('The request_uri MUST use the https scheme.');
        }

        try {
            // The size limit is applied to the transfer itself, not only to the read that follows.
            $response = $this->artifactFetcher->fromNetwork(
                $uri,
                HttpMethodsEnum::GET,
                ['timeout' => $timeout],
                $maxSizeBytes,
            );

            return $this->artifactFetcher->readResponseBodyAsString($response, $maxSizeBytes);
        } catch (\Throwable $throwable) {
            if ($throwable instanceof FetchException) {
                throw $throwable;
            }

            throw new FetchException(
                'Failed to fetch request_uri from network: ' . $throwable->getMessage(),
                (int)$throwable->getCode(),
                $throwable,
            );
        }
    }
}
