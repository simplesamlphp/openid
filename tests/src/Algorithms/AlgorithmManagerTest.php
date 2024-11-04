<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Algorithms;

use Jose\Component\Signature\Algorithm\SignatureAlgorithm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManager;

#[CoversClass(AlgorithmManager::class)]
class AlgorithmManagerTest extends TestCase
{
    protected MockObject $signatureAlgorithmMock;
    /** @var \PHPUnit\Framework\MockObject\MockObject[] */
    protected array $signatureAlgorithmMocks;

    protected function setUp(): void
    {
        $this->signatureAlgorithmMock = $this->createMock(SignatureAlgorithm::class);
        $this->signatureAlgorithmMocks[] = $this->signatureAlgorithmMock;
    }

    protected function sut(?array $signatureAlgorithms = null): AlgorithmManager
    {
        $signatureAlgorithms ??= $this->signatureAlgorithmMocks;
        return new AlgorithmManager($signatureAlgorithms);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AlgorithmManager::class, $this->sut());
    }
}
