<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\RequestObject;

use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

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
    public function fetch(string $uri, int $timeout = 5, int $maxSizeBytes = 102400): string
    {
        if (!str_starts_with(strtolower($uri), 'https://')) {
            throw new FetchException('The request_uri MUST use the https scheme.');
        }

        try {
            $response = $this->artifactFetcher->fromNetwork($uri, HttpMethodsEnum::GET, [
                'timeout' => $timeout,
                'stream' => true,
            ]);

            $body = $response->getBody();
            $content = '';

            while (!$body->eof()) {
                $chunk = $body->read(8192);
                $content .= $chunk;

                if (strlen($content) > $maxSizeBytes) {
                    throw new FetchException(
                        'The request_uri response body size exceeded the limit of ' . $maxSizeBytes . ' bytes.',
                    );
                }
            }

            return $content;
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
