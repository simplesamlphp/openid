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
     */
    public function build(?Client $client = null, array $httpClientConfig = []): HttpClientDecorator
    {
        if (!is_null($client)) {
            return new HttpClientDecorator($client);
        }

        if ($httpClientConfig === []) {
            return new HttpClientDecorator();
        }

        return new HttpClientDecorator(
            /** @phpstan-ignore argument.type */
            new Client(array_merge(HttpClientDecorator::DEFAULT_HTTP_CLIENT_CONFIG, $httpClientConfig)),
        );
    }
}
