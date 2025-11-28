<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum TokenEndpointAuthMethodsEnum: string
{
    case ClientSecretPost = 'client_secret_post';

    case ClientSecretBasic = 'client_secret_basic';

    case ClientSecretJwt = 'client_secret_jwt';

    case PrivateKeyJwt = 'private_key_jwt';

    case None = 'none';
}
