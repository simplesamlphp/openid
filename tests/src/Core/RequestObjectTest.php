<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Core;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\RequestObject;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\RequestObjectException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

#[CoversClass(RequestObject::class)]
#[UsesClass(ParsedJws::class)]
class RequestObjectTest extends TestCase
{
    protected MockObject $signatureMock;
    protected MockObject $jwsMock;
    protected MockObject $jwsDecoratorMock;
    protected MockObject $jwsVerifierMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $jwsSerializerManagerMock;
    protected MockObject $dateIntervalDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $jsonHelperMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];

    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $this->jwsMock = $this->createMock(JWS::class);
        $this->jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($this->jwsMock);

        $this->jwsVerifierMock = $this->createMock(JwsVerifier::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManager::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
    }

    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifier $jwsVerifier = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManager $jwsSerializerManager = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
    ): RequestObject {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifier ??= $this->jwsVerifierMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManager ??= $this->jwsSerializerManagerMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;

        return new RequestObject(
            $jwsDecorator,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $dateIntervalDecorator,
            $helpers,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(RequestObject::class, $this->sut());
    }

    public function testCanCheckIsProtected(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertTrue($this->sut()->isProtected());
    }

    public function testCanCheckIsNotProtected(): void
    {
        $header = $this->sampleHeader;
        $header['alg'] = 'none';

        $this->signatureMock->method('getProtectedHeader')->willReturn($header);

        $this->assertFalse($this->sut()->isProtected());
    }

    public function testThrowsForNonExistingAlgHeader(): void
    {

        $header = $this->sampleHeader;
        unset($header['alg']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($header);

        $this->expectException(RequestObjectException::class);
        $this->expectExceptionMessage('Alg');

        $this->sut()->isProtected();
    }
}
