<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\RequestObject;

use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory as ConnectRequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory as FederationRequestObjectFactory;
use SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory as JarRequestObjectFactory;

class RequestObjectFactories
{
    public function __construct(
        protected readonly ConnectRequestObjectFactory $connectRequestObjectFactory,
        protected readonly JarRequestObjectFactory $jarRequestObjectFactory,
        protected readonly FederationRequestObjectFactory $federationRequestObjectFactory,
    ) {
    }


    public function getConnectRequestObjectFactory(): ConnectRequestObjectFactory
    {
        return $this->connectRequestObjectFactory;
    }


    public function getJarRequestObjectFactory(): JarRequestObjectFactory
    {
        return $this->jarRequestObjectFactory;
    }


    public function getFederationRequestObjectFactory(): FederationRequestObjectFactory
    {
        return $this->federationRequestObjectFactory;
    }


    /**
     * @return array<\SimpleSAML\OpenID\Core\Factories\RequestObjectFactory|\SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory|\SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory>
     */
    public function getAll(): array
    {
        return [
            $this->connectRequestObjectFactory,
            $this->jarRequestObjectFactory,
            $this->federationRequestObjectFactory,
        ];
    }
}
