<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(JwsParser::class)]
#[UsesClass(JwsDecorator::class)]
final class JwsParserTest extends TestCase
{
    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $jwsDecoratorMock;

    protected function setUp(): void
    {
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
    }

    protected function sut(
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
    ): JwsParser {
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;

        return new JwsParser($jwsSerializerManagerDecorator);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsParser::class, $this->sut());
    }

    public function testCanParseToken(): void
    {
        $this->jwsSerializerManagerDecoratorMock->expects($this->once())->method('unserialize')
            ->willReturn($this->jwsDecoratorMock);

        $this->assertInstanceOf(JwsDecorator::class, $this->sut()->parse('token'));
    }

    public function testThrowsOnTokenParseError(): void
    {
        $this->jwsSerializerManagerDecoratorMock->expects($this->once())->method('unserialize')
            ->willThrowException(new \Exception('Error'));

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('parse');

        $this->sut()->parse('token');
    }
}
