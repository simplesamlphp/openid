<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use Jose\Component\Signature\Serializer\JWSSerializerManager;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedSerializers;

class JwsSerializerManagerDecoratorFactory
{
    public function build(SupportedSerializers $supportedSerializers): JwsSerializerManagerDecorator
    {
        return new JwsSerializerManagerDecorator(
            new JWSSerializerManager(
                $supportedSerializers->getJwsSerializerBag()->getAllInstances(),
            ),
        );
    }
}
