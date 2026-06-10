<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\RequestObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory as ConnectRequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory as FederationRequestObjectFactory;
use SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory as JarRequestObjectFactory;
use SimpleSAML\OpenID\RequestObject\RequestObjectFactories;

#[CoversClass(RequestObjectFactories::class)]
final class RequestObjectFactoriesTest extends TestCase
{
    public function testGettersAndGetAll(): void
    {
        $connectFactoryMock = $this->createStub(ConnectRequestObjectFactory::class);
        $jarFactoryMock = $this->createStub(JarRequestObjectFactory::class);
        $federationFactoryMock = $this->createStub(FederationRequestObjectFactory::class);

        $factories = new RequestObjectFactories(
            $connectFactoryMock,
            $jarFactoryMock,
            $federationFactoryMock,
        );

        $this->assertSame($connectFactoryMock, $factories->getConnectRequestObjectFactory());
        $this->assertSame($jarFactoryMock, $factories->getJarRequestObjectFactory());
        $this->assertSame($federationFactoryMock, $factories->getFederationRequestObjectFactory());

        $expectedAll = [
            $connectFactoryMock,
            $jarFactoryMock,
            $federationFactoryMock,
        ];
        $this->assertSame($expectedAll, $factories->getAll());
    }
}
