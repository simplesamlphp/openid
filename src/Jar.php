<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class Jar
{
    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected ?RequestObjectFactory $requestObjectFactory = null;

    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;

    protected ?JwsSerializerManagerDecoratorFactory $jwsSerializerManagerDecoratorFactory = null;

    protected ?JwsDecoratorBuilderFactory $jwsDecoratorBuilderFactory = null;

    protected ?AlgorithmManagerDecoratorFactory $algorithmManagerDecoratorFactory = null;

    protected ?AlgorithmManagerDecorator $algorithmManagerDecorator = null;

    protected ?Helpers $helpers = null;

    protected ?JwsVerifierDecoratorFactory $jwsVerifierDecoratorFactory = null;

    protected ?JwsVerifierDecorator $jwsVerifierDecorator  = null;

    protected ?JwksDecoratorFactory $jwksDecoratorFactory = null;

    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected ?ClaimFactory $claimFactory = null;

    protected ?JwsDecoratorBuilder $jwsDecoratorBuilder = null;


    public function __construct(
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
    ) {
        $this->timestampValidationLeewayDecorator = $this->dateIntervalDecoratorFactory()
        ->build($timestampValidationLeeway);
    }


    public function dateIntervalDecoratorFactory(): DateIntervalDecoratorFactory
    {
        return $this->dateIntervalDecoratorFactory ??= new DateIntervalDecoratorFactory();
    }


    public function jwsSerializerManagerDecoratorFactory(): JwsSerializerManagerDecoratorFactory
    {
        return $this->jwsSerializerManagerDecoratorFactory ??= new JwsSerializerManagerDecoratorFactory();
    }


    public function supportedSerializers(): SupportedSerializers
    {
        return $this->supportedSerializers;
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


    public function algorithmManagerDecoratorFactory(): AlgorithmManagerDecoratorFactory
    {
        return $this->algorithmManagerDecoratorFactory ??= new AlgorithmManagerDecoratorFactory();
    }


    public function algorithmManagerDecorator(): AlgorithmManagerDecorator
    {
        return $this->algorithmManagerDecorator ??= $this->algorithmManagerDecoratorFactory()
            ->build($this->supportedAlgorithms);
    }


    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }


    public function jwsDecoratorBuilder(): JwsDecoratorBuilder
    {
        return $this->jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderFactory()->build(
            $this->jwsSerializerManagerDecorator(),
            $this->algorithmManagerDecorator(),
            $this->helpers(),
        );
    }


    public function jwsVerifierDecoratorFactory(): JwsVerifierDecoratorFactory
    {
        return $this->jwsVerifierDecoratorFactory ??= new JwsVerifierDecoratorFactory();
    }


    public function jwsVerifierDecorator(): JwsVerifierDecorator
    {
        return $this->jwsVerifierDecorator ??= $this->jwsVerifierDecoratorFactory()->build(
            $this->algorithmManagerDecorator(),
        );
    }


    public function jwksDecoratorFactory(): JwksDecoratorFactory
    {
        return $this->jwksDecoratorFactory ??= new JwksDecoratorFactory();
    }


    public function timestampValidationLeewayDecorator(): DateIntervalDecorator
    {
        return $this->timestampValidationLeewayDecorator;
    }


    public function claimFactory(): ClaimFactory
    {
        return $this->claimFactory ??= new ClaimFactory(
            $this->helpers(),
        );
    }


    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory ??= new RequestObjectFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
        );
    }
}
