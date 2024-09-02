<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use SimpleSAML\OpenID\Serializers\JwsSerializerManager;
use SimpleSAML\OpenID\SupportedSerializers;

class JwsSerializerManagerFactory
{
    public function build(SupportedSerializers $supportedSerializers): JwsSerializerManager
    {
        return new JwsSerializerManager($supportedSerializers->getJwsSerializerBag()->getAllInstances());
    }
}
