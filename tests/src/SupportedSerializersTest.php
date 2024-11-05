<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(SupportedSerializers::class)]
class SupportedSerializersTest extends TestCase
{
    protected MockObject $jwsSerializerBagMock;

    protected function setUp(): void
    {
        $this->jwsSerializerBagMock = $this->createMock(JwsSerializerBag::class);
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
