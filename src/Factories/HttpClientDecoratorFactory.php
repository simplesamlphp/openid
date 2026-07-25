<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use GuzzleHttp\Client;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;

/**
 * @see \SimpleSAML\Test\OpenID\Factories\HttpClientDecoratorFactoryTest
 */
class HttpClientDecoratorFactory
{
    /**
     * @param \GuzzleHttp\Client|null $client A pre-built client. If given, $httpClientConfig is ignored.
     * @param array<string,mixed> $httpClientConfig Guzzle client options merged over the defaults when no
     *        $client is supplied. See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param int $maxFetchSizeBytes Maximum response body size to read, regardless of which client is used.
     *        Enforced with a per request "sink" and "on_headers", which take precedence over client level
     *        defaults for those two options. So a "sink" or "on_headers" set in $httpClientConfig (or on a
     *        pre-built $client) does not apply to requests made through the decorator. Pass them per request
     *        instead, which for "sink" also opts that request out of the size cap. Guzzle features that swap
     *        the sink out mid transfer, notably digest "auth", likewise bypass the cap during their retries.
     */
    public function build(
        ?Client $client = null,
        array $httpClientConfig = [],
        int $maxFetchSizeBytes = HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
    ): HttpClientDecorator {
        if (!is_null($client)) {
            return new HttpClientDecorator($client, $maxFetchSizeBytes);
        }

        if ($httpClientConfig === []) {
            return new HttpClientDecorator(maxFetchSizeBytes: $maxFetchSizeBytes);
        }

        return new HttpClientDecorator(
            /** @phpstan-ignore argument.type */
            new Client(array_merge(HttpClientDecorator::DEFAULT_HTTP_CLIENT_CONFIG, $httpClientConfig)),
            $maxFetchSizeBytes,
        );
    }
}
