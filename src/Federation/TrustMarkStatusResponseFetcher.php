<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkStatusResponseFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

class TrustMarkStatusResponseFetcher extends JwsFetcher
{
    public function __construct(
        private readonly TrustMarkStatusResponseFactory $parsedJwsFactory,
        ArtifactFetcher $artifactFetcher,
        DateIntervalDecorator $maxCacheDuration,
        Helpers $helpers,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($parsedJwsFactory, $artifactFetcher, $maxCacheDuration, $helpers, $logger);
    }


    protected function buildJwsInstance(string $token): TrustMarkStatusResponse
    {
        return $this->parsedJwsFactory->fromToken($token);
    }


    public function getExpectedContentTypeHttpHeader(): string
    {
        return ContentTypesEnum::ApplicationTrustMarkStatusResponseJwt->value;
    }


    /**
     * @param \SimpleSAML\OpenID\Federation\TrustMark $trustMark Trust Mark to send it to the
     * federation_trust_mark_status_endpoint.
     * @param \SimpleSAML\OpenID\Federation\EntityStatement $entityConfiguration Entity from which to use the
     * federation_trust_mark_status_endpoint.
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function fromFederationTrustMarkStatusEndpoint(
        TrustMark $trustMark,
        EntityStatement $entityConfiguration,
    ): TrustMarkStatusResponse {
        $trustMarkStatusEndpoint = $entityConfiguration->getFederationTrustMarkStatusEndpoint() ??
        throw new EntityStatementException('No federation trust mark status endpoint found in entity configuration.');

        $this->logger?->debug(
            'Trust Mark status fetch from trust mark status endpoint.',
            ['trustMarkStatusEndpoint' => $trustMarkStatusEndpoint, 'trustMarkType' => $trustMark->getType()],
        );

        return $this->fromNetwork(
            $trustMarkStatusEndpoint,
            options: [
                'form_params' => [
                    'trust_mark' => $trustMark->getToken(),
                ],
            ],
        );
    }


    /**
     * Fetch Trust Mark Status from the network.
     *
     * @param array<string, mixed> $options See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param bool $shouldCache If true, each successful fetch will be cached, with URI being used as a cache key.
     * @param string ...$additionalCacheKeyElements Additional string elements to be used as cache key.
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetwork(
        string $uri,
        HttpMethodsEnum $httpMethodsEnum = HttpMethodsEnum::POST,
        array $options = [],
        bool $shouldCache = false,
        string ...$additionalCacheKeyElements,
    ): TrustMarkStatusResponse {
        $trustMarkStatusResponse = parent::fromNetwork($uri, $httpMethodsEnum, $options, $shouldCache);

        if ($trustMarkStatusResponse instanceof TrustMarkStatusResponse) {
            return $trustMarkStatusResponse;
        }

        // @codeCoverageIgnoreStart
        $message = 'Unexpected Trust Mark Status instance encountered for network fetch.';
        $this->logger?->error(
            $message,
            ['uri' => $uri, 'trustMarkStatusResponse' => $trustMarkStatusResponse],
        );

        throw new FetchException($message);
        // @codeCoverageIgnoreEnd
    }
}
