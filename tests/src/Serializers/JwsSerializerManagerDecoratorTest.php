<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Serializers;

use Jose\Component\Signature\Serializer\JWSSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(JwsDecorator::class)]
class JwsSerializerManagerDecoratorTest extends TestCase
{
    protected MockObject $jwsSerializerMock;
    protected MockObject $jwsDecoratorMock;
    protected JWSSerializerManager $jwsSerializerManager;

    protected function setUp(): void
    {
        $this->jwsSerializerMock = $this->createMock(JWSSerializer::class);
        $this->jwsSerializerMock->method('name')->willReturn('mockSerializer');

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);

        // Can not mock, since it is final.
        $this->jwsSerializerManager = new JWSSerializerManager([
            $this->jwsSerializerMock,
        ]);
    }

    protected function sut(
        ?JWSSerializerManager $jwsSerializerManager = null,
    ): JwsSerializerManagerDecorator {
        $jwsSerializerManager ??= $this->jwsSerializerManager;

        return new JwsSerializerManagerDecorator(
            $jwsSerializerManager,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsSerializerManagerDecorator::class, $this->sut());
    }

    public function testCanSerialize(): void
    {
        $this->jwsSerializerMock->expects($this->once())->method('serialize')->willReturn('token');

        $this->assertSame(
            'token',
            $this->sut()->serialize('mockSerializer', $this->jwsDecoratorMock),
        );
    }

    public function testCanUnserialize(): void
    {
        $this->jwsSerializerMock->expects($this->once())->method('unserialize');

        $this->assertInstanceOf(
            JwsDecorator::class,
            $this->sut()->unserialize('token'),
        );
    }
}
