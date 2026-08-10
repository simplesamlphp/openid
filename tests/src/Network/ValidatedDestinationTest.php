<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Network\ValidatedDestination;

#[CoversClass(ValidatedDestination::class)]
final class ValidatedDestinationTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $sut = new ValidatedDestination('example.org', 443, ['93.184.216.34']);

        $this->assertSame('example.org', $sut->host);
        $this->assertSame(443, $sut->port);
        $this->assertSame(['93.184.216.34'], $sut->addresses);
        $this->assertFalse($sut->isHostAllowListed);
        $this->assertFalse($sut->isHostLiteralAddress);
    }


    public function testHostSpellings(): void
    {
        $this->assertSame(
            ['example.org'],
            (new ValidatedDestination('example.org', 443, ['93.184.216.34']))->hostSpellings(),
        );

        $this->assertSame(
            ['example.org.', 'example.org'],
            (new ValidatedDestination('example.org', 443, ['93.184.216.34'], requestHost: 'example.org.'))
                ->hostSpellings(),
        );
    }


    public function testResolvedHostIsPinnable(): void
    {
        $this->assertTrue((new ValidatedDestination('example.org', 443, ['93.184.216.34']))->isPinnable());
    }


    /**
     * Nothing that has no second resolution ahead of it is worth pinning.
     */
    public function testIsNotPinnableWithoutASecondResolutionToClose(): void
    {
        $this->assertFalse(
            (new ValidatedDestination('example.org', 443, [], isHostAllowListed: true))->isPinnable(),
        );
        $this->assertFalse(
            (new ValidatedDestination('93.184.216.34', 443, ['93.184.216.34'], isHostLiteralAddress: true))
                ->isPinnable(),
        );
        $this->assertFalse((new ValidatedDestination('example.org', null, ['93.184.216.34']))->isPinnable());
        $this->assertFalse((new ValidatedDestination('example.org', 443, []))->isPinnable());
    }
}
