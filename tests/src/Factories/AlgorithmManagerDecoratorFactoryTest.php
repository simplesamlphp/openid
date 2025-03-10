<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use Jose\Component\Signature\Algorithm\SignatureAlgorithm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\SupportedAlgorithms;

#[CoversClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
final class AlgorithmManagerDecoratorFactoryTest extends TestCase
{
    protected MockObject $supportedAlgorithmsMock;



    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $signatureAlgorithmBagMock = $this->createMock(SignatureAlgorithmBag::class);
        $signatureAlgorithmMock = $this->createMock(SignatureAlgorithm::class);

        $this->supportedAlgorithmsMock->method('getSignatureAlgorithmBag')
            ->willReturn($signatureAlgorithmBagMock);
        $signatureAlgorithmBagMock->method('getAllInstances')
            ->willReturn([
                $signatureAlgorithmMock,
            ]);
    }

    protected function sut(): AlgorithmManagerDecoratorFactory
    {
        return new AlgorithmManagerDecoratorFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            AlgorithmManagerDecoratorFactory::class,
            $this->sut(),
        );
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            AlgorithmManagerDecorator::class,
            $this->sut()->build($this->supportedAlgorithmsMock),
        );
    }
}
