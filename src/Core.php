<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;

class Core
{
    protected DateIntervalDecorator $timestampValidationLeeway;
    protected RequestObjectFactory $requestObjectFactory;

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
        AlgorithmManagerFactory $algorithmManagerFactory = new AlgorithmManagerFactory(),
        JwsParserFactory $jwsParserFactory = new JwsParserFactory(),
        JwsVerifierFactory $jwsVerifierFactory = new JwsVerifierFactory(),
        JwksFactory $jwksFactory = new JwksFactory(),
        RequestObjectFactory $requestObjectFactory = null,
    ) {
        $this->timestampValidationLeeway = new DateIntervalDecorator($timestampValidationLeeway);

        $this->requestObjectFactory = $requestObjectFactory ?? new RequestObjectFactory(
            $jwsParserFactory->build(),
            $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms)),
            $jwksFactory,
            $this->timestampValidationLeeway,
        );
    }

    public function getRequestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory;
    }
}
