<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ClaimsEnum: string
{
    case AcrValuesSupported = 'acr_values_supported';
    // Algorithm
    case Alg = 'alg';
    case ApplicationType = 'application_type';
    // Audience
    case Aud = 'aud';
    case AuthorityHints = 'authority_hints';
    case AuthorizationEndpoint = 'authorization_endpoint';
    case BackChannelLogoutSessionSupported = 'backchannel_logout_session_supported';
    case BackChannelLogoutSupported = 'backchannel_logout_supported';
    case BackChannelLogoutUri = 'backchannel_logout_uri';
    case ClaimsSupported = 'claims_supported';
    case ClaimsParameterSupported = 'claims_parameter_supported';
    case ClientId = 'client_id';
    case ClientName = 'client_name';
    case ClientRegistrationTypes = 'client_registration_types';
    case ClientRegistrationTypesSupported = 'client_registration_types_supported';
    case CodeChallengeMethodsSupported = 'code_challenge_methods_supported';
    case Contacts = 'contacts';
    case Delegation = 'delegation';
    case EndSessionEndpoint = 'end_session_endpoint';
    // ExpirationTime
    case Exp = 'exp';
    case FederationFetchEndpoint = 'federation_fetch_endpoint';
    case FederationListEndpoint = 'federation_list_endpoint';
    case FederationTrustMarkEndpoint = 'federation_trust_mark_endpoint';
    case GrantTypes = 'grant_types';
    case GrantTypesSupported = 'grant_types_supported';
    case HomepageUri = 'homepage_uri';
    // IssuedAt
    case Iat = 'iat';
    // Identifier
    case Id = 'id';
    case IdTokenSigningAlgValuesSupported = 'id_token_signing_alg_values_supported';
    // Issuer
    case Iss = 'iss';
    case Issuer = 'issuer';
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
    // Reference
    case Ref = 'ref';
    case ScopesSupported = 'scopes_supported';
    case SignedJwksUri = 'signed_jwks_uri';
    // PublicKeyUse
    case Use = 'use';
    case RedirectUris = 'redirect_uris';
    case RequestAuthenticationMethodsSupported = 'request_authentication_methods_supported';
    case RequestAuthenticationSigningAlgValuesSupported = 'request_authentication_signing_alg_values_supported';
    case RequestObjectSigningAlgValuesSupported = 'request_object_signing_alg_values_supported';
    case RequestParameterSupported = 'request_parameter_supported';
    case RequestUriParameterSupported = 'request_uri_parameter_supported';
    case ResponseTypes = 'response_types';
    case ResponseTypesSupported = 'response_types_supported';
    case Scope = 'scope';
    // Subject
    case Sub = 'sub';
    case SubjectTypesSupported = 'subject_types_supported';
    case TokenEndpoint = 'token_endpoint';
    case TokenEndpointAuthMethod = 'token_endpoint_auth_method';
    case TokenEndpointAuthMethodsSupported = 'token_endpoint_auth_methods_supported';
    case TokenEndpointAuthSigningAlgValuesSupported = 'token_endpoint_auth_signing_alg_values_supported';
    // Type
    case Typ = 'typ';
    case TrustChain = 'trust_chain';
    case TrustMark = 'trust_mark';
    case TrustMarkId = 'trust_mark_id';
    case TrustMarkOwners = 'trust_mark_owners';
    case TrustMarks = 'trust_marks';
    case UserinfoEndpoint = 'userinfo_endpoint';
}
