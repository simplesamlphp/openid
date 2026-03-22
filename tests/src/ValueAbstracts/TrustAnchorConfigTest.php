<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\JwksException;
use SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig;

#[CoversClass(TrustAnchorConfig::class)]
final class TrustAnchorConfigTest extends TestCase
{
    public function testGetEntityId(): void
    {
        $entityId = 'https://trust-anchor.example.com';
        $config = new TrustAnchorConfig($entityId);

        $this->assertSame($entityId, $config->getEntityId());
    }


    public function testGetJwksReturnsNullWhenNotProvided(): void
    {
        $config = new TrustAnchorConfig('https://trust-anchor.example.com');

        $this->assertNull($config->getJwks());
    }


    public function testGetJwksReturnsProvidedString(): void
    {
        $jwks = '{"keys": [{"kty": "RSA", "kid": "1"}]}';
        $config = new TrustAnchorConfig('https://trust-anchor.example.com', $jwks);

        $this->assertSame($jwks, $config->getJwks());
    }


    public function testGetJwksAsArrayReturnsNullWhenJwksNotProvided(): void
    {
        $config = new TrustAnchorConfig('https://trust-anchor.example.com');

        $this->assertNull($config->getJwksAsArray());
    }


    public function testGetJwksAsArrayReturnsArrayWhenJwksIsValid(): void
    {
        $jwksArray = ['keys' => [['kty' => 'RSA', 'kid' => '1']]];
        $jwksString = (string) json_encode($jwksArray);
        $config = new TrustAnchorConfig('https://trust-anchor.example.com', $jwksString);

        $this->assertSame($jwksArray, $config->getJwksAsArray());
    }


    public function testGetJwksAsArrayThrowsExceptionWhenJwksIsInvalidJson(): void
    {
        $config = new TrustAnchorConfig('https://trust-anchor.example.com', 'invalid-json');

        $this->expectException(JwksException::class);
        $this->expectExceptionMessage('Invalid JWKS JSON object.');
        $config->getJwksAsArray();
    }


    public function testGetJwksAsArrayThrowsExceptionWhenJwksIsMissingKeys(): void
    {
        $config = new TrustAnchorConfig('https://trust-anchor.example.com', '{"not_keys": []}');

        $this->expectException(JwksException::class);
        $this->expectExceptionMessage('Invalid JWKS JSON object.');
        $config->getJwksAsArray();
    }


    public function testGetJwksAsArrayThrowsExceptionWhenKeysIsEmpty(): void
    {
        $config = new TrustAnchorConfig('https://trust-anchor.example.com', '{"keys": []}');

        $this->expectException(JwksException::class);
        $this->expectExceptionMessage('Invalid JWKS JSON object.');
        $config->getJwksAsArray();
    }


    public function testGetJwksAsArrayThrowsExceptionWhenKeysIsNotArray(): void
    {
        $config = new TrustAnchorConfig('https://trust-anchor.example.com', '{"keys": "not-an-array"}');

        $this->expectException(JwksException::class);
        $this->expectExceptionMessage('Invalid JWKS JSON object.');
        $config->getJwksAsArray();
    }
}
