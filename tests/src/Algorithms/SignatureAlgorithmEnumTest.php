<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Algorithms;

use Jose\Component\Signature\Algorithm\EdDSA;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\ES384;
use Jose\Component\Signature\Algorithm\ES512;
use Jose\Component\Signature\Algorithm\None;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\Algorithm\PS384;
use Jose\Component\Signature\Algorithm\PS512;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS384;
use Jose\Component\Signature\Algorithm\RS512;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;

#[CoversClass(SignatureAlgorithmEnum::class)]
class SignatureAlgorithmEnumTest extends TestCase
{
    public function testCanGetProperSignatureAlgorithmInstance(): void
    {
        $this->assertInstanceOf(
            EdDSA::class,
            SignatureAlgorithmEnum::EdDSA->instance(),
        );
        $this->assertInstanceOf(
            ES256::class,
            SignatureAlgorithmEnum::ES256->instance(),
        );
        $this->assertInstanceOf(
            ES384::class,
            SignatureAlgorithmEnum::ES384->instance(),
        );
        $this->assertInstanceOf(
            ES512::class,
            SignatureAlgorithmEnum::ES512->instance(),
        );
        $this->assertInstanceOf(
            None::class,
            SignatureAlgorithmEnum::none->instance(),
        );
        $this->assertInstanceOf(
            PS256::class,
            SignatureAlgorithmEnum::PS256->instance(),
        );
        $this->assertInstanceOf(
            PS384::class,
            SignatureAlgorithmEnum::PS384->instance(),
        );
        $this->assertInstanceOf(
            PS512::class,
            SignatureAlgorithmEnum::PS512->instance(),
        );
        $this->assertInstanceOf(
            RS256::class,
            SignatureAlgorithmEnum::RS256->instance(),
        );
        $this->assertInstanceOf(
            RS384::class,
            SignatureAlgorithmEnum::RS384->instance(),
        );
        $this->assertInstanceOf(
            RS512::class,
            SignatureAlgorithmEnum::RS512->instance(),
        );
    }
}
