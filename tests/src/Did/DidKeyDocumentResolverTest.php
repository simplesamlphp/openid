<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Did\AbstractDidResolver;
use SimpleSAML\OpenID\Did\DidDocument;
use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidKeyDocumentResolver;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Did\PublicJwkValidator;
use SimpleSAML\OpenID\Did\ResolvedVerificationMethod;
use SimpleSAML\OpenID\Did\VerificationMethod;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(DidKeyDocumentResolver::class)]
#[UsesClass(AbstractDidResolver::class)]
#[UsesClass(DidUrl::class)]
#[UsesClass(DidDocument::class)]
#[UsesClass(DidDocumentFactory::class)]
#[UsesClass(DidJwkResolver::class)]
#[UsesClass(MultibaseKeyDecoder::class)]
#[UsesClass(PublicJwkValidator::class)]
#[UsesClass(ResolvedVerificationMethod::class)]
#[UsesClass(VerificationMethod::class)]
#[UsesClass(VerificationMethodTypeEnum::class)]
#[UsesClass(VerificationRelationshipEnum::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Base64Url::class)]
final class DidKeyDocumentResolverTest extends TestCase
{
    /** A real Ed25519 did:key multibase value. */
    private const ED25519_MULTIBASE = 'z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK';

    private const DID = 'did:key:' . self::ED25519_MULTIBASE;

    /** A real X25519 did:key multibase value, which agrees keys rather than signing. */
    private const X25519_MULTIBASE = 'z6LSbysY2xFMRpGMhb7tFTLMpeuPRaqaWM1yECx2AtzE3KCc';


    protected function sut(): DidKeyDocumentResolver
    {
        $helpers = new Helpers();

        return new DidKeyDocumentResolver(
            new DidDocumentFactory(
                new MultibaseKeyDecoder($helpers),
                new DidJwkResolver($helpers),
                new PublicJwkValidator(),
            ),
        );
    }


    public function testHandlesTheKeyMethod(): void
    {
        $this->assertSame('key', $this->sut()->methodName());
        $this->assertTrue($this->sut()->supports(self::DID));
        $this->assertFalse($this->sut()->supports('did:web:example.org'));
    }


    public function testResolvesTheDocumentTheIdentifierDescribes(): void
    {
        $document = $this->sut()->resolveDocument(self::DID);

        $this->assertSame(self::DID, $document->getId());

        // The method repeats the multibase value as the verification method fragment.
        $resolved = $document->resolveVerificationMethod(
            new DidUrl(self::DID . '#' . self::ED25519_MULTIBASE),
            VerificationRelationshipEnum::Authentication,
        );

        $this->assertSame('OKP', $resolved->getPublicJwk()['kty']);
        $this->assertSame('Ed25519', $resolved->getPublicJwk()['crv']);
    }


    /**
     * The method also implies a derived X25519 key agreement method for an Ed25519 key. Deriving it is out
     * of scope, so a key agreement lookup finds nothing rather than the wrong key.
     */
    public function testDoesNotDeriveAKeyAgreementMethod(): void
    {
        $this->assertSame(
            [],
            $this->sut()->resolveDocument(self::DID)
                ->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement),
        );
    }


    public function testResolvesAKeyAgreementKeyIntoItsOwnRelationship(): void
    {
        $document = $this->sut()->resolveDocument('did:key:' . self::X25519_MULTIBASE);

        $this->assertNotSame(
            [],
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement),
        );
        $this->assertSame(
            [],
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication),
        );
    }


    /**
     * Nothing is fetched, so a budget for network work having elapsed says nothing about whether this can be
     * answered.
     */
    public function testIgnoresAnExhaustedDeadline(): void
    {
        $this->assertSame(
            self::DID,
            $this->sut()->resolveDocument(self::DID, microtime(true) - 60.0)->getId(),
        );
    }


    public function testRefusesAnotherMethod(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('is not one this resolver handles');

        $this->sut()->resolveDocument('did:web:example.org');
    }


    public function testRefusesADidUrlThatIsNotBare(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('bare DID');

        $this->sut()->resolveDocument(self::DID . '#' . self::ED25519_MULTIBASE);
    }
}
