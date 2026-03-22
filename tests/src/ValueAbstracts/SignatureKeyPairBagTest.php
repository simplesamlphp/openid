<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\ValueAbstracts\KeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairBag;

#[CoversClass(SignatureKeyPairBag::class)]
final class SignatureKeyPairBagTest extends TestCase
{
    private function mockSignatureKeyPair(string $keyId, SignatureAlgorithmEnum $algorithm): SignatureKeyPair
    {
        $keyPair = $this->createMock(KeyPair::class);
        $keyPair->method('getKeyId')->willReturn($keyId);
        $keyPair->method('getPublicKey')->willReturn($this->createStub(JwkDecorator::class));

        $signatureKeyPair = $this->createMock(SignatureKeyPair::class);
        $signatureKeyPair->method('getKeyPair')->willReturn($keyPair);
        $signatureKeyPair->method('getSignatureAlgorithm')->willReturn($algorithm);

        return $signatureKeyPair;
    }


    public function testCanCreateInstance(): void
    {
        $sut = new SignatureKeyPairBag();
        $this->assertInstanceOf(SignatureKeyPairBag::class, $sut);
        $this->assertEmpty($sut->getAll());
    }


    public function testCanAddKeyPairs(): void
    {
        $kp1 = $this->mockSignatureKeyPair('key1', SignatureAlgorithmEnum::RS256);
        $kp2 = $this->mockSignatureKeyPair('key2', SignatureAlgorithmEnum::ES256);

        $sut = new SignatureKeyPairBag($kp1);
        $sut->add($kp2);

        $this->assertCount(2, $sut->getAll());
        $this->assertSame($kp1, $sut->getByKeyId('key1'));
        $this->assertSame($kp2, $sut->getByKeyId('key2'));
    }


    public function testGetByKeyIdReturnsNullIfNotFound(): void
    {
        $sut = new SignatureKeyPairBag();
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair::class,
            $sut->getByKeyId('non-existent'),
        );
    }


    public function testHasKeyId(): void
    {
        $kp1 = $this->mockSignatureKeyPair('key1', SignatureAlgorithmEnum::RS256);
        $sut = new SignatureKeyPairBag($kp1);

        $this->assertTrue($sut->hasKeyId('key1'));
        $this->assertFalse($sut->hasKeyId('key2'));
    }


    public function testGetFirstReturnsNullIfEmpty(): void
    {
        $sut = new SignatureKeyPairBag();
        $this->assertNotInstanceOf(\SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair::class, $sut->getFirst());
    }


    public function testGetFirstReturnsFirstAdded(): void
    {
        $kp1 = $this->mockSignatureKeyPair('key1', SignatureAlgorithmEnum::RS256);
        $kp2 = $this->mockSignatureKeyPair('key2', SignatureAlgorithmEnum::ES256);

        $sut = new SignatureKeyPairBag($kp1, $kp2);

        $this->assertSame($kp1, $sut->getFirst());
    }


    public function testGetFirstOrFailThrowsExceptionIfEmpty(): void
    {
        $sut = new SignatureKeyPairBag();

        $this->expectException(OpenIdException::class);
        $this->expectExceptionMessage('Signature key pair is not set.');

        $sut->getFirstOrFail();
    }


    public function testGetFirstOrFailReturnsFirstAdded(): void
    {
        $kp1 = $this->mockSignatureKeyPair('key1', SignatureAlgorithmEnum::RS256);
        $sut = new SignatureKeyPairBag($kp1);

        $this->assertSame($kp1, $sut->getFirstOrFail());
    }


    public function testGetFirstByAlgorithmReturnsNullIfNotFound(): void
    {
        $kp1 = $this->mockSignatureKeyPair('key1', SignatureAlgorithmEnum::RS256);
        $sut = new SignatureKeyPairBag($kp1);

        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair::class,
            $sut->getFirstByAlgorithm(SignatureAlgorithmEnum::ES256),
        );
    }


    public function testGetFirstByAlgorithmReturnsCorrectOne(): void
    {
        $kp1 = $this->mockSignatureKeyPair('key1', SignatureAlgorithmEnum::RS256);
        $kp2 = $this->mockSignatureKeyPair('key2', SignatureAlgorithmEnum::ES256);

        $sut = new SignatureKeyPairBag($kp1, $kp2);

        $this->assertSame($kp2, $sut->getFirstByAlgorithm(SignatureAlgorithmEnum::ES256));
    }


    public function testGetFirstByAlgorithmOrFailThrowsExceptionIfNotFound(): void
    {
        $sut = new SignatureKeyPairBag();

        $this->expectException(OpenIdException::class);
        $this->expectExceptionMessage('Signature key pair for algorithm RS256 is not set.');

        $sut->getFirstByAlgorithmOrFail(SignatureAlgorithmEnum::RS256);
    }


    public function testGetAllAlgorithmNamesUnique(): void
    {
        $kp1 = $this->mockSignatureKeyPair('key1', SignatureAlgorithmEnum::RS256);
        $kp2 = $this->mockSignatureKeyPair('key2', SignatureAlgorithmEnum::RS256);
        $kp3 = $this->mockSignatureKeyPair('key3', SignatureAlgorithmEnum::ES256);

        $sut = new SignatureKeyPairBag($kp1, $kp2, $kp3);

        $names = $sut->getAllAlgorithmNamesUnique();
        $this->assertCount(2, $names);
        $this->assertContains('RS256', $names);
        $this->assertContains('ES256', $names);
    }


    public function testGetAllPublicKeys(): void
    {
        $kp1 = $this->mockSignatureKeyPair('key1', SignatureAlgorithmEnum::RS256);
        $kp2 = $this->mockSignatureKeyPair('key2', SignatureAlgorithmEnum::ES256);

        $sut = new SignatureKeyPairBag($kp1, $kp2);

        $publicKeys = $sut->getAllPublicKeys();
        $this->assertCount(2, $publicKeys);
        $this->assertInstanceOf(JwkDecorator::class, $publicKeys[0]);
        $this->assertInstanceOf(JwkDecorator::class, $publicKeys[1]);
        $this->assertSame($kp1->getKeyPair()->getPublicKey(), $publicKeys[0]);
        $this->assertSame($kp2->getKeyPair()->getPublicKey(), $publicKeys[1]);
    }
}
