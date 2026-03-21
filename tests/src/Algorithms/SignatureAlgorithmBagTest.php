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
final class SignatureAlgorithmBagTest extends TestCase
{
    protected SignatureAlgorithmEnum $signatureAlgorithmEnumRs256;


    protected function setUp(): void
    {
        $this->signatureAlgorithmEnumRs256 = SignatureAlgorithmEnum::RS256;
    }


    protected function sut(SignatureAlgorithmEnum ...$signatureAlgorithmEnums): SignatureAlgorithmBag
    {
        $signatureAlgorithmEnums = $signatureAlgorithmEnums === [] ?
        [$this->signatureAlgorithmEnumRs256] :
        $signatureAlgorithmEnums;

        return new SignatureAlgorithmBag(...$signatureAlgorithmEnums);
    }


    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(SignatureAlgorithmBag::class, $this->sut());
    }


    public function testCanAddAndGetAll(): void
    {
        $signatureAlgorithmBag = $this->sut();
        $this->assertCount(1, $signatureAlgorithmBag->getAll());

        $signatureAlgorithmBag->add(SignatureAlgorithmEnum::RS384);
        $this->assertCount(2, $signatureAlgorithmBag->getAll());
    }


    public function testCanGetAllInstances(): void
    {
        $this->assertNotEmpty($this->sut()->getAllInstances());
    }


    public function testCanGetAllNamesUnique(): void
    {
        $signatureAlgorithmBag = $this->sut(
            SignatureAlgorithmEnum::RS256,
            SignatureAlgorithmEnum::RS384,
            SignatureAlgorithmEnum::RS256,
        );
        $signatureAlgorithmBag->add(SignatureAlgorithmEnum::RS512);
        $signatureAlgorithmBag->add(SignatureAlgorithmEnum::RS384);

        $names = $signatureAlgorithmBag->getAllNamesUnique();
        $this->assertCount(3, $names);
        $this->assertSame(['RS256', 'RS384', 'RS512'], $names);
    }
}
