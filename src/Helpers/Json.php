<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

class Json
{
    /**
     * @throws \JsonException
     */
    public function decode(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
