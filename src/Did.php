<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Did\DidKeyJwkResolver;

class Did
{
    protected ?DidKeyJwkResolver $didKeyResolver = null;

    protected ?Helpers $helpers = null;

    public function didKeyResolver(): DidKeyJwkResolver
    {
        return $this->didKeyResolver ??= new DidKeyJwkResolver(
            $this->helpers(),
        );
    }

    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }
}
