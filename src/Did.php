<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidKeyJwkResolver;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;

/**
 * @see \SimpleSAML\Test\OpenID\DidTest
 */
class Did
{
    protected ?DidKeyJwkResolver $didKeyResolver = null;

    protected ?DidJwkResolver $didJwkResolver = null;

    protected ?MultibaseKeyDecoder $multibaseKeyDecoder = null;

    protected ?Helpers $helpers = null;


    public function didKeyResolver(): DidKeyJwkResolver
    {
        return $this->didKeyResolver ??= new DidKeyJwkResolver(
            $this->helpers(),
            $this->multibaseKeyDecoder(),
        );
    }


    public function didJwkResolver(): DidJwkResolver
    {
        return $this->didJwkResolver ??= new DidJwkResolver(
            $this->helpers(),
        );
    }


    public function multibaseKeyDecoder(): MultibaseKeyDecoder
    {
        return $this->multibaseKeyDecoder ??= new MultibaseKeyDecoder(
            $this->helpers(),
        );
    }


    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }
}
