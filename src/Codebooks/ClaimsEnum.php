<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ClaimsEnum: string
{
    // Algorithm
    case Alg = 'alg';
    case ApplicationType = 'application_type';
    // Audience
    case Aud = 'aud';
    case AuthorityHints = 'authority_hints';
    case AuthorizationEndpoint = 'authorization_endpoint';
    case BackChannelLogoutUri = 'backchannel_logout_uri';
    case ClientId = 'client_id';
    case ClientName = 'client_name';
    case ClientRegistrationTypes = 'client_registration_types';
    case ClientRegistrationTypesSupported = 'client_registration_types_supported';
    case Contacts = 'contacts';
    // ExpirationTime
    case Exp = 'exp';
    case FederationFetchEndpoint = 'federation_fetch_endpoint';
    case GrantTypes = 'grant_types';
    case HomepageUri = 'homepage_uri';
    // IssuedAt
    case Iat = 'iat';
    // Issuer
    case Iss = 'iss';
    // JWT ID
    case Jti = 'jti';
    // JsonWebKeySet
    case Jwks = 'jwks';
    case JwksUri = 'jwks_uri';
    // KeyId
    case Kid = 'kid';
    case Keys = 'keys';
    case LogoUri = 'logo_uri';
    case Metadata = 'metadata';
    case MetadataPolicy = 'metadata_policy';
    // MetadataPolicyCritical
    case MetadataPolicyCrit = 'metadata_policy_crit';
    case OrganizationName = 'organization_name';
    case PolicyUri = 'policy_uri';
    case PostLogoutRedirectUris = 'post_logout_redirect_uris';
    // PublicKeyUse
    case Use = 'use';
    case RedirectUris = 'redirect_uris';
    case RequestAuthenticationMethodsSupported = 'request_authentication_methods_supported';
    case RequestAuthenticationSigningAlgValuesSupported = 'request_authentication_signing_alg_values_supported';
    case ResponseTypes = 'response_types';
    case Scope = 'scope';
    // Subject
    case Sub = 'sub';
    case TokenEndpointAuthMethod = 'token_endpoint_auth_method';
    // Type
    case Typ = 'typ';
    case TrustChain = 'trust_chain';
}
