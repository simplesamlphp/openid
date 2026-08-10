<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Network;

use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Network\AddressValidator;

#[CoversClass(AddressValidator::class)]
final class AddressValidatorTest extends TestCase
{
    protected function sut(): AddressValidator
    {
        return new AddressValidator();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AddressValidator::class, $this->sut());
    }


    #[DataProvider('nonPublicIpv4Provider')]
    public function testRefusesNonPublicIpv4(string $address): void
    {
        $this->assertFalse($this->sut()->isPublic($address));
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function nonPublicIpv4Provider(): Iterator
    {
        yield 'loopback' => ['127.0.0.1'];
        yield 'loopback, elsewhere in the range' => ['127.10.20.30'];
        yield 'private, class A' => ['10.0.0.5'];
        yield 'private, class B' => ['172.16.0.1'];
        yield 'private, class C' => ['192.168.1.1'];
        yield 'link local, cloud metadata' => ['169.254.169.254'];
        yield 'shared address space' => ['100.64.0.1'];
        yield 'unspecified' => ['0.0.0.0'];
        yield 'this network' => ['0.1.2.3'];
        yield 'multicast' => ['224.0.0.1'];
        yield 'broadcast' => ['255.255.255.255'];
        yield 'reserved' => ['240.0.0.1'];
        yield 'benchmarking' => ['198.18.0.1'];
        yield 'documentation' => ['192.0.2.1'];
        yield 'protocol assignments' => ['192.0.0.1'];
        yield 'deprecated 6to4 relay anycast' => ['192.88.99.1'];
    }


    #[DataProvider('nonPublicIpv6Provider')]
    public function testRefusesNonPublicIpv6(string $address): void
    {
        $this->assertFalse($this->sut()->isPublic($address));
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function nonPublicIpv6Provider(): Iterator
    {
        yield 'loopback' => ['::1'];
        yield 'unspecified' => ['::'];
        yield 'unique local' => ['fd00::1'];
        yield 'unique local, lower half' => ['fc00::1'];
        yield 'link local' => ['fe80::1'];
        yield 'site local' => ['fec0::1'];
        yield 'multicast' => ['ff02::1'];
        yield 'documentation' => ['2001:db8::1'];
        yield 'Teredo' => ['2001::1'];
        yield 'ORCHIDv2' => ['2001:20::1'];
        yield 'benchmarking' => ['2001:2::1'];
        yield 'IETF protocol assignments' => ['2001:100::1'];
        yield 'IETF protocol assignments, top of the block' => ['2001:1ff:ffff:ffff:ffff:ffff:ffff:ffff'];
        yield 'documentation, second block' => ['3fff::1'];
        yield 'segment routing SIDs' => ['5f00::1'];
        yield '6to4' => ['2002:7f00:1::'];
        yield 'discard only' => ['100::1'];
        yield 'unallocated' => ['4000::1'];
    }


    /**
     * The forms that carry an IPv4 address, which a check that only reads the IPv6 address itself misses.
     */
    #[DataProvider('ipv4BearingIpv6Provider')]
    public function testJudgesIpv6FormsCarryingIpv4ByThatAddress(string $address, bool $expectedIsPublic): void
    {
        $this->assertSame($expectedIsPublic, $this->sut()->isPublic($address));
    }


    /**
     * @return \Iterator<string, array{string, bool}>
     */
    public static function ipv4BearingIpv6Provider(): Iterator
    {
        yield 'mapped loopback' => ['::ffff:127.0.0.1', false];
        yield 'mapped loopback, hex notation' => ['::ffff:7f00:1', false];
        yield 'mapped private' => ['::ffff:10.0.0.5', false];
        yield 'mapped cloud metadata' => ['::ffff:169.254.169.254', false];
        yield 'mapped public' => ['::ffff:8.8.8.8', true];
        yield 'compatible loopback' => ['::127.0.0.1', false];
        yield 'compatible public, deprecated form' => ['::8.8.8.8', false];
        yield 'NAT64 loopback' => ['64:ff9b::127.0.0.1', false];
        yield 'NAT64 cloud metadata' => ['64:ff9b::169.254.169.254', false];
        yield 'NAT64 public' => ['64:ff9b::8.8.8.8', true];
        yield 'NAT64 local use' => ['64:ff9b:1::1', false];
    }


    #[DataProvider('publicAddressProvider')]
    public function testAllowsPublicAddress(string $address): void
    {
        $this->assertTrue($this->sut()->isPublic($address));
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function publicAddressProvider(): Iterator
    {
        yield 'IPv4' => ['8.8.8.8'];
        yield 'IPv4, another' => ['93.184.216.34'];
        yield 'IPv6 global unicast' => ['2001:4860:4860::8888'];
        yield 'IPv6, bracketed as a URI host would be' => ['[2001:4860:4860::8888]'];
    }


    #[DataProvider('nonAddressProvider')]
    public function testRefusesWhatIsNotAnAddress(string $value): void
    {
        $this->assertFalse($this->sut()->isPublic($value));
        $this->assertNull($this->sut()->normalize($value));
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function nonAddressProvider(): Iterator
    {
        yield 'hostname' => ['example.org'];
        yield 'empty' => [''];
        yield 'decimal notation for the loopback address' => ['2130706433'];
        yield 'octal notation for the loopback address' => ['0177.0.0.1'];
        yield 'zone index' => ['fe80::1%eth0'];
        yield 'address with a port' => ['127.0.0.1:80'];
    }


    public function testNormalizeCanonicalizesSpelling(): void
    {
        $sut = $this->sut();

        $this->assertSame('2001:db8::1', $sut->normalize('2001:0DB8:0000:0000:0000:0000:0000:0001'));
        $this->assertSame('2001:db8::1', $sut->normalize('[2001:db8::1]'));
        $this->assertSame('127.0.0.1', $sut->normalize(' 127.0.0.1 '));
    }


    public function testNormalizeUnwrapsEmbeddedIpv4(): void
    {
        $this->assertSame('127.0.0.1', $this->sut()->normalize('::ffff:127.0.0.1'));
        $this->assertSame('8.8.8.8', $this->sut()->normalize('64:ff9b::8.8.8.8'));
    }


    public function testIsWithinCidr(): void
    {
        $sut = $this->sut();

        $this->assertTrue($sut->isWithinCidr('10.1.2.3', '10.0.0.0/8'));
        $this->assertFalse($sut->isWithinCidr('11.1.2.3', '10.0.0.0/8'));
        $this->assertTrue($sut->isWithinCidr('10.1.2.3', '10.1.2.3/32'));
        $this->assertFalse($sut->isWithinCidr('10.1.2.4', '10.1.2.3/32'));
        $this->assertTrue($sut->isWithinCidr('2001:db8::1', '2001:db8::/32'));
        $this->assertFalse($sut->isWithinCidr('2001:db9::1', '2001:db8::/32'));
        $this->assertTrue($sut->isWithinCidr('1.2.3.4', '0.0.0.0/0'));
    }


    /**
     * A prefix that ends inside a byte has to compare only the bits it covers.
     */
    public function testIsWithinCidrComparesPartialBytes(): void
    {
        $sut = $this->sut();

        $this->assertTrue($sut->isWithinCidr('100.64.0.1', '100.64.0.0/10'));
        $this->assertTrue($sut->isWithinCidr('100.127.255.255', '100.64.0.0/10'));
        $this->assertFalse($sut->isWithinCidr('100.128.0.1', '100.64.0.0/10'));
        $this->assertFalse($sut->isWithinCidr('100.63.255.255', '100.64.0.0/10'));
    }


    /**
     * Neither family is inside a range belonging to the other, so a range can not be widened by writing it in
     * the other notation.
     */
    public function testIsWithinCidrDoesNotCrossFamilies(): void
    {
        $sut = $this->sut();

        $this->assertFalse($sut->isWithinCidr('127.0.0.1', '::/0'));
        $this->assertFalse($sut->isWithinCidr('::1', '0.0.0.0/0'));
    }


    public function testIsWithinCidrRefusesUnusableInput(): void
    {
        $sut = $this->sut();

        $this->assertFalse($sut->isWithinCidr('10.1.2.3', '10.0.0.0'));
        $this->assertFalse($sut->isWithinCidr('10.1.2.3', '10.0.0.0/33'));
        $this->assertFalse($sut->isWithinCidr('10.1.2.3', 'nonsense/8'));
        $this->assertFalse($sut->isWithinCidr('nonsense', '10.0.0.0/8'));
        $this->assertFalse($sut->isWithinCidr('10.1.2.3', '10.0.0.0/eight'));
        $this->assertFalse($sut->isWithinCidr('10.1.2.3', '10.0.0.0/8/8'));
    }


    /**
     * A range written for IPv4 also covers the IPv6 spellings of the addresses in it.
     */
    public function testMatchesAnyCidrNormalizesFirst(): void
    {
        $sut = $this->sut();

        $this->assertTrue($sut->matchesAnyCidr('::ffff:10.1.2.3', ['10.0.0.0/8']));
        $this->assertTrue($sut->matchesAnyCidr('10.1.2.3', ['192.168.0.0/16', '10.0.0.0/8']));
        $this->assertFalse($sut->matchesAnyCidr('10.1.2.3', ['192.168.0.0/16']));
        $this->assertFalse($sut->matchesAnyCidr('10.1.2.3', []));
        $this->assertFalse($sut->matchesAnyCidr('nonsense', ['0.0.0.0/0']));
    }


    public function testIsValidCidr(): void
    {
        $sut = $this->sut();

        $this->assertTrue($sut->isValidCidr('10.0.0.0/8'));
        $this->assertTrue($sut->isValidCidr('::1/128'));
        $this->assertTrue($sut->isValidCidr(' 10.0.0.0/8 '));
        $this->assertFalse($sut->isValidCidr('10.0.0.0'));
        $this->assertFalse($sut->isValidCidr('10.0.0.0/-1'));
        $this->assertFalse($sut->isValidCidr('::1/129'));
    }


    public function testRecognizesAddressFamilies(): void
    {
        $sut = $this->sut();

        $this->assertTrue($sut->isAddress('127.0.0.1'));
        $this->assertTrue($sut->isAddress('::1'));
        $this->assertFalse($sut->isAddress('example.org'));

        $this->assertTrue($sut->isIpv4('127.0.0.1'));
        $this->assertFalse($sut->isIpv4('::1'));

        $this->assertTrue($sut->isIpv6('::1'));
        $this->assertFalse($sut->isIpv6('127.0.0.1'));
    }
}
