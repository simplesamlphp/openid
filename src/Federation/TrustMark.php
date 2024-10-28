<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\TrustMarkException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class TrustMark extends ParsedJws
{
    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getIssuer(): string
    {
        return parent::getIssuer() ?? throw new TrustMarkException('No Issuer claim found.');
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getSubject(): string
    {
        return parent::getSubject() ?? throw new TrustMarkException('No Subject claim found.');
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getIdentifier(): string
    {
        return parent::getIdentifier() ?? throw new TrustMarkException('No Identifier claim found.');
    }

    /**
     * @return int
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getIssuedAt(): int
    {
        return parent::getIssuedAt() ?? throw new TrustMarkException('No Issued At claim found.');
    }

    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getLogoUri(): ?string
    {
        /** @psalm-suppress MixedAssignment */
        $logoUri = $this->getPayloadClaim(ClaimsEnum::LogoUri->value);

        if (is_null($logoUri)) {
            return null;
        }

        return $this->ensureNonEmptyString($logoUri, ClaimsEnum::LogoUri->value);
    }

    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getReference(): ?string
    {
        /** @psalm-suppress MixedAssignment */
        $ref = $this->getPayloadClaim(ClaimsEnum::Ref->value);

        if (is_null($ref)) {
            return null;
        }

        return $this->ensureNonEmptyString($ref, ClaimsEnum::Ref->value);
    }

    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getDelegation(): ?string
    {
        /** @psalm-suppress MixedAssignment */
        $delegation = $this->getPayloadClaim(ClaimsEnum::Delegation->value);

        if (is_null($delegation)) {
            return null;
        }

        return $this->ensureNonEmptyString($delegation, ClaimsEnum::Delegation->value);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function validate(): void
    {
        $this->validateByCallbacks(
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getIdentifier(...),
            $this->getIssuedAt(...),
            $this->getLogoUri(...),
            $this->getExpirationTime(...),
            $this->getReference(...),
            $this->getDelegation(...),
        );
    }
}
