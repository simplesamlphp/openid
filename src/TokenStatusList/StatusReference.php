<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;

/**
 * Reference from a Referenced Token to its entry in a Status List, being the contents of the `status_list` member.
 *
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list#name-referenced-token-in-jose
 *
 * The URI is used verbatim: it identifies the Status List Token, and a Relying Party rejects a token whose `sub`
 * claim is not equal to it, so it must not be normalised, re-derived or rebuilt from parts along the way.
 *
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\StatusReferenceTest
 */
class StatusReference implements JsonSerializable
{
    /**
     * @param string $uri Identifies the Status List Token containing the status information for the Referenced
     * Token. A URI conforming to RFC 3986.
     * @param int $idx Index to check for status information in the Status List.
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function __construct(
        protected readonly string $uri,
        protected readonly int $idx,
        protected readonly Helpers $helpers = new Helpers(),
    ) {
        $this->helpers->type()->enforceUri($this->uri, ClaimsEnum::Uri->value);

        if ($this->idx < 0) {
            throw new StatusListException(
                sprintf('Status List index must be a non-negative integer, %d given.', $this->idx),
            );
        }
    }


    public function getUri(): string
    {
        return $this->uri;
    }


    public function getIdx(): int
    {
        return $this->idx;
    }


    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            ClaimsEnum::Idx->value => $this->idx,
            ClaimsEnum::Uri->value => $this->uri,
        ];
    }
}
