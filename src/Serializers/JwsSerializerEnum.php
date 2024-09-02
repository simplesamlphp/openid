<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Serializers;

use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JSONFlattenedSerializer;
use Jose\Component\Signature\Serializer\JSONGeneralSerializer;
use Jose\Component\Signature\Serializer\JWSSerializer;

enum JwsSerializerEnum: string
{
    case Compact = 'jws_compact';
    case JsonGeneral = 'jws_json_general';
    case JsonFlattened = 'jws_json_flattened';

    public function instance(): JWSSerializer
    {
        return match ($this) {
            self::Compact => new CompactSerializer(),
            self::JsonGeneral => new JSONGeneralSerializer(),
            self::JsonFlattened => new JSONFlattenedSerializer(),
        };
    }
}
