<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\JwksException;

class TrustAnchorConfig
{
    /**
     * @param non-empty-string $entityId Trust Anchor Entity ID
     * @param string|null $jwks Trust Anchor's JWKS JSON object string value, or
     * null. If JWKS is provided, it can be used to validate Trust Anchor
     * Configuration Statement in addition to using JWKS acquired during Trust
     * Chain resolution. If JWKS is not provided, the validity of Trust Anchor
     * Configuration Statement will "only" be validated by the JWKS acquired
     * during Trust Chain resolution. This means that security will rely "only"
     * on protection implied from using TLS on endpoints used during
     * Trust Chain resolution.
     */
    public function __construct(
        protected readonly string $entityId,
        protected readonly ?string $jwks = null,
    ) {
    }


    /**
     * @return non-empty-string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }


    public function getJwks(): ?string
    {
        return $this->jwks;
    }


    /**
     * @return array{keys:non-empty-array<mixed>}|null
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     */
    public function getJwksAsArray(): ?array
    {
        if (is_string($this->jwks)) {
            $jwks = json_decode($this->jwks, true);

            if (
                is_array($jwks) &&
                array_key_exists(ClaimsEnum::Keys->value, $jwks) &&
                is_array($jwks[ClaimsEnum::Keys->value]) &&
                $jwks[ClaimsEnum::Keys->value] !== []
            ) {
                return $jwks;
            }

            throw new JwksException('Invalid JWKS JSON object.');
        }

        return null;
    }
}
