<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws\Factories;

use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

class JwsParserFactory
{
    public function build(JwsSerializerManager $jwsSerializerManager): JwsParser
    {
        return new JwsParser($jwsSerializerManager);
    }
}
