<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\RequestObject;

use SimpleSAML\OpenID\Core\RequestObject as ConnectRequestObject;
use SimpleSAML\OpenID\Exceptions\RequestObjectException;
use SimpleSAML\OpenID\Federation\RequestObject as FederationRequestObject;
use SimpleSAML\OpenID\Jar\RequestObject as JarRequestObject;

class RequestObjectBag
{
    /**
     * @var array<class-string, \SimpleSAML\OpenID\Core\RequestObject|\SimpleSAML\OpenID\Jar\RequestObject|\SimpleSAML\OpenID\Federation\RequestObject>
     */
    protected array $requestObjects = [];


    public function __construct(
        ConnectRequestObject|JarRequestObject|FederationRequestObject ...$requestObject,
    ) {
        foreach ($requestObject as $request) {
            $this->add($request);
        }
    }


    public function add(ConnectRequestObject|JarRequestObject|FederationRequestObject $requestObject): void
    {
        $this->requestObjects[$requestObject::class] = $requestObject;
    }


    public function get(string $class): ConnectRequestObject|JarRequestObject|FederationRequestObject|null
    {
        return $this->requestObjects[$class] ?? null;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    public function getOrFail(string $class): ConnectRequestObject|JarRequestObject|FederationRequestObject
    {
        $requestObject = $this->get($class);
        if ($requestObject === null) {
            throw new RequestObjectException(sprintf('Request object of type %s not found', $class));
        }

        return $requestObject;
    }


    /**
     * @return array<class-string, \SimpleSAML\OpenID\Core\RequestObject|\SimpleSAML\OpenID\Jar\RequestObject|\SimpleSAML\OpenID\Federation\RequestObject>
     */
    public function all(): array
    {
        return $this->requestObjects;
    }
}
