<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ErrorsEnum: string
{
    case InvalidRequest = 'invalid_request';

    case InvalidClient = 'invalid_client';

    case InvalidIssuer = 'invalid_issuer';

    case InvalidMetadata = 'invalid_metadata';

    case InvalidSubject = 'invalid_subject';

    case InvalidTrustAnchor = 'invalid_trust_anchor';

    case InvalidTrustChain = 'invalid_trust_chain';

    case NotFound = 'not_found';

    case ServerError = 'server_error';

    case TemporarilyUnavailable = 'temporarily_unavailable';

    case UnsupportedParameter = 'unsupported_parameter';
}
