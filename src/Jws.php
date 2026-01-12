<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class Jws
{
    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected ?ParsedJwsFactory $parsedJwsFactory = null;

    protected ?JwsDecoratorBuilderFactory $jwsDecoratorBuilderFactory = null;

    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;

    protected ?JwsSerializerManagerDecoratorFactory $jwsSerializerManagerDecoratorFactory = null;

    protected ?AlgorithmManagerDecoratorFactory $algorithmManagerDecoratorFactory = null;

    protected ?JwsVerifierDecoratorFactory $jwsVerifierDecoratorFactory = null;

    protected ?JwsVerifierDecorator $jwsVerifierDecorator = null;

    protected ?Helpers $helpers = null;

    protected ?JwksDecoratorFactory $jwksDecoratorFactory = null;

    protected ?ClaimFactory $claimFactory = null;

    protected ?JwsDecoratorBuilder $jwsDecoratorBuilder = null;

    protected ?AlgorithmManagerDecorator $algorithmManagerDecorator = null;


    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        protected readonly ?LoggerInterface $logger = null,
    ) {
        $this->timestampValidationLeewayDecorator = $this->dateIntervalDecoratorFactory()
            ->build($timestampValidationLeeway);
    }


    public function dateIntervalDecoratorFactory(): DateIntervalDecoratorFactory
    {
        return $this->dateIntervalDecoratorFactory ??= new DateIntervalDecoratorFactory();
    }


    public function parsedJwsFactory(): ParsedJwsFactory
    {
        return $this->parsedJwsFactory ??= new ParsedJwsFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function jwsDecoratorBuilder(): JwsDecoratorBuilder
    {
        return $this->jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderFactory()->build(
            $this->jwsSerializerManagerDecorator(),
            $this->algorithmManagerDecorator(),
            $this->helpers(),
        );
    }


    public function jwsDecoratorBuilderFactory(): JwsDecoratorBuilderFactory
    {
        return $this->jwsDecoratorBuilderFactory ??= new JwsDecoratorBuilderFactory();
    }


    public function jwsSerializerManagerDecorator(): JwsSerializerManagerDecorator
    {
        return $this->jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorFactory()
            ->build($this->supportedSerializers);
    }


    public function jwsSerializerManagerDecoratorFactory(): JwsSerializerManagerDecoratorFactory
    {
        return $this->jwsSerializerManagerDecoratorFactory ??= new JwsSerializerManagerDecoratorFactory();
    }


    public function algorithmManagerDecorator(): AlgorithmManagerDecorator
    {
        return $this->algorithmManagerDecorator ??= $this->algorithmManagerDecoratorFactory()
            ->build($this->supportedAlgorithms);
    }


    public function algorithmManagerDecoratorFactory(): AlgorithmManagerDecoratorFactory
    {
        return $this->algorithmManagerDecoratorFactory ??= new AlgorithmManagerDecoratorFactory();
    }


    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }


    public function jwsVerifierDecorator(): JwsVerifierDecorator
    {
        return $this->jwsVerifierDecorator ??= $this->jwsVerifierDecoratorFactory()->build(
            $this->algorithmManagerDecorator(),
        );
    }


    public function jwsVerifierDecoratorFactory(): JwsVerifierDecoratorFactory
    {
        return $this->jwsVerifierDecoratorFactory ??= new JwsVerifierDecoratorFactory();
    }


    public function jwksDecoratorFactory(): JwksDecoratorFactory
    {
        return $this->jwksDecoratorFactory ??= new JwksDecoratorFactory();
    }


    public function claimFactory(): ClaimFactory
    {
        return $this->claimFactory ??= new ClaimFactory(
            $this->helpers(),
        );
    }
}
