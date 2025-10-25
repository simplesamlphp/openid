<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jwk\Factories;

use InvalidArgumentException;
use Jose\Component\Core\JWK;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleSAML\OpenID\Jwk\Factories\JwkDecoratorFactory;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use Throwable;

#[CoversClass(JwkDecoratorFactory::class)]
#[UsesClass(JwkDecorator::class)]
final class JwkDecoratorFactoryTest extends TestCase
{
    protected function sut(): JwkDecoratorFactory
    {
        return new JwkDecoratorFactory();
    }


    public function testFromDataReturnsJwkDecorator(): void
    {
        $decorator = $this->sut()->fromData(['kty' => 'oct', 'k' => 'abc']);

        $this->assertInstanceOf(JwkDecorator::class, $decorator);
        $this->assertInstanceOf(JWK::class, $decorator->jwk());
    }


    public function testFromDataPassesValuesToUnderlyingJwk(): void
    {
        $data = ['kty' => 'oct', 'k' => 'xyz', 'alg' => 'HS256'];
        $decorator = $this->sut()->fromData($data);

        $this->assertSame($data, $decorator->jwk()->all());
    }


    public function testFromDataThrowsWhenKtyIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->sut()->fromData(['alg' => 'HS256']);
    }


    public function testFromPkcs1Or8KeyFileThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Non-existing file path should cause KeyConverter to fail reading the file
        @$this->sut()->fromPkcs1Or8KeyFile(__DIR__ . DIRECTORY_SEPARATOR . 'no_such_key.pem');
    }


    public function testFromPkcs12CertificateFileThrowsOnMissingFile(): void
    {
        // The vendor code wraps any error into a RuntimeException with a generic message
        $this->expectException(RuntimeException::class);
        @$this->sut()->fromPkcs12CertificateFile(__DIR__ . DIRECTORY_SEPARATOR . 'no_such_cert.p12', 'secret');
    }


    public function testFromX509CertificateFileThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        @$this->sut()->fromX509CertificateFile(__DIR__ . DIRECTORY_SEPARATOR . 'no_such_cert.crt');
    }


    public function testFromPkcs1Or8KeyThrowsOnInvalidKeyString(): void
    {
        // Depending on the environment (OpenSSL availability), the vendor may throw RuntimeException or
        // InvalidArgumentException
        $this->expectException(Throwable::class);
        $this->sut()->fromPkcs1Or8Key('not a valid key');
    }


    public function testFromX509CertificateThrowsOnInvalidCertificateString(): void
    {
        // Depending on the environment (OpenSSL availability), the vendor may throw RuntimeException or
        // InvalidArgumentException
        $this->expectException(Throwable::class);
        $this->sut()->fromX509Certificate('not a valid certificate');
    }
}
