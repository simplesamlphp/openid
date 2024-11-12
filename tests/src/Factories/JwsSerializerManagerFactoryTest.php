<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use Jose\Component\Signature\Serializer\JWSSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(JwsSerializerManagerFactory::class)]
#[UsesClass(JwsSerializerManager::class)]
class JwsSerializerManagerFactoryTest extends TestCase
{
    protected MockObject $supportedSerializersMock;
    protected MockObject $jwsSerializerBagMock;
    protected MockObject $jwsSerializerMock;

    protected function setUp(): void
    {
        $this->supportedSerializersMock = $this->createMock(SupportedSerializers::class);
        $this->jwsSerializerBagMock = $this->createMock(JwsSerializerBag::class);
        $this->jwsSerializerMock = $this->createMock(JwsSerializer::class);

        $this->supportedSerializersMock->method('getJwsSerializerBag')
            ->willReturn($this->jwsSerializerBagMock);

        $this->jwsSerializerBagMock->method('getAllInstances')
            ->willReturn([$this->jwsSerializerMock]);
    }

    protected function sut(): JwsSerializerManagerFactory
    {
        return new JwsSerializerManagerFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            JwsSerializerManagerFactory::class,
            $this->sut(),
        );
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            JwsSerializerManager::class,
            $this->sut()->build($this->supportedSerializersMock),
        );
    }
}
