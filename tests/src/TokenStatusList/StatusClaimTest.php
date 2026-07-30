<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\StatusClaim;
use SimpleSAML\OpenID\TokenStatusList\StatusReference;

#[CoversClass(StatusClaim::class)]
#[UsesClass(StatusReference::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Type::class)]
final class StatusClaimTest extends TestCase
{
    protected function sut(?StatusReference $statusReference = null): StatusClaim
    {
        $statusReference ??= new StatusReference('https://example.com/statuslists/1', 0, new Helpers());

        return new StatusClaim($statusReference);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusClaim::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $statusReference = new StatusReference('https://example.com/statuslists/1', 5, new Helpers());

        $this->assertSame($statusReference, $this->sut($statusReference)->getStatusReference());
        $this->assertSame('status', $this->sut()->getName());
    }


    public function testCanGetValue(): void
    {
        $this->assertSame(
            [
                'status_list' => [
                    'idx' => 0,
                    'uri' => 'https://example.com/statuslists/1',
                ],
            ],
            $this->sut()->getValue(),
        );
    }


    /**
     * Reproduces the claim structure the specification shows for a Referenced Token.
     */
    public function testCanJsonSerialize(): void
    {
        $this->assertSame(
            [
                'status' => [
                    'status_list' => [
                        'idx' => 0,
                        'uri' => 'https://example.com/statuslists/1',
                    ],
                ],
            ],
            $this->sut()->jsonSerialize(),
        );
    }
}
