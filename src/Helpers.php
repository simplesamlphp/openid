<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Helpers\Json;
use SimpleSAML\OpenID\Helpers\Url;

class Helpers
{
    protected static ?Url $url = null;
    protected static ?Json $json = null;

    public function url(): Url
    {
        return self::$url ??= new Url();
    }

    public function json(): Json
    {
        return self::$json ??= new Json();
    }
}
