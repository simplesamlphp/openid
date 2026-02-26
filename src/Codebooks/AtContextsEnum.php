<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum AtContextsEnum: string
{
    case W3Org2018CredentialsV1 = 'https://www.w3.org/2018/credentials/v1';

    case W3OrgNsCredentialsV2 = 'https://www.w3.org/ns/credentials/v2';
}
