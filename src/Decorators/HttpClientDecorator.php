<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Decorators;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Exceptions\HttpException;
use Throwable;

class HttpClientDecorator
{
    public const DEFAULT_HTTP_CLIENT_CONFIG = [RequestOptions::ALLOW_REDIRECTS => true,];


    public function __construct(public readonly Client $client = new Client(self::DEFAULT_HTTP_CLIENT_CONFIG))
    {
    }


    /**
     * @param array<string, mixed> $options See https://docs.guzzlephp.org/en/stable/request-options.html
     * @throws \SimpleSAML\OpenID\Exceptions\HttpException
     */
    public function request(
        HttpMethodsEnum $httpMethodsEnum,
        string $uri,
        array $options = [],
    ): ResponseInterface {
        try {
            $response = $this->client->request($httpMethodsEnum->value, $uri, $options);
        } catch (Throwable $throwable) {
            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $throwable->getMessage(),
            );
            throw new HttpException($message, (int)$throwable->getCode(), $throwable);
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $message = sprintf(
                'Unexpected HTTP response for URI %s. Status code: %s, reason: %s.',
                $uri,
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            );
            throw new HttpException($message);
        }

        return $response;
    }
}
