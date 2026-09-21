<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * Values of the `type` member of a W3C VCDM `termsOfUse` entry.
 *
 * The data model requires every entry to state its type and leaves the vocabulary to whoever defines the
 * policy: https://www.w3.org/TR/vc-data-model-2.0/#terms-of-use
 */
enum TermsOfUseTypesEnum: string
{
    // The credential was issued under an OpenID Federation trust framework; the entry's `policyId` is the
    // Issuer's Entity Identifier. Defined by OpenID Fed DCP (DIIP v5, Appendix B, "W3C VCDM Credentials").
    case OpenIdFederation = 'OpenIDFederation';
}
