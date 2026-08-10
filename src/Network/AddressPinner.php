<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Network;

use GuzzleHttp\RequestOptions;

/**
 * Makes a request connect to the addresses that were validated, instead of resolving the host a second time.
 *
 * Checking a hostname and then handing the hostname to the client resolves it twice, and whoever controls the
 * name controls both answers: the first can be a public address that passes the check and the second the
 * loopback address the connection then uses. Pinning removes the second lookup.
 *
 * With cURL that is CURLOPT_RESOLVE, which seeds its resolver cache with a `host:port:address` entry. Only
 * where the connection goes changes: the request is still made to the original hostname, so the TLS handshake
 * still validates the certificate against the name and the Host header is unaffected.
 *
 * @see \SimpleSAML\Test\OpenID\Network\AddressPinnerTest
 */
class AddressPinner
{
    /**
     * The libcurl release that first accepted several addresses in one CURLOPT_RESOLVE entry (7.59.0).
     * Older builds read the comma separated list as one malformed address and drop the entry with nothing
     * more than a notice, which would leave the request unpinned while the policy believed otherwise.
     */
    protected const MULTIPLE_ADDRESS_LIBCURL_VERSION = 0x073B00;

    /**
     * Proxy environment variables libcurl reads on its own. A request that goes through a proxy has its
     * destination resolved by the proxy, where a cURL option cannot reach.
     *
     * @var list<string>
     */
    protected const PROXY_ENVIRONMENT_VARIABLES = [
        'http_proxy',
        'https_proxy',
        'HTTPS_PROXY',
        'all_proxy',
        'ALL_PROXY',
    ];


    /**
     * @param bool $handlerIsCurl Whether the handler this will run on is known to be a cURL one. Defaults to
     *        no, so that a transport nothing has been established about is reported as unpinnable rather than
     *        assumed to honour a cURL option: claiming a pin that the transport ignores is worse than
     *        reporting that there is none. The library passes true for a client it built itself, having
     *        asked Guzzle which handler it chose.
     */
    public function __construct(
        protected readonly bool $handlerIsCurl = false,
    ) {
    }


    /**
     * Whether a request made with these options can be pinned.
     *
     * cURL is the only handler that can be told where to connect, so everything here is about establishing
     * that the request will reach one and that the pin will decide the connection when it does:
     *
     * - the handler has to be a known cURL one, and the option has to exist to be set;
     * - a streaming request is handed to the stream handler wherever Guzzle can, cURL present or not;
     * - a proxied request has its destination resolved by the proxy, out of reach of a cURL option;
     * - no cURL option may be in play that decides the connection over the resolver cache.
     *
     * A false answer is never fatal in itself: it is what the pinning mode is then applied to.
     *
     * @param array<mixed> $options Request options, as the middleware has them.
     */
    public function isSupported(array $options = []): bool
    {
        if (!$this->handlerIsCurl || is_null($this->resolveOption())) {
            return false;
        }

        if ($this->hasProxyOption($options) || $this->hasProxyEnvironment()) {
            return false;
        }

        if ($this->hasRoutingOverride($options)) {
            return false;
        }

        // Guzzle decides which handler a request goes to on whether "stream" is empty, so anything it reads
        // as streaming has to count as not pinnable, not only a literal true.
        return empty($options[RequestOptions::STREAM]);
    }


    /**
     * Whether the request options put a proxy in front of this request.
     *
     * The array form of the option names a proxy per scheme alongside a "no" list of hosts to reach
     * directly. A configuration holding nothing but exclusions selects no proxy at all, and reading it as
     * one would cost a pin that could perfectly well have been made.
     *
     * @param array<mixed> $options
     */
    protected function hasProxyOption(array $options): bool
    {
        $proxy = $options[RequestOptions::PROXY] ?? null;

        if (is_array($proxy)) {
            return !empty($proxy['http']) || !empty($proxy['https']);
        }

        return !empty($proxy);
    }


    /**
     * Whether the environment puts a proxy in front of outbound requests.
     *
     * Read conservatively: any proxy variable at all counts, without working out whether "no_proxy" would
     * exempt this particular destination. Over-reporting costs a pin; under-reporting claims one that was
     * never made.
     */
    protected function hasProxyEnvironment(): bool
    {
        foreach (self::PROXY_ENVIRONMENT_VARIABLES as $variable) {
            $value = getenv($variable);

            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }


    /**
     * Request options that connect to the validated addresses.
     *
     * Returned unchanged when there is nothing to pin, so this is safe to apply unconditionally.
     *
     * A pin supplied by the caller for the same host and port is left in place but superseded: cURL takes the
     * last entry for a host and port, and an unvalidated pin must not decide where a request goes.
     *
     * @param array<mixed> $options
     * @return array<mixed>
     */
    public function pin(array $options, ValidatedDestination $destination): array
    {
        $resolveOption = $this->resolveOption();
        $resolveEntries = $this->buildResolveEntries($destination);

        if (is_null($resolveOption) || $resolveEntries === []) {
            return $options;
        }

        $curlOptions = $options[RequestOptions::CURL] ?? [];
        $curlOptions = is_array($curlOptions) ? $curlOptions : [];

        $existingEntries = $curlOptions[$resolveOption] ?? [];
        $existingEntries = is_array($existingEntries) ? array_values($existingEntries) : [];

        $curlOptions[$resolveOption] = array_merge($existingEntries, $resolveEntries);
        $options[RequestOptions::CURL] = $curlOptions;

        return $options;
    }


    /**
     * The cURL resolve entries for the destination, empty when there is nothing to pin.
     *
     * One entry per spelling of the host: cURL looks its cache up under the host exactly as the request
     * writes it, so a destination written `example.org.` is not covered by an entry for `example.org`, and a
     * pin that quietly matches nothing is worse than no pin at all.
     *
     * IPv6 addresses are bracketed, which is the form cURL documents for this option and the only unambiguous
     * one given that the entry is itself colon separated. Where several addresses cannot be named at once,
     * the first is pinned on its own: every one of them passed the policy, so which is taken only decides
     * whether a failing address can be worked around, not whether the connection is bounded.
     *
     * @return list<string>
     */
    protected function buildResolveEntries(ValidatedDestination $destination): array
    {
        if (!$destination->isPinnable()) {
            return [];
        }

        $addresses = $this->supportsMultipleAddressesPerEntry() ?
        $destination->addresses :
        array_slice($destination->addresses, 0, 1);

        $addresses = array_map(
            fn(string $address): string => str_contains($address, ':') ? '[' . $address . ']' : $address,
            $addresses,
        );
        $addressList = implode(',', $addresses);

        return array_map(
            fn(string $host): string => sprintf('%s:%d:%s', $host, $destination->port, $addressList),
            $destination->hostSpellings(),
        );
    }


    /**
     * Whether an option is in play that decides the connection over the resolver cache a pin seeds.
     *
     * cURL applies these to the network destination itself, so a hostname could pass the policy and the
     * connection still be made somewhere else entirely.
     *
     * @param array<mixed> $options
     */
    protected function hasRoutingOverride(array $options): bool
    {
        $curlOptions = $options[RequestOptions::CURL] ?? null;

        if (!is_array($curlOptions)) {
            return false;
        }

        foreach ($this->routingOverrideOptions() as $option) {
            if (!empty($curlOptions[$option])) {
                return true;
            }
        }

        return false;
    }


    /**
     * cURL options that decide where a connection actually goes, over the resolver cache a pin seeds.
     *
     * Named one by one and behind defined(), since each arrived in a different libcurl release and the
     * extension is not a requirement of this library at all.
     *
     * @return list<int>
     */
    protected function routingOverrideOptions(): array
    {
        $options = [];

        if (defined('CURLOPT_CONNECT_TO')) {
            $options[] = CURLOPT_CONNECT_TO;
        }

        if (defined('CURLOPT_PROXY')) {
            $options[] = CURLOPT_PROXY;
        }

        if (defined('CURLOPT_PRE_PROXY')) {
            $options[] = CURLOPT_PRE_PROXY;
        }

        if (defined('CURLOPT_UNIX_SOCKET_PATH')) {
            $options[] = CURLOPT_UNIX_SOCKET_PATH;
        }

        // A pin is made for one host and port pair, so a port set underneath the URI leaves it matching
        // nothing and the host resolved again.
        if (defined('CURLOPT_PORT')) {
            $options[] = CURLOPT_PORT;
        }

        return $options;
    }


    /**
     * Whether the installed libcurl reads a comma separated address list in one resolve entry.
     */
    protected function supportsMultipleAddressesPerEntry(): bool
    {
        if (!function_exists('curl_version')) {
            return false;
        }

        $version = curl_version();
        $versionNumber = is_array($version) ? ($version['version_number'] ?? null) : null;

        return is_int($versionNumber) && $versionNumber >= self::MULTIPLE_ADDRESS_LIBCURL_VERSION;
    }


    /**
     * The CURLOPT_RESOLVE option, or null where the cURL extension is not present to define it.
     *
     * Guarded rather than referenced outright, since the extension is not a requirement of this library and
     * the constant does not exist without it.
     */
    protected function resolveOption(): ?int
    {
        return defined('CURLOPT_RESOLVE') ? CURLOPT_RESOLVE : null;
    }
}
