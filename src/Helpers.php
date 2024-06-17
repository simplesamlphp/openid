<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Helpers\Cache;
use SimpleSAML\OpenID\Helpers\Http;
use SimpleSAML\OpenID\Helpers\Uri;

class Helpers
{
    protected static ?Uri $uri = null;
    protected static ?Cache $cache = null;
    protected static ?Http $http = null;

    public function uri(): Uri
    {
        return self::$uri ??= new Uri();
    }

    public function cache(): Cache
    {
        return self::$cache ??= new Cache();
    }

    public function http(): Http
    {
        return self::$http ??= new Http();
    }
}
