<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Algorithms;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;

#[CoversClass(SignatureAlgorithmBag::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
class SignatureAlgorithmBagTest extends TestCase
{
    protected SignatureAlgorithmEnum $signatureAlgorithmEnumRs256;

    protected function setUp(): void
    {
        $this->signatureAlgorithmEnumRs256 = SignatureAlgorithmEnum::RS256;
    }

    protected function mock(SignatureAlgorithmEnum ...$signatureAlgorithmEnums): SignatureAlgorithmBag
    {
        $signatureAlgorithmEnums = !empty($signatureAlgorithmEnums) ?
        $signatureAlgorithmEnums :
        [$this->signatureAlgorithmEnumRs256];

        return new SignatureAlgorithmBag(...$signatureAlgorithmEnums);
    }

    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(SignatureAlgorithmBag::class, $this->mock());
    }

    public function testCanAddAndGetAll(): void
    {
        $signatureAlgorithmBag = $this->mock();
        $this->assertCount(1, $signatureAlgorithmBag->getAll());

        $signatureAlgorithmBag->add(SignatureAlgorithmEnum::RS384);
        $this->assertCount(2, $signatureAlgorithmBag->getAll());
    }

    public function testCanGetAllInstances()
    {
        $this->assertNotEmpty($this->mock()->getAllInstances());
    }
}
