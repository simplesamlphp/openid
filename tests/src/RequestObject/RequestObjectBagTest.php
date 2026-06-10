<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\RequestObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\RequestObject as ConnectRequestObject;
use SimpleSAML\OpenID\Exceptions\RequestObjectException;
use SimpleSAML\OpenID\Jar\RequestObject as JarRequestObject;
use SimpleSAML\OpenID\RequestObject\RequestObjectBag;

#[CoversClass(RequestObjectBag::class)]
final class RequestObjectBagTest extends TestCase
{
    public function testCanInstantiateEmpty(): void
    {
        $bag = new RequestObjectBag();
        $this->assertEmpty($bag->all());
    }


    public function testCanInstantiateWithItems(): void
    {
        $connectMock = $this->createStub(ConnectRequestObject::class);
        $jarMock = $this->createStub(JarRequestObject::class);

        $bag = new RequestObjectBag($connectMock, $jarMock);

        $this->assertCount(2, $bag->all());
        $this->assertSame($connectMock, $bag->get($connectMock::class));
        $this->assertSame($jarMock, $bag->get($jarMock::class));
    }


    public function testGetReturnsNullIfNotFound(): void
    {
        $bag = new RequestObjectBag();
        $this->assertNull($bag->get(ConnectRequestObject::class));
    }


    public function testGetOrFailThrowsIfNotFound(): void
    {
        $bag = new RequestObjectBag();
        $this->expectException(RequestObjectException::class);
        $this->expectExceptionMessage('Request object of type SimpleSAML\OpenID\Core\RequestObject not found');
        $bag->getOrFail(ConnectRequestObject::class);
    }


    public function testGetOrFailReturnsObjectIfFound(): void
    {
        $connectMock = $this->createStub(ConnectRequestObject::class);
        $bag = new RequestObjectBag($connectMock);
        $this->assertSame($connectMock, $bag->getOrFail($connectMock::class));
    }
}
