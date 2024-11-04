<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use GuzzleHttp\Client;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;

class HttpClientDecoratorFactory
{
    public function build(?Client $client = null): HttpClientDecorator
    {
        return new HttpClientDecorator($client);
    }
}
