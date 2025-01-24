<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws\Factories;

use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class JwsParserFactory
{
    public function build(JwsSerializerManagerDecorator $jwsSerializerManagerDecorator): JwsParser
    {
        return new JwsParser($jwsSerializerManagerDecorator);
    }
}
