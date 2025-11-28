<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

use SimpleSAML\OpenID\Exceptions\OpenIdException;

enum HashAlgorithmsEnum: string
{
    // IANA Named Information Hash Algorithm Registry
    // https://www.iana.org/assignments/named-information/named-information.xhtml
    case SHA_256 = 'sha-256';

    case SHA_256_128 = 'sha-256-128';

    case SHA_256_120 = 'sha-256-120';

    case SHA_256_96 = 'sha-256-96';

    case SHA_256_64 = 'sha-256-64';

    case SHA_256_32 = 'sha-256-32';

    case SHA_384 = 'sha-384';

    case SHA_512 = 'sha-512';

    case SHA3_224 = 'sha3-224';

    case SHA3_256 = 'sha3-256';

    case SHA3_384 = 'sha3-384';

    case SHA3_512 = 'sha3-512';

    case BLAKE2S_256 = 'blake2s-256';

    case BLAKE2B_256 = 'blake2b-256';

    case BLAKE2B_512 = 'blake2b-512';

    case K12_256 = 'k12-256';

    case K12_512 = 'k12-512';


    public function ianaName(): string
    {
        return $this->value;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function phpName(): string
    {
        return match ($this) {
            self::SHA_256 => 'sha256',
            self::SHA_384 => 'sha384',
            self::SHA_512 => 'sha512',
            self::SHA3_224 => 'sha3-224',
            self::SHA3_256 => 'sha3-256',
            self::SHA3_384 => 'sha3-384',
            self::SHA3_512 => 'sha3-512',
            self::SHA_256_128,
            self::SHA_256_120,
            self::SHA_256_96,
            self::SHA_256_64,
            self::SHA_256_32,
            self::BLAKE2S_256,
            self::BLAKE2B_256,
            self::BLAKE2B_512,
            self::K12_256,
            self::K12_512 => throw new OpenIdException('Hash algorithm not supported (.' . $this->ianaName() . ').',),
        };
    }
}
