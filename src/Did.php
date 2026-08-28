<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidKeyJwkResolver;
use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Did\PublicJwkValidator;

/**
 * @see \SimpleSAML\Test\OpenID\DidTest
 */
class Did
{
    protected ?DidKeyJwkResolver $didKeyResolver = null;

    protected ?DidJwkResolver $didJwkResolver = null;

    protected ?MultibaseKeyDecoder $multibaseKeyDecoder = null;

    protected ?PublicJwkValidator $publicJwkValidator = null;

    protected ?DidDocumentFactory $didDocumentFactory = null;

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


    public function publicJwkValidator(): PublicJwkValidator
    {
        return $this->publicJwkValidator ??= new PublicJwkValidator();
    }


    public function didDocumentFactory(): DidDocumentFactory
    {
        return $this->didDocumentFactory ??= new DidDocumentFactory(
            $this->multibaseKeyDecoder(),
            $this->didJwkResolver(),
            $this->publicJwkValidator(),
        );
    }


    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }
}
