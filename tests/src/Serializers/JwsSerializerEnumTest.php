<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Serializers;

use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JSONFlattenedSerializer;
use Jose\Component\Signature\Serializer\JSONGeneralSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;

#[CoversClass(JwsSerializerEnum::class)]
class JwsSerializerEnumTest extends TestCase
{
    public function testCanGetInstance(): void
    {
        $this->assertInstanceOf(
            CompactSerializer::class,
            JwsSerializerEnum::Compact->instance(),
        );

        $this->assertInstanceOf(
            JSONGeneralSerializer::class,
            JwsSerializerEnum::JsonGeneral->instance(),
        );

        $this->assertInstanceOf(
            JSONFlattenedSerializer::class,
            JwsSerializerEnum::JsonFlattened->instance(),
        );
    }
}
