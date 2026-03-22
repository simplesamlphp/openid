<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\ValueAbstracts\KeyPairConfigInterface;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfigBag;

#[CoversClass(SignatureKeyPairConfigBag::class)]
final class SignatureKeyPairConfigBagTest extends TestCase
{
    private function mockConfig(
        ?string $keyId = null,
        SignatureAlgorithmEnum $algo = SignatureAlgorithmEnum::RS256,
    ): SignatureKeyPairConfig {
        $keyPairConfig = $this->createMock(KeyPairConfigInterface::class);
        $keyPairConfig->method('getKeyId')->willReturn($keyId);

        $config = $this->createMock(SignatureKeyPairConfig::class);
        $config->method('getKeyPairConfig')->willReturn($keyPairConfig);
        $config->method('getSignatureAlgorithm')->willReturn($algo);

        return $config;
    }


    public function testConstructAndGetAll(): void
    {
        $config1 = $this->mockConfig('key1');
        $config2 = $this->mockConfig('key2');
        $bag = new SignatureKeyPairConfigBag($config1, $config2);

        $this->assertCount(2, $bag->getAll());
        $this->assertSame($config1, $bag->getByKeyId('key1'));
        $this->assertSame($config2, $bag->getByKeyId('key2'));
    }


    public function testAddWithAndWithoutKeyId(): void
    {
        $config1 = $this->mockConfig('key1');
        $config2 = $this->mockConfig();
        $bag = new SignatureKeyPairConfigBag();
        $bag->add($config1, $config2);

        $all = $bag->getAll();
        $this->assertCount(2, $all);
        $this->assertSame($config1, $bag->getByKeyId('key1'));
        $this->assertContains($config2, $all);
    }


    public function testGetAllAlgorithmNamesUnique(): void
    {
        $config1 = $this->mockConfig('key1', SignatureAlgorithmEnum::RS256);
        $config2 = $this->mockConfig('key2', SignatureAlgorithmEnum::RS256);
        $config3 = $this->mockConfig('key3', SignatureAlgorithmEnum::ES256);
        $bag = new SignatureKeyPairConfigBag($config1, $config2, $config3);

        $algos = $bag->getAllAlgorithmNamesUnique();
        $this->assertCount(2, $algos);
        $this->assertContains('RS256', $algos);
        $this->assertContains('ES256', $algos);
    }


    public function testGetFirstReturnsNullWhenEmpty(): void
    {
        $bag = new SignatureKeyPairConfigBag();
        $this->assertNotInstanceOf(\SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig::class, $bag->getFirst());
    }


    public function testGetFirstReturnsFirstElement(): void
    {
        $config1 = $this->mockConfig('key1');
        $config2 = $this->mockConfig('key2');
        $bag = new SignatureKeyPairConfigBag($config1, $config2);

        $this->assertSame($config1, $bag->getFirst());
    }


    public function testGetFirstOrFailThrowsWhenEmpty(): void
    {
        $bag = new SignatureKeyPairConfigBag();
        $this->expectException(OpenIdException::class);
        $this->expectExceptionMessage('Signature key pair config is not set.');
        $bag->getFirstOrFail();
    }


    public function testGetFirstOrFailReturnsFirstElement(): void
    {
        $config1 = $this->mockConfig('key1');
        $bag = new SignatureKeyPairConfigBag($config1);

        $this->assertSame($config1, $bag->getFirstOrFail());
    }
}
