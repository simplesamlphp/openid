<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

class Hash
{
    /**
     * @param mixed[] $options
     */
    public function for(
        string $algorithm,
        string $data,
        bool $binary = false,
        array $options = [],
    ): string {
        return hash($algorithm, $data, $binary, $options);
    }
}
