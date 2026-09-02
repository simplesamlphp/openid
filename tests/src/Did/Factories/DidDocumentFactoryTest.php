<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did\Factories;

use Jose\Component\Core\JWK;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Did\AbstractDidResolver;
use SimpleSAML\OpenID\Did\DidDocument;
use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\DidWebResolver;
use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Did\PublicJwkValidator;
use SimpleSAML\OpenID\Did\ResolvedVerificationMethod;
use SimpleSAML\OpenID\Did\VerificationMethod;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\ValueAbstracts\KeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairBag;

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
#[UsesClass(AbstractDidResolver::class)]
#[UsesClass(DidWebResolver::class)]
#[UsesClass(JwkDecorator::class)]
#[UsesClass(KeyPair::class)]
#[UsesClass(SignatureKeyPair::class)]
#[UsesClass(SignatureKeyPairBag::class)]
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


    /**
     * @param array<non-empty-string, mixed>|null $additionalData
     * @param array<string, mixed>|null $jwk
     */
    protected function signatureKeyPair(
        string $keyId,
        ?array $additionalData = null,
        ?array $jwk = null,
    ): SignatureKeyPair {
        $additionalData ??= ['use' => 'sig', 'alg' => 'ES256', 'kid' => $keyId];

        return new SignatureKeyPair(
            SignatureAlgorithmEnum::ES256,
            new KeyPair(
                $this->createStub(JwkDecorator::class),
                new JwkDecorator(new JWK($jwk ?? self::EC_JWK), $additionalData),
                $keyId,
            ),
        );
    }


    /**
     * @return array<string, mixed>
     */
    protected function publishedJwk(string $keyId): array
    {
        return array_merge(self::EC_JWK, ['use' => 'sig', 'alg' => 'ES256', 'kid' => $keyId]);
    }


    public function testForDidWebPublishesEverySignerUnderAssertionMethod(): void
    {
        $document = $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag(
                $this->signatureKeyPair('key-1'),
                $this->signatureKeyPair('key-2'),
            ),
        );

        $this->assertSame(self::SUBJECT, $document->getId());
        $this->assertSame(
            ['did:web:example.org#key-1', 'did:web:example.org#key-2'],
            array_keys($document->getVerificationMethods()),
        );
        $this->assertSame(
            ['did:web:example.org#key-1', 'did:web:example.org#key-2'],
            array_keys($document->getVerificationMethodsFor(VerificationRelationshipEnum::AssertionMethod)),
        );
        $this->assertSame(
            [],
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication),
        );
    }


    public function testForDidWebPublishesTheKeyMaterialTheSignerCarries(): void
    {
        $document = $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
        );

        $resolved = $document->resolveVerificationMethod(
            new DidUrl('did:web:example.org#key-1'),
            VerificationRelationshipEnum::AssertionMethod,
        );

        // Merged rather than the bare JWK: a published key without its algorithm tells a verifier less
        // than the JWKS entry for the same key does.
        $this->assertSame($this->publishedJwk('key-1'), $resolved->getPublicJwk());
    }


    public function testForDidWebDeclaresTheDidCoreContextAndTheOneItsTypeComesFrom(): void
    {
        $this->assertSame(
            ['https://www.w3.org/ns/did/v1', 'https://w3id.org/security/suites/jws-2020/v1'],
            $this->sut()->forDidWeb(
                new DidUrl(self::SUBJECT),
                new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
            )->getContexts(),
        );

        $this->assertSame(
            ['https://www.w3.org/ns/did/v1', 'https://w3id.org/security/jwk/v1'],
            $this->sut()->forDidWeb(
                new DidUrl(self::SUBJECT),
                new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
                type: VerificationMethodTypeEnum::JsonWebKey,
            )->getContexts(),
        );
    }


    public function testForDidWebPlacesEveryKeyInEveryRelationshipAskedFor(): void
    {
        $document = $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
            [
                VerificationRelationshipEnum::AssertionMethod,
                VerificationRelationshipEnum::Authentication,
            ],
        );

        $this->assertArrayHasKey(
            'did:web:example.org#key-1',
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::AssertionMethod),
        );
        $this->assertArrayHasKey(
            'did:web:example.org#key-1',
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::Authentication),
        );
    }


    /**
     * A key identifier is whatever the deployment configured, so a document has to be publishable for one
     * that a fragment can not carry as it stands.
     */
    public function testForDidWebEncodesAKeyIdentifierIntoTheFragment(): void
    {
        $document = $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('did:jwk:eyJrdHkiOiJFQyJ9#0')),
        );

        $this->assertSame(
            ['did:web:example.org#did:jwk:eyJrdHkiOiJFQyJ9%230'],
            array_keys($document->getVerificationMethods()),
        );
    }


    /**
     * A bag files a pair under its key identifier, and PHP turns a numeric string array key into an
     * integer, so the fragment has to be taken from the pair rather than from the key it was filed under.
     */
    public function testForDidWebPublishesANumericKeyIdentifier(): void
    {
        $document = $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('123')),
        );

        $this->assertSame(['did:web:example.org#123'], array_keys($document->getVerificationMethods()));
    }


    /**
     * The id in the published document and the one a caller emits as a `kid` for the same key have to be
     * the same string, which they only are by construction if both come from here.
     */
    public function testVerificationMethodIdForIsWhatThePublishedDocumentUses(): void
    {
        $sut = $this->sut();

        $this->assertSame(
            $sut->verificationMethodIdFor(new DidUrl(self::SUBJECT), 'did:jwk:eyJrdHkiOiJFQyJ9#0')->getValue(),
            array_key_first(
                $sut->forDidWeb(
                    new DidUrl(self::SUBJECT),
                    new SignatureKeyPairBag($this->signatureKeyPair('did:jwk:eyJrdHkiOiJFQyJ9#0')),
                )->getVerificationMethods(),
            ),
        );
    }


    public function testVerificationMethodIdForIgnoresWhateverTheDidUrlCarriedBesidesItsDid(): void
    {
        $this->assertSame(
            'did:web:example.org#key-1',
            $this->sut()
                ->verificationMethodIdFor(new DidUrl('did:web:example.org#other'), 'key-1')
                ->getValue(),
        );
    }


    public function testVerificationMethodIdForRejectsAnEmptyKeyIdentifier(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('needs a key identifier to name the key by');

        $this->sut()->verificationMethodIdFor(new DidUrl(self::SUBJECT), '');
    }


    public function testForDidWebSerializesToThePublishedDocument(): void
    {
        $document = $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
        );

        $this->assertSame(
            [
                '@context' => [
                    'https://www.w3.org/ns/did/v1',
                    'https://w3id.org/security/suites/jws-2020/v1',
                ],
                'id' => self::SUBJECT,
                'verificationMethod' => [
                    [
                        'id' => 'did:web:example.org#key-1',
                        'type' => 'JsonWebKey2020',
                        'controller' => self::SUBJECT,
                        'publicKeyJwk' => $this->publishedJwk('key-1'),
                    ],
                ],
                'assertionMethod' => ['did:web:example.org#key-1'],
            ],
            $document->jsonSerialize(),
        );
    }


    public function testForDidWebRejectsAnotherMethod(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('A did:web document can only be built from a did:web value.');

        $this->sut()->forDidWeb(
            new DidUrl('did:key:' . self::ED25519_MULTIBASE),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
        );
    }


    /**
     * Syntactically a DID is not enough. A document published under an identifier this library could not
     * resolve sits somewhere nothing can look it up, so the resolver's own rules are applied here too.
     */
    #[DataProvider('unresolvableDidWebDataProvider')]
    public function testForDidWebRejectsAnIdentifierItCouldNotResolve(
        string $did,
        string $expectedExceptionMessage,
    ): void {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->sut()->forDidWeb(
            new DidUrl($did),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
        );
    }


    public static function unresolvableDidWebDataProvider(): \Iterator
    {
        yield 'single label host' => [
            'did:web:localhost',
            'did:web host is not a syntactically valid domain name.',
        ];
        yield 'IPv4 literal host' => [
            'did:web:192.168.1.1',
            'did:web does not permit an IP address as the host.',
        ];
        yield 'encoded slash in the host' => [
            'did:web:example.org%2Fevil.test',
            'did:web host must not be percent encoded',
        ];
        yield 'relative path segment' => [
            'did:web:example.org:..',
            'did:web path segments must not be relative references.',
        ];
    }


    public function testForDidWebRejectsADidUrlNamingSomethingInsideTheDocument(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('can only be built for a bare DID');

        $this->sut()->forDidWeb(
            new DidUrl(self::METHOD_ID),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
        );
    }


    public function testForDidWebRejectsPublishingKeysUnderNoRelationship(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('at least one verification relationship');

        $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
            [],
        );
    }


    public function testForDidWebRejectsATypeThatDoesNotCarryAJwk(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('does not carry publicKeyJwk');

        $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1')),
            type: VerificationMethodTypeEnum::Multikey,
        );
    }


    public function testForDidWebRejectsADocumentWithNothingToPublish(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('at least one verification method');

        $this->sut()->forDidWeb(new DidUrl(self::SUBJECT), new SignatureKeyPairBag());
    }


    /**
     * The one document this library publishes rather than reads is the one where we are the party who could
     * leak a private key, so the check runs on the way out too.
     */
    public function testForDidWebRefusesToPublishPrivateKeyMaterial(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('must not carry private key material');

        $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag(
                $this->signatureKeyPair(
                    'key-1',
                    [],
                    array_merge(self::EC_JWK, ['d' => 'ROUb7hgIt5rzYrCd0FI1pcyCwXsE9AAHYCBkTCSPMkI']),
                ),
            ),
        );
    }


    public function testForDidWebRefusesAKeyItsOwnUseRulesOutOfTheRelationship(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('can not list this key under the assertionMethod relationship');

        $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1', ['use' => 'enc'])),
        );
    }


    /**
     * The rule for a document we are the author of is not the one for a document somebody else wrote. There,
     * an absent `use` contradicts nothing and is let through; here, publishing this key under a signature
     * relationship would be this deployment asserting that a key agreement key signs.
     */
    public function testForDidWebRefusesAKeyAgreementCurveWhichDeclaresNoUse(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('can not list this key under the assertionMethod relationship');

        $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag(
                $this->signatureKeyPair(
                    'key-1',
                    [],
                    ['kty' => 'OKP', 'crv' => 'X25519', 'x' => 'W_Vcc7guviK-gPNDBmevVw-uJVamQV5rMNQGUwCqlH0'],
                ),
            ),
        );
    }


    public function testForDidWebRefusesAKeyWhoseUseNamesNeitherPurpose(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('is neither sig nor enc');

        $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag($this->signatureKeyPair('key-1', ['use' => 'wrapKey'])),
        );
    }


    public function testForDidWebCanPublishAKeyAgreementKeyUnderKeyAgreement(): void
    {
        $document = $this->sut()->forDidWeb(
            new DidUrl(self::SUBJECT),
            new SignatureKeyPairBag(
                $this->signatureKeyPair(
                    'key-1',
                    [],
                    ['kty' => 'OKP', 'crv' => 'X25519', 'x' => 'W_Vcc7guviK-gPNDBmevVw-uJVamQV5rMNQGUwCqlH0'],
                ),
            ),
            [VerificationRelationshipEnum::KeyAgreement],
        );

        $this->assertArrayHasKey(
            'did:web:example.org#key-1',
            $document->getVerificationMethodsFor(VerificationRelationshipEnum::KeyAgreement),
        );
    }
}
