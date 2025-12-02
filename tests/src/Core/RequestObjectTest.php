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
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(RequestObject::class)]
#[UsesClass(ParsedJws::class)]
final class RequestObjectTest extends TestCase
{
    protected MockObject $signatureMock;

    protected MockObject $jwsDecoratorMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksDecoratorFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $claimFactoryMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];


    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createMock(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureString')->willReturnArgument(0);
        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);
    }


    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): RequestObject {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new RequestObject(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
            $claimFactory,
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
