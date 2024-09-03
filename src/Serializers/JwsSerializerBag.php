<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Serializers;

class JwsSerializerBag
{
    /**
     * @var \SimpleSAML\OpenID\Serializers\JwsSerializerEnum[]
     */
    protected array $jwsSerializers;

    public function __construct(JwsSerializerEnum ...$jwsSerializers)
    {
        $this->jwsSerializers = $jwsSerializers;
    }

    public function add(JwsSerializerEnum $jwsSerializer): void
    {
        $this->jwsSerializers[] = $jwsSerializer;
    }

    /**
     * @return \SimpleSAML\OpenID\Serializers\JwsSerializerEnum[]
     */
    public function getAll(): array
    {
        return $this->jwsSerializers;
    }

    /**
     * @return \Jose\Component\Signature\Serializer\JWSSerializer[]
     */
    public function getAllInstances(): array
    {
        return array_map(
            function (JwsSerializerEnum $jwsSerializerEnum) {
                return $jwsSerializerEnum->instance();
            },
            $this->getAll(),
        );
    }
}