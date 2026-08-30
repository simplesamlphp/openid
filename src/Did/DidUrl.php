<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Exceptions\DidException;

/**
 * A parsed, absolute DID URL.
 *
 * Relative DID URLs are deliberately not representable. The did:web method specification requires every DID URL
 * inside a document to be absolute, including the ones inside embedded key material, precisely to prevent key
 * confusion attacks, and DID Core's general permission for relative references does not override it.
 *
 * @see https://www.w3.org/TR/did-core/#did-url-syntax
 * @see https://w3c-ccg.github.io/did-method-web/
 * @see \SimpleSAML\Test\OpenID\Did\DidUrlTest
 */
class DidUrl
{
    /** Method names are lowercase alphanumeric. */
    protected const METHOD_NAME = '[a-z0-9]+';

    /** DID Core idchar: ALPHA / DIGIT / "." / "-" / "_" / pct-encoded. */
    protected const ID_CHAR = '(?:[A-Za-z0-9._-]|%[0-9A-Fa-f]{2})';

    /** RFC 3986 pchar, used by path segments, query and fragment. */
    protected const P_CHAR = '(?:[-A-Za-z0-9._~!$&\'()*+,;=:@]|%[0-9A-Fa-f]{2})';


    protected readonly string $method;

    protected readonly string $methodSpecificId;

    protected readonly ?string $path;

    protected readonly ?string $query;

    protected readonly ?string $fragment;


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function __construct(
        protected readonly string $value,
    ) {
        if ($this->value === '') {
            throw new DidException('DID URL must not be empty.');
        }

        if (
            str_starts_with($this->value, '#') ||
            str_starts_with($this->value, '/') ||
            str_starts_with($this->value, '?')
        ) {
            throw new DidException('Relative DID URLs are not supported, an absolute DID URL is required.');
        }

        $remainder = $this->value;

        [$remainder, $this->fragment] = $this->split($remainder, '#');
        [$remainder, $this->query] = $this->split($remainder, '?');
        [$remainder, $path] = $this->split($remainder, '/');
        // The delimiter belongs to the path itself, since path-abempty is *( "/" segment ).
        $this->path = $path === null ? null : '/' . $path;

        // Every pattern here ends in $D rather than $. Without the D, PCRE lets $ match before a trailing
        // newline, so "did:web:example.org\n" would parse as the DID without it while getValue() kept the
        // newline - and that value travels on as a verification method id, into a cache key, into a log
        // context, and eventually into an issued credential's cnf.kid.
        $didPattern = '/^did:(' . self::METHOD_NAME . '):((?:' . self::ID_CHAR . '*:)*' . self::ID_CHAR . '+)$/D';

        if (preg_match($didPattern, $remainder, $matches) !== 1) {
            throw new DidException('DID URL does not contain a syntactically valid DID.');
        }

        $this->method = $matches[1];
        $this->methodSpecificId = $matches[2];

        // path-abempty is *( "/" segment ), where segment is *pchar.
        $this->assertMatches($this->path, '/^(?:\/' . self::P_CHAR . '*)*$/D', 'path');
        // Both query and fragment are *( pchar / "/" / "?" ).
        $this->assertMatches($this->query, '/^(?:' . self::P_CHAR . '|[\/?])*$/D', 'query');
        $this->assertMatches($this->fragment, '/^(?:' . self::P_CHAR . '|[\/?])*$/D', 'fragment');
    }


    /**
     * Split off everything from the first occurrence of the delimiter, returning [before, after-or-null].
     *
     * @return array{0: string, 1: string|null}
     */
    protected function split(string $subject, string $delimiter): array
    {
        $position = strpos($subject, $delimiter);

        if ($position === false) {
            return [$subject, null];
        }

        return [substr($subject, 0, $position), substr($subject, $position + 1)];
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function assertMatches(?string $component, string $pattern, string $componentName): void
    {
        if ($component === null) {
            return;
        }

        if (preg_match($pattern, $component) !== 1) {
            throw new DidException(sprintf('DID URL %s component is not syntactically valid.', $componentName));
        }
    }


    /**
     * The DID URL exactly as given.
     */
    public function getValue(): string
    {
        return $this->value;
    }


    /**
     * The bare DID, without path, query or fragment.
     */
    public function getDid(): string
    {
        return 'did:' . $this->method . ':' . $this->methodSpecificId;
    }


    public function getMethod(): string
    {
        return $this->method;
    }


    public function getMethodSpecificId(): string
    {
        return $this->methodSpecificId;
    }


    public function getPath(): ?string
    {
        return $this->path;
    }


    public function getQuery(): ?string
    {
        return $this->query;
    }


    public function getFragment(): ?string
    {
        return $this->fragment;
    }


    /**
     * Whether this DID URL names something within the DID document, rather than the document subject itself.
     *
     * An empty fragment names nothing, so it does not count.
     */
    public function hasFragment(): bool
    {
        return $this->fragment !== null && $this->fragment !== '';
    }


    /**
     * Whether this is a bare DID, carrying no path, query or fragment.
     */
    public function isBareDid(): bool
    {
        return $this->path === null && $this->query === null && $this->fragment === null;
    }
}
