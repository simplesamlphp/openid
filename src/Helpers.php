<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Helpers\Arr;
use SimpleSAML\OpenID\Helpers\Json;
use SimpleSAML\OpenID\Helpers\Type;
use SimpleSAML\OpenID\Helpers\Url;

class Helpers
{
    protected static ?Url $url = null;

    protected static ?Json $json = null;

    protected static ?Arr $arr = null;

    protected static ?Type $type = null;

    public function url(): Url
    {
        return self::$url ??= new Url();
    }

    public function json(): Json
    {
        return self::$json ??= new Json();
    }

    public function arr(): Arr
    {
        return self::$arr ??= new Arr();
    }

    public function type(): Type
    {
        return self::$type ??= new Type();
    }
}
