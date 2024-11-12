<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

#[CoversClass(JwsParserFactory::class)]
#[UsesClass(JwsParser::class)]
class JwsParserFactoryTest extends TestCase
{
    protected MockObject $jwsSerializerManagerMock;

    protected function setUp(): void
    {
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManager::class);
    }

    protected function sut(): JwsParserFactory
    {
        return new JwsParserFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsParserFactory::class, $this->sut());
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            JwsParser::class,
            $this->sut()->build($this->jwsSerializerManagerMock),
        );
    }
}
