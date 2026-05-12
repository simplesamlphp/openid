<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(DidJwkResolver::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(DidException::class)]
final class DidJwkResolverTest extends TestCase
{
    protected DidJwkResolver $resolver;


    protected function setUp(): void
    {
        $this->resolver = new DidJwkResolver(new Helpers());
    }


    public function testExtractJwkFromDidJwkSucceeds(): void
    {
        // phpcs:ignore
        $didJwk = 'did:jwk:eyJrdHkiOiJPS1AiLCJjcnYiOiJFZDI1NTE5IiwieCI6IjExLU9fSjZfSzhfbXUyXzVfSzhfbXUyXzVfSzhfbXUyXzUifQ';
        $expectedJwk = [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => '11-O_J6_K8_mu2_5_K8_mu2_5_K8_mu2_5',
        ];

        $jwk = $this->resolver->extractJwkFromDidJwk($didJwk);
        $this->assertSame($expectedJwk, $jwk);
    }


    public function testExtractJwkFromDidJwkThrowsExceptionOnInvalidPrefix(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Invalid did:jwk format. Must start with "did:jwk:"');

        $this->resolver->extractJwkFromDidJwk('did:key:abc');
    }


    public function testExtractJwkFromDidJwkThrowsExceptionOnInvalidBase64(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Error processing did:jwk:');

        $this->resolver->extractJwkFromDidJwk('did:jwk:!!!');
    }


    public function testExtractJwkFromDidJwkThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Error processing did:jwk:');

        // 'abc' in base64url is 'YWJj'
        $this->resolver->extractJwkFromDidJwk('did:jwk:YWJj');
    }


    public function testGenerateDidJwkFromJwkSucceeds(): void
    {
        $jwk = [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => '11-O_J6_K8_mu2_5_K8_mu2_5_K8_mu2_5',
        ];
        // phpcs:ignore
        $expectedDidJwk = 'did:jwk:eyJrdHkiOiJPS1AiLCJjcnYiOiJFZDI1NTE5IiwieCI6IjExLU9fSjZfSzhfbXUyXzVfSzhfbXUyXzVfSzhfbXUyXzUifQ';

        $didJwk = $this->resolver->generateDidJwkFromJwk($jwk);
        $this->assertSame($expectedDidJwk, $didJwk);
    }
}
