<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Helpers\Cache;
use SimpleSAML\OpenID\Helpers\Uri;

class Helpers
{
    private static ?Uri $uri = null;
    private static ?Cache $cache = null;

    public function uri(): Uri
    {
        return self::$uri ??= new Uri();
    }

    public function cache(): Cache
    {
        return self::$cache ??= new Cache();
    }
}
