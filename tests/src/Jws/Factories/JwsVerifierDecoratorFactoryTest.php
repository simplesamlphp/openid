<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws\Factories;

use Jose\Component\Core\AlgorithmManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;

#[CoversClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
class JwsVerifierDecoratorFactoryTest extends TestCase
{
    protected AlgorithmManagerDecorator $algorithmManagerDecorator;

    protected function setUp(): void
    {
        // Includes final class, so can't mock.
        $this->algorithmManagerDecorator = new AlgorithmManagerDecorator(
            new AlgorithmManager([]),
        );
    }

    protected function sut(): JwsVerifierDecoratorFactory
    {
        return new JwsVerifierDecoratorFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            JwsVerifierDecoratorFactory::class,
            $this->sut(),
        );
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            JwsVerifierDecorator::class,
            $this->sut()->build($this->algorithmManagerDecorator),
        );
    }
}
