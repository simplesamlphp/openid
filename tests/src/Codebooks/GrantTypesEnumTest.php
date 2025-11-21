<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\GrantTypesEnum;

#[CoversClass(GrantTypesEnum::class)]
final class GrantTypesEnumTest extends TestCase
{
    public function testCanBeUsedForVerifiableCredentialIssuance(): void
    {
        $this->assertTrue(GrantTypesEnum::PreAuthorizedCode->canBeUsedForVerifiableCredentialIssuance());
        $this->assertTrue(GrantTypesEnum::AuthorizationCode->canBeUsedForVerifiableCredentialIssuance());
        $this->assertFalse(GrantTypesEnum::Implicit->canBeUsedForVerifiableCredentialIssuance());
        $this->assertFalse(GrantTypesEnum::RefreshToken->canBeUsedForVerifiableCredentialIssuance());
    }
}
