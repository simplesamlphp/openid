<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws\Factories;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;

#[CoversClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
final class JwsDecoratorBuilderFactoryTest extends TestCase
{
    protected AlgorithmManagerDecorator $algorithmManagerDecorator;


    protected function setUp(): void
    {
        $this->algorithmManagerDecorator = new AlgorithmManagerDecorator(
            new AlgorithmManager( // Final class, can't mock.
                [new RS256()],
            ),
        );
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
                $this->createStub(\SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator::class),
                $this->algorithmManagerDecorator,
                $this->createStub(\SimpleSAML\OpenID\Helpers::class),
            ),
        );
    }
}
