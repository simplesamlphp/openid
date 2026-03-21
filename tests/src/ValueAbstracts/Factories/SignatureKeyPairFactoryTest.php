<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts\Factories;

use Jose\Component\Core\JWK as StandardJwk;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Jwk;
use SimpleSAML\OpenID\Jwk\Factories\JwkDecoratorFactory;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\ValueAbstracts\Factories\SignatureKeyPairFactory;
use SimpleSAML\OpenID\ValueAbstracts\KeyPair;
use SimpleSAML\OpenID\ValueAbstracts\KeyPairConfigInterface;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig;

#[CoversClass(SignatureKeyPairFactory::class)]
#[UsesClass(SignatureKeyPairConfig::class)]
#[UsesClass(SignatureKeyPair::class)]
#[UsesClass(KeyPair::class)]
#[UsesClass(HashAlgorithmsEnum::class)]
final class SignatureKeyPairFactoryTest extends TestCase
{
    /** @var \SimpleSAML\OpenID\Jwk&\PHPUnit\Framework\MockObject\MockObject */
    protected \PHPUnit\Framework\MockObject\MockObject $jwkMock;

    /** @var \SimpleSAML\OpenID\Jwk\Factories\JwkDecoratorFactory&\PHPUnit\Framework\MockObject\MockObject */
    protected \PHPUnit\Framework\MockObject\MockObject $jwkDecoratorFactoryMock;

    /** @var \SimpleSAML\OpenID\Jwk\JwkDecorator&\PHPUnit\Framework\MockObject\MockObject */
    protected \PHPUnit\Framework\MockObject\MockObject $publicKeyJwkDecoratorMock;

    /** @var \SimpleSAML\OpenID\Jwk\JwkDecorator&\PHPUnit\Framework\MockObject\Stub */
    protected \PHPUnit\Framework\MockObject\Stub $privateKeyJwkDecoratorMock;


    protected function setUp(): void
    {
        $this->jwkMock = $this->createMock(Jwk::class);
        $this->jwkDecoratorFactoryMock = $this->createMock(JwkDecoratorFactory::class);
        $this->publicKeyJwkDecoratorMock = $this->createMock(JwkDecorator::class);
        $this->privateKeyJwkDecoratorMock = $this->createStub(JwkDecorator::class);

        $this->jwkMock->method('jwkDecoratorFactory')->willReturn($this->jwkDecoratorFactoryMock);
    }


    protected function sut(): SignatureKeyPairFactory
    {
        return new SignatureKeyPairFactory($this->jwkMock);
    }


    public function testFromConfigWithProvidedKeyId(): void
    {
        $keyPairConfigMock = $this->createMock(KeyPairConfigInterface::class);
        $keyPairConfigMock->method('getPublicKeyString')->willReturn('pub_key_string');
        $keyPairConfigMock->method('getPrivateKeyString')->willReturn('priv_key_string');
        $keyPairConfigMock->method('getKeyId')->willReturn('provided_key_id');
        $keyPairConfigMock->method('getPrivateKeyPassword')->willReturn('priv_password');

        $signatureKeyPairConfig = new SignatureKeyPairConfig(
            SignatureAlgorithmEnum::RS256,
            $keyPairConfigMock,
        );

        $this->jwkDecoratorFactoryMock->expects($this->exactly(2))
            ->method('fromPkcs1Or8Key')
            ->willReturnCallback(function (
                string $key,
                ?string $password,
                array $additionalData,
            ): \PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\Stub|null {
                if ($key === 'pub_key_string') {
                    return $this->publicKeyJwkDecoratorMock;
                }

                if ($key === 'priv_key_string') {
                    return $this->privateKeyJwkDecoratorMock;
                }

                return null;
            });

        $this->publicKeyJwkDecoratorMock->expects($this->once())
            ->method('addAdditionalData')
            ->with(ClaimsEnum::Kid->value, 'provided_key_id');

        $factory = $this->sut();
        $result = $factory->fromConfig($signatureKeyPairConfig);

        $this->assertInstanceOf(SignatureKeyPair::class, $result);
        $this->assertSame(SignatureAlgorithmEnum::RS256, $result->getSignatureAlgorithm());
        $this->assertSame('provided_key_id', $result->getKeyPair()->getKeyId());
        $this->assertSame('priv_password', $result->getKeyPair()->getPrivateKeyPassword());
        $this->assertSame($this->publicKeyJwkDecoratorMock, $result->getKeyPair()->getPublicKey());
        $this->assertSame($this->privateKeyJwkDecoratorMock, $result->getKeyPair()->getPrivateKey());
    }


    public function testFromConfigWithGeneratedKeyIdThumbprint(): void
    {
        $keyPairConfigMock = $this->createMock(KeyPairConfigInterface::class);
        $keyPairConfigMock->method('getPublicKeyString')->willReturn('pub_key_string');
        $keyPairConfigMock->method('getPrivateKeyString')->willReturn('priv_key_string');
        $keyPairConfigMock->method('getKeyId')->willReturn(null);
        $keyPairConfigMock->method('getPrivateKeyPassword')->willReturn(null);

        $signatureKeyPairConfig = new SignatureKeyPairConfig(
            SignatureAlgorithmEnum::ES256,
            $keyPairConfigMock,
        );

        $this->jwkDecoratorFactoryMock->expects($this->exactly(2))
            ->method('fromPkcs1Or8Key')
            ->willReturnCallback(function (
                string $key,
                ?string $password,
                array $additionalData,
            ): \PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\Stub|null {
                if ($key === 'pub_key_string') {
                    return $this->publicKeyJwkDecoratorMock;
                }

                if ($key === 'priv_key_string') {
                    return $this->privateKeyJwkDecoratorMock;
                }

                return null;
            });

        $standardJwkMock = $this->createMock(StandardJwk::class);
        $standardJwkMock->method('thumbprint')
            ->with(HashAlgorithmsEnum::SHA_256->phpName())
            ->willReturn('generated_thumbprint_key_id');

        $this->publicKeyJwkDecoratorMock->method('jwk')->willReturn($standardJwkMock);

        $this->publicKeyJwkDecoratorMock->expects($this->once())
            ->method('addAdditionalData')
            ->with(ClaimsEnum::Kid->value, 'generated_thumbprint_key_id');

        $factory = $this->sut();
        $result = $factory->fromConfig($signatureKeyPairConfig, HashAlgorithmsEnum::SHA_256);

        $this->assertInstanceOf(SignatureKeyPair::class, $result);
        $this->assertSame(SignatureAlgorithmEnum::ES256, $result->getSignatureAlgorithm());
        $this->assertSame('generated_thumbprint_key_id', $result->getKeyPair()->getKeyId());
        $this->assertNull($result->getKeyPair()->getPrivateKeyPassword());
        $this->assertSame($this->publicKeyJwkDecoratorMock, $result->getKeyPair()->getPublicKey());
        $this->assertSame($this->privateKeyJwkDecoratorMock, $result->getKeyPair()->getPrivateKey());
    }
}
