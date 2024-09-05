<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;

class SupportedSerializers
{
    public function __construct(
        protected readonly JwsSerializerBag $jwsSerializerBag = new JwsSerializerBag(
            JwsSerializerEnum::Compact,
        ),
    ) {
    }

    /**
     * @return \SimpleSAML\OpenID\Serializers\JwsSerializerBag
     */
    public function getJwsSerializerBag(): JwsSerializerBag
    {
        return $this->jwsSerializerBag;
    }
}
