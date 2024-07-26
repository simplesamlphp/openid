<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Helpers\Cache;
use SimpleSAML\OpenID\Helpers\Url;

class Helpers
{
    protected static ?Url $url = null;
    protected static ?Cache $cache = null;

    public function url(): Url
    {
        return self::$url ??= new Url();
    }

    public function cache(): Cache
    {
        return self::$cache ??= new Cache();
    }
}
