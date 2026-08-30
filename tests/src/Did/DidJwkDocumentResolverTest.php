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
use SimpleSAML\OpenID\Did\DidJwkDocumentResolver;
use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Did\PublicJwkValidator;
use SimpleSAML\OpenID\Did\ResolvedVerificationMethod;
use SimpleSAML\OpenID\Did\VerificationMethod;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(DidJwkDocumentResolver::class)]
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
final class DidJwkDocumentResolverTest extends TestCase
{
    private const JWK = [
        'kty' => 'EC',
        'crv' => 'P-256',
        'x' => 'dDfIibQM-949qf4jj-8mBY4Azq34ygSGhzd8AT2mx6s',
        'y' => 'VUyI8G-OYirMrrsCB9lvUbr6Wjq2ef73ne_paBqLPxw',
    ];


    private Helpers $helpers;


    protected function setUp(): void
    {
        $this->helpers = new Helpers();
    }


    protected function sut(): DidJwkDocumentResolver
    {
        return new DidJwkDocumentResolver(
            new DidDocumentFactory(
                new MultibaseKeyDecoder($this->helpers),
                new DidJwkResolver($this->helpers),
                new PublicJwkValidator(),
            ),
        );
    }


    protected function did(): string
    {
        return 'did:jwk:' . $this->helpers->base64Url()->encode($this->helpers->json()->encode(self::JWK));
    }


    public function testHandlesTheJwkMethod(): void
    {
        $this->assertSame('jwk', $this->sut()->methodName());
        $this->assertTrue($this->sut()->supports($this->did()));
        $this->assertFalse($this->sut()->supports('did:web:example.org'));
    }


    public function testResolvesTheDocumentTheIdentifierDescribes(): void
    {
        $did = $this->did();
        $document = $this->sut()->resolveDocument($did);

        $this->assertSame($did, $document->getId());

        // The method fixes the verification method fragment at "0".
        $resolved = $document->resolveVerificationMethod(
            new DidUrl($did . '#0'),
            VerificationRelationshipEnum::Authentication,
        );

        $this->assertSame(self::JWK, $resolved->getPublicJwk());
    }


    /**
     * Nothing is fetched, so a budget for network work having elapsed says nothing about whether this can be
     * answered. Refusing here would turn a free operation into a failure.
     */
    public function testIgnoresAnExhaustedDeadline(): void
    {
        $this->assertSame(
            $this->did(),
            $this->sut()->resolveDocument($this->did(), microtime(true) - 60.0)->getId(),
        );
    }


    /**
     * The bound lives on the resolver that decodes, so every caller of it inherits the refusal - this
     * one, the document factory, and the module code that reads a proof key identifier.
     */
    public function testRefusesAnOverLongIdentifier(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('longer than the 4096 characters');

        $this->sut()->resolveDocument(
            DidJwkResolver::PREFIX . str_repeat('A', DidJwkResolver::MAX_ENCODED_JWK_LENGTH + 1),
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

        $this->sut()->resolveDocument($this->did() . '#0');
    }
}
