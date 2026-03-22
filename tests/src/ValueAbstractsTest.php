<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jwk;
use SimpleSAML\OpenID\ValueAbstracts;
use SimpleSAML\OpenID\ValueAbstracts\Factories\SignatureKeyPairBagFactory;
use SimpleSAML\OpenID\ValueAbstracts\Factories\SignatureKeyPairFactory;

#[CoversClass(ValueAbstracts::class)]
#[UsesClass(SignatureKeyPairFactory::class)]
#[UsesClass(SignatureKeyPairBagFactory::class)]
#[UsesClass(Jwk::class)]
final class ValueAbstractsTest extends TestCase
{
    public function testJwk(): void
    {
        $jwk = new Jwk();
        $valueAbstracts = new ValueAbstracts($jwk);

        $this->assertSame($jwk, $valueAbstracts->jwk());
    }


    public function testSignatureKeyPairFactory(): void
    {
        $valueAbstracts = new ValueAbstracts();

        $factory1 = $valueAbstracts->signatureKeyPairFactory();
        $this->assertInstanceOf(SignatureKeyPairFactory::class, $factory1);

        $factory2 = $valueAbstracts->signatureKeyPairFactory();
        $this->assertSame($factory1, $factory2);
    }


    public function testSignatureKeyPairBagFactory(): void
    {
        $valueAbstracts = new ValueAbstracts();

        $factory1 = $valueAbstracts->signatureKeyPairBagFactory();
        $this->assertInstanceOf(SignatureKeyPairBagFactory::class, $factory1);

        $factory2 = $valueAbstracts->signatureKeyPairBagFactory();
        $this->assertSame($factory1, $factory2);
    }
}
