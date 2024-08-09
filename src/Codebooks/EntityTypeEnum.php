<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum EntityTypeEnum: string
{
    case FederationEntity = 'federation_entity';
    case OpenIdProvider = 'openid_provider';
    case OpenIdRelyingParty = 'openid_relying_party';
    case OAuthAuthorizationServer = 'oauth_authorization_server';
    case OAuthClient = 'oauth_client';
    case OAuthProtectedResource = 'oauth_resource';
}
