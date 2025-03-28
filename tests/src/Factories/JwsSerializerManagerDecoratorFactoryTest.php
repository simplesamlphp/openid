<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use Jose\Component\Signature\Serializer\JWSSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
final class JwsSerializerManagerDecoratorFactoryTest extends TestCase
{
    protected MockObject $supportedSerializersMock;



    protected function setUp(): void
    {
        $this->supportedSerializersMock = $this->createMock(SupportedSerializers::class);
        $jwsSerializerBagMock = $this->createMock(JwsSerializerBag::class);
        $jwsSerializerMock = $this->createMock(JwsSerializer::class);

        $this->supportedSerializersMock->method('getJwsSerializerBag')
            ->willReturn($jwsSerializerBagMock);

        $jwsSerializerBagMock->method('getAllInstances')
            ->willReturn([$jwsSerializerMock]);
    }

    protected function sut(): JwsSerializerManagerDecoratorFactory
    {
        return new JwsSerializerManagerDecoratorFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            JwsSerializerManagerDecoratorFactory::class,
            $this->sut(),
        );
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            JwsSerializerManagerDecorator::class,
            $this->sut()->build($this->supportedSerializersMock),
        );
    }
}
