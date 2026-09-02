<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did\Factories;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\PublicKeyUseEnum;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Did\DidDocument;
use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\DidWebResolver;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Did\PublicJwkValidator;
use SimpleSAML\OpenID\Did\VerificationMethod;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairBag;

/**
 * Builds DID documents: by parsing one that was retrieved, by constructing the document a self describing DID
 * implies, or by assembling the one a deployment publishes for its own did:web identity.
 *
 * Parsing is deliberately strict. The documents this is pointed at are supplied by whoever controls the DID
 * being resolved, so anything ambiguous is refused rather than guessed at.
 *
 * @see \SimpleSAML\Test\OpenID\Did\Factories\DidDocumentFactoryTest
 */
class DidDocumentFactory
{
    /** The context every DID document declares, defining the members DID Core itself specifies. */
    public const DID_CORE_CONTEXT = 'https://www.w3.org/ns/did/v1';

    /**
     * Methods whose document is derived from the identifier itself. A document for one of these is never
     * supplied by anyone, so accepting supplied data for one would let a different key be resolved under an
     * identifier that is supposed to be the key.
     */
    protected const SELF_CERTIFYING_METHODS = [
        'jwk',
        'key',
    ];

    /** Curves that exist to agree keys, never to sign with. */
    protected const KEY_AGREEMENT_CURVES = [
        'X25519',
        'X448',
    ];


    public function __construct(
        protected readonly MultibaseKeyDecoder $multibaseKeyDecoder,
        protected readonly DidJwkResolver $didJwkResolver,
        protected readonly PublicJwkValidator $publicJwkValidator,
    ) {
    }


    /**
     * Parse a retrieved DID document.
     *
     * @param string $expectedDid The DID this document was retrieved for. It is required rather than optional
     * so that binding the document to it cannot be forgotten by a caller; without that binding a redirect can
     * substitute another party's document and every later check would run against the wrong subject.
     * @param array<array-key, mixed> $data The decoded DID document.
     * @param bool $requireControllerToMatchSubject Whether every verification method must be controlled by the
     * document subject itself. DID Core permits a different controller, so this is a profile policy rather than
     * a syntax rule, but it defaults to on.
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function fromData(
        string $expectedDid,
        array $data,
        bool $requireControllerToMatchSubject = true,
    ): DidDocument {
        $expected = new DidUrl($expectedDid);

        if (!$expected->isBareDid()) {
            throw new DidException('A DID document can only be parsed for a bare DID.');
        }

        if (in_array($expected->getMethod(), self::SELF_CERTIFYING_METHODS, true)) {
            throw new DidException(
                sprintf(
                    'A did:%s document is derived from the identifier itself, so it must be built rather than ' .
                    'parsed from supplied data.',
                    $expected->getMethod(),
                ),
            );
        }

        $subject = new DidUrl($this->requireString($data, ClaimsEnum::Id->value, 'id'));

        if (!$subject->isBareDid()) {
            throw new DidException('DID document id must be a bare DID, carrying no path, query or fragment.');
        }

        if ($subject->getDid() !== $expected->getDid()) {
            throw new DidException('DID document id is not the DID the document was resolved for.');
        }

        /** @var array<string, true> $seenIds */
        $seenIds = [];
        $verificationMethods = [];

        foreach ($this->requireList($data, ClaimsEnum::VerificationMethod->value) as $entry) {
            $verificationMethod = $this->buildVerificationMethod(
                $entry,
                $subject,
                $requireControllerToMatchSubject,
            );

            $this->claimId($seenIds, $verificationMethod);
            $verificationMethods[$verificationMethod->getId()->getValue()] = $verificationMethod;
        }

        $relationshipMethods = [];

        foreach (VerificationRelationshipEnum::cases() as $relationship) {
            if (!array_key_exists($relationship->value, $data)) {
                continue;
            }

            $relationshipMethods[$relationship->value] = $this->buildRelationshipMethods(
                $this->requireList($data, $relationship->value),
                $relationship,
                $subject,
                $requireControllerToMatchSubject,
                $verificationMethods,
                $seenIds,
            );
        }

        return new DidDocument($subject->getDid(), $verificationMethods, $relationshipMethods);
    }


    /**
     * Build the document implied by a did:jwk value.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function forDidJwk(DidUrl $did): DidDocument
    {
        if ($did->getMethod() !== 'jwk') {
            throw new DidException('A did:jwk document can only be built from a did:jwk value.');
        }

        return $this->buildSelfDescribingDocument(
            $did->getDid(),
            // The did:jwk method fixes the verification method fragment at "0".
            new DidUrl($did->getDid() . '#0'),
            VerificationMethodTypeEnum::JsonWebKey2020,
            $this->didJwkResolver->extractJwkFromDidJwk($did->getDid()),
        );
    }


    /**
     * Build the document implied by a did:key value.
     *
     * Note that for an Ed25519 signing key the did:key method also implies a derived X25519 key agreement
     * method. Deriving it is out of scope here, so a key agreement lookup against such a document finds
     * nothing rather than the wrong key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function forDidKey(DidUrl $did): DidDocument
    {
        if ($did->getMethod() !== 'key') {
            throw new DidException('A did:key document can only be built from a did:key value.');
        }

        $multibaseKey = $did->getMethodSpecificId();

        return $this->buildSelfDescribingDocument(
            $did->getDid(),
            // The did:key method repeats the multibase value as the verification method fragment.
            new DidUrl($did->getDid() . '#' . $multibaseKey),
            VerificationMethodTypeEnum::Multikey,
            $this->multibaseKeyDecoder->decodeToJwk($multibaseKey),
        );
    }


    /**
     * Build the DID document a deployment publishes for its own did:web identity.
     *
     * Every key pair given becomes a verification method and is placed in every relationship asked for.
     * Which keys those are is the caller's decision and a consequential one: a key that signed something
     * still being verified has to stay in the document, and in the relationship a verifier looks under,
     * long after it has stopped signing. Dropping a retired signer makes everything it signed
     * unverifiable, while the key merely remaining under `verificationMethod` does not help a verifier
     * that checks the relationship.
     *
     * @param \SimpleSAML\OpenID\Did\DidUrl $did The did:web identity this document is published under.
     * @param \SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairBag $signatureKeyPairBag The signers to
     * publish. Only the public half of each pair is read.
     * @param list<\SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum> $relationships The
     * relationships every published key is placed in. Signing credentials and status tokens is asserting,
     * so assertionMethod is the default; nothing authenticates as an issuer.
     * @param \SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum $type How each verification method
     * declares its key material. JsonWebKey2020 by default: the DID Specification Registries deprecate it
     * in favour of JsonWebKey, but it remains the more widely understood of the two.
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function forDidWeb(
        DidUrl $did,
        SignatureKeyPairBag $signatureKeyPairBag,
        array $relationships = [VerificationRelationshipEnum::AssertionMethod],
        VerificationMethodTypeEnum $type = VerificationMethodTypeEnum::JsonWebKey2020,
    ): DidDocument {
        if ($did->getMethod() !== 'web') {
            throw new DidException('A did:web document can only be built from a did:web value.');
        }

        if (!$did->isBareDid()) {
            throw new DidException(
                'A DID document can only be built for a bare DID, carrying no path, query or fragment.',
            );
        }

        // Beyond being syntactically a DID, the identifier has to be one this library could resolve, or the
        // document is published under a name nothing can look up - an IP literal host, a single label one, a
        // percent encoded segment. The resolver owns those rules, so they are asked for rather than restated.
        DidWebResolver::assertIdentifierIsResolvable($did->getDid());

        if ($relationships === []) {
            throw new DidException(
                'A DID document must place its verification methods under at least one verification ' .
                'relationship, since a key belonging to none of them can not be used for anything.',
            );
        }

        if (!in_array($type, VerificationMethodTypeEnum::withPublicKeyJwk(), true)) {
            throw new DidException(
                sprintf(
                    'Verification method type %s does not carry publicKeyJwk, so it can not declare key ' .
                    'material published as a JWK.',
                    $type->value,
                ),
            );
        }

        if ($signatureKeyPairBag->getAll() === []) {
            throw new DidException('A DID document must publish at least one verification method.');
        }

        /** @var array<string, true> $seenIds */
        $seenIds = [];
        $verificationMethods = [];

        foreach ($signatureKeyPairBag->getAll() as $signatureKeyPair) {
            $publicJwk = $this->publicJwkFor($signatureKeyPair);
            $publishableUnder = $this->relationshipsFor($publicJwk);

            foreach ($relationships as $relationship) {
                if (in_array($relationship, $publishableUnder, true)) {
                    continue;
                }

                throw new DidException(
                    sprintf(
                        'A DID document can not list this key under the %s relationship, since what the ' .
                        'key is for - its "use", or the curve it is on where it declares none - says ' .
                        'otherwise.',
                        $relationship->value,
                    ),
                );
            }

            // The pair's own key id rather than the key it is filed under: a bag keys by that same value,
            // but PHP turns a numeric string array key into an integer on the way in.
            $verificationMethod = new VerificationMethod(
                $this->verificationMethodIdFor($did, $signatureKeyPair->getKeyPair()->getKeyId()),
                $type,
                $did->getDid(),
                $publicJwk,
            );

            $this->claimId($seenIds, $verificationMethod);
            $verificationMethods[$verificationMethod->getId()->getValue()] = $verificationMethod;
        }

        $relationshipMethods = [];

        foreach ($relationships as $relationship) {
            // Referenced rather than embedded when this is serialised, since every one of them is also a
            // document wide method. See DidDocument::serializeRelationship().
            $relationshipMethods[$relationship->value] = $verificationMethods;
        }

        return new DidDocument(
            $did->getDid(),
            $verificationMethods,
            $relationshipMethods,
            [self::DID_CORE_CONTEXT, $type->jsonLdContext()],
        );
    }


    /**
     * The absolute DID URL naming one key within a DID document.
     *
     * A published document and the signed artifacts naming keys in it have to agree on these ids, so this
     * is the one place a key identifier becomes a fragment. A caller emitting a `kid` for a key it also
     * publishes asks here rather than assembling the id itself, since two spellings of that mapping is how
     * a signature comes to name a verification method its own document does not contain.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function verificationMethodIdFor(DidUrl $did, string $keyId): DidUrl
    {
        $fragment = DidUrl::encodeFragment($keyId);

        if ($fragment === '') {
            throw new DidException('A verification method id needs a key identifier to name the key by.');
        }

        return new DidUrl($did->getDid() . '#' . $fragment);
    }


    /**
     * The public key a pair publishes, checked by the same rules a retrieved document's key material is.
     *
     * A DID document must carry no private key material, and this is the one document where we are the
     * party who could put it there, so the check runs on the way out as well as on the way in.
     *
     * @return array<array-key, mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function publicJwkFor(SignatureKeyPair $signatureKeyPair): array
    {
        $publicJwk = $signatureKeyPair->getKeyPair()->getPublicKey()->jsonSerialize();

        $this->publicJwkValidator->validate($publicJwk);

        return $publicJwk;
    }


    /**
     * @param array<array-key, mixed> $publicJwk
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function buildSelfDescribingDocument(
        string $subject,
        DidUrl $id,
        VerificationMethodTypeEnum $type,
        array $publicJwk,
    ): DidDocument {
        $this->publicJwkValidator->validate($publicJwk);

        $verificationMethods = [
            $id->getValue() => new VerificationMethod($id, $type, $subject, $publicJwk),
        ];

        $relationshipMethods = [];

        foreach ($this->relationshipsFor($publicJwk) as $relationship) {
            $relationshipMethods[$relationship->value] = $verificationMethods;
        }

        return new DidDocument($subject, $verificationMethods, $relationshipMethods);
    }


    /**
     * The relationships a key may be listed under in a document this library is the author of.
     *
     * Unlike a retrieved document, where the author chose the relationships and an absent `use` contradicts
     * nothing, here we are the ones asserting them. Granting every relationship would be us claiming an
     * X25519 key authenticates, so an absent `use` is inferred from the curve instead.
     *
     * This is why {@see assertUsableFor()} is not what the building paths ask. That one is the rule for a
     * document somebody else wrote, and it deliberately lets an absent `use` through.
     *
     * @param array<array-key, mixed> $publicJwk
     * @return list<\SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum>
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function relationshipsFor(array $publicJwk): array
    {
        $use = $publicJwk[ClaimsEnum::Use->value] ?? null;

        return match (true) {
            $use === PublicKeyUseEnum::Encryption->value => VerificationRelationshipEnum::forEncryptionUse(),
            $use === PublicKeyUseEnum::Signature->value => VerificationRelationshipEnum::forSignatureUse(),
            $use !== null => throw new DidException(
                'A DID document cannot be built for a key whose "use" is neither sig nor enc, since which ' .
                'verification relationships it belongs to would be a guess.',
            ),
            in_array($publicJwk['crv'] ?? null, self::KEY_AGREEMENT_CURVES, true) =>
            VerificationRelationshipEnum::forEncryptionUse(),
            default => VerificationRelationshipEnum::forSignatureUse(),
        };
    }


    /**
     * @param list<mixed> $entries
     * @param array<string, \SimpleSAML\OpenID\Did\VerificationMethod> $verificationMethods
     * @param array<string, true> $seenIds
     * @return array<string, \SimpleSAML\OpenID\Did\VerificationMethod>
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function buildRelationshipMethods(
        array $entries,
        VerificationRelationshipEnum $relationship,
        DidUrl $subject,
        bool $requireControllerToMatchSubject,
        array $verificationMethods,
        array &$seenIds,
    ): array {
        $methods = [];

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                // A reference. Parsing it first means a relative reference is refused as such, instead of
                // being reported as a method that simply is not there.
                $referenced = $verificationMethods[(new DidUrl($entry))->getValue()] ?? null;

                if (!$referenced instanceof VerificationMethod) {
                    throw new DidException(
                        sprintf(
                            'DID document relationship %s references a verification method the document does ' .
                            'not define.',
                            $relationship->value,
                        ),
                    );
                }

                $this->assertUsableFor($referenced, $relationship);
                $methods[$referenced->getId()->getValue()] = $referenced;

                continue;
            }

            // Otherwise the relationship embeds the method inline, which DID Core allows equally.
            $embedded = $this->buildVerificationMethod($entry, $subject, $requireControllerToMatchSubject);
            $this->assertUsableFor($embedded, $relationship);
            $this->claimId($seenIds, $embedded);
            $methods[$embedded->getId()->getValue()] = $embedded;
        }

        return $methods;
    }


    /**
     * Refuse a key whose own declared use contradicts the relationship it is being listed under.
     *
     * Only the key's own `use` is consulted, never the curve. A document that simply omits `use` is making no
     * claim to contradict, and refusing it on an inference would turn a permitted document into an error.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function assertUsableFor(
        VerificationMethod $verificationMethod,
        VerificationRelationshipEnum $relationship,
    ): void {
        $use = $verificationMethod->getPublicJwk()[ClaimsEnum::Use->value] ?? null;
        $isKeyAgreement = $relationship === VerificationRelationshipEnum::KeyAgreement;

        if ($use === PublicKeyUseEnum::Encryption->value && !$isKeyAgreement) {
            throw new DidException(
                sprintf(
                    'DID document lists a key whose use is enc under the %s relationship, which needs a ' .
                    'signing key.',
                    $relationship->value,
                ),
            );
        }

        if ($use === PublicKeyUseEnum::Signature->value && $isKeyAgreement) {
            throw new DidException(
                'DID document lists a key whose use is sig under the keyAgreement relationship, which needs a ' .
                'key agreement key.',
            );
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function buildVerificationMethod(
        mixed $data,
        DidUrl $subject,
        bool $requireControllerToMatchSubject,
    ): VerificationMethod {
        if (!is_array($data)) {
            throw new DidException('DID document verification method must be an object.');
        }

        $id = new DidUrl($this->requireString($data, ClaimsEnum::Id->value, 'verification method id'));

        if ($id->getDid() !== $subject->getDid()) {
            throw new DidException(
                'DID document verification method id must be under the document subject. A method claiming an ' .
                'id under another DID would have that id travel onwards as the key identifier.',
            );
        }

        $typeValue = $this->requireString($data, ClaimsEnum::Type->value, 'verification method type');
        $type = VerificationMethodTypeEnum::tryFrom($typeValue);

        if (!$type instanceof VerificationMethodTypeEnum) {
            throw new DidException(sprintf('Unsupported verification method type: %s.', $typeValue));
        }

        $controller = $this->requireString(
            $data,
            ClaimsEnum::Controller->value,
            'verification method controller',
        );

        if (!(new DidUrl($controller))->isBareDid()) {
            throw new DidException(
                'DID document verification method controller must be a bare DID, carrying no path, query or ' .
                'fragment.',
            );
        }

        if ($requireControllerToMatchSubject && $controller !== $subject->getDid()) {
            throw new DidException('DID document verification method must be controlled by the document subject.');
        }

        return new VerificationMethod(
            $id,
            $type,
            $controller,
            $this->extractPublicJwk($data, $type),
        );
    }


    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function extractPublicJwk(array $data, VerificationMethodTypeEnum $type): array
    {
        $hasJwk = array_key_exists(ClaimsEnum::PublicKeyJwk->value, $data);
        $hasMultibase = array_key_exists(ClaimsEnum::PublicKeyMultibase->value, $data);

        if ($hasJwk && $hasMultibase) {
            throw new DidException(
                'DID document verification method must carry exactly one key material property, but it ' .
                'declares both publicKeyJwk and publicKeyMultibase.',
            );
        }

        if ($hasJwk) {
            if (!in_array($type, VerificationMethodTypeEnum::withPublicKeyJwk(), true)) {
                throw new DidException(
                    sprintf('Verification method type %s does not carry publicKeyJwk.', $type->value),
                );
            }

            $publicJwk = $data[ClaimsEnum::PublicKeyJwk->value];

            if (!is_array($publicJwk)) {
                throw new DidException('DID document publicKeyJwk must be an object.');
            }
        } elseif ($hasMultibase) {
            if (!in_array($type, VerificationMethodTypeEnum::withPublicKeyMultibase(), true)) {
                throw new DidException(
                    sprintf('Verification method type %s does not carry publicKeyMultibase.', $type->value),
                );
            }

            $publicJwk = $this->multibaseKeyDecoder->decodeToJwk(
                $this->requireString($data, ClaimsEnum::PublicKeyMultibase->value, 'publicKeyMultibase'),
            );
        } else {
            throw new DidException(
                'DID document verification method carries no supported key material property.',
            );
        }

        $this->publicJwkValidator->validate($publicJwk);

        $expectedCurve = $type->expectedCurve();

        if ($expectedCurve !== null && ($publicJwk['crv'] ?? null) !== $expectedCurve) {
            throw new DidException(
                sprintf('Verification method type %s requires an %s key.', $type->value, $expectedCurve),
            );
        }

        return $publicJwk;
    }


    /**
     * @param array<string, true> $seenIds
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function claimId(array &$seenIds, VerificationMethod $verificationMethod): void
    {
        $id = $verificationMethod->getId()->getValue();

        if (array_key_exists($id, $seenIds)) {
            throw new DidException('DID document declares the same verification method id more than once.');
        }

        $seenIds[$id] = true;
    }


    /**
     * @param array<array-key, mixed> $data
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function requireString(array $data, string $member, string $context): string
    {
        $value = $data[$member] ?? null;

        if (!is_string($value) || $value === '') {
            throw new DidException(sprintf('DID document %s must be a non-empty string.', $context));
        }

        return $value;
    }


    /**
     * @param array<array-key, mixed> $data
     * @return list<mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function requireList(array $data, string $member): array
    {
        if (!array_key_exists($member, $data)) {
            return [];
        }

        $value = $data[$member];

        if (!is_array($value) || !array_is_list($value)) {
            throw new DidException(sprintf('DID document %s must be an array.', $member));
        }

        return $value;
    }
}
