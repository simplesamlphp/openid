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
