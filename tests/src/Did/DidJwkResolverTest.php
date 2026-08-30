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


    /**
     * The value arrives in a proof key identifier chosen by whoever is being authenticated, and both
     * decodes allocate in proportion to its length, so it is refused before either of them runs.
     */
    public function testExtractJwkFromDidJwkThrowsForAnOverLongEncodedValue(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('longer than the 4096 characters');

        $this->resolver->extractJwkFromDidJwk(
            DidJwkResolver::PREFIX . str_repeat('A', DidJwkResolver::MAX_ENCODED_JWK_LENGTH + 1),
        );
    }


    public function testExtractJwkFromDidJwkAcceptsTheLongestPermittedValue(): void
    {
        // Long enough to be refused by the JSON decode rather than by the length bound.
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Error processing did:jwk:');

        $this->resolver->extractJwkFromDidJwk(
            DidJwkResolver::PREFIX . str_repeat('A', DidJwkResolver::MAX_ENCODED_JWK_LENGTH),
        );
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


    /**
     * The bound holds on both sides, so this class can never emit an identifier it would then refuse to
     * resolve. A JWK is free to carry a certificate chain, and PublicJwkValidator permits one, but a
     * verification method has no use for it and it is what pushes an encoded value past the bound.
     */
    public function testGenerateDidJwkFromJwkThrowsWhenTheResultWouldNotResolve(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('more than the 4096 characters');

        $this->resolver->generateDidJwkFromJwk([
            'kty' => 'RSA',
            'n' => 'AQAB',
            'e' => 'AQAB',
            'x5c' => [str_repeat('A', DidJwkResolver::MAX_ENCODED_JWK_LENGTH)],
        ]);
    }


    public function testGeneratedDidJwkResolvesBack(): void
    {
        $jwk = [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => '11-O_J6_K8_mu2_5_K8_mu2_5_K8_mu2_5',
        ];

        $this->assertSame(
            $jwk,
            $this->resolver->extractJwkFromDidJwk($this->resolver->generateDidJwkFromJwk($jwk)),
        );
    }
}
