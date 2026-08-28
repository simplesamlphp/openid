<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\ResolvedVerificationMethod;

#[CoversClass(ResolvedVerificationMethod::class)]
#[UsesClass(DidUrl::class)]
final class ResolvedVerificationMethodTest extends TestCase
{
    public function testExposesItsParts(): void
    {
        $id = new DidUrl('did:web:example.org#key-1');
        $publicJwk = ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc'];

        $sut = new ResolvedVerificationMethod(
            'did:web:example.org',
            $id,
            $publicJwk,
            VerificationRelationshipEnum::Authentication,
        );

        $this->assertSame('did:web:example.org', $sut->getDid());
        $this->assertSame($id, $sut->getId());
        $this->assertSame($publicJwk, $sut->getPublicJwk());
        $this->assertSame(VerificationRelationshipEnum::Authentication, $sut->getRelationship());
    }


    public function testRelationshipIsOptional(): void
    {
        $sut = new ResolvedVerificationMethod(
            'did:web:example.org',
            new DidUrl('did:web:example.org#key-1'),
            [],
        );

        $this->assertNotInstanceOf(VerificationRelationshipEnum::class, $sut->getRelationship());
    }
}
