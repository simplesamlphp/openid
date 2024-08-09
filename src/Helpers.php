<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Helpers\Url;

class Helpers
{
    protected static ?Url $url = null;

    public function url(): Url
    {
        return self::$url ??= new Url();
    }
}
