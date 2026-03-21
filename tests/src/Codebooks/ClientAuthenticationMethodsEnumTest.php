<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ClientAuthenticationMethodsEnum;

#[CoversClass(ClientAuthenticationMethodsEnum::class)]
final class ClientAuthenticationMethodsEnumTest extends TestCase
{
    public function testIsNone(): void
    {
        $this->assertTrue(ClientAuthenticationMethodsEnum::None->isNone());
        $this->assertFalse(ClientAuthenticationMethodsEnum::ClientSecretBasic->isNone());
        $this->assertFalse(ClientAuthenticationMethodsEnum::ClientSecretPost->isNone());
        $this->assertFalse(ClientAuthenticationMethodsEnum::ClientSecretJwt->isNone());
        $this->assertFalse(ClientAuthenticationMethodsEnum::PrivateKeyJwt->isNone());
    }


    public function testIsNotNone(): void
    {
        $this->assertFalse(ClientAuthenticationMethodsEnum::None->isNotNone());
        $this->assertTrue(ClientAuthenticationMethodsEnum::ClientSecretBasic->isNotNone());
        $this->assertTrue(ClientAuthenticationMethodsEnum::ClientSecretPost->isNotNone());
        $this->assertTrue(ClientAuthenticationMethodsEnum::ClientSecretJwt->isNotNone());
        $this->assertTrue(ClientAuthenticationMethodsEnum::PrivateKeyJwt->isNotNone());
    }
}
