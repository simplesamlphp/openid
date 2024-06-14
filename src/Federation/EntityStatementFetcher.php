<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\Module\oidc\Codebooks\HttpHeaderValues\ContentTypeEnum;
use SimpleSAML\OpenID\Codebooks\HttpHeadersEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Codebooks\WellKnownEnum;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwt\Parser;
use Throwable;

class EntityStatementFetcher
{
    public function __construct(
        private readonly \DateInterval $maxCacheDuration = new \DateInterval('PT6H'),
        private readonly ?CacheInterface $cache = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ClientInterface $httpClient = new Client(),
        private readonly RequestFactoryInterface $requestFactory = new HttpFactory(),
        private readonly Helpers $helpers = new Helpers(),
        private readonly Parser $parser = new Parser(),
    ) {
    }

    /**
     * @param non-empty-string $entityId
     * @param \SimpleSAML\OpenID\Codebooks\WellKnownEnum $wellKnownEnum
     * @return string
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function forWellKnown(
        string $entityId,
        WellKnownEnum $wellKnownEnum = WellKnownEnum::OpenIdFederation,
    ): string {
        $wellKnownUri = $wellKnownEnum->uriFor($entityId);
        $this->logger?->debug(
            'Entity statement fetch initiated.',
            compact('entityId', 'wellKnownUri', 'wellKnownEnum'),
        );
        $jws = null;
        $cacheKey = $this->helpers->cache()->keyFor($wellKnownUri);
        try {
            $jws = $this->cache?->get($cacheKey);
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error trying to get entity statement from cache: ' . $exception->getMessage(),
                compact('wellKnownUri', 'cacheKey'),
            );
        }

        if (is_null($jws)) {
            $this->logger?->debug(
                'Entity statement not cached, proceeding with network fetch.',
                compact('entityId', 'wellKnownUri'),
            );
            $jws = $this->fromNetwork($wellKnownUri);
        }

        // TODO mivanci validate header, iat, exp.
        $payload = $this->parser->jwsPayload($jws);

        $expiration = (int)($payload['exp'] ?? 0);
        $duration = $this->helpers->cache()->maxDuration($this->maxCacheDuration, $expiration);

        $this->logger?->debug('Fetched entity statement.', compact('wellKnownUri', 'jws'));
        $this->cache?->set($cacheKey, $jws, $duration);

        return $jws;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetwork(string $uri): string
    {
        try {
            $request = $this->requestFactory->createRequest(HttpMethodsEnum::GET->value, $uri);
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $e->getMessage(),
            );
            $this->logger?->error($message);
            throw new FetchException($message, $e->getCode(), $e);
        }

        if ($response->getStatusCode() !== 200) {
            $message = sprintf(
                'Unexpected HTTP response for entity statement fetch, status code: %s, reason: %s.',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        if (
            !str_contains(
                ContentTypeEnum::ApplicationEntityStatementJwt->value,
                $response->getHeaderLine(HttpHeadersEnum::ContentType->value),
            )
        ) {
            $message = sprintf(
                'Unexpected content type in response for entity statement fetch: %s, expected: %s.',
                $response->getHeaderLine(HttpHeadersEnum::ContentType->value),
                ContentTypeEnum::ApplicationEntityStatementJwt->value,
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        $this->logger?->info('Successful HTTP response for entity statement fetch to: ' . $uri);
        return $response->getBody()->getContents();
    }
}
