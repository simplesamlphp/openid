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
     * @param false|array|int|string|null $parsedUri
     * @param string $newQueryString
     * @return string
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
