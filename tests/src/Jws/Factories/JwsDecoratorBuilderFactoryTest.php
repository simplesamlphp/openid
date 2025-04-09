<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws\Factories;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
final class JwsDecoratorBuilderFactoryTest extends TestCase
{
    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected AlgorithmManagerDecorator $algorithmManagerDecorator;

    protected MockObject $helpersMock;

    protected function setUp(): void
    {
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->algorithmManagerDecorator = new AlgorithmManagerDecorator(
            new AlgorithmManager( // Final class, can't mock.
                [new RS256()],
            ),
        );
        $this->helpersMock = $this->createMock(Helpers::class);
    }

    protected function sut(): JwsDecoratorBuilderFactory
    {
        return new JwsDecoratorBuilderFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsDecoratorBuilderFactory::class, $this->sut());
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            JwsDecoratorBuilder::class,
            $this->sut()->build(
                $this->jwsSerializerManagerDecoratorMock,
                $this->algorithmManagerDecorator,
                $this->helpersMock,
            ),
        );
    }
}
