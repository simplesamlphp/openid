<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use Jose\Component\Signature\Algorithm\SignatureAlgorithm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManager;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\SupportedAlgorithms;

#[CoversClass(AlgorithmManagerFactory::class)]
class AlgorithmManagerFactoryTest extends TestCase
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

    protected function sut(): AlgorithmManagerFactory
    {
        return new AlgorithmManagerFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            AlgorithmManagerFactory::class,
            $this->sut(),
        );
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            AlgorithmManager::class,
            $this->sut()->build($this->supportedAlgorithmsMock),
        );
    }
}
