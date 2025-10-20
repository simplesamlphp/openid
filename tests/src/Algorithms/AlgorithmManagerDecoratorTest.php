<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Algorithms;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;

#[CoversClass(AlgorithmManagerDecorator::class)]
final class AlgorithmManagerDecoratorTest extends TestCase
{
    protected AlgorithmManager $algorithmManager;


    protected function setUp(): void
    {
        $signatureAlgorithmMock = $this->createMock(SignatureAlgorithm::class);
        $signatureAlgorithmMocks[] = $signatureAlgorithmMock;

        $this->algorithmManager = new AlgorithmManager($signatureAlgorithmMocks);
    }


    protected function sut(
        ?AlgorithmManager $algorithmManager = null,
    ): AlgorithmManagerDecorator {
        $algorithmManager ??= $this->algorithmManager;

        return new AlgorithmManagerDecorator($algorithmManager);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AlgorithmManagerDecorator::class, $this->sut());
    }


    public function testCanGetAlgorithmManager(): void
    {
        $this->assertInstanceOf(AlgorithmManager::class, $this->sut()->algorithmManager());
    }
}
