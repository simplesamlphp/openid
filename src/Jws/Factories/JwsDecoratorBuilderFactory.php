<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws\Factories;

use Jose\Component\Signature\JWSBuilder;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class JwsDecoratorBuilderFactory
{
    public function build(
        JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        AlgorithmManagerDecorator $algorithmManagerDecorator,
        Helpers $helpers,
    ): JwsDecoratorBuilder
    {
        return new JwsDecoratorBuilder(
            $jwsSerializerManagerDecorator,
            new JWSBuilder($algorithmManagerDecorator->algorithmManager()),
            $helpers,
        );
    }
}
