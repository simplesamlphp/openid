<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Core\Factories\ClientAssertionFactory;
use SimpleSAML\OpenID\Core\Factories\IdTokenFactory;
use SimpleSAML\OpenID\Core\Factories\LogoutTokenFactory;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class Core
{
    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;

    protected ?JwsDecoratorBuilder $jwsDecoratorBuilder = null;

    protected ?JwsVerifierDecorator $jwsVerifierDecorator = null;

    protected ?RequestObjectFactory $requestObjectFactory = null;

    protected ?ClientAssertionFactory $clientAssertionFactory = null;

    protected ?IdTokenFactory $idTokenFactory = null;

    protected ?Helpers $helpers = null;

    protected ?AlgorithmManagerDecoratorFactory $algorithmManagerDecoratorFactory = null;

    protected ?JwsSerializerManagerDecoratorFactory $jwsSerializerManagerDecoratorFactory = null;

    protected ?JwsDecoratorBuilderFactory $jwsDecoratorBuilderFactory = null;

    protected ?JwsVerifierDecoratorFactory $jwsVerifierDecoratorFactory = null;

    protected ?JwksDecoratorFactory $jwksDecoratorFactory = null;

    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected ?ClaimFactory $claimFactory = null;

    protected ?AlgorithmManagerDecorator $algorithmManagerDecorator = null;

    protected ?LogoutTokenFactory $logoutTokenFactory = null;


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
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function clientAssertionFactory(): ClientAssertionFactory
    {
        return $this->clientAssertionFactory ??= new ClientAssertionFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function idTokenFactory(): IdTokenFactory
    {
        return $this->idTokenFactory ??= new IdTokenFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->claimFactory(),
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


    public function algorithmManagerDecorator(): AlgorithmManagerDecorator
    {
        return $this->algorithmManagerDecorator ??= $this->algorithmManagerDecoratorFactory()
            ->build($this->supportedAlgorithms);
    }


    public function jwsSerializerManagerDecoratorFactory(): JwsSerializerManagerDecoratorFactory
    {
        if (is_null($this->jwsSerializerManagerDecoratorFactory)) {
            $this->jwsSerializerManagerDecoratorFactory = new JwsSerializerManagerDecoratorFactory();
        }

        return $this->jwsSerializerManagerDecoratorFactory;
    }


    public function jwsDecoratorBuilderFactory(): JwsDecoratorBuilderFactory
    {
        if (is_null($this->jwsDecoratorBuilderFactory)) {
            $this->jwsDecoratorBuilderFactory = new JwsDecoratorBuilderFactory();
        }

        return $this->jwsDecoratorBuilderFactory;
    }


    public function jwsVerifierDecoratorFactory(): JwsVerifierDecoratorFactory
    {
        if (is_null($this->jwsVerifierDecoratorFactory)) {
            $this->jwsVerifierDecoratorFactory = new JwsVerifierDecoratorFactory();
        }

        return $this->jwsVerifierDecoratorFactory;
    }


    public function jwksDecoratorFactory(): JwksDecoratorFactory
    {
        return $this->jwksDecoratorFactory ??= new JwksDecoratorFactory();
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


    public function jwsDecoratorBuilder(): JwsDecoratorBuilder
    {
        if (is_null($this->jwsDecoratorBuilder)) {
            $this->jwsDecoratorBuilder = $this->jwsDecoratorBuilderFactory()->build(
                $this->jwsSerializerManagerDecorator(),
                $this->algorithmManagerDecorator(),
                $this->helpers(),
            );
        }

        return $this->jwsDecoratorBuilder;
    }


    public function jwsVerifierDecorator(): JwsVerifierDecorator
    {
        if (is_null($this->jwsVerifierDecorator)) {
            $this->jwsVerifierDecorator = $this->jwsVerifierDecoratorFactory()->build(
                $this->algorithmManagerDecorator(),
            );
        }

        return $this->jwsVerifierDecorator;
    }


    public function claimFactory(): ClaimFactory
    {
        return $this->claimFactory ??= new ClaimFactory(
            $this->helpers(),
        );
    }


    public function logoutTokenFactory(): LogoutTokenFactory
    {
        return $this->logoutTokenFactory ??= new LogoutTokenFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->claimFactory(),
        );
    }
}
