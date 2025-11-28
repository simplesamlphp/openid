<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum CredentialFormatIdentifiersEnum: string
{
    // W3C Verifiable Credentials
    // VC signed as a JWT, not using JSON-LD
    case JwtVcJson = 'jwt_vc_json';

    // VC signed as a JWT, using JSON-LD
    case JwtVcJsonLd = 'jwt_vc_json-ld';

    // VC secured using Data Integrity, using JSON-LD, with a proof suite requiring Linked Data canonicalization
    case LdpVc = 'ldp_vc';

    // ISO mDL
    // Mobile Security Object (MSO) which secures the mdoc data model encoded as CBOR.
    case MsoMdoc = 'mso_mdoc';

    // IETF SD-JWT VC
    case DcSdJwt = 'dc+sd-jwt';

    // Deprecated identifier for IETF SD-JWT VC. Use 'dc+sd-jwt' instead.
    case VcSdJwt = 'vc+sd-jwt';
}
