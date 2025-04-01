<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ParamsEnum: string
{
    case AcrValues = 'acr_values';
    case Assertion = 'assertion';
    case Claims = 'claims';
    case ClientAssertion = 'client_assertion';
    case ClientAssertionType = 'client_assertion_type';
    case ClientId = 'client_id';
    case ClientSecret = 'client_secret';
    case CodeChallenge = 'code_challenge';
    case CodeChallengeMethod = 'code_challenge_method';
    case CodeVerifier = 'code_verifier';
    case Display = 'display';
    case EntityType = 'entity_type';
    case Error = 'error';
    case ErrorDescription = 'error_description';
    case IdTokenHint = 'id_token_hint';
    case Intermediate = 'intermediate';
    case IssuerState = 'issuer_state';
    case LoginHint = 'login_hint';
    case MaxAge = 'max_age';
    case Nonce = 'nonce';
    case PostLogoutRedirectUri = 'post_logout_redirect_uri';
    case Prompt = 'prompt';
    case RedirectUri = 'redirect_uri';
    case Request = 'request';
    case ResponseMode = 'response_mode';
    case ResponseType = 'response_type';
    case Scope = 'scope';
    case State = 'state';
    case TrustMarked = 'trust_marked';
    case TrustMarkId = 'trust_mark_id';
    case UiLocales = 'ui_locales';
}
