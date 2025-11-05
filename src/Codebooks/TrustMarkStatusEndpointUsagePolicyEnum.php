<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * Trust Mark Status Endpoint Usage Policy for Trust Mark validation.
 */
enum TrustMarkStatusEndpointUsagePolicyEnum
{
    // Trust Mark will always be checked using the Trust Mark Status Endpoint.
    // Trust Mark Status Endpoint must be exposed by Trust Mark Issuer.
    case Required;

    // Trust Mark will be checked using the Trust Mark Status Endpoint if the
    // Trust Mark does not expire. Trust Mark Status Endpoint must be exposed
    // by Trust Mark Issuer.
    case RequiredForNonExpiringTrustMarksOnly;

    // Trust Mark will be checked using the Trust Mark Status Endpoint if the
    // Trust Mark Issuer provides the endpoint.
    case RequiredIfEndpointProvided;

    // Trust Mark will be checked using the Trust Mark Status Endpoint if the
    // Trust Mark does not expire and the Trust Mark Issuer provides the
    // endpoint.
    case RequiredIfEndpointProvidedForNonExpiringTrustMarksOnly;

    // Trust Mark will not be checked using the Trust Mark Status Endpoint.
    case NotUtilized;
}
