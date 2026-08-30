<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

/**
 * @see \SimpleSAML\Test\OpenID\Helpers\JsonTest
 */
class Json
{
    /** PHP's own default nesting limit, kept as the default here so existing callers are unaffected. */
    public const DEFAULT_MAX_DEPTH = 512;

    /**
     * The deepest json_decode() accepts. Past it, and below one, it raises ValueError rather than the
     * JsonException callers here are told to expect, so both ends are clamped instead of passed through.
     */
    public const MAX_DEPTH = 2147483647;


    /**
     * @param int $maxDepth How deeply the document may nest. Worth lowering for a document that arrives from
     *        the network and has a known shape, so that one written to make the parser do the work is
     *        refused rather than parsed. Clamped to what json_decode accepts, so that a depth chosen badly
     *        is a depth that still decodes rather than an error outside this method's contract.
     * @throws \JsonException
     */
    public function decode(string $json, int $maxDepth = self::DEFAULT_MAX_DEPTH): mixed
    {
        return json_decode($json, true, min(self::MAX_DEPTH, max(1, $maxDepth)), JSON_THROW_ON_ERROR);
    }


    /**
     * @throws \JsonException
     */
    public function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
