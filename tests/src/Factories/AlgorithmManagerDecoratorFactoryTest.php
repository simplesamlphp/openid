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
class AlgorithmManagerDecoratorFactoryTest extends TestCase
{
    protected MockObject $supportedAlgorithmsMock;
    protected MockObject $signatureAlgorithmBagMock;
    protected MockObject $signatureAlgorithmMock;

    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $this->signatureAlgorithmBagMock = $this->createMock(SignatureAlgorithmBag::class);
        $this->signatureAlgorithmMock = $this->createMock(SignatureAlgorithm::class);

        $this->supportedAlgorithmsMock->method('getSignatureAlgorithmBag')
            ->willReturn($this->signatureAlgorithmBagMock);
        $this->signatureAlgorithmBagMock->method('getAllInstances')
            ->willReturn([
                $this->signatureAlgorithmMock,
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
