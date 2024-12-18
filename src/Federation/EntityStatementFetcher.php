<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Codebooks\HttpHeadersEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Codebooks\WellKnownEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Helpers;
use Throwable;

class EntityStatementFetcher
{
    public function __construct(
        protected readonly HttpClientDecorator $httpClientDecorator,
        protected readonly EntityStatementFactory $entityStatementFactory,
        protected readonly DateIntervalDecorator $maxCacheDuration,
        protected readonly Helpers $helpers,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Fetch entity statement from a well-known URI. By default, this will be openid-federation (entity configuration).
     * Fetch will first check if the entity statement is available in cache. If not, it will do a network fetch.
     *
     * @param non-empty-string $entityId
     * @param \SimpleSAML\OpenID\Codebooks\WellKnownEnum $wellKnownEnum
     * @return \SimpleSAML\OpenID\Federation\EntityStatement
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromCacheOrWellKnownEndpoint(
        string $entityId,
        WellKnownEnum $wellKnownEnum = WellKnownEnum::OpenIdFederation,
    ): EntityStatement {
        $wellKnownUri = $wellKnownEnum->uriFor($entityId);
        $this->logger?->debug(
            'Entity statement fetch from cache or well-known endpoint.',
            compact('entityId', 'wellKnownUri', 'wellKnownEnum'),
        );

        return $this->fromCacheOrNetwork($wellKnownUri);
    }

    /**
     * @param string $subjectId
     * @param \SimpleSAML\OpenID\Federation\EntityStatement $entityConfiguration Entity from which to use the fetch
     * endpoint (issuer).
     * @return \SimpleSAML\OpenID\Federation\EntityStatement
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrFetchEndpoint(
        string $subjectId,
        EntityStatement $entityConfiguration,
    ): EntityStatement {
        $entityConfigurationPayload = $entityConfiguration->getPayload();

        $fetchEndpointUri = (string)($entityConfigurationPayload[ClaimsEnum::Metadata->value]
            [EntityTypesEnum::FederationEntity->value]
            [ClaimsEnum::FederationFetchEndpoint->value] ??
            throw new JwsException('No fetch endpoint found in entity configuration.'));

        $this->logger?->debug(
            'Entity statement fetch from cache or fetch endpoint.',
            compact('subjectId', 'fetchEndpointUri'),
        );

        return $this->fromCacheOrNetwork(
            $this->helpers->url()->withParams(
                $fetchEndpointUri,
                [
                    ClaimsEnum::Sub->value => $subjectId,
                ],
            ),
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrNetwork(string $uri): EntityStatement
    {
        return $this->fromCache($uri) ?? $this->fromNetwork($uri);
    }

    /**
     * Fetch entity statement from cache, if available. URI is used as cache key.
     *
     * @param string $uri
     * @return \SimpleSAML\OpenID\Federation\EntityStatement|null
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromCache(string $uri): ?EntityStatement
    {
        $this->logger?->debug(
            'Trying to get entity statement token from cache.',
            compact('uri'),
        );

        try {
            /** @var ?string $jws */
            $jws = $this->cacheDecorator?->get(null, $uri);
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error trying to get entity statement from cache: ' . $exception->getMessage(),
                compact('uri'),
            );
            return null;
        }

        if (!is_string($jws)) {
            $this->logger?->debug('Entity statement token not found in cache.', compact('uri'));
            return null;
        }

        $this->logger?->debug(
            'Entity statement token found in cache, trying to build instance.',
            compact('uri'),
        );

        return $this->prepareEntityStatement($jws);
    }

    /**
     * Fetch entity statement from network. Each successful fetch will be cached, with URI being used as a cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetwork(string $uri): EntityStatement
    {
        try {
            $response = $this->httpClientDecorator->request(HttpMethodsEnum::GET, $uri);
        } catch (Throwable $e) {
            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $e->getMessage(),
            );
            $this->logger?->error($message);
            throw new FetchException($message, (int)$e->getCode(), $e);
        }

        if ($response->getStatusCode() !== 200) {
            $message = sprintf(
                'Unexpected HTTP response for entity statement fetch, status code: %s, reason: %s. URI %s',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                $uri,
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        /** @psalm-suppress InvalidLiteralArgument */
        if (
            !str_contains(
                $response->getHeaderLine(HttpHeadersEnum::ContentType->value),
                ContentTypesEnum::ApplicationEntityStatementJwt->value,
            )
        ) {
            $message = sprintf(
                'Unexpected content type in response for entity statement fetch: %s, expected: %s. URI %s',
                $response->getHeaderLine(HttpHeadersEnum::ContentType->value),
                ContentTypesEnum::ApplicationEntityStatementJwt->value,
                $uri,
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        $token = $response->getBody()->getContents();
        $this->logger?->debug('Successful HTTP response for entity statement fetch.', compact('uri', 'token'));
        $this->logger?->debug('Proceeding to EntityStatement instance building.');

        $entityStatement = $this->entityStatementFactory->fromToken($token);
        $this->logger?->debug('Entity Statement instance built, saving its token to cache.', compact('uri', 'token'));

        // Cache it
        try {
            $cacheTtl = $this->maxCacheDuration->lowestInSecondsComparedToExpirationTime(
                $entityStatement->getExpirationTime(),
            );
            $this->cacheDecorator?->set(
                $token,
                $cacheTtl,
                $uri,
            );
            $this->logger?->debug(
                'Entity Statement token successfully cached.',
                compact('uri', 'token', 'cacheTtl'),
            );
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error setting entity statement to cache: ' . $exception->getMessage(),
                compact('uri'),
            );
        }

        $this->logger?->debug('Returning built Entity Statement instance.', compact('uri', 'token'));
        return $entityStatement;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function prepareEntityStatement(string $jws): EntityStatement
    {
        return $this->entityStatementFactory->fromToken($jws);
    }
}
