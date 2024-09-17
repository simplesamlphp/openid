<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Core\Factories\ClientAssertionFactory;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;

class Core
{
    protected DateIntervalDecorator $timestampValidationLeeway;
    protected RequestObjectFactory $requestObjectFactory;
    protected ClientAssertionFactory $clientAssertionFactory;

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(
            new SignatureAlgorithmBag(
                SignatureAlgorithmEnum::none,
                SignatureAlgorithmEnum::RS256,
            ),
        ),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
        AlgorithmManagerFactory $algorithmManagerFactory = new AlgorithmManagerFactory(),
        JwsSerializerManagerFactory $jwsSerializerManagerFactory = new JwsSerializerManagerFactory(),
        JwsParserFactory $jwsParserFactory = new JwsParserFactory(),
        JwsVerifierFactory $jwsVerifierFactory = new JwsVerifierFactory(),
        JwksFactory $jwksFactory = new JwksFactory(),
        RequestObjectFactory $requestObjectFactory = null,
        ClientAssertionFactory $clientAssertionFactory = null,
    ) {
        $this->timestampValidationLeeway = new DateIntervalDecorator($timestampValidationLeeway);
        $jwsSerializerManager = $jwsSerializerManagerFactory->build($this->supportedSerializers);
        $jwsParser =  $jwsParserFactory->build($jwsSerializerManager);
        $jwsVerifier = $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms));

        $this->requestObjectFactory = $requestObjectFactory ?? new RequestObjectFactory(
            $jwsParser,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $this->timestampValidationLeeway,
        );

        $this->clientAssertionFactory = $clientAssertionFactory ?? new ClientAssertionFactory(
            $jwsParser,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $this->timestampValidationLeeway,
        );
    }

    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory;
    }

    public function clientAssertionFactory(): ClientAssertionFactory
    {
        return $this->clientAssertionFactory;
    }
}
