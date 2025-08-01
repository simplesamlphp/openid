<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ClaimsEnum: string
{
    case AcrValuesSupported = 'acr_values_supported';
    // Algorithm
    case Alg = 'alg';
    // AlgorithmValuesSupported
    case AlgValuesSupported = 'alg_values_supported';
    // AlternativeText
    case AltText = 'alt_text';
    case ApplicationType = 'application_type';
    // Audience
    case Aud = 'aud';
    case AuthorityHints = 'authority_hints';
    case AuthorizationEndpoint = 'authorization_endpoint';
    case AuthorizationServers = 'authorization_servers';
    case BackChannelLogoutSessionSupported = 'backchannel_logout_session_supported';
    case BackChannelLogoutSupported = 'backchannel_logout_supported';
    case BackChannelLogoutUri = 'backchannel_logout_uri';
    case BatchCredentialIssuance = 'batch_credential_issuance';
    case BackgroundColor = 'background_color';
    case BackgroundImage = 'background_image';
    case BatchSize = 'batch_size';
    case Claims = 'claims';
    case ClaimsSupported = 'claims_supported';
    case ClaimsParameterSupported = 'claims_parameter_supported';
    case ClientId = 'client_id';
    case ClientName = 'client_name';
    case ClientRegistrationTypes = 'client_registration_types';
    case ClientRegistrationTypesSupported = 'client_registration_types_supported';
    case CodeChallengeMethodsSupported = 'code_challenge_methods_supported';
    case Contacts = 'contacts';
    case CredentialConfigurationsSupported = 'credential_configurations_supported';
    case CredentialDefinition = 'credential_definition';
    case CredentialEndpoint = 'credential_endpoint';
    case CredentialIssuer = 'credential_issuer';
    case CredentialResponseEncryption = 'credential_response_encryption';
    // CredentialSigningAlgorithmValuesSupported
    case CredentialSigningAlgValuesSupported = 'credential_signing_alg_values_supported';
    case CryptographicBindingMethodsSupported = 'cryptographic_binding_methods_supported';
    case DeferredCredentialEndpoint = 'deferred_credential_endpoint';
    case Delegation = 'delegation';
    case Description = 'description';
    case Display = 'display';
    case DisplayName = 'display_name';
    case EndSessionEndpoint = 'end_session_endpoint';
    // ExpirationTime
    case Exp = 'exp';
    case EncryptionRequired = 'encryption_required';
    // EncryptionValuesSupported
    case EncValuesSupported = 'enc_values_supported';
    case FederationFetchEndpoint = 'federation_fetch_endpoint';
    case FederationListEndpoint = 'federation_list_endpoint';
    case FederationTrustMarkEndpoint = 'federation_trust_mark_endpoint';
    case Format = 'format';
    case GrantTypes = 'grant_types';
    case GrantTypesSupported = 'grant_types_supported';
    case HomepageUri = 'homepage_uri';
    // IssuedAt
    case Iat = 'iat';
    // Identifier
    case Id = 'id';
    case IdTokenSigningAlgValuesSupported = 'id_token_signing_alg_values_supported';
    case InformationUri = 'information_uri';
    case IntrospectionEndpoint = 'introspection_endpoint';
    case IntrospectionEndpointAuthMethodsSupported = 'introspection_endpoint_auth_methods_supported';
    case IntrospectionEndpointAuthSigningAlgValuesSupported =
    'introspection_endpoint_auth_signing_alg_values_supported';
    // Issuer
    case Iss = 'iss';
    case Issuer = 'issuer';
    // JWT ID
    case Jti = 'jti';
    // JsonWebKeySet
    case Jwks = 'jwks';
    case JwksUri = 'jwks_uri';
    case Keywords = 'keywords';
    // KeyId
    case Kid = 'kid';
    case KeyAttestationsRequired = 'key_attestations_required';
    case KeyStorage = 'key_storage';
    case Keys = 'keys';
    case Locale = 'locale';
    case Logo = 'logo';
    case LogoUri = 'logo_uri';
    case Mandatory = 'mandatory';
    case Metadata = 'metadata';
    case MetadataPolicy = 'metadata_policy';
    // MetadataPolicyCritical
    case MetadataPolicyCrit = 'metadata_policy_crit';
    case Name = 'name';
    case NonceEndpoint = 'nonce_endpoint';
    case NotificationEndpoint = 'notification_endpoint';
    // OpenIDProviderPolicyUri
    case OpPolicyUri = 'op_policy_uri';
    // OpenIDProviderTermsOfServiceUri
    case OpTosUri = 'op_tos_uri';
    case OrganizationName = 'organization_name';
    case OrganizationUri = 'organization_uri';
    case Path = 'path';
    case PolicyUri = 'policy_uri';
    case PostLogoutRedirectUris = 'post_logout_redirect_uris';
    case PreAuthorizedGrantAnonymousAccessSupported = 'pre-authorized_grant_anonymous_access_supported';
    // ProofSigningAlgorithmValuesSupported
    case ProofSigningAlgValuesSupported = 'proof_signing_alg_values_supported';
    case ProofTypesSupported = 'proof_types_supported';
    // Reference
    case Ref = 'ref';
    // PublicKeyUse
    case RedirectUris = 'redirect_uris';
    case RegistrationEndpoint = 'registration_endpoint';
    case RequestAuthenticationMethodsSupported = 'request_authentication_methods_supported';
    case RequestAuthenticationSigningAlgValuesSupported = 'request_authentication_signing_alg_values_supported';
    case RequestObjectSigningAlgValuesSupported = 'request_object_signing_alg_values_supported';
    case RequestParameterSupported = 'request_parameter_supported';
    case RequestUriParameterSupported = 'request_uri_parameter_supported';
    case ResponseModesSupported = 'response_modes_supported';
    case ResponseTypes = 'response_types';
    case ResponseTypesSupported = 'response_types_supported';
    case RevocationEndpoint = 'revocation_endpoint';
    case RevocationEndpointAuthMethodsSupported = 'revocation_endpoint_auth_methods_supported';
    case RevocationEndpointAuthSigningAlgValuesSupported = 'revocation_endpoint_auth_signing_alg_values_supported';
    case Scope = 'scope';
    case ScopesSupported = 'scopes_supported';
    case ServiceDocumentation = 'service_documentation';
    case SignedJwksUri = 'signed_jwks_uri';
    case SignedMetadata = 'signed_metadata';
    // Subject
    case Sub = 'sub';
    case SubjectTypesSupported = 'subject_types_supported';
    case TextColor = 'text_color';
    case TokenEndpoint = 'token_endpoint';
    case TokenEndpointAuthMethod = 'token_endpoint_auth_method';
    case TokenEndpointAuthMethodsSupported = 'token_endpoint_auth_methods_supported';
    case TokenEndpointAuthSigningAlgValuesSupported = 'token_endpoint_auth_signing_alg_values_supported';
    // Type
    case Typ = 'typ';
    case Type = 'type';
    case TrustChain = 'trust_chain';
    case TrustMark = 'trust_mark';
    case TrustMarkOwners = 'trust_mark_owners';
    case TrustMarkType = 'trust_mark_type';
    case TrustMarks = 'trust_marks';
    // UserInterfaceLocalesSupported
    case UiLocalesSupported = 'ui_locales_supported';
    case Uri = 'uri';
    case Use = 'use';
    case UserAuthentication = 'user_authentication';
    case UserinfoEndpoint = 'userinfo_endpoint';
}
