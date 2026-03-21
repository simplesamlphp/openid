<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\Factories\SignatureKeyPairBagFactory;
use SimpleSAML\OpenID\ValueAbstracts\Factories\SignatureKeyPairFactory;
use SimpleSAML\OpenID\ValueAbstracts\KeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairBag;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfigBag;

#[CoversClass(SignatureKeyPairBagFactory::class)]
#[UsesClass(SignatureKeyPairBag::class)]
final class SignatureKeyPairBagFactoryTest extends TestCase
{
    public function testFromConfig(): void
    {
        $signatureKeyPairFactoryMock = $this->createMock(SignatureKeyPairFactory::class);
        $signatureKeyPairConfigBagMock = $this->createMock(SignatureKeyPairConfigBag::class);

        $signatureKeyPairConfig1 = $this->createStub(SignatureKeyPairConfig::class);
        $signatureKeyPairConfig2 = $this->createStub(SignatureKeyPairConfig::class);

        $signatureKeyPairConfigBagMock->expects($this->once())
            ->method('getAll')
            ->willReturn([$signatureKeyPairConfig1, $signatureKeyPairConfig2]);

        $signatureKeyPair1 = $this->createMock(SignatureKeyPair::class);
        $signatureKeyPair2 = $this->createMock(SignatureKeyPair::class);

        $keyPair1 = $this->createMock(KeyPair::class);
        $keyPair1->method('getKeyId')->willReturn('key1');
        $signatureKeyPair1->method('getKeyPair')->willReturn($keyPair1);

        $keyPair2 = $this->createMock(KeyPair::class);
        $keyPair2->method('getKeyId')->willReturn('key2');
        $signatureKeyPair2->method('getKeyPair')->willReturn($keyPair2);

        $signatureKeyPairFactoryMock->method('fromConfig')
            ->willReturnCallback(function (
                $config,
            ) use (
                $signatureKeyPairConfig1,
                $signatureKeyPairConfig2,
                $signatureKeyPair1,
                $signatureKeyPair2,
            ): ?\PHPUnit\Framework\MockObject\MockObject {
                if ($config === $signatureKeyPairConfig1) {
                    return $signatureKeyPair1;
                }

                if ($config === $signatureKeyPairConfig2) {
                    return $signatureKeyPair2;
                }

                return null;
            });

        $factory = new SignatureKeyPairBagFactory($signatureKeyPairFactoryMock);
        $result = $factory->fromConfig($signatureKeyPairConfigBagMock);

        $this->assertInstanceOf(SignatureKeyPairBag::class, $result);
        $this->assertCount(2, $result->getAll());
        $this->assertSame($signatureKeyPair1, $result->getByKeyId('key1'));
        $this->assertSame($signatureKeyPair2, $result->getByKeyId('key2'));
    }
}
