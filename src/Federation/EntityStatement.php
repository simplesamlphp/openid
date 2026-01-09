<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimBag;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\ValueAbstracts\JwksClaim;

class EntityStatement extends ParsedJws
{
    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     */
    public function getIssuer(): string
    {
        return parent::getIssuer() ?? throw new EntityStatementException('No Issuer claim found.');
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
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
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
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
     * @return null|non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
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
     * @return null|non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     */
    public function getTrustAnchorHints(): ?array
    {
        // trust_anchor_hints
        // OPTIONAL. An array of strings representing the Entity Identifiers of Trust Anchors trusted by the Entity.
        // Its value MUST NOT be the empty array []. This Claim MUST NOT be present in Entity Configurations
        // of Trust Anchors with no Superiors.

        $claimKey = ClaimsEnum::TrustAnchorHints->value;
        $trustAnchorHints = $this->getPayloadClaim($claimKey);

        if (is_null($trustAnchorHints)) {
            return null;
        }

        if (!is_array($trustAnchorHints)) {
            throw new EntityStatementException('Invalid Trust Anchor Hints claim.');
        }

        if ($trustAnchorHints === []) {
            throw new EntityStatementException('Empty Trust Anchor Hints claim encountered.');
        }

        // It MUST NOT be present in Subordinate Statements.
        if (!$this->isConfiguration()) {
            throw new EntityStatementException('Trust Anchor Hints claim encountered in non-configuration statement.');
        }

        return $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($trustAnchorHints, $claimKey);
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

        // It MUST NOT be present in Subordinate Statements.
        if (!$this->isConfiguration()) {
            throw new EntityStatementException('Trust Marks claim encountered in configuration statement.');
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
        // OPTIONAL. It is a JSON object with member names that are Trust Mark Type identifiers, and each
        // corresponding value is a JSON object with members: sub, jwks and optionally other members.

        $claimKey = ClaimsEnum::TrustMarkOwners->value;
        $trustMarkOwnersClaimData = $this->getPayloadClaim($claimKey);

        if (is_null($trustMarkOwnersClaimData)) {
            return null;
        }

        return $this->claimFactory->forFederation()->buildTrustMarkOwnersClaimBagFrom($trustMarkOwnersClaimData);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     */
    public function getTrustMarkIssuers(): ?TrustMarkIssuersClaimBag
    {
        // trust_mark_issuers
        // OPTIONAL. A Trust Anchor MAY use this claim to tell which combination of Trust Mark type identifiers and
        // issuers are trusted by the federation. It is a JSON object with member names that are Trust Mark type
        // identifiers, and each corresponding value being an array of Entity Identifiers that are trusted to
        // represent the accreditation authority for Trust Marks with that identifier.

        $claimKey = ClaimsEnum::TrustMarkIssuers->value;
        $trustMarkIssuersClaimData = $this->getPayloadClaim($claimKey);

        if (is_null($trustMarkIssuersClaimData)) {
            return null;
        }

        if (!$this->isConfiguration()) {
            throw new EntityStatementException('Trust Mark Issuers claim encountered in non-configuration statement.');
        }

        return $this->claimFactory->forFederation()->buildTrustMarkIssuersClaimBagFrom($trustMarkIssuersClaimData);
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     */
    public function getKeyId(): string
    {
        return parent::getKeyId() ?? throw new EntityStatementException('No KeyId header claim found.');
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
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
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
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
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getFederationTrustMarkStatusEndpoint(): ?string
    {
        $federationTrustMarkEndpoint = $this->helpers->arr()->getNestedValue(
            $this->getPayload(),
            ClaimsEnum::Metadata->value,
            EntityTypesEnum::FederationEntity->value,
            ClaimsEnum::FederationTrustMarkStatusEndpoint->value,
        );

        if (is_null($federationTrustMarkEndpoint)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($federationTrustMarkEndpoint);
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenId4VciProofException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAlgorithm(): string
    {
        return parent::getAlgorithm() ?? throw new EntityStatementException('No Algorithm header claim found.');
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
            $this->getAlgorithm(...),
            $this->getKeyId(...),
            $this->getAuthorityHints(...),
            $this->getTrustAnchorHints(...),
            $this->getMetadata(...),
            $this->getMetadataPolicy(...),
            $this->getTrustMarks(...),
            $this->getTrustMarkOwners(...),
            $this->getTrustMarkIssuers(...),
            $this->getFederationFetchEndpoint(...),
            $this->getFederationTrustMarkEndpoint(...),
            $this->getFederationTrustMarkStatusEndpoint(...),
        );
    }
}
