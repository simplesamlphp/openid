<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\ValueAbstracts\KeyPair;

#[CoversClass(KeyPair::class)]
final class KeyPairTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $privateKeyMock;

    protected \PHPUnit\Framework\MockObject\Stub $publicKeyMock;


    protected function setUp(): void
    {
        $this->privateKeyMock = $this->createStub(JwkDecorator::class);
        $this->publicKeyMock = $this->createStub(JwkDecorator::class);
    }


    protected function sut(
        ?JwkDecorator $privateKey = null,
        ?JwkDecorator $publicKey = null,
        ?string $keyId = null,
        ?string $privateKeyPassword = null,
    ): KeyPair {
        return new KeyPair(
            $privateKey ?? $this->privateKeyMock,
            $publicKey ?? $this->publicKeyMock,
            $keyId ?? 'test-key-id',
            $privateKeyPassword,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(KeyPair::class, $this->sut());
    }


    public function testGetPrivateKeyReturnsInjectedJwkDecorator(): void
    {
        $privateKey = $this->createStub(JwkDecorator::class);
        $sut = $this->sut(privateKey: $privateKey);

        $this->assertSame($privateKey, $sut->getPrivateKey());
    }


    public function testGetPublicKeyReturnsInjectedJwkDecorator(): void
    {
        $publicKey = $this->createStub(JwkDecorator::class);
        $sut = $this->sut(publicKey: $publicKey);

        $this->assertSame($publicKey, $sut->getPublicKey());
    }


    public function testGetKeyIdReturnsInjectedString(): void
    {
        $keyId = 'specific-key-id-123';
        $sut = $this->sut(keyId: $keyId);

        $this->assertSame($keyId, $sut->getKeyId());
    }


    public function testGetPrivateKeyPasswordReturnsInjectedString(): void
    {
        $password = 'secret-password';
        $sut = $this->sut(privateKeyPassword: $password);

        $this->assertSame($password, $sut->getPrivateKeyPassword());
    }


    public function testGetPrivateKeyPasswordReturnsNullWhenNullInjected(): void
    {
        $sut = $this->sut(privateKeyPassword: null);

        $this->assertNull($sut->getPrivateKeyPassword());
    }
}
