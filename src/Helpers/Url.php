<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

/**
 * @see \SimpleSAML\Test\OpenID\Helpers\UrlTest
 */
class Url
{
    /**
     * An authority without userinfo: host [ ":" port ] (RFC 3986 section 3.2). The host is an IP-literal holding an
     * IPv6 address, or a reg-name, which also covers an IPv4 address (section 3.2.2: host = IP-literal /
     * IPv4address / reg-name; reg-name = *( unreserved / pct-encoded / sub-delims )). It is never empty: RFC 9110
     * section 4.2.2, "A sender MUST NOT generate an "https" URI with an empty host identifier. A recipient that
     * processes such a URI reference MUST reject it as invalid." The port is digits only (section 3.2.3: port =
     * *DIGIT). Whether the host is a valid host name or address is left to isValid().
     */
    protected const ISSUER_AUTHORITY_PATTERN =
    '(?:\[[0-9a-f:.]++\]|(?:[a-z0-9\-._~!$&\'()*+,;=]++|%[0-9a-f]{2})++)(?::[0-9]*+)?+';

    /**
     * A path in the characters RFC 3986 allows there (section 3.3: path-abempty = *( "/" segment ), segment =
     * *pchar, pchar = unreserved / pct-encoded / sub-delims / ":" / "@"), so neither "?" nor "#".
     */
    protected const ISSUER_PATH_PATTERN = '(?:\/(?:[a-z0-9\-._~!$&\'()*+,;=:@]++|%[0-9a-f]{2})*+)*+';

    /**
     * "https://", an authority and a path, with no room for a query or a fragment. The scheme is matched
     * case-insensitively, which RFC 3986 section 3.1 asks of an implementation: it "should accept uppercase letters
     * as equivalent to lowercase in scheme names". The quantifiers are possessive: no part can be matched in more
     * than one way, and without backtracking state a long path does not exhaust the PCRE JIT stack. The D modifier
     * keeps $ from matching before a trailing newline.
     */
    protected const ISSUER_IDENTIFIER_PATTERN =
    '/^https:\/\/' . self::ISSUER_AUTHORITY_PATTERN . self::ISSUER_PATH_PATTERN . '$/Di';

    /**
     * The characters RFC 3986 allows in a URI at all: unreserved (section 2.3), reserved (section 2.2), and "%" to
     * start a percent-encoding (section 2.1).
     */
    protected const URI_CHARACTERS_PATTERN = '/^[A-Za-z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]*+\z/';

    /**
     * The regular expression of RFC 3986 Appendix B, which splits a URI reference into its components, with the
     * groups not used here made non-capturing: 1 is the scheme, 2 the authority, 3 the path.
     */
    protected const URI_COMPONENTS_PATTERN = '~^(?:([^:/?#]++):)?(?://([^/?#]*+))?([^?#]*+)(?:\?[^#]*+)?(?:#.*+)?\z~s';

    /**
     * An authority without userinfo: an IP-literal or a reg-name for the host (RFC 3986 section 3.2.2), then an
     * optional port of digits (section 3.2.3).
     */
    protected const HOST_AND_PORT_PATTERN = '/^(\[[^\[\]]*+\]|[^:\[\]]*+)(?::([0-9]*+))?\z/';

    /**
     * RFC 9110 section 4.2.1: for "http", when the port "subcomponent is empty or not given, TCP port 80", and
     * section 4.2.2: for "https", "TCP port 443".
     */
    protected const DEFAULT_PORT_BY_HTTP_SCHEME = [
        'http' => 80,
        'https' => 443,
    ];


    public function isValid(string $url): bool
    {
        // For now, do simple validation. Adjust when needed.
        return (bool)filter_var($url, FILTER_VALIDATE_URL);
    }


    /**
     * Whether the value is usable as an issuer identifier. RFC 8414 section 2: "The authorization server's issuer
     * identifier, which is a URL that uses the "https" scheme and has no query or fragment components."
     *
     * A userinfo subcomponent is refused as well. RFC 9110 section 4.2.4: "A sender MUST NOT generate the userinfo
     * subcomponent (and its "@" delimiter) when an "http" or "https" URI reference is generated within a message
     * as a target URI or field value." and a recipient "SHOULD parse for userinfo and treat its presence as an
     * error". So is any character RFC 3986 does not allow in a URI, whitespace included, and a host which is not
     * a valid host name or IP address (the check isValid() makes). A value too long for PCRE's limits (megabytes)
     * is refused, not accepted.
     */
    public function isIssuerIdentifier(string $value): bool
    {
        return preg_match(self::ISSUER_IDENTIFIER_PATTERN, $value) === 1 && $this->isValid($value);
    }


    /**
     * An "http" or "https" URI reduced to the scheme, the authority and the path, in the normal form RFC 3986
     * section 6.2 gives them, so that two spellings of one target URI compare equal as strings. Made for the "htu"
     * check of RFC 9449 section 4.3, which compares it with the request's URI "ignoring any query and fragment
     * parts", after "syntax-based normalization (Section 6.2.2 of [RFC3986]) and scheme-based normalization
     * (Section 6.2.3 of [RFC3986])".
     *
     * Syntax-based: the scheme lowercased (section 6.2.2.1); a percent-encoded unreserved character decoded and
     * the hexadecimal digits of any other percent-encoding uppercased (sections 6.2.2.1 and 6.2.2.2), in the path
     * and in the host; the host lowercased after that; dot segments removed from the path with the algorithm of
     * section 5.2.4 (section 6.2.2.3). Scheme-based, by the rules RFC 9110 section 4.2.3 gives "http" and "https":
     * an empty port, or the scheme's default one, dropped; an empty path made "/", "When not being used as the
     * target of an OPTIONS request" -- for one that is, say so with $isOptionsRequest, and an empty path, which
     * there targets the server as a whole, stays apart from "/". The query and the fragment are dropped, unread,
     * so what they hold can not make the rest match or fail to. A port with leading zeros is written without
     * them, being the same TCP port. Nothing else is rewritten: each step is an equivalence those sections state,
     * so what it leaves different stays different.
     *
     * Null for a value that is not such a URI, as far as the part before any query or fragment goes: one with a
     * character RFC 3986 does not allow in a URI, a "%" which does not start a percent-encoding, a scheme other
     * than "http" and "https", no authority, an empty host, a host in brackets which is not an IPv6 address
     * (section 3.2.2, IP-literal; the IPvFuture form is not taken), a port above 65535, a "[" or "]" in the path,
     * which section 3.3 does not allow there (pchar = unreserved / pct-encoded / sub-delims / ":" / "@"), or a
     * userinfo subcomponent, whose presence RFC 9110 section 4.2.4 has a recipient "treat [...] as an error".
     *
     * Linear in the length of the URI, so that a long one costs no more than reading it.
     *
     * @return ?non-empty-string
     */
    public function normalizeHttpTargetUri(string $uri, bool $isOptionsRequest = false): ?string
    {
        // The query starts at the first "?" and the fragment at the first "#" (RFC 3986 sections 3.4 and 3.5),
        // neither of which can occur before them.
        $target = substr($uri, 0, strcspn($uri, '?#'));

        if (
            preg_match(self::URI_CHARACTERS_PATTERN, $target) !== 1 ||
            preg_match('/%(?![0-9A-Fa-f]{2})/', $target) === 1 ||
            preg_match(self::URI_COMPONENTS_PATTERN, $target, $components, PREG_UNMATCHED_AS_NULL) !== 1
        ) {
            return null;
        }

        $scheme = $components[1] ?? null;
        $authority = $components[2] ?? null;
        $path = $components[3];

        if (!is_string($scheme) || !is_string($authority)) {
            return null;
        }

        $scheme = strtolower($scheme);
        $defaultPort = self::DEFAULT_PORT_BY_HTTP_SCHEME[$scheme] ?? null;

        if (is_null($defaultPort) || str_contains($authority, '@')) {
            return null;
        }

        if (preg_match(self::HOST_AND_PORT_PATTERN, $authority, $hostAndPort, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        $host = $hostAndPort[1];
        $port = $hostAndPort[2] ?? '';

        if ($host === '' || strlen($port) > 5 || (int)$port > 65535 || strpbrk($path, '[]') !== false) {
            return null;
        }

        if (
            str_starts_with($host, '[') &&
            filter_var(substr($host, 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false
        ) {
            return null;
        }

        $host = strtolower($this->normalizePercentEncoding($host));
        $port = $port === '' || (int)$port === $defaultPort ? '' : ':' . (int)$port;

        $path = $this->removeDotSegments($this->normalizePercentEncoding($path));

        return $scheme . '://' . $host . $port . ($path === '' && !$isOptionsRequest ? '/' : $path);
    }


    /**
     * RFC 3986 section 6.2.2.2: a percent-encoded octet which "corresponds to an unreserved character" is decoded,
     * and section 6.2.2.1: the hexadecimal digits of the rest are uppercased.
     */
    protected function normalizePercentEncoding(string $value): string
    {
        return (string)preg_replace_callback(
            '/%([0-9A-Fa-f]{2})/',
            static function (array $match): string {
                $character = rawurldecode('%' . $match[1]);

                return preg_match('/^[A-Za-z0-9\-._~]\z/', $character) === 1 ?
                $character :
                '%' . strtoupper($match[1]);
            },
            $value,
        );
    }


    /**
     * The remove_dot_segments algorithm of RFC 3986 section 5.2.4, for the paths a URI with an authority has
     * (section 3.3, path-abempty: empty, or starting with "/"). Worked one segment at a time on a stack rather
     * than with the section's two buffers, whose copying makes a long path cost quadratic time; the outcome is
     * the same: a "." segment is dropped (step 2B), a ".." segment drops the segment before it, if any (step 2C),
     * and a path that ends in either one ends in "/", since those steps leave a "/" behind them.
     */
    protected function removeDotSegments(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $segments = [];
        $endsInDotSegment = false;

        // The first piece is what precedes the leading "/", which is nothing.
        foreach (array_slice(explode('/', $path), 1) as $segment) {
            $endsInDotSegment = $segment === '.' || $segment === '..';

            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.') {
                $segments[] = $segment;
            }
        }

        return '/' . implode('/', $segments) . ($endsInDotSegment && $segments !== [] ? '/' : '');
    }


    /**
     * Add (new) params to URL while preserving existing ones (if any).
     * @param array<string,mixed> $params
     */
    public function withParams(string $url, array $params): string
    {
        if ($params === []) {
            return $url;
        }

        $parsedUri = parse_url($url);

        $queryParams = [];
        if (isset($parsedUri['query'])) {
            parse_str($parsedUri['query'], $queryParams);
        }

        $queryParams = array_merge($queryParams, $params);
        $newQueryString = http_build_query($queryParams);

        return $this->prepareUri($parsedUri, $newQueryString);
    }


    /**
     * Build a URL with repeated (multi-value) query parameters.
     * Array values are serialized as repeated keys: ?key=a&key=b
     *
     * @param array<string, array<string>|string|int|float> $params
     */
    public function withMultiValueParams(string $url, array $params): string
    {
        if ($params === []) {
            return $url;
        }

        $parsedUri = parse_url($url);

        $queryParams = [];
        if (isset($parsedUri['query'])) {
            parse_str($parsedUri['query'], $queryParams);
        }

        $queryElements = [];
        // Preserve existing query params
        foreach ($queryParams as $key => $value) {
            $strKey = (string)$key;
            if (is_array($value)) {
                foreach ($value as $subValue) {
                    /** @var string $subValue */
                    $queryElements[] = urlencode($strKey) . '=' . urlencode($subValue);
                }
            } else {
                /** @var string $value */
                $queryElements[] = urlencode($strKey) . '=' . urlencode($value);
            }
        }

        // Add new multi-value params
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subValue) {
                    $queryElements[] = urlencode($key) . '=' . urlencode((string)$subValue);
                }
            } else {
                $queryElements[] = urlencode($key) . '=' . urlencode((string)$value);
            }
        }

        $newQueryString = implode('&', $queryElements);

        return $this->prepareUri($parsedUri, $newQueryString);
    }


    /**
     * @param array<string|int> $parsedUri
     */
    protected function prepareUri(false|array|int|string|null $parsedUri, string $newQueryString): string
    {
        return (isset($parsedUri['scheme']) ? $parsedUri['scheme'] . '://' : '') .
        ($parsedUri['host'] ?? '') .
        (isset($parsedUri['port']) ? ':' . $parsedUri['port'] : '') .
        ($parsedUri['path'] ?? '') .
        '?' . $newQueryString .
        (isset($parsedUri['fragment']) ? '#' . $parsedUri['fragment'] : '');
    }
}
