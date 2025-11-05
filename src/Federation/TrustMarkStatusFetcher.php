<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkStatusFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

class TrustMarkStatusFetcher extends JwsFetcher
{
    public function __construct(
        private readonly TrustMarkStatusFactory $parsedJwsFactory,
        ArtifactFetcher $artifactFetcher,
        DateIntervalDecorator $maxCacheDuration,
        Helpers $helpers,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($parsedJwsFactory, $artifactFetcher, $maxCacheDuration, $helpers, $logger);
    }


    protected function buildJwsInstance(string $token): TrustMarkStatus
    {
        return $this->parsedJwsFactory->fromToken($token);
    }


    public function getExpectedContentTypeHttpHeader(): string
    {
        return ContentTypesEnum::ApplicationTrustMarkJwt->value;
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
    ): TrustMarkStatus {
        $trustMarkStatusEndpoint = $entityConfiguration->getFederationTrustMarkStatusEndpoint() ??
        throw new EntityStatementException('No federation trust mark status endpoint found in entity configuration.');

        $this->logger?->debug(
            'Trust Mark status fetch from trust mark status endpoint.',
            ['trustMarkStatusEndpoint' => $trustMarkStatusEndpoint, 'trustMarkType' => $trustMark->getType()],
        );

        $trustMarkStatus =  $this->fromNetwork(
            $trustMarkStatusEndpoint,
            HttpMethodsEnum::POST,
            [
                'form_params' => [
                    'trust_mark' => $trustMark->getToken(),
                ],
            ],
            false,
        );

        if ($trustMarkStatus instanceof TrustMarkStatus) {
            return $trustMarkStatus;
        }

        $message = 'Unexpected Trust Mark Status instance encountered for network fetch.';
        $this->logger?->error(
            $message,
            [
                'trustMarkStatusEndpoint' => $trustMarkStatusEndpoint,
                'trustMarkType' => $trustMark->getType(),
                'trustMarkStatus' => $trustMarkStatus,
            ],
        );

        throw new FetchException($message);
    }
}
