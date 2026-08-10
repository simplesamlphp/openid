<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Network;

/**
 * Decides whether an IP address is one the deployment may be sent to.
 *
 * "Public" here means globally reachable in the sense of the IANA special-purpose address registries: an
 * address that a request leaving the deployment could legitimately be routed to. Everything else - loopback,
 * private, link-local (the cloud metadata address among it), multicast, unspecified, documentation and
 * benchmarking space - is refused.
 *
 * The two families are handled differently on purpose. IPv4 is a small, stable set of carve-outs out of an
 * otherwise routable space, so it is expressed as a deny list. IPv6 is mostly unallocated, so it is expressed
 * the other way round: only global unicast is considered reachable, minus the carve-outs inside it. Addresses
 * that embed an IPv4 address (v4-mapped, and the well-known NAT64 prefix) are unwrapped and judged as the
 * IPv4 address they carry, which is what closes forms like `::ffff:127.0.0.1` that a v4-only check misses.
 * The deprecated IPv4-compatible form (`::x.y.z.w`, and with it `::1` and `::`) needs no unwrapping: it sits
 * outside global unicast and is refused for that reason alone.
 *
 * @see \SimpleSAML\Test\OpenID\Network\AddressValidatorTest
 */
class AddressValidator
{
    /**
     * IPv4 ranges that are not globally reachable.
     *
     * @see https://www.iana.org/assignments/iana-ipv4-special-registry/
     * @var list<string>
     */
    public const NON_PUBLIC_IPV4_CIDRS = [
        '0.0.0.0/8',          // "This network", and the unspecified address.
        '10.0.0.0/8',         // Private use.
        '100.64.0.0/10',      // Shared address space (carrier-grade NAT).
        '127.0.0.0/8',        // Loopback.
        '169.254.0.0/16',     // Link local, including the 169.254.169.254 cloud metadata address.
        '172.16.0.0/12',      // Private use.
        '192.0.0.0/24',       // IETF protocol assignments.
        '192.0.2.0/24',       // Documentation (TEST-NET-1).
        '192.88.99.0/24',     // Deprecated 6to4 relay anycast.
        '192.168.0.0/16',     // Private use.
        '198.18.0.0/15',      // Benchmarking.
        '198.51.100.0/24',    // Documentation (TEST-NET-2).
        '203.0.113.0/24',     // Documentation (TEST-NET-3).
        '224.0.0.0/4',        // Multicast.
        '240.0.0.0/4',        // Reserved, including the 255.255.255.255 broadcast address.
    ];

    /**
     * The only IPv6 range treated as reachable. Everything outside it - loopback and the unspecified address
     * (::/128, ::1/128), unique local (fc00::/7), link local (fe80::/10), multicast (ff00::/8), and the vast
     * unallocated remainder - is refused without having to be enumerated.
     */
    public const GLOBAL_UNICAST_IPV6_CIDR = '2000::/3';

    /**
     * Carve-outs inside global unicast that are not globally reachable, or that tunnel to an address this
     * class can not see.
     *
     * @see https://www.iana.org/assignments/iana-ipv6-special-registry/
     * @var list<string>
     */
    public const NON_PUBLIC_IPV6_CIDRS = [
        // IETF protocol assignments, refused whole rather than by its parts. Nothing in it is globally
        // reachable unless a sub-allocation says so, and the ones that would matter here are the opposite:
        // Teredo (2001::/32) and 6to4's successor tunnel arbitrary IPv4 addresses, benchmarking
        // (2001:2::/48) and ORCHID (2001:10::/28, 2001:20::/28) are not routed at all.
        '2001::/23',
        '2001:db8::/32',      // Documentation.
        '2002::/16',          // Deprecated 6to4: embeds an arbitrary IPv4 address.
        '3fff::/20',          // Documentation.
        '5f00::/16',          // Segment routing (SRv6) SIDs.
    ];

    /**
     * IPv6 ranges that carry an IPv4 address in their low 32 bits, which is the address a connection actually
     * ends up at and therefore the one that has to be judged.
     *
     * @var list<string>
     */
    protected const IPV4_BEARING_IPV6_CIDRS = [
        '::ffff:0:0/96',      // IPv4-mapped.
        '64:ff9b::/96',       // NAT64 well-known prefix.
    ];


    public function isAddress(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }


    public function isIpv4(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }


    public function isIpv6(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }


    /**
     * Reduce an address to the form its reachability should be judged on, or null when it is not an address
     * at all.
     *
     * Surrounding brackets are accepted, so a URI host can be handed over as it stands. Spellings of one
     * address are canonicalized, so a deny list entry can not be walked around by writing the same address
     * differently, and an address carrying an IPv4 address comes back as that IPv4 address.
     */
    public function normalize(string $address): ?string
    {
        $address = trim($address);

        if (str_starts_with($address, '[') && str_ends_with($address, ']')) {
            $address = substr($address, 1, -1);
        }

        if (!$this->isAddress($address)) {
            return null;
        }

        if ($this->isIpv4($address)) {
            return $this->canonicalize($address);
        }

        return $this->extractEmbeddedIpv4($address) ?? $this->canonicalize($address);
    }


    /**
     * Whether an address is one an outbound request may legitimately be routed to.
     *
     * Anything that is not recognizably an address is refused, so a caller can hand over unvalidated input.
     */
    public function isPublic(string $address): bool
    {
        $normalizedAddress = $this->normalize($address);

        if (is_null($normalizedAddress)) {
            return false;
        }

        if ($this->isIpv4($normalizedAddress)) {
            return !$this->isWithinAnyCidr($normalizedAddress, self::NON_PUBLIC_IPV4_CIDRS);
        }

        if (!$this->isWithinCidr($normalizedAddress, self::GLOBAL_UNICAST_IPV6_CIDR)) {
            return false;
        }

        return !$this->isWithinAnyCidr($normalizedAddress, self::NON_PUBLIC_IPV6_CIDRS);
    }


    /**
     * Whether an address falls inside any of the given ranges.
     *
     * The address is normalized first, so an allowed range written as IPv4 also covers the v4-mapped and
     * NAT64 spellings of an address inside it.
     *
     * @param list<string> $cidrs
     */
    public function matchesAnyCidr(string $address, array $cidrs): bool
    {
        $normalizedAddress = $this->normalize($address);

        if (is_null($normalizedAddress)) {
            return false;
        }

        return $this->isWithinAnyCidr($normalizedAddress, $cidrs);
    }


    /**
     * Whether an address falls inside a single range, comparing the two exactly as written.
     *
     * An address of one family is never inside a range of the other, so an IPv4 address is not covered by
     * `::/0`, and a range of the wrong family simply does not match instead of raising an error.
     */
    public function isWithinCidr(string $address, string $cidr): bool
    {
        $range = $this->parseCidr($cidr);

        if (is_null($range)) {
            return false;
        }

        [$networkBytes, $prefixLength] = $range;

        $addressBytes = $this->pack($address);

        if (is_null($addressBytes) || strlen($addressBytes) !== strlen($networkBytes)) {
            return false;
        }

        $wholeBytes = intdiv($prefixLength, 8);

        if ($wholeBytes > 0 && strncmp($addressBytes, $networkBytes, $wholeBytes) !== 0) {
            return false;
        }

        $remainingBits = $prefixLength % 8;

        if ($remainingBits === 0) {
            return true;
        }

        // Compare only the leading bits of the byte the prefix ends inside of.
        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($addressBytes[$wholeBytes]) & $mask) === (ord($networkBytes[$wholeBytes]) & $mask);
    }


    /**
     * Whether a string is a range this class can compare against, for validating configuration up front
     * rather than silently never matching.
     */
    public function isValidCidr(string $cidr): bool
    {
        return !is_null($this->parseCidr($cidr));
    }


    /**
     * @param list<string> $cidrs
     */
    protected function isWithinAnyCidr(string $address, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            if ($this->isWithinCidr($address, $cidr)) {
                return true;
            }
        }

        return false;
    }


    /**
     * The IPv4 address an IPv6 address carries in its low 32 bits, for the prefixes where those bits are an
     * IPv4 address rather than part of the IPv6 address itself.
     */
    protected function extractEmbeddedIpv4(string $address): ?string
    {
        foreach (self::IPV4_BEARING_IPV6_CIDRS as $cidr) {
            if (!$this->isWithinCidr($address, $cidr)) {
                continue;
            }

            $addressBytes = $this->pack($address);

            if (is_null($addressBytes)) {
                return null;
            }

            $embeddedAddress = inet_ntop(substr($addressBytes, 12, 4));

            return ($embeddedAddress === false) ? null : $embeddedAddress;
        }

        return null;
    }


    /**
     * The single spelling of an address, so that equivalent spellings compare equal.
     */
    protected function canonicalize(string $address): ?string
    {
        $addressBytes = $this->pack($address);

        if (is_null($addressBytes)) {
            return null;
        }

        $canonicalAddress = inet_ntop($addressBytes);

        return ($canonicalAddress === false) ? null : $canonicalAddress;
    }


    protected function pack(string $address): ?string
    {
        if (!$this->isAddress($address)) {
            return null;
        }

        $addressBytes = inet_pton($address);

        return ($addressBytes === false) ? null : $addressBytes;
    }


    /**
     * @return ?array{string, int} The network in packed form, and the prefix length.
     */
    protected function parseCidr(string $cidr): ?array
    {
        $parts = explode('/', trim($cidr));

        if (count($parts) !== 2) {
            return null;
        }

        [$network, $prefix] = $parts;

        if ($prefix === '' || ltrim($prefix, '0123456789') !== '') {
            return null;
        }

        $networkBytes = $this->pack($network);

        if (is_null($networkBytes)) {
            return null;
        }

        $prefixLength = (int)$prefix;

        return ($prefixLength > strlen($networkBytes) * 8) ? null : [$networkBytes, $prefixLength];
    }
}
