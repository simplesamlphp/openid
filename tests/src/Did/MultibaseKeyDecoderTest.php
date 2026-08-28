<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Helpers\Base64Url;

#[CoversClass(MultibaseKeyDecoder::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Base64Url::class)]
final class MultibaseKeyDecoderTest extends TestCase
{
    private MockObject $helpersMock;


    protected function setUp(): void
    {
        $base64UrlMock = $this->createMock(Base64Url::class);

        $base64UrlMock->method('encode')
            ->willReturnCallback(base64_encode(...));

        $this->helpersMock = $this->createMock(Helpers::class);

        $this->helpersMock->method('base64Url')
            ->willReturn($base64UrlMock);
    }


    protected function sut(
        ?Helpers $helpers = null,
    ): MultibaseKeyDecoder {
        $helpers ??= $this->helpersMock;

        return new MultibaseKeyDecoder($helpers);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(MultibaseKeyDecoder::class, $this->sut());
    }


    #[DataProvider('varintValidDataProvider')]
    public function testVarintDecodeValid(string $bytes, int $expectedValue, int $expectedLength): void
    {
        [$value, $length] = $this->sut()->varintDecode($bytes);

        $this->assertSame($expectedValue, $value, "Decoded value mismatch for input: " . bin2hex($bytes));
        $this->assertSame($expectedLength, $length, "Decoded length mismatch for input: " . bin2hex($bytes));
    }


    public static function varintValidDataProvider(): \Iterator
    {
        // Single byte.
        yield 'zero' => ["\x00", 0, 1];
        yield 'one' => ["\x01", 1, 1];
        yield 'max single byte' => ["\x7F", 127, 1];
        // Multi-byte. 150-128=22 -> 0x96 = 0x80 | 22.
        yield 'min two-byte (128)' => ["\x80\x01", 128, 2];
        yield '150' => ["\x96\x01", 150, 2];
        // 300 = (2 * 128) + 44. 44 = 0x2C. \xAC = 0x80 | 0x2C.
        yield '300' => ["\xAC\x02", 300, 2];
        yield 'multicodec Ed25519 (0xed)' => ["\xED\x01", 0xed, 2];
        yield 'multicodec X25519 (0xec)' => ["\xEC\x01", 0xec, 2];
        yield 'multicodec secp256k1-pub (0xe7)' => ["\xE7\x01", 0xe7, 2];
        // Max 9-byte value (2^63 - 1, which is PHP_INT_MAX on 64-bit systems).
        yield 'max 9-byte value (PHP_INT_MAX)' => [str_repeat("\xFF", 8) . "\x7F", PHP_INT_MAX, 9];
    }


    #[DataProvider('varintInvalidDataProvider')]
    public function testVarintDecodeInvalid(string $bytes, string $expectedExceptionMessage): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->sut()->varintDecode($bytes);
    }


    public static function varintInvalidDataProvider(): \Iterator
    {
        yield 'empty string' => ['', 'Invalid varint: input is empty'];
        yield 'unterminated sequence (single byte)' => [
            "\x80",
            'Invalid varint: incomplete sequence (unterminated).',
        ];
        yield 'unterminated sequence (multi-byte)' => [
            "\xFF\xFF",
            'Invalid varint: incomplete sequence (unterminated).',
        ];
        yield 'overlong encoding of 0' => [
            "\x80\x00",
            'Invalid varint: overlong encoding (minimality constraint violated).',
        ];
        yield 'overlong encoding of 1' => [
            "\x81\x00",
            'Invalid varint: overlong encoding (minimality constraint violated).',
        ];
        yield 'too many bytes (10th byte)' => [
            str_repeat("\x80", 9) . "\x01",
            'Invalid varint: too many bytes (max 9 for this implementation).',
        ];
        yield 'unterminated, 9 bytes with MSB set' => [
            str_repeat("\xFF", 9),
            'Invalid varint: incomplete sequence (unterminated).',
        ];
    }


    #[DataProvider('base58DecodeValidDataProvider')]
    public function testBase58BtcDecodeValidInputs(string $base58encoded, string $expectedDecoded): void
    {
        $this->assertSame(
            $expectedDecoded,
            $this->sut()->base58BtcDecode($base58encoded),
            "Failed for input: " . $base58encoded,
        );
    }


    public static function base58DecodeValidDataProvider(): \Iterator
    {
        yield 'empty string' => ['', ''];
        // 'z' is value 57, chr(57) is '9'.
        yield 'single char "z" (value 57)' => ['z', '9'];
        yield 'single char "6" (value 5)' => ['6', chr(5)];
        yield 'single char "L" (value 25)' => ['L', chr(19)];
        // chr(97) is 'a'.
        yield 'two chars "2g" (value 88)' => ['2g', 'a'];
        yield 'leading zero "1"' => ['1', "\0"];
        yield 'multiple leading zeros "111"' => ['111', "\0\0\0"];
        yield 'leading zero and char "1z"' => ['1z', "\0" . '9'];
        yield 'leading zero and char "16"' => ['16', "\0" . chr(5)];
        yield 'leading zero and char "1L"' => ['1L', "\0" . chr(19)];
        yield 'leading zero and two chars "12g"' => ['12g', "\0a"];
        // Decodes to bytes representing 256.
        yield 'value 256 ("5R")' => ['5R', "\x01\x00"];
        yield 'string "Hello World"' => ['JxF12TrwUP45BMd', "Hello World"];
        yield 'three leading ones then value 1 ("1112")' => ['1112', "\0\0\0\x01"];
        // Test vector from cryptocoinjs/bs58 test suite, hex "000000287fb4cd".
        yield 'bs58 lib vector 1' => ['111233QC4', "\x00\x00\x00\x28\x7f\xb4\xcd"];
    }


    #[DataProvider('base58DecodeInvalidCharDataProvider')]
    public function testBase58BtcDecodeThrowsOnInvalidCharacter(string $base58invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid character in base58 string');

        $this->sut()->base58BtcDecode($base58invalid);
    }


    public static function base58DecodeInvalidCharDataProvider(): \Iterator
    {
        yield 'invalid char "0" (zero)' => ['0'];
        yield 'invalid char "I" (capital i)' => ['I'];
        yield 'invalid char "O" (capital o)' => ['O'];
        yield 'invalid char "l" (lowercase L)' => ['l'];
        yield 'valid prefix, invalid suffix "abc0def"' => ['abc0def'];
        yield 'invalid char with space "ab c"' => ['ab c'];
        yield 'invalid char with newline "ab\nc"' => ["ab\nc"];
        yield 'invalid char with tab "ab\tc"' => ["ab\tc"];
    }


    public function testCreateEd25519Jwk(): void
    {
        $rawKeyBytes = 'test-key-data';

        $this->assertSame(
            [
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => base64_encode($rawKeyBytes),
                'use' => 'sig',
            ],
            $this->sut()->createEd25519Jwk($rawKeyBytes),
        );
    }


    public function testCreateX25519Jwk(): void
    {
        $rawKeyBytes = 'test-key-data';

        $this->assertSame(
            [
                'kty' => 'OKP',
                'crv' => 'X25519',
                'x' => base64_encode($rawKeyBytes),
                'use' => 'enc',
            ],
            $this->sut()->createX25519Jwk($rawKeyBytes),
        );
    }


    public function testCreateSecp256k1JwkWithValidUncompressedPoint(): void
    {
        // Uncompressed point format (0x04 || x || y).
        $x = str_repeat('x', 32);
        $y = str_repeat('y', 32);

        $this->assertSame(
            [
                'kty' => 'EC',
                'crv' => 'secp256k1',
                'x' => base64_encode($x),
                'y' => base64_encode($y),
                'use' => 'sig',
            ],
            $this->sut()->createSecp256k1Jwk("\x04" . $x . $y),
        );
    }


    public function testCreateSecp256k1JwkWithCompressedPointThrows(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Compressed Secp256k1 keys are not currently supported');

        // Compressed point format (0x02 or 0x03 followed by 32 bytes for x).
        $this->sut()->createSecp256k1Jwk("\x02" . str_repeat('x', 32));
    }


    public function testCreateSecp256k1JwkWithInvalidKeyFormatThrows(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Invalid Secp256k1 public key format');

        $this->sut()->createSecp256k1Jwk("\x04" . str_repeat('x', 10));
    }


    public function testCreateP256JwkWithValidUncompressedPoint(): void
    {
        $x = str_repeat('x', 32);
        $y = str_repeat('y', 32);

        $this->assertSame(
            [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => base64_encode($x),
                'y' => base64_encode($y),
                'use' => 'sig',
            ],
            $this->sut()->createP256Jwk("\x04" . $x . $y),
        );
    }


    public function testCreateP256JwkWithRawCoordinatesOnly(): void
    {
        // Some implementations omit the leading 0x04 byte and provide raw x || y.
        $x = str_repeat('x', 32);
        $y = str_repeat('y', 32);

        $this->assertSame(
            [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => base64_encode($x),
                'y' => base64_encode($y),
                'use' => 'sig',
            ],
            $this->sut()->createP256Jwk($x . $y),
        );
    }


    public function testCreateP256JwkWithCompressedPointThrows(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Compressed P-256 keys are not currently supported');

        $this->sut()->createP256Jwk("\x03" . str_repeat('x', 32));
    }


    public function testCreateP256JwkWithInvalidKeyFormatThrows(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Invalid P-256 public key format');

        $this->sut()->createP256Jwk("\x02" . str_repeat('x', 64));
    }


    public function testCreateP384JwkWithValidUncompressedPoint(): void
    {
        $x = str_repeat('x', 48);
        $y = str_repeat('y', 48);

        $this->assertSame(
            [
                'kty' => 'EC',
                'crv' => 'P-384',
                'x' => base64_encode($x),
                'y' => base64_encode($y),
                'use' => 'sig',
            ],
            $this->sut()->createP384Jwk("\x04" . $x . $y),
        );
    }


    public function testCreateP384JwkWithInvalidKeyFormatThrows(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Invalid P-384 public key format');

        $this->sut()->createP384Jwk("\x04" . str_repeat('x', 50));
    }


    public function testCreateP521JwkWithValidUncompressedPoint(): void
    {
        $x = str_repeat('x', 66);
        $y = str_repeat('y', 66);

        $this->assertSame(
            [
                'kty' => 'EC',
                'crv' => 'P-521',
                'x' => base64_encode($x),
                'y' => base64_encode($y),
                'use' => 'sig',
            ],
            $this->sut()->createP521Jwk("\x04" . $x . $y),
        );
    }


    public function testCreateP521JwkWithInvalidKeyFormatThrows(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Invalid P-521 public key format');

        $this->sut()->createP521Jwk("\x04" . str_repeat('x', 70));
    }


    public function testCreateJwkFromRawJson(): void
    {
        // phpcs:ignore
        $jsonData = '{"crv":"P-256","kty":"EC","x":"dDfIibQM-949qf4jj-8mBY4Azq34ygSGhzd8AT2mx6s","y":"VUyI8G-OYirMrrsCB9lvUbr6Wjq2ef73ne_paBqLPxw"}';

        $jwk = $this->sut(new Helpers())->createJwkFromRawJson($jsonData);

        $this->assertEquals('EC', $jwk['kty']);
        $this->assertEquals('P-256', $jwk['crv']);
        $this->assertEquals('dDfIibQM-949qf4jj-8mBY4Azq34ygSGhzd8AT2mx6s', $jwk['x']);
        $this->assertEquals('VUyI8G-OYirMrrsCB9lvUbr6Wjq2ef73ne_paBqLPxw', $jwk['y']);
        $this->assertEquals('sig', $jwk['use']);
    }


    public function testCreateJwkFromRawJsonKeepsExplicitUse(): void
    {
        $jsonData = '{"kty":"OKP","crv":"X25519","x":"dDfIibQM","use":"enc"}';

        $jwk = $this->sut(new Helpers())->createJwkFromRawJson($jsonData);

        $this->assertEquals('enc', $jwk['use']);
    }


    public function testCreateJwkFromRawJsonAcceptsUnvalidatedKeyType(): void
    {
        // Key types other than EC, OKP and RSA are passed through without member validation.
        $jwk = $this->sut(new Helpers())->createJwkFromRawJson('{"kty":"oct","k":"dDfIibQM"}');

        $this->assertEquals('oct', $jwk['kty']);
        $this->assertEquals('sig', $jwk['use']);
    }


    public function testCreateJwkFromInvalidJsonThrows(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Failed to parse JWK JSON');

        $this->sut(new Helpers())->createJwkFromRawJson('{"crv":"P-256","kty":"EC",');
    }


    #[DataProvider('invalidJwkJsonDataProvider')]
    public function testCreateJwkFromInvalidJwkThrows(string $json, string $expectedExceptionMessage): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->sut(new Helpers())->createJwkFromRawJson($json);
    }


    public static function invalidJwkJsonDataProvider(): \Iterator
    {
        yield 'not an object' => [
            '"not-a-jwk"',
            'Invalid JWK format: missing required "kty" property',
        ];
        yield 'missing kty' => [
            '{"crv":"P-256"}',
            'Invalid JWK format: missing required "kty" property',
        ];
        yield 'EC without coordinates' => [
            '{"kty":"EC","crv":"P-256"}',
            'Invalid EC JWK format: missing required properties',
        ];
        yield 'EC without y' => [
            '{"kty":"EC","crv":"P-256","x":"dDfIibQM"}',
            'Invalid EC JWK format: missing required properties',
        ];
        yield 'OKP without x' => [
            '{"kty":"OKP","crv":"Ed25519"}',
            'Invalid OKP JWK format: missing required properties',
        ];
        yield 'RSA without e' => [
            '{"kty":"RSA","n":"dDfIibQM"}',
            'Invalid RSA JWK format: missing required properties',
        ];
    }
}
