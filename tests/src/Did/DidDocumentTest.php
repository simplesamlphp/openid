<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Did\DidDocument;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\ResolvedVerificationMethod;
use SimpleSAML\OpenID\Did\VerificationMethod;
use SimpleSAML\OpenID\Exceptions\DidException;

#[CoversClass(DidDocument::class)]
#[UsesClass(DidUrl::class)]
#[UsesClass(VerificationMethod::class)]
#[UsesClass(ResolvedVerificationMethod::class)]
final class DidDocumentTest extends TestCase
{
    private const SUBJECT = 'did:web:example.org';

    private const METHOD_ID = 'did:web:example.org#key-1';

    private const PUBLIC_JWK = ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc'];


    protected function verificationMethod(string $id = self::METHOD_ID): VerificationMethod
    {
        return new VerificationMethod(
            new DidUrl($id),
            VerificationMethodTypeEnum::JsonWebKey2020,
            self::SUBJECT,
            self::PUBLIC_JWK,
        );
    }


    protected function sut(
        ?array $verificationMethods = null,
        ?array $relationshipMethods = null,
    ): DidDocument {
        $verificationMethod = $this->verificationMethod();

        $verificationMethods ??= [self::METHOD_ID => $verificationMethod];
        $relationshipMethods ??= [
            VerificationRelationshipEnum::Authentication->value => [self::METHOD_ID => $verificationMethod],
        ];

        return new DidDocument(self::SUBJECT, $verificationMethods, $relationshipMethods);
    }


    public function testExposesItsParts(): void
    {
        $sut = $this->sut();

        $this->assertSame(self::SUBJECT, $sut->getId());
        $this->assertArrayHasKey(self::METHOD_ID, $sut->getVerificationMethods());
    }


    public function testGetVerificationMethodsForReturnsRelationshipMembers(): void
    {
        $methods = $this->sut()->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication);

        $this->assertArrayHasKey(self::METHOD_ID, $methods);
    }


    public function testGetVerificationMethodsForReturnsEmptyArrayForAbsentRelationship(): void
    {
        $this->assertSame(
            [],
            $this->sut()->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement),
        );
    }


    public function testCanResolveWithoutRelationship(): void
    {
        $resolved = $this->sut()->resolveVerificationMethod(new DidUrl(self::METHOD_ID));

        $this->assertInstanceOf(ResolvedVerificationMethod::class, $resolved);
        $this->assertSame(self::SUBJECT, $resolved->getDid());
        $this->assertSame(self::METHOD_ID, $resolved->getId()->getValue());
        $this->assertSame(self::PUBLIC_JWK, $resolved->getPublicJwk());
        $this->assertNotInstanceOf(VerificationRelationshipEnum::class, $resolved->getRelationship());
    }


    public function testCanResolveWithinRelationship(): void
    {
        $resolved = $this->sut()->resolveVerificationMethod(
            new DidUrl(self::METHOD_ID),
            VerificationRelationshipEnum::Authentication,
        );

        $this->assertSame(VerificationRelationshipEnum::Authentication, $resolved->getRelationship());
        $this->assertSame(self::METHOD_ID, $resolved->getId()->getValue());
    }


    public function testMembershipOfAnotherRelationshipIsNotAFallback(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage(
            'DID document does not list the requested verification method under the assertionMethod relationship.',
        );

        $this->sut()->resolveVerificationMethod(
            new DidUrl(self::METHOD_ID),
            VerificationRelationshipEnum::AssertionMethod,
        );
    }


    public function testResolvingAnUnknownMethodThrows(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID document does not contain the requested verification method.');

        $this->sut()->resolveVerificationMethod(new DidUrl('did:web:example.org#other'));
    }


    public function testMethodEmbeddedInARelationshipIsScopedToIt(): void
    {
        $embedded = $this->verificationMethod('did:web:example.org#embedded');

        $sut = new DidDocument(
            self::SUBJECT,
            [],
            [
                VerificationRelationshipEnum::Authentication->value => [
                    'did:web:example.org#embedded' => $embedded,
                ],
            ],
        );

        $this->assertSame(
            'did:web:example.org#embedded',
            $sut->resolveVerificationMethod(
                new DidUrl('did:web:example.org#embedded'),
                VerificationRelationshipEnum::Authentication,
            )->getId()->getValue(),
        );

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID document does not contain the requested verification method.');

        $sut->resolveVerificationMethod(new DidUrl('did:web:example.org#embedded'));
    }
}
