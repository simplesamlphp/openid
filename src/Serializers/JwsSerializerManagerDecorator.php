<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Serializers;

use Jose\Component\Signature\Serializer\JWSSerializerManager;
use SimpleSAML\OpenID\Jws\JwsDecorator;

class JwsSerializerManagerDecorator
{
    public function __construct(
        protected readonly JWSSerializerManager $jwsSerializerManager,
    ) {
    }

    public function jwsSerializerManager(): JWSSerializerManager
    {
        return $this->jwsSerializerManager;
    }

    public function serialize(string $name, JwsDecorator $jwsDecorator, ?int $signatureIndex = null): string
    {
        return $this->jwsSerializerManager()->serialize($name, $jwsDecorator->jws(), $signatureIndex);
    }

    public function unserialize(string $input, ?string &$name = null): JwsDecorator
    {
        return new JWSDecorator($this->jwsSerializerManager()->unserialize($input, $name));
    }
}
