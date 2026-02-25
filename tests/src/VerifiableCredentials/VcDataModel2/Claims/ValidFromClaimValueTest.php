<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\ValidFromClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(ValidFromClaimValue::class)]
final class ValidFromClaimValueTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $date = new DateTimeImmutable('2010-01-01T19:23:24Z');
        $sut = new ValidFromClaimValue($date);

        $this->assertInstanceOf(ValidFromClaimValue::class, $sut);
        $this->assertSame(ClaimsEnum::ValidFrom->value, $sut->getName());
        $this->assertSame($date, $sut->getValue());
        $this->assertSame('2010-01-01T19:23:24+00:00', $sut->jsonSerialize());
    }
}
