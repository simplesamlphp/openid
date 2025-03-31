<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Claims\JwksClaim;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimBag;
use SimpleSAML\OpenID\Jws\ParsedJws;

class EntityStatement extends ParsedJws
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getIssuer(): string
    {
        return parent::getIssuer() ?? throw new EntityStatementException('No Issuer claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getSubject(): string
    {
        return parent::getSubject() ?? throw new EntityStatementException('No Subject claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuedAt(): int
    {
        return parent::getIssuedAt() ?? throw new EntityStatementException('No Issued At claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getExpirationTime(): int
    {
        return parent::getExpirationTime() ?? throw new EntityStatementException('No Expiration Time claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getJwks(): JwksClaim
    {
        $jwks = $this->getPayloadClaim(ClaimsEnum::Jwks->value) ?? throw new EntityStatementException(
            'No JWKS claim found.',
        );

        return $this->claimFactory->buildJwks($jwks);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getType(): string
    {
        $typ = parent::getType() ?? throw new EntityStatementException('No Type header claim found.');

        if ($typ !== JwtTypesEnum::EntityStatementJwt->value) {
            throw new EntityStatementException('Invalid Type header claim.');
        }

        return $typ;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return null|non-empty-string[]
     */
    public function getAuthorityHints(): ?array
    {
        $claimKey = ClaimsEnum::AuthorityHints->value;
        $authorityHints = $this->getPayloadClaim($claimKey);

        if (is_null($authorityHints)) {
            return null;
        }

        // authority_hints
        // OPTIONAL. An array of strings representing the Entity Identifiers of Intermediate Entities or Trust Anchors
        // that are Immediate Superiors of the Entity.
        if (!is_array($authorityHints)) {
            throw new EntityStatementException('Invalid Authority Hints claim.');
        }

        // Its value MUST contain the Entity Identifiers of its Immediate Superiors and MUST NOT be the empty array []
        if ($authorityHints === []) {
            throw new EntityStatementException('Empty Authority Hints claim encountered.');
        }

        // It MUST NOT be present in Subordinate Statements.
        if (!$this->isConfiguration()) {
            throw new EntityStatementException('Authority Hints claim encountered in non-configuration statement.');
        }

        return $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($authorityHints, $claimKey);
    }

    /**
     * @return ?array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     */
    public function getMetadata(): ?array
    {
        $claimKey = ClaimsEnum::Metadata->value;
        $metadata = $this->getPayloadClaim($claimKey);

        if (is_null($metadata)) {
            return null;
        }

        // metadata
        // OPTIONAL. JSON object that represents the Entity's Types and the metadata for those Entity Types.
        if (!is_array($metadata)) {
            throw new EntityStatementException('Invalid Metadata claim.');
        }

        return $this->helpers->type()->ensureArrayWithKeysAsStrings($metadata);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @phpstan-ignore missingType.iterableValue (We will ensure proper format in policy resolver.)
     */
    public function getMetadataPolicy(): ?array
    {
        $claimKey = ClaimsEnum::MetadataPolicy->value;
        $metadataPolicy = $this->getPayloadClaim($claimKey);

        if (is_null($metadataPolicy)) {
            return null;
        }

        // metadata_policy
        // OPTIONAL. JSON object that defines a metadata policy.
        if (!is_array($metadataPolicy)) {
            throw new EntityStatementException('Invalid Metadata Policy claim.');
        }

        // Only Subordinate Statements MAY include this claim.
        if ($this->isConfiguration()) {
            throw new EntityStatementException('Metadata Policy claim encountered in configuration statement.');
        }

        return $metadataPolicy;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getTrustMarks(): ?TrustMarksClaimBag
    {
        // trust_marks
        // OPTIONAL. An array of JSON objects, each representing a Trust Mark.

        $claimKey = ClaimsEnum::TrustMarks->value;
        $trustMarksClaims = $this->getPayloadClaim($claimKey);

        if (is_null($trustMarksClaims)) {
            return null;
        }

        if (!is_array($trustMarksClaims)) {
            throw new EntityStatementException('Invalid Trust Marks claim.');
        }

        $trustMarkClaimBag = $this->claimFactory->forFederation()->buildTrustMarksClaimBag();

        while (is_array($trustMarkClaimData = array_pop($trustMarksClaims))) {
            $trustMarkClaimBag->add(
                $this->claimFactory->forFederation()->buildTrustMarksClaimValueFrom($trustMarkClaimData),
            );
        }

        return $trustMarkClaimBag;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getTrustMarkOwners(): ?TrustMarkOwnersClaimBag
    {
        // trust_mark_owners
        // OPTIONAL. It is a JSON object with member names that are Trust Mark identifiers and each
        // corresponding value being a JSON object with members: sub, jwks and optionally other members.

        $claimKey = ClaimsEnum::TrustMarkOwners->value;
        $trustMarkOwnersClaimData = $this->getPayloadClaim($claimKey);

        if (is_null($trustMarkOwnersClaimData)) {
            return null;
        }

        return $this->claimFactory->forFederation()->buildTrustMarkOwnersClaimBagFrom($trustMarkOwnersClaimData);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getKeyId(): string
    {
        return parent::getKeyId() ?? throw new EntityStatementException('No KeyId header claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     *
     * @return ?non-empty-string
     */
    public function getFederationFetchEndpoint(): ?string
    {
        $federationFetchEndpoint = $this->helpers->arr()->getNestedValue(
            $this->getPayload(),
            ClaimsEnum::Metadata->value,
            EntityTypesEnum::FederationEntity->value,
            ClaimsEnum::FederationFetchEndpoint->value,
        );

        if (is_null($federationFetchEndpoint)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($federationFetchEndpoint);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     *
     * @return ?non-empty-string
     */
    public function getFederationTrustMarkEndpoint(): ?string
    {
        $federationTrustMarkEndpoint = $this->helpers->arr()->getNestedValue(
            $this->getPayload(),
            ClaimsEnum::Metadata->value,
            EntityTypesEnum::FederationEntity->value,
            ClaimsEnum::FederationTrustMarkEndpoint->value,
        );

        if (is_null($federationTrustMarkEndpoint)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($federationTrustMarkEndpoint);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function isConfiguration(): bool
    {
        return $this->getIssuer() === $this->getSubject();
    }

    /**
     * @param array<mixed>|null $jwks
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function verifyWithKeySet(?array $jwks = null, int $signatureIndex = 0): void
    {
        // Verify with provided JWKS, otherwise use own JWKS.
        $jwks ??= $this->getJwks()->getValue();

        parent::verifyWithKeySet($jwks, $signatureIndex);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getIssuedAt(...),
            $this->getExpirationTime(...),
            $this->getJwks(...),
            $this->getType(...),
            $this->getKeyId(...),
            $this->getAuthorityHints(...),
            $this->getMetadata(...),
            $this->getMetadataPolicy(...),
            $this->getTrustMarks(...),
            $this->getTrustMarkOwners(...),
            $this->getFederationFetchEndpoint(...),
            $this->getFederationTrustMarkEndpoint(...),
        );
    }
}
