<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManager;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\JwsVerifier;

#[CoversClass(JwsVerifierFactory::class)]
#[UsesClass(JwsVerifier::class)]
class JwsVerifierFactoryTest extends TestCase
{
    protected MockObject $algorithmManagerMock;

    protected function setUp(): void
    {
        $this->algorithmManagerMock = $this->createMock(AlgorithmManager::class);
    }

    protected function sut(): JwsVerifierFactory
    {
        return new JwsVerifierFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            JwsVerifierFactory::class,
            $this->sut(),
        );
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            JwsVerifier::class,
            $this->sut()->build($this->algorithmManagerMock),
        );
    }
}
