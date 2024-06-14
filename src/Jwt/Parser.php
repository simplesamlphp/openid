<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwt;

use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;

class Parser
{
    private JWSSerializerManager $serializerManager;

    public function __construct(
        JWSSerializerManager $serializerManager = null,
    ) {
        $this->serializerManager = $serializerManager ?? new JWSSerializerManager([
            new CompactSerializer(),
        ]);
    }

    public function jwsPayload(string $token): array
    {
        $jws = $this->serializerManager->unserialize($token);
        $payload = $jws->getPayload();
        return $payload ? json_decode($payload, true) : [];
    }
}
