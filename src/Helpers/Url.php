<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

class Url
{
    public function isValid(string $url): bool
    {
        // For now, do simple validation. Adjust when needed.
        return (bool)filter_var($url, FILTER_VALIDATE_URL);
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

        return (isset($parsedUri['scheme']) ? $parsedUri['scheme'] . '://' : '') .
        ($parsedUri['host'] ?? '') .
        (isset($parsedUri['port']) ? ':' . $parsedUri['port'] : '') .
        ($parsedUri['path'] ?? '') .
        '?' . $newQueryString .
        (isset($parsedUri['fragment']) ? '#' . $parsedUri['fragment'] : '');
    }
}
