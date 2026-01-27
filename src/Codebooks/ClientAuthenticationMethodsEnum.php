<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ClientAuthenticationMethodsEnum: string
{
    case ClientSecretBasic = 'client_secret_basic';

    case ClientSecretPost = 'client_secret_post';

    case ClientSecretJwt = 'client_secret_jwt';

    case PrivateKeyJwt = 'private_key_jwt';

    case None = 'none';
}
