<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use SimpleSAML\OpenID\Jws\JwsParser;

class JwsParserFactory
{
    public function build(): JwsParser
    {
        return new JwsParser(new JWSSerializerManager([new CompactSerializer(),]));
    }
}