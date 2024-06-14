<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

class Uri
{
    public function isValid(string $uri): bool
    {
        // For now, do simple validation. Adjust when needed.
        return (bool)filter_var($uri, FILTER_VALIDATE_URL);
    }
}
