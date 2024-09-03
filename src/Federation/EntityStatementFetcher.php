<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\ContentTypeEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypeEnum;
use SimpleSAML\OpenID\Codebooks\HttpHeadersEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Codebooks\WellKnownEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Helpers;
use Throwable;

class EntityStatementFetcher
{
    public function __construct(
        protected readonly Client $httpClient,
        protected readonly EntityStatementFactory $entityStatementFactory,
        protected readonly DateIntervalDecorator $maxCacheDuration,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
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
            [EntityTypeEnum::FederationEntity->value]
            [ClaimsEnum::FederationFetchEndpoint->value] ??
            throw new JwsException('No fetch endpoint found in entity configuration.'));
        $issuer = $entityConfiguration->getIssuer();

        $this->logger?->debug(
            'Entity statement fetch from cache or fetch endpoint.',
            compact('subjectId', 'fetchEndpointUri', 'issuer'),
        );

        return $this->fromCacheOrNetwork(
            $this->helpers->url()->withParams(
                $fetchEndpointUri,
                [
                    ClaimsEnum::Sub->value => $subjectId,
                    ClaimsEnum::Iss->value => $issuer,
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

        if (is_string($jws)) {
            $this->logger?->error(
                'Entity statement token found in cache, trying to build instance.',
                compact('uri'),
            );

            return $this->prepareEntityStatement($jws);
        }

        return null;
    }

    /**
     * Fetch entity statement from network. Each successful fetch will be cached, with URI being used as a cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetwork(string $uri): EntityStatement
    {
        // TODO mivanci refactor to HttpClientDecorator
        try {
            $response = $this->httpClient->request(HttpMethodsEnum::GET->value, $uri);
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
                'Unexpected HTTP response for entity statement fetch, status code: %s, reason: %s.',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        /** @psalm-suppress InvalidLiteralArgument */
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

        $token = $response->getBody()->getContents();
        $this->logger?->info('Successful HTTP response for entity statement fetch.', compact('uri', 'token'));
        $this->logger?->debug('Proceeding to EntityStatement instance building.');

        $entityStatement = $this->entityStatementFactory->fromToken($token);
        $this->logger?->debug('Entity Statement instance built, saving its token to cache.', compact('uri', 'token'));

        // Cache it
        try {
            $this->cacheDecorator?->set(
                $token,
                $this->maxCacheDuration->lowestInSecondsComparedToExpirationTime($entityStatement->getExpirationTime()),
                $uri,
            );
            $this->logger?->debug('Entity Statement token successfully cached.', compact('uri', 'token'));
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
