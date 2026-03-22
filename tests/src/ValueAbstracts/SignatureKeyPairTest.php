<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\ValueAbstracts\KeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;

#[CoversClass(SignatureKeyPair::class)]
final class SignatureKeyPairTest extends TestCase
{
    private function sut(
        ?SignatureAlgorithmEnum $signatureAlgorithm = null,
        ?KeyPair $keyPair = null,
    ): SignatureKeyPair {
        return new SignatureKeyPair(
            $signatureAlgorithm ?? SignatureAlgorithmEnum::RS256,
            $keyPair ?? $this->createStub(KeyPair::class),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(SignatureKeyPair::class, $this->sut());
    }


    public function testGetSignatureAlgorithmReturnsInjectedValue(): void
    {
        $algorithm = SignatureAlgorithmEnum::ES256;
        $sut = $this->sut(signatureAlgorithm: $algorithm);

        $this->assertSame($algorithm, $sut->getSignatureAlgorithm());
    }


    public function testGetKeyPairReturnsInjectedValue(): void
    {
        $keyPair = $this->createStub(KeyPair::class);
        $sut = $this->sut(keyPair: $keyPair);

        $this->assertSame($keyPair, $sut->getKeyPair());
    }
}
