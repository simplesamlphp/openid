<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws;

use Jose\Component\Signature\JWS;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

#[CoversClass(JwsParser::class)]
#[UsesClass(JwsDecorator::class)]
class JwsParserTest extends TestCase
{
    protected MockObject $jwsSerializerManagerMock;
    protected MockObject $jwsMock;

    protected function setUp(): void
    {
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManager::class);
        $this->jwsMock = $this->createMock(JWS::class);
    }

    protected function sut(
        ?JwsSerializerManager $jwsSerializerManager = null,
    ): JwsParser {
        $jwsSerializerManager ??= $this->jwsSerializerManagerMock;

        return new JwsParser($jwsSerializerManager);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsParser::class, $this->sut());
    }

    public function testCanParseToken(): void
    {
        $this->jwsSerializerManagerMock->expects($this->once())->method('unserialize')
            ->willReturn($this->jwsMock);

        $this->assertInstanceOf(JwsDecorator::class, $this->sut()->parse('token'));
    }

    public function testThrowsOnTokenParseError(): void
    {
        $this->jwsSerializerManagerMock->expects($this->once())->method('unserialize')
            ->willThrowException(new \Exception('Error'));

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('parse');

        $this->sut()->parse('token');
    }
}
