<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Network;

/**
 * What a destination policy established about one outbound destination.
 *
 * Carries the addresses the check was made against, so that the connection can be made to those same
 * addresses rather than to whatever a second resolution would return.
 *
 * @see \SimpleSAML\Test\OpenID\Network\ValidatedDestinationTest
 */
class ValidatedDestination
{
    /**
     * The host as the request itself writes it, which is not always how the policy compares it: a trailing
     * root label is dropped for comparison, and a client keys its resolver cache on the spelling it was
     * given, so a pin made for the compared form would not be found.
     */
    public readonly string $requestHost;


    /**
     * @param string $host The host in the form the policy judges it: lower case, without brackets around an
     *        IPv6 literal, and without a trailing root label.
     * @param ?int $port The port the connection will use, or null when the scheme implies none.
     * @param list<string> $addresses Every address the host resolved to, all of which passed the policy.
     * @param bool $isHostAllowListed Whether the host itself was allowed, which skips the address check.
     * @param bool $isHostLiteralAddress Whether the host was already an address rather than a name.
     * @param ?string $requestHost The host as the request writes it, where that differs from $host.
     */
    public function __construct(
        public readonly string $host,
        public readonly ?int $port,
        public readonly array $addresses,
        public readonly bool $isHostAllowListed = false,
        public readonly bool $isHostLiteralAddress = false,
        ?string $requestHost = null,
    ) {
        $this->requestHost = $requestHost ?? $host;
    }


    /**
     * Every spelling of the host that a pin has to cover, so that whichever one the client keys its
     * resolver cache on is the one that is found.
     *
     * @return list<string>
     */
    public function hostSpellings(): array
    {
        return array_values(array_unique([$this->requestHost, $this->host]));
    }


    /**
     * Whether there is anything to pin.
     *
     * An address written into the URI can not be re-resolved into something else, and an allowed host was
     * deliberately exempted from the address check, so neither has a second resolution to close off.
     */
    public function isPinnable(): bool
    {
        return !$this->isHostAllowListed &&
        !$this->isHostLiteralAddress &&
        !is_null($this->port) &&
        $this->addresses !== [];
    }
}
