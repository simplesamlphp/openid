<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Serializers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;

#[CoversClass(JwsSerializerBag::class)]
#[UsesClass(JwsSerializerEnum::class)]
final class JwsSerializerBagTest extends TestCase
{
    /** @var JwsSerializerEnum[] */
    protected array $jwsSerializers;

    protected function setUp(): void
    {
        $this->jwsSerializers = [
            JwsSerializerEnum::Compact,
        ];
    }

    /**
     * @param ?JwsSerializerEnum[] $jwsSerializers
     */
    protected function sut(
        ?array $jwsSerializers = null,
    ): JwsSerializerBag {
        $jwsSerializers ??= $this->jwsSerializers;

        return new JwsSerializerBag(...$jwsSerializers);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsSerializerBag::class, $this->sut());
    }

    public function testCanAddAndGet(): void
    {
        $sut = $this->sut();
        $this->assertCount(1, $sut->getAll());
        $this->assertCount(1, $sut->getAllInstances());

        $sut->add(JwsSerializerEnum::JsonGeneral);
        $this->assertCount(2, $sut->getAll());
        $this->assertCount(2, $sut->getAllInstances());
    }
}
