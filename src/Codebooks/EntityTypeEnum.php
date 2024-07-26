<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum EntityTypeEnum: string
{
    case FederationEntity = 'federation_entity';
    case OpenIdProvider = 'openid_provider';
    case OpenIdRelyingParty = 'openid_relying_party';
}
