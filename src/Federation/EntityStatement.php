<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimBagFactory;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimFactory;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaimBag;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

class EntityStatement extends ParsedJws
{
    public function __construct(
        JwsDecorator $jwsDecorator,
        JwsVerifier $jwsVerifier,
        JwksFactory $jwksFactory,
        JwsSerializerManager $jwsSerializerManager,
        DateIntervalDecorator $timestampValidationLeeway,
        Helpers $helpers,
        protected readonly TrustMarkClaimFactory $trustMarkClaimFactory,
        protected readonly TrustMarkClaimBagFactory $trustMarkClaimBagFactory,
    ) {
        parent::__construct(
            $jwsDecorator,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $timestampValidationLeeway,
            $helpers,
        );
    }
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
     */
    public function getExpirationTime(): int
    {
        return parent::getExpirationTime() ?? throw new EntityStatementException('No Expiration Time claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return array{keys:array<array<string,mixed>>}
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getJwks(): array
    {
        /** @psalm-suppress MixedAssignment We check the type manually. */
        $jwks = $this->getPayloadClaim(ClaimsEnum::Jwks->value);

        if (
            !is_array($jwks) ||
            !array_key_exists(ClaimsEnum::Keys->value, $jwks) ||
            !is_array($jwks[ClaimsEnum::Keys->value]) ||
            (empty($jwks[ClaimsEnum::Keys->value]))
        ) {
            throw new JwsException('Invalid JWKS encountered: ' . var_export($jwks, true));
        }

        $jwks[ClaimsEnum::Keys->value] = array_map(
            $this->helpers->arr()->ensureStringKeys(...),
            $jwks[ClaimsEnum::Keys->value],
        );

        /** @var array{keys:array<array<string,mixed>>} $jwks */
        return $jwks;
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
        /** @psalm-suppress MixedAssignment */
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

        return $this->ensureNonEmptyStrings($authorityHints, $claimKey);
    }

    /**
     * @return ?array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     */
    public function getMetadata(): ?array
    {
        $claimKey = ClaimsEnum::Metadata->value;
        /** @psalm-suppress MixedAssignment */
        $metadata = $this->getPayloadClaim($claimKey);

        if (is_null($metadata)) {
            return null;
        }

        // metadata
        // OPTIONAL. JSON object that represents the Entity's Types and the metadata for those Entity Types.
        if (!is_array($metadata)) {
            throw new EntityStatementException('Invalid Metadata claim.');
        }

        return $this->helpers->arr()->ensureStringKeys($metadata);
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
     */
    public function getTrustMarks(): ?TrustMarkClaimBag
    {
        // trust_marks
        // OPTIONAL. An array of JSON objects, each representing a Trust Mark.

        $claimKey = ClaimsEnum::TrustMarks->value;
        /** @psalm-suppress MixedAssignment */
        $trustMarksClaims = $this->getPayloadClaim($claimKey);

        if (is_null($trustMarksClaims)) {
            return null;
        }

        if (!is_array($trustMarksClaims)) {
            throw new EntityStatementException('Invalid Trust Marks claim.');
        }

        $trustMarkClaimBag = $this->trustMarkClaimBagFactory->build();

        /** @psalm-suppress MixedAssignment */
        while (is_array($trustMarkClaimData = array_pop($trustMarksClaims))) {
            $trustMarkClaimData = $this->helpers->arr()->ensureStringKeys($trustMarkClaimData);
            $trustMarkClaimBag->add($this->trustMarkClaimFactory->buildFrom($trustMarkClaimData));
        }

        return $trustMarkClaimBag;
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
     */
    public function getFederationFetchEndpoint(): ?string
    {
        /** @psalm-suppress MixedAssignment */
        $federationFetchEndpoint = $this->getPayload()
        [ClaimsEnum::Metadata->value]
        [EntityTypesEnum::FederationEntity->value]
        [ClaimsEnum::FederationFetchEndpoint->value] ?? null;

        if (is_null($federationFetchEndpoint)) {
            return null;
        }

        return (string)$federationFetchEndpoint;
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
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @phpstan-ignore missingType.iterableValue (Format is validated later.)
     */
    public function verifyWithKeySet(?array $jwks = null, int $signatureIndex = 0): void
    {
        // Verify with provided JWKS, otherwise use own JWKS.
        $jwks ??= $this->getJwks();

        parent::verifyWithKeySet($jwks, $signatureIndex);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
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
            $this->getFederationFetchEndpoint(...),
        );
    }
}
