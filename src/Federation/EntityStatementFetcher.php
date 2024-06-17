<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use DateInterval;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\Module\oidc\Codebooks\HttpHeaderValues\ContentTypeEnum;
use SimpleSAML\OpenID\Codebooks\ClaimNamesEnum;
use SimpleSAML\OpenID\Codebooks\HttpHeadersEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Codebooks\WellKnownEnum;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Helpers;
use Throwable;

class EntityStatementFetcher
{
    public function __construct(
        protected readonly Client $httpClient,
        protected readonly DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        protected readonly ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
        protected readonly EntityStatementFactory $entityStatementFactory = new EntityStatementFactory(),
    ) {
    }

    /**
     * @param non-empty-string $entityId
     * @param \SimpleSAML\OpenID\Codebooks\WellKnownEnum $wellKnownEnum
     * @return \SimpleSAML\OpenID\Federation\EntityStatement
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function forWellKnown(
        string $entityId,
        WellKnownEnum $wellKnownEnum = WellKnownEnum::OpenIdFederation,
    ): EntityStatement {
        $wellKnownUri = $wellKnownEnum->uriFor($entityId);
        $this->logger?->debug(
            'Entity statement fetch initiated.',
            compact('entityId', 'wellKnownUri', 'wellKnownEnum'),
        );
        $jws = null;
        $cacheKey = $this->helpers->cache()->keyFor($wellKnownUri);
        try {
            /** @var ?string $jws */
            $jws = $this->cache?->get($cacheKey);
            $jws = is_string($jws) ? $jws : null;
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error trying to get entity statement from cache: ' . $exception->getMessage(),
                compact('wellKnownUri', 'cacheKey'),
            );
        }

        if (!is_string($jws)) {
            $this->logger?->debug(
                'Entity statement not cached, proceeding with network fetch.',
                compact('entityId', 'wellKnownUri'),
            );
            $jws = $this->fromNetwork($wellKnownUri);
        }

        // TODO mivanci Important Validate header, iat, exp.
        $entityStatement = $this->entityStatementFactory->fromToken($jws);

        $expiration = (int)($entityStatement->getPayloadClaim(ClaimNamesEnum::ExpirationTime->value) ?? 0);
        $duration = $this->helpers->cache()->maxDuration($this->maxCacheDuration, $expiration);

        $this->logger?->debug('Fetched entity statement.', compact('wellKnownUri', 'jws'));
        $this->cache?->set($cacheKey, $jws, $duration);

        return $entityStatement;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetwork(string $uri): string
    {
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

        $this->logger?->info('Successful HTTP response for entity statement fetch to: ' . $uri);
        return $response->getBody()->getContents();
    }
}
