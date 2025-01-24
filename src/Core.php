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
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class Core
{
    protected DateIntervalDecorator $timestampValidationLeewayDecorator;
    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;
    protected ?JwsParser $jwsParser = null;
    protected ?JwsVerifierDecorator $jwsVerifierDecorator = null;
    protected ?RequestObjectFactory $requestObjectFactory = null;
    protected ?ClientAssertionFactory $clientAssertionFactory = null;
    protected ?Helpers $helpers = null;
    protected ?AlgorithmManagerDecoratorFactory $algorithmManagerDecoratorFactory = null;
    protected ?JwsSerializerManagerDecoratorFactory $jwsSerializerManagerDecoratorFactory = null;
    protected ?JwsParserFactory $jwsParserFactory = null;
    protected ?JwsVerifierDecoratorFactory $jwsVerifierDecoratorFactory = null;
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
            $this->jwsVerifierDecorator(),
            $this->jwksFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
        );
    }

    public function clientAssertionFactory(): ClientAssertionFactory
    {
        return $this->clientAssertionFactory ??= new ClientAssertionFactory(
            $this->jwsParser(),
            $this->jwsVerifierDecorator(),
            $this->jwksFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
        );
    }

    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }

    public function algorithmManagerDecoratorFactory(): AlgorithmManagerDecoratorFactory
    {
        if (is_null($this->algorithmManagerDecoratorFactory)) {
            $this->algorithmManagerDecoratorFactory = new AlgorithmManagerDecoratorFactory();
        }
        return $this->algorithmManagerDecoratorFactory;
    }

    public function jwsSerializerManagerDecoratorFactory(): JwsSerializerManagerDecoratorFactory
    {
        if (is_null($this->jwsSerializerManagerDecoratorFactory)) {
            $this->jwsSerializerManagerDecoratorFactory = new JwsSerializerManagerDecoratorFactory();
        }
        return $this->jwsSerializerManagerDecoratorFactory;
    }

    public function jwsParserFactory(): JwsParserFactory
    {
        if (is_null($this->jwsParserFactory)) {
            $this->jwsParserFactory = new JwsParserFactory();
        }
        return $this->jwsParserFactory;
    }

    public function jwsVerifierDecoratorFactory(): JwsVerifierDecoratorFactory
    {
        if (is_null($this->jwsVerifierDecoratorFactory)) {
            $this->jwsVerifierDecoratorFactory = new JwsVerifierDecoratorFactory();
        }
        return $this->jwsVerifierDecoratorFactory;
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

    public function jwsSerializerManagerDecorator(): JwsSerializerManagerDecorator
    {
        if (is_null($this->jwsSerializerManagerDecorator)) {
            $this->jwsSerializerManagerDecorator = $this->jwsSerializerManagerDecoratorFactory()
                ->build($this->supportedSerializers);
        }
        return $this->jwsSerializerManagerDecorator;
    }

    public function jwsParser(): JwsParser
    {
        if (is_null($this->jwsParser)) {
            $this->jwsParser = $this->jwsParserFactory()->build($this->jwsSerializerManagerDecorator());
        }
        return $this->jwsParser;
    }

    public function jwsVerifierDecorator(): JwsVerifierDecorator
    {
        if (is_null($this->jwsVerifierDecorator)) {
            $this->jwsVerifierDecorator = $this->jwsVerifierDecoratorFactory()->build(
                $this->algorithmManagerDecoratorFactory()->build($this->supportedAlgorithms),
            );
        }
        return $this->jwsVerifierDecorator;
    }
}
