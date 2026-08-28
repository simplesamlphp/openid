<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * Verification relationships defined by DID Core.
 *
 * @see https://www.w3.org/TR/did-core/#verification-relationships
 */
enum VerificationRelationshipEnum: string
{
    case AssertionMethod = 'assertionMethod';

    case Authentication = 'authentication';

    case CapabilityDelegation = 'capabilityDelegation';

    case CapabilityInvocation = 'capabilityInvocation';

    case KeyAgreement = 'keyAgreement';


    /**
     * Relationships in which a signing key participates.
     *
     * @return list<self>
     */
    public static function forSignatureUse(): array
    {
        return [
            self::AssertionMethod,
            self::Authentication,
            self::CapabilityDelegation,
            self::CapabilityInvocation,
        ];
    }


    /**
     * Relationships in which a key agreement key participates.
     *
     * @return list<self>
     */
    public static function forEncryptionUse(): array
    {
        return [
            self::KeyAgreement,
        ];
    }
}
