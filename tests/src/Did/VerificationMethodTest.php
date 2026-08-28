<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\VerificationMethod;

#[CoversClass(VerificationMethod::class)]
#[UsesClass(DidUrl::class)]
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
}
