<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
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
    ) {
        $this->timestampValidationLeeway = new DateIntervalDecorator($timestampValidationLeeway);
        $jwsSerializerManager = $jwsSerializerManagerFactory->build($this->supportedSerializers);

        $this->requestObjectFactory = $requestObjectFactory ?? new RequestObjectFactory(
            $jwsParserFactory->build($jwsSerializerManager),
            $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms)),
            $jwksFactory,
            $jwsSerializerManager,
            $this->timestampValidationLeeway,
        );
    }

    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory;
    }
}
