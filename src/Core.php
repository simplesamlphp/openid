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
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

class Core
{
    protected DateIntervalDecorator $timestampValidationLeeway;
    protected JwsSerializerManager $jwsSerializerManager;
    protected JwsParser $jwsParser;
    protected JwsVerifier $jwsVerifier;
    protected ?RequestObjectFactory $requestObjectFactory = null;
    protected ?ClientAssertionFactory $clientAssertionFactory = null;

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
        protected JwksFactory $jwksFactory = new JwksFactory(),
        DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = new DateIntervalDecoratorFactory(),
    ) {
        $this->timestampValidationLeeway = $dateIntervalDecoratorFactory->build($timestampValidationLeeway);
        $this->jwsSerializerManager = $jwsSerializerManagerFactory->build($this->supportedSerializers);
        $this->jwsParser =  $jwsParserFactory->build($this->jwsSerializerManager);
        $this->jwsVerifier = $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms));
    }

    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory ??= new RequestObjectFactory(
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
            $this->helpers,
        );
    }

    public function clientAssertionFactory(): ClientAssertionFactory
    {
        return $this->clientAssertionFactory ??= new ClientAssertionFactory(
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
            $this->helpers,
        );
    }
}
