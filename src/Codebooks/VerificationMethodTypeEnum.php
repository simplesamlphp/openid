<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * Verification method types understood when parsing a DID document.
 *
 * Each type dictates which key material property the verification method must carry, so that a document cannot
 * declare one encoding and supply another.
 *
 * @see https://www.w3.org/TR/did-core/#verification-methods
 */
enum VerificationMethodTypeEnum: string
{
    case Ed25519VerificationKey2020 = 'Ed25519VerificationKey2020';

    case JsonWebKey = 'JsonWebKey';

    case JsonWebKey2020 = 'JsonWebKey2020';

    case Multikey = 'Multikey';


    /**
     * The JSON-LD context defining this type.
     *
     * A published document declares the contexts its members come from, and the verification method type is
     * the member whose definition varies between documents, so the context follows from the type rather than
     * being chosen alongside it.
     */
    public function jsonLdContext(): string
    {
        return match ($this) {
            self::Ed25519VerificationKey2020 => 'https://w3id.org/security/suites/ed25519-2020/v1',
            self::JsonWebKey => 'https://w3id.org/security/jwk/v1',
            self::JsonWebKey2020 => 'https://w3id.org/security/suites/jws-2020/v1',
            self::Multikey => 'https://w3id.org/security/multikey/v1',
        };
    }


    /**
     * The curve a suite specific type pins its key material to, or null where the type is a generic one.
     *
     * Without this, a method could name Ed25519VerificationKey2020 and supply an X25519 key, which would then
     * be placed in signing relationships it cannot sign for.
     */
    public function expectedCurve(): ?string
    {
        return match ($this) {
            self::Ed25519VerificationKey2020 => 'Ed25519',
            default => null,
        };
    }


    /**
     * Types whose key material is carried in the `publicKeyJwk` property.
     *
     * @return list<self>
     */
    public static function withPublicKeyJwk(): array
    {
        return [
            self::JsonWebKey,
            self::JsonWebKey2020,
        ];
    }


    /**
     * Types whose key material is carried in the `publicKeyMultibase` property.
     *
     * @return list<self>
     */
    public static function withPublicKeyMultibase(): array
    {
        return [
            self::Ed25519VerificationKey2020,
            self::Multikey,
        ];
    }
}
