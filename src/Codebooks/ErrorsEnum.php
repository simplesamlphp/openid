<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ErrorsEnum: string
{
    case InvalidRequest = 'invalid_request';

    case InvalidClient = 'invalid_client';

    case InvalidClientMetadata = 'invalid_client_metadata';

    // RFC 9449 sections 5 and 7.1
    case InvalidDpopProof = 'invalid_dpop_proof';

    case InvalidIssuer = 'invalid_issuer';

    case InvalidMetadata = 'invalid_metadata';

    case InvalidRedirectUri = 'invalid_redirect_uri';

    case InvalidSubject = 'invalid_subject';

    case InvalidTrustAnchor = 'invalid_trust_anchor';

    case InvalidTrustChain = 'invalid_trust_chain';

    case NotFound = 'not_found';

    case ServerError = 'server_error';

    case TemporarilyUnavailable = 'temporarily_unavailable';

    case UnsupportedParameter = 'unsupported_parameter';

    // RFC 9449 sections 8 and 9
    case UseDpopNonce = 'use_dpop_nonce';
}
