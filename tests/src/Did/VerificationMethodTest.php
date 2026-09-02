<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\VerificationMethod;
use SimpleSAML\OpenID\Exceptions\DidException;

#[CoversClass(VerificationMethod::class)]
#[UsesClass(DidUrl::class)]
#[UsesClass(VerificationMethodTypeEnum::class)]
final class VerificationMethodTest extends TestCase
{
    public function testExposesItsParts(): void
    {
        $id = new DidUrl('did:web:example.org#key-1');
        $publicJwk = ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc'];

        $sut = new VerificationMethod(
            $id,
            VerificationMethodTypeEnum::JsonWebKey2020,
            'did:web:example.org',
            $publicJwk,
        );

        $this->assertSame($id, $sut->getId());
        $this->assertSame(VerificationMethodTypeEnum::JsonWebKey2020, $sut->getType());
        $this->assertSame('did:web:example.org', $sut->getController());
        $this->assertSame($publicJwk, $sut->getPublicJwk());
    }


    #[DataProvider('publicKeyJwkTypeDataProvider')]
    public function testSerializesWithItsKeyMaterialAsAJwk(VerificationMethodTypeEnum $type): void
    {
        $publicJwk = ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc'];

        $sut = new VerificationMethod(
            new DidUrl('did:web:example.org#key-1'),
            $type,
            'did:web:example.org',
            $publicJwk,
        );

        $this->assertSame(
            [
                'id' => 'did:web:example.org#key-1',
                'type' => $type->value,
                'controller' => 'did:web:example.org',
                'publicKeyJwk' => $publicJwk,
            ],
            $sut->jsonSerialize(),
        );
    }


    public static function publicKeyJwkTypeDataProvider(): \Iterator
    {
        yield 'JsonWebKey' => [VerificationMethodTypeEnum::JsonWebKey];
        yield 'JsonWebKey2020' => [VerificationMethodTypeEnum::JsonWebKey2020];
    }


    /**
     * The multibase value a method arrived as is not retained, so emitting the JWK it decoded to under a
     * type that declares publicKeyMultibase would publish a method this library would itself refuse.
     */
    #[DataProvider('publicKeyMultibaseTypeDataProvider')]
    public function testRefusesToSerializeAMultibaseType(VerificationMethodTypeEnum $type): void
    {
        $sut = new VerificationMethod(
            new DidUrl('did:web:example.org#key-1'),
            $type,
            'did:web:example.org',
            ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc'],
        );

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('can not be serialised');

        $sut->jsonSerialize();
    }


    public static function publicKeyMultibaseTypeDataProvider(): \Iterator
    {
        yield 'Multikey' => [VerificationMethodTypeEnum::Multikey];
        yield 'Ed25519VerificationKey2020' => [VerificationMethodTypeEnum::Ed25519VerificationKey2020];
    }
}
