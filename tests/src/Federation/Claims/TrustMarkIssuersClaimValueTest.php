<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimValue;

#[CoversClass(TrustMarkIssuersClaimValue::class)]
final class TrustMarkIssuersClaimValueTest extends TestCase
{
    protected string $trustMarkType;

    /**
     * @var array<non-empty-string>
     */
    protected array $trustMarkIssuers;


    protected function setUp(): void
    {
        $this->trustMarkType = 'trustMarkType';
        $this->trustMarkIssuers = ['https://issuer1.org', 'https://issuer2.org'];
    }


    protected function sut(
        ?string $trustMarkType = null,
        ?array $trustMarkIssuers = null,
    ): TrustMarkIssuersClaimValue {
        $trustMarkType ??= $this->trustMarkType;
        $trustMarkIssuers ??= $this->trustMarkIssuers;

        return new TrustMarkIssuersClaimValue($trustMarkType, $trustMarkIssuers);
        ;
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkIssuersClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->trustMarkType, $sut->getTrustMarkType());
        $this->assertSame($this->trustMarkIssuers, $sut->getTrustMarkIssuers());
    }


    public function testCanJsonSerialize(): void
    {
        $this->assertSame(
            [$this->trustMarkType => $this->trustMarkIssuers],
            $this->sut()->jsonSerialize(),
        );
    }
}
