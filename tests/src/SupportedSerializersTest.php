<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(SupportedSerializers::class)]
final class SupportedSerializersTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerBagMock;


    protected function setUp(): void
    {
        $this->jwsSerializerBagMock = $this->createStub(JwsSerializerBag::class);
    }


    protected function sut(
        ?JwsSerializerBag $jwsSerializerBag = null,
    ): SupportedSerializers {
        $jwsSerializerBag ??= $this->jwsSerializerBagMock;

        return new SupportedSerializers($jwsSerializerBag);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(SupportedSerializers::class, $this->sut());
    }


    public function testCanGetJwsSerializerBag(): void
    {
        $this->assertInstanceOf(JwsSerializerBag::class, $this->sut()->getJwsSerializerBag());
    }
}
