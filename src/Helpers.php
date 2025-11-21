<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Helpers\Arr;
use SimpleSAML\OpenID\Helpers\Base64Url;
use SimpleSAML\OpenID\Helpers\DateTime;
use SimpleSAML\OpenID\Helpers\Hash;
use SimpleSAML\OpenID\Helpers\Json;
use SimpleSAML\OpenID\Helpers\Random;
use SimpleSAML\OpenID\Helpers\Type;
use SimpleSAML\OpenID\Helpers\Url;

class Helpers
{
    protected static ?Url $url = null;

    protected static ?Json $json = null;

    protected static ?Arr $arr = null;

    protected static ?Type $type = null;

    protected static ?DateTime $dateTime = null;

    protected static ?Base64Url $base64Url = null;

    protected static ?Hash $hash = null;

    protected static ?Random $random = null;


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


    public function dateTime(): DateTime
    {
        return self::$dateTime ??= new DateTime();
    }


    public function base64Url(): Base64Url
    {
        return self::$base64Url ??= new Base64Url();
    }


    public function hash(): Hash
    {
        return self::$hash ??= new Hash();
    }


    public function random(): Random
    {
        return self::$random ??= new Random();
    }
}
