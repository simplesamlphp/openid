<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Network\AddressResolver;
use SimpleSAML\OpenID\Network\AddressValidator;

#[CoversClass(AddressResolver::class)]
#[UsesClass(AddressValidator::class)]
final class AddressResolverTest extends TestCase
{
    /**
     * A resolver whose DNS answers are supplied by the test, so that nothing here depends on a name that
     * happens to exist.
     *
     * @param array<array-key, array<string,mixed>>|false $records
     */
    protected function sut(array|false $records = []): AddressResolver
    {
        return new class ($records) extends AddressResolver {
            /**
             * @param array<array-key, array<string,mixed>>|false $records
             */
            public function __construct(protected readonly array|false $records)
            {
                parent::__construct(new AddressValidator());
            }


            /**
             * @return array<array-key, array<string,mixed>>
             */
            protected function queryDnsRecords(string $host): array
            {
                return ($this->records === false) ? [] : $this->records;
            }
        };
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AddressResolver::class, new AddressResolver());
    }


    public function testCollectsBothFamilies(): void
    {
        $sut = $this->sut([
            ['type' => 'A', 'ip' => '93.184.216.34'],
            ['type' => 'AAAA', 'ipv6' => '2001:db8::1'],
        ]);

        $this->assertSame(['93.184.216.34', '2001:db8::1'], $sut->resolve('example.org'));
    }


    public function testDropsDuplicates(): void
    {
        $sut = $this->sut([
            ['type' => 'A', 'ip' => '93.184.216.34'],
            ['type' => 'A', 'ip' => '93.184.216.34'],
        ]);

        $this->assertSame(['93.184.216.34'], $sut->resolve('example.org'));
    }


    public function testDropsRecordsThatAreNotAddresses(): void
    {
        $sut = $this->sut([
            ['type' => 'CNAME', 'target' => 'elsewhere.example.org'],
            ['type' => 'A', 'ip' => 'not-an-address'],
            ['type' => 'A', 'ip' => 93],
            ['type' => 'A', 'ip' => '93.184.216.34'],
        ]);

        $this->assertSame(['93.184.216.34'], $sut->resolve('example.org'));
    }


    /**
     * A failed lookup has to read as "no addresses", which callers treat as a refusal.
     */
    public function testReportsNothingForAFailedLookup(): void
    {
        $this->assertSame([], $this->sut(false)->resolve('example.invalid'));
        $this->assertSame([], $this->sut([])->resolve('example.invalid'));
    }


    /**
     * The real lookup path, exercised against a name reserved for never resolving, so that a failing lookup
     * is a silent empty result rather than a warning.
     */
    public function testRealLookupOfAnUnresolvableNameIsQuiet(): void
    {
        $this->assertSame([], (new AddressResolver())->resolve('openid-library-test.invalid'));
    }
}
