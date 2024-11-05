<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\SupportedAlgorithms;

#[CoversClass(SupportedAlgorithms::class)]
class SupportedAlgorithmsTest extends TestCase
{
    protected MockObject $signatureAlgorithmBagMock;

    protected function setUp(): void
    {
        $this->signatureAlgorithmBagMock = $this->createMock(SignatureAlgorithmBag::class);
    }

    protected function sut(
        ?SignatureAlgorithmBag $signatureAlgorithmBag = null,
    ): SupportedAlgorithms {
        $signatureAlgorithmBag ??= $this->signatureAlgorithmBagMock;

        return new SupportedAlgorithms($signatureAlgorithmBag);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(SupportedAlgorithms::class, $this->sut());
    }

    public function testCanGetSignatureAlgorithmBag(): void
    {
        $this->assertInstanceOf(SignatureAlgorithmBag::class, $this->sut()->getSignatureAlgorithmBag());
    }
}
