<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum EntityTypesEnum: string
{
    case FederationEntity = 'federation_entity';

    case OpenIdProvider = 'openid_provider';

    case OpenIdRelyingParty = 'openid_relying_party';

    case OAuthAuthorizationServer = 'oauth_authorization_server';

    case OAuthClient = 'oauth_client';

    case OAuthProtectedResource = 'oauth_resource';

    // OpenID Federation Digital Credentials Profile (OpenID Fed DCP), published as Appendix B of DIIP v5.
    // Section 5.1 of OpenID Federation leaves room for these: "Additional Entity Type Identifiers MAY be
    // defined to support use cases for other protocols."

    // Carries the OpenID4VCI Credential Issuer metadata.
    case OpenIdCredentialIssuer = 'openid_credential_issuer';

    // Carries the OpenID4VP Verifier metadata.
    case OpenIdCredentialVerifier = 'openid_credential_verifier';

    // Carries, in `jwks`, the keys the Issuer signs Digital Credentials with - as opposed to the
    // Federation Entity Keys in the Entity Statement's own `jwks` claim, which sign the statement.
    case VcIssuer = 'vc_issuer';
}
