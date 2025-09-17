<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ClaimsEnum: string
{
    // _SelectiveDisclosure
    case _Sd = '_sd';
    // _SelectiveDisclosureAlgorithm
    case _SdAlg = '_sd_alg';
    // @context
    case AtContext = '@context';
    // @type
    case AtType = '@type';
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
    case AuthorizationServer = 'authorization_server';
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
    // Confirmation
    case Cnf = 'cnf';
    case CodeChallengeMethodsSupported = 'code_challenge_methods_supported';
    case Contacts = 'contacts';
    case CredentialConfigurationId = 'credential_configuration_id';
    case CredentialConfigurationIds = 'credential_configuration_ids';
    case CredentialConfigurationsSupported = 'credential_configurations_supported';
    case CredentialDefinition = 'credential_definition';
    case CredentialEndpoint = 'credential_endpoint';
    case CredentialIdentifier = 'credential_identifier';
    case CredentialIdentifiers = 'credential_identifiers';
    case CredentialIssuer = 'credential_issuer';
    case CredentialMetadata = 'credential_metadata';
    case CredentialResponseEncryption = 'credential_response_encryption';
    case Credential_Schema = 'credentialSchema';
    // CredentialSigningAlgorithmValuesSupported
    case CredentialSigningAlgValuesSupported = 'credential_signing_alg_values_supported';
    case Credential_Status = 'credentialStatus';
    case Credential_Subject = 'credentialSubject';
    case CryptographicBindingMethodsSupported = 'cryptographic_binding_methods_supported';
    case CryptographicSuitesSupported = 'cryptographic_suites_supported';
    case DeferredCredentialEndpoint = 'deferred_credential_endpoint';
    case Delegation = 'delegation';
    case Description = 'description';
    case Display = 'display';
    case DisplayName = 'display_name';
    case DotDotDot = '...';
    case EndSessionEndpoint = 'end_session_endpoint';
    case EncryptionRequired = 'encryption_required';
    // EncryptionValuesSupported
    case EncValuesSupported = 'enc_values_supported';
    case Evidence = 'evidence';
    // ExpirationTime
    case Exp = 'exp';
    case Expiration_Date = 'expirationDate';
    case FederationFetchEndpoint = 'federation_fetch_endpoint';
    case FederationListEndpoint = 'federation_list_endpoint';
    case FederationTrustMarkEndpoint = 'federation_trust_mark_endpoint';
    case Format = 'format';
    case Grants = 'grants';
    case GrantTypes = 'grant_types';
    case GrantTypesSupported = 'grant_types_supported';
    case HomepageUri = 'homepage_uri';
    // IssuedAt
    case Iat = 'iat';
    // Identifier
    case Id = 'id';
    case InputMode = 'input_mode';
    case IdTokenSigningAlgValuesSupported = 'id_token_signing_alg_values_supported';
    case InformationUri = 'information_uri';
    case IntrospectionEndpoint = 'introspection_endpoint';
    case IntrospectionEndpointAuthMethodsSupported = 'introspection_endpoint_auth_methods_supported';
    case IntrospectionEndpointAuthSigningAlgValuesSupported =
    'introspection_endpoint_auth_signing_alg_values_supported';
    // Issuer
    case Iss = 'iss';
    case Issuance_Date = 'issuanceDate';
    case Issuer = 'issuer';
    case IssuerState = 'issuer_state';
    // JWT ID
    case Jti = 'jti';
    // JsonWebKey
    case Jwk = 'jwk';
    // JsonWebKeySet
    case Jwks = 'jwks';
    case JwksUri = 'jwks_uri';
    case Keywords = 'keywords';
    // KeyId
    case Kid = 'kid';
    case KeyAttestationsRequired = 'key_attestations_required';
    case KeyStorage = 'key_storage';
    case Keys = 'keys';
    case Length = 'length';
    case Locale = 'locale';
    case Logo = 'logo';
    case LogoUri = 'logo_uri';
    case Mandatory = 'mandatory';
    case Metadata = 'metadata';
    case MetadataPolicy = 'metadata_policy';
    // MetadataPolicyCritical
    case MetadataPolicyCrit = 'metadata_policy_crit';
    case Name = 'name';
    case Nonce = 'nonce';
    case NonceEndpoint = 'nonce_endpoint';
    // NotBefore
    case Nbf = 'nbf';
    case Notification = 'notification';
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
    case PreAuthorizedCode = 'pre-authorized_code';
    case PreAuthorizedGrantAnonymousAccessSupported = 'pre-authorized_grant_anonymous_access_supported';
    case Proof = 'proof';
    case Proofs = 'proofs';
    // ProofSigningAlgorithmValuesSupported
    case ProofSigningAlgValuesSupported = 'proof_signing_alg_values_supported';
    case ProofTypesSupported = 'proof_types_supported';
    // Reference
    case Ref = 'ref';
    case Refresh_Service = 'refreshService';
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
    case Status = 'status';
    // Subject
    case Sub = 'sub';
    case SubjectTypesSupported = 'subject_types_supported';
    case Terms_Of_Use = 'termsOfUse';
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
    // TransactionCode
    case TxCode = 'tx_code';
    // UserInterfaceLocalesSupported
    case UiLocalesSupported = 'ui_locales_supported';
    case Uri = 'uri';
    case Use = 'use';
    case UserAuthentication = 'user_authentication';
    case UserinfoEndpoint = 'userinfo_endpoint';
    // VerifiableCredential
    case Vc = 'vc';
    // VerifiableCredentialType
    case Vct = 'vct';
    // X509certificateChain
    case X5c = 'x5c';
}
