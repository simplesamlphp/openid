<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Did\DidDocument;
use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Did\PublicJwkValidator;
use SimpleSAML\OpenID\Did\ResolvedVerificationMethod;
use SimpleSAML\OpenID\Did\VerificationMethod;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(DidDocumentFactory::class)]
#[UsesClass(DidDocument::class)]
#[UsesClass(DidUrl::class)]
#[UsesClass(VerificationMethod::class)]
#[UsesClass(ResolvedVerificationMethod::class)]
#[UsesClass(PublicJwkValidator::class)]
#[UsesClass(MultibaseKeyDecoder::class)]
#[UsesClass(DidJwkResolver::class)]
#[UsesClass(VerificationRelationshipEnum::class)]
#[UsesClass(VerificationMethodTypeEnum::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Base64Url::class)]
final class DidDocumentFactoryTest extends TestCase
{
    private const SUBJECT = 'did:web:example.org';

    private const METHOD_ID = 'did:web:example.org#key-1';

    private const EC_JWK = [
        'kty' => 'EC',
        'crv' => 'P-256',
        'x' => 'dDfIibQM-949qf4jj-8mBY4Azq34ygSGhzd8AT2mx6s',
        'y' => 'VUyI8G-OYirMrrsCB9lvUbr6Wjq2ef73ne_paBqLPxw',
    ];

    /** A real Ed25519 did:key multibase value, reused as publicKeyMultibase. */
    private const ED25519_MULTIBASE = 'z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK';

    /** A real X25519 did:key multibase value, reused as publicKeyMultibase. */
    private const X25519_MULTIBASE = 'z6LSbysY2xFMRpGMhb7tFTLMpeuPRaqaWM1yECx2AtzE3KCc';

    private const X25519_DID_KEY = 'did:key:' . self::X25519_MULTIBASE;


    private Helpers $helpers;


    protected function setUp(): void
    {
        $this->helpers = new Helpers();
    }


    protected function sut(): DidDocumentFactory
    {
        return new DidDocumentFactory(
            new MultibaseKeyDecoder($this->helpers),
            new DidJwkResolver($this->helpers),
            new PublicJwkValidator(),
        );
    }


    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function documentData(array $overrides = []): array
    {
        return array_merge(
            [
                'id' => self::SUBJECT,
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
                'authentication' => [self::METHOD_ID],
            ],
            $overrides,
        );
    }


    /**
     * @param array<string, mixed> $jwk
     */
    protected function didJwk(array $jwk): string
    {
        return 'did:jwk:' . $this->helpers->base64Url()->encode($this->helpers->json()->encode($jwk));
    }


    public function testParsesMinimalDocument(): void
    {
        $document = $this->sut()->fromData(self::SUBJECT, $this->documentData());

        $this->assertSame(self::SUBJECT, $document->getId());
        $this->assertArrayHasKey(self::METHOD_ID, $document->getVerificationMethods());

        $resolved = $document->resolveVerificationMethod(
            new DidUrl(self::METHOD_ID),
            VerificationRelationshipEnum::Authentication,
        );

        $this->assertSame(self::EC_JWK, $resolved->getPublicJwk());
        $this->assertSame(self::SUBJECT, $resolved->getDid());
    }


    public function testParsesDocumentWithoutRelationships(): void
    {
        $data = $this->documentData();
        unset($data['authentication']);

        $document = $this->sut()->fromData(self::SUBJECT, $data);

        $this->assertArrayHasKey(self::METHOD_ID, $document->getVerificationMethods());
        $this->assertSame([], $document->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication));
    }


    public function testParsesRelationshipWithEmbeddedVerificationMethod(): void
    {
        $document = $this->sut()->fromData(self::SUBJECT, $this->documentData([
            'verificationMethod' => [],
            'assertionMethod' => [
                [
                    'id' => 'did:web:example.org#embedded',
                    'type' => 'JsonWebKey2020',
                    'controller' => self::SUBJECT,
                    'publicKeyJwk' => self::EC_JWK,
                ],
            ],
            'authentication' => [],
        ]));

        $this->assertSame([], $document->getVerificationMethods());
        $this->assertArrayHasKey(
            'did:web:example.org#embedded',
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::AssertionMethod),
        );
    }


    public function testParsesPublicKeyMultibase(): void
    {
        $document = $this->sut()->fromData(self::SUBJECT, $this->documentData([
            'verificationMethod' => [
                [
                    'id' => self::METHOD_ID,
                    'type' => 'Multikey',
                    'controller' => self::SUBJECT,
                    'publicKeyMultibase' => self::ED25519_MULTIBASE,
                ],
            ],
        ]));

        $publicJwk = $document->getVerificationMethods()[self::METHOD_ID]->getPublicJwk();

        $this->assertSame('OKP', $publicJwk['kty']);
        $this->assertSame('Ed25519', $publicJwk['crv']);
    }


    public function testParsesEd25519SuiteType(): void
    {
        $document = $this->sut()->fromData(self::SUBJECT, $this->documentData([
            'verificationMethod' => [
                [
                    'id' => self::METHOD_ID,
                    'type' => 'Ed25519VerificationKey2020',
                    'controller' => self::SUBJECT,
                    'publicKeyMultibase' => self::ED25519_MULTIBASE,
                ],
            ],
        ]));

        $this->assertSame(
            'Ed25519',
            $document->getVerificationMethods()[self::METHOD_ID]->getPublicJwk()['crv'],
        );
    }


    public function testForDidJwkRejectsUnusableUse(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('is neither sig nor enc');

        $this->sut()->forDidJwk(new DidUrl($this->didJwk(self::EC_JWK + ['use' => 'wrapKey'])));
    }


    public function testControllerMismatchIsAllowedWhenNotRequired(): void
    {
        $document = $this->sut()->fromData(
            self::SUBJECT,
            $this->documentData([
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => 'did:web:other.example',
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
            ]),
            false,
        );

        $this->assertSame(
            'did:web:other.example',
            $document->getVerificationMethods()[self::METHOD_ID]->getController(),
        );
    }


    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('rejectedDocumentDataProvider')]
    public function testRejectsMalformedDocument(array $overrides, string $expectedExceptionMessage): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->sut()->fromData(self::SUBJECT, $this->documentData($overrides));
    }


    public static function rejectedDocumentDataProvider(): \Iterator
    {
        yield 'id is not a DID' => [
            ['id' => 'https://example.org/did.json'],
            'DID URL does not contain a syntactically valid DID.',
        ];
        yield 'id carries a fragment' => [
            ['id' => 'did:web:example.org#key-1'],
            'DID document id must be a bare DID, carrying no path, query or fragment.',
        ];
        yield 'verificationMethod is not a list' => [
            ['verificationMethod' => ['key-1' => []]],
            'DID document verificationMethod must be an array.',
        ];
        yield 'verification method is not an object' => [
            ['verificationMethod' => ['not-an-object']],
            'DID document verification method must be an object.',
        ];
        yield 'verification method id is under another DID' => [
            [
                'verificationMethod' => [
                    [
                        'id' => 'did:web:other.example#key-1',
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
                'authentication' => [],
            ],
            'verification method id must be under the document subject',
        ];
        yield 'duplicate verification method ids' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
            ],
            'DID document declares the same verification method id more than once.',
        ];
        yield 'unsupported verification method type' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'BogusKey2099',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
            ],
            'Unsupported verification method type: BogusKey2099.',
        ];
        yield 'controller is not a DID' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => 'https://example.org',
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
            ],
            'DID URL does not contain a syntactically valid DID.',
        ];
        yield 'controller carries a fragment' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => 'did:web:example.org#key-1',
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
            ],
            'controller must be a bare DID',
        ];
        yield 'controller is not the subject' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => 'did:web:other.example',
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
            ],
            'DID document verification method must be controlled by the document subject.',
        ];
        yield 'two key material properties' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK,
                        'publicKeyMultibase' => self::ED25519_MULTIBASE,
                    ],
                ],
            ],
            'declares both publicKeyJwk and publicKeyMultibase',
        ];
        yield 'no key material property' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                    ],
                ],
            ],
            'carries no supported key material property',
        ];
        yield 'jwk type declaring multibase material' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyMultibase' => self::ED25519_MULTIBASE,
                    ],
                ],
            ],
            'Verification method type JsonWebKey2020 does not carry publicKeyMultibase.',
        ];
        yield 'multibase type declaring jwk material' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'Multikey',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
            ],
            'Verification method type Multikey does not carry publicKeyJwk.',
        ];
        yield 'publicKeyJwk is not an object' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => 'not-an-object',
                    ],
                ],
            ],
            'DID document publicKeyJwk must be an object.',
        ];
        yield 'Ed25519 suite type carrying an X25519 key' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'Ed25519VerificationKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyMultibase' => self::X25519_MULTIBASE,
                    ],
                ],
            ],
            'Verification method type Ed25519VerificationKey2020 requires an Ed25519 key.',
        ];
        yield 'publicKeyJwk carries private key material' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK + ['d' => 'private'],
                    ],
                ],
            ],
            'JWK member "d" is not permitted for key type EC.',
        ];
        yield 'encryption key under a signature relationship' => [
            [
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK + ['use' => 'enc'],
                    ],
                ],
            ],
            'lists a key whose use is enc under the authentication relationship',
        ];
        yield 'signing key under keyAgreement' => [
            [
                'authentication' => [],
                'keyAgreement' => [self::METHOD_ID],
                'verificationMethod' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK + ['use' => 'sig'],
                    ],
                ],
            ],
            'lists a key whose use is sig under the keyAgreement relationship',
        ];
        yield 'embedded encryption key under a signature relationship' => [
            [
                'verificationMethod' => [],
                'authentication' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK + ['use' => 'enc'],
                    ],
                ],
            ],
            'lists a key whose use is enc under the authentication relationship',
        ];
        yield 'relationship is not a list' => [
            ['authentication' => ['first' => self::METHOD_ID]],
            'DID document authentication must be an array.',
        ];
        yield 'relative relationship reference' => [
            ['authentication' => ['#key-1']],
            'Relative DID URLs are not supported',
        ];
        yield 'relationship references an undefined method' => [
            ['authentication' => ['did:web:example.org#missing']],
            'DID document relationship authentication references a verification method the document does not ' .
            'define.',
        ];
        yield 'relationship entry is neither reference nor object' => [
            ['authentication' => [42]],
            'DID document verification method must be an object.',
        ];
        yield 'embedded method duplicates a defined id' => [
            [
                'authentication' => [
                    [
                        'id' => self::METHOD_ID,
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => self::EC_JWK,
                    ],
                ],
            ],
            'DID document declares the same verification method id more than once.',
        ];
    }


    public function testRejectsDocumentForAnotherDid(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID document id is not the DID the document was resolved for.');

        $this->sut()->fromData('did:web:other.example', $this->documentData());
    }


    public function testRejectsParsingASelfCertifyingDid(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage(
            'A did:jwk document is derived from the identifier itself, so it must be built rather than parsed ' .
            'from supplied data.',
        );

        $this->sut()->fromData($this->didJwk(self::EC_JWK), $this->documentData());
    }


    public function testRejectsParsingADidKey(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage(
            'A did:key document is derived from the identifier itself, so it must be built rather than parsed ' .
            'from supplied data.',
        );

        $this->sut()->fromData('did:key:' . self::ED25519_MULTIBASE, $this->documentData());
    }


    public function testRejectsParsingForANonBareDid(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('A DID document can only be parsed for a bare DID.');

        $this->sut()->fromData(self::SUBJECT . '#key-1', $this->documentData());
    }


    public function testRejectsDocumentWithoutId(): void
    {
        $data = $this->documentData();
        unset($data['id']);

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID document id must be a non-empty string.');

        $this->sut()->fromData(self::SUBJECT, $data);
    }


    public function testForDidJwkBuildsDocument(): void
    {
        $did = $this->didJwk(self::EC_JWK);

        $document = $this->sut()->forDidJwk(new DidUrl($did));

        $this->assertSame($did, $document->getId());
        $this->assertArrayHasKey($did . '#0', $document->getVerificationMethods());

        $resolved = $document->resolveVerificationMethod(
            new DidUrl($did . '#0'),
            VerificationRelationshipEnum::Authentication,
        );

        $this->assertSame(self::EC_JWK, $resolved->getPublicJwk());
    }


    public function testForDidJwkWithoutUseInfersRelationshipsFromTheCurve(): void
    {
        // We are asserting the relationships here rather than reading someone else's, so an absent use is
        // inferred rather than taken as permission for every relationship.
        $document = $this->sut()->forDidJwk(new DidUrl($this->didJwk(self::EC_JWK)));

        foreach (VerificationRelationshipEnum::forSignatureUse() as $relationship) {
            $this->assertNotSame([], $document->getVerificationMethodsFor($relationship));
        }

        $this->assertSame([], $document->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement));
    }


    public function testForDidJwkWithoutUseTreatsAKeyAgreementCurveAsSuch(): void
    {
        $document = $this->sut()->forDidJwk(new DidUrl($this->didJwk([
            'kty' => 'OKP',
            'crv' => 'X25519',
            'x' => 'dDfIibQM-949qf4jj-8mBY4Azq34ygSGhzd8AT2mx6s',
        ])));

        $this->assertNotSame([], $document->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement));
        $this->assertSame(
            [],
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication),
        );
    }


    public function testForDidJwkWithSignatureUseSkipsKeyAgreement(): void
    {
        $document = $this->sut()->forDidJwk(new DidUrl($this->didJwk(self::EC_JWK + ['use' => 'sig'])));

        $this->assertSame([], $document->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement));
        $this->assertNotSame(
            [],
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication),
        );
    }


    public function testForDidJwkWithEncryptionUseOnlyAgrees(): void
    {
        $document = $this->sut()->forDidJwk(new DidUrl($this->didJwk(self::EC_JWK + ['use' => 'enc'])));

        $this->assertNotSame([], $document->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement));
        $this->assertSame(
            [],
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication),
        );
    }


    public function testForDidJwkRejectsPrivateKeyMaterial(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('JWK member "d" is not permitted for key type EC.');

        $this->sut()->forDidJwk(new DidUrl($this->didJwk(self::EC_JWK + ['d' => 'private'])));
    }


    public function testForDidJwkRejectsAnotherMethod(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('A did:jwk document can only be built from a did:jwk value.');

        $this->sut()->forDidJwk(new DidUrl('did:web:example.org'));
    }


    public function testForDidKeyBuildsDocument(): void
    {
        $did = 'did:key:' . self::ED25519_MULTIBASE;

        $document = $this->sut()->forDidKey(new DidUrl($did));

        $this->assertSame($did, $document->getId());

        $resolved = $document->resolveVerificationMethod(
            new DidUrl($did . '#' . self::ED25519_MULTIBASE),
            VerificationRelationshipEnum::AssertionMethod,
        );

        $this->assertSame('Ed25519', $resolved->getPublicJwk()['crv']);
        // A signing key does not agree keys, and the derived X25519 method is deliberately not built.
        $this->assertSame([], $document->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement));
    }


    public function testForDidKeyWithEncryptionKeyOnlyAgrees(): void
    {
        $document = $this->sut()->forDidKey(new DidUrl(self::X25519_DID_KEY));

        $this->assertNotSame([], $document->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement));
        $this->assertSame(
            [],
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication),
        );
    }


    public function testForDidKeyRejectsAnotherMethod(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('A did:key document can only be built from a did:key value.');

        $this->sut()->forDidKey(new DidUrl('did:web:example.org'));
    }
}
