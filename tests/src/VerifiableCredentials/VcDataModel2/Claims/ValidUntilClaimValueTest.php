<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\ValidUntilClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(ValidUntilClaimValue::class)]
final class ValidUntilClaimValueTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $date = new DateTimeImmutable('2025-01-01T19:23:24Z');
        $sut = new ValidUntilClaimValue($date);

        $this->assertInstanceOf(ValidUntilClaimValue::class, $sut);
        $this->assertSame(ClaimsEnum::ValidUntil->value, $sut->getName());
        $this->assertSame($date, $sut->getValue());
        $this->assertSame('2025-01-01T19:23:24+00:00', $sut->jsonSerialize());
    }
}
