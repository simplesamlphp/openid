<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class Http
{
    public function followRedirects(
        ClientInterface $httpClient,
        RequestInterface $request,
        int $maxRedirects = 5,
    )
    {

    }
}
