<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

class Json
{
    /**
     * @throws \JsonException
     */
    public function decode(string $json): mixed
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }


    /**
     * @throws \JsonException
     */
    public function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
