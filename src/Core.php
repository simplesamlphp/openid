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
    protected DateIntervalDecorator $timestampValidationLeewayDecorator;
    protected ?JwsSerializerManager $jwsSerializerManager = null;
    protected ?JwsParser $jwsParser = null;
    protected ?JwsVerifier $jwsVerifier = null;
    protected ?RequestObjectFactory $requestObjectFactory = null;
    protected ?ClientAssertionFactory $clientAssertionFactory = null;
    protected ?Helpers $helpers = null;
    protected ?AlgorithmManagerFactory $algorithmManagerFactory = null;
    protected ?JwsSerializerManagerFactory $jwsSerializerManagerFactory = null;
    protected ?JwsParserFactory $jwsParserFactory = null;
    protected ?JwsVerifierFactory $jwsVerifierFactory = null;
    protected ?JwksFactory $jwksFactory = null;
    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

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
    ) {
        $this->timestampValidationLeewayDecorator = $this->dateIntervalDecoratorFactory()
            ->build($timestampValidationLeeway);
    }

    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory ??= new RequestObjectFactory(
            $this->jwsParser(),
            $this->jwsVerifier(),
            $this->jwksFactory(),
            $this->jwsSerializerManager(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
        );
    }

    public function clientAssertionFactory(): ClientAssertionFactory
    {
        return $this->clientAssertionFactory ??= new ClientAssertionFactory(
            $this->jwsParser(),
            $this->jwsVerifier(),
            $this->jwksFactory(),
            $this->jwsSerializerManager(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
        );
    }

    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }

    public function algorithmManagerFactory(): AlgorithmManagerFactory
    {
        if (is_null($this->algorithmManagerFactory)) {
            $this->algorithmManagerFactory = new AlgorithmManagerFactory();
        }
        return $this->algorithmManagerFactory;
    }

    public function jwsSerializerManagerFactory(): JwsSerializerManagerFactory
    {
        if (is_null($this->jwsSerializerManagerFactory)) {
            $this->jwsSerializerManagerFactory = new JwsSerializerManagerFactory();
        }
        return $this->jwsSerializerManagerFactory;
    }

    public function jwsParserFactory(): JwsParserFactory
    {
        if (is_null($this->jwsParserFactory)) {
            $this->jwsParserFactory = new JwsParserFactory();
        }
        return $this->jwsParserFactory;
    }

    public function jwsVerifierFactory(): JwsVerifierFactory
    {
        if (is_null($this->jwsVerifierFactory)) {
            $this->jwsVerifierFactory = new JwsVerifierFactory();
        }
        return $this->jwsVerifierFactory;
    }

    public function jwksFactory(): JwksFactory
    {
        return $this->jwksFactory ??= new JwksFactory();
    }

    public function dateIntervalDecoratorFactory(): DateIntervalDecoratorFactory
    {
        if (is_null($this->dateIntervalDecoratorFactory)) {
            $this->dateIntervalDecoratorFactory = new DateIntervalDecoratorFactory();
        }

        return $this->dateIntervalDecoratorFactory;
    }

    public function jwsSerializerManager(): JwsSerializerManager
    {
        if (is_null($this->jwsSerializerManager)) {
            $this->jwsSerializerManager = $this->jwsSerializerManagerFactory()->build($this->supportedSerializers);
        }
        return $this->jwsSerializerManager;
    }

    public function jwsParser(): JwsParser
    {
        if (is_null($this->jwsParser)) {
            $this->jwsParser = $this->jwsParserFactory()->build($this->jwsSerializerManager());
        }
        return $this->jwsParser;
    }

    public function jwsVerifier(): JwsVerifier
    {
        if (is_null($this->jwsVerifier)) {
            $this->jwsVerifier = $this->jwsVerifierFactory()->build(
                $this->algorithmManagerFactory()->build($this->supportedAlgorithms),
            );
        }
        return $this->jwsVerifier;
    }
}
