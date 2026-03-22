<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\ValueAbstracts\KeyPairConfigInterface;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig;

#[CoversClass(SignatureKeyPairConfig::class)]
final class SignatureKeyPairConfigTest extends TestCase
{
    private function sut(
        ?SignatureAlgorithmEnum $signatureAlgorithm = null,
        ?KeyPairConfigInterface $keyPairConfig = null,
    ): SignatureKeyPairConfig {
        return new SignatureKeyPairConfig(
            $signatureAlgorithm ?? SignatureAlgorithmEnum::RS256,
            $keyPairConfig ?? $this->createStub(KeyPairConfigInterface::class),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(SignatureKeyPairConfig::class, $this->sut());
    }


    public function testGetSignatureAlgorithmReturnsInjectedValue(): void
    {
        $algorithm = SignatureAlgorithmEnum::ES256;
        $sut = $this->sut(signatureAlgorithm: $algorithm);

        $this->assertSame($algorithm, $sut->getSignatureAlgorithm());
    }


    public function testGetKeyPairConfigReturnsInjectedValue(): void
    {
        $keyPairConfig = $this->createStub(KeyPairConfigInterface::class);
        $sut = $this->sut(keyPairConfig: $keyPairConfig);

        $this->assertSame($keyPairConfig, $sut->getKeyPairConfig());
    }
}
