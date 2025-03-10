<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\WellKnownEnum;

#[CoversClass(WellKnownEnum::class)]
final class WellKnownEnumTest extends TestCase
{
    public function testReturnsPrefixOnlyPathForPrefixCase(): void
    {
        $this->assertSame(
            WellKnownEnum::Prefix->value,
            WellKnownEnum::Prefix->path(),
        );
    }

    public function testReturnsPrefixAndValueForNonPrefixCase(): void
    {
        $path = WellKnownEnum::OpenIdFederation->path();
        $this->assertStringEndsWith(
            WellKnownEnum::OpenIdFederation->value,
            $path,
        );

        $this->assertStringStartsWith(
            WellKnownEnum::Prefix->value,
            $path,
        );
    }

    public function testCanGetWellKnownUri(): void
    {
        $entityId = 'https://example.org';
        $this->assertSame(
            $entityId . '/.well-known/openid-federation',
            WellKnownEnum::OpenIdFederation->uriFor($entityId),
        );
    }
}
