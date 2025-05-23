<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum WellKnownEnum: string
{
    case Prefix = '.well-known';
    case OpenIdFederation = 'openid-federation';

    public function path(): string
    {
        if ($this === WellKnownEnum::Prefix) {
            return $this->value;
        }

        return self::Prefix->value . '/' . $this->value;
    }

    public function uriFor(string $entityId): string
    {
        return rtrim($entityId, '/') . '/' . $this->path();
    }
}
