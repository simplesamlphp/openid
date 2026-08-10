<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Network;

/**
 * Resolves a hostname to the addresses a connection to it could end up at.
 *
 * Both families are asked for, since a check that only looks at A records lets an AAAA record pointing at
 * `::1` through.
 *
 * Note that this asks DNS directly, and so does not see names that the host system resolves by other means,
 * `/etc/hosts` in particular. A name like that resolves to nothing here and is therefore refused; allow it by
 * host in the destination policy if a deployment relies on one.
 *
 * @see \SimpleSAML\Test\OpenID\Network\AddressResolverTest
 */
class AddressResolver
{
    public function __construct(
        protected readonly AddressValidator $addressValidator = new AddressValidator(),
    ) {
    }


    /**
     * Every address the given hostname currently resolves to, without duplicates.
     *
     * An empty result means the name could not be resolved, which callers are expected to treat as a refusal
     * rather than as an absence of restrictions.
     *
     * @return list<string>
     */
    public function resolve(string $host): array
    {
        $addresses = [];

        foreach ($this->queryDnsRecords($host) as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;

            if (!is_string($address)) {
                continue;
            }

            if (!$this->addressValidator->isAddress($address)) {
                continue;
            }

            $addresses[$address] = true;
        }

        return array_keys($addresses);
    }


    /**
     * The A and AAAA records for a hostname, or an empty list when the lookup fails.
     *
     * A failed lookup is a warning rather than an error in PHP, and it is an expected outcome here (a name
     * that does not exist, a resolver that is unreachable), so it is silenced and reported as "no addresses".
     *
     * @return list<array<string,mixed>>
     */
    protected function queryDnsRecords(string $host): array
    {
        /** @var list<array<string,mixed>>|false $records */
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        return ($records === false) ? [] : $records;
    }
}
