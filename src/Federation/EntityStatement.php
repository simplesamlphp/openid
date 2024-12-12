<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
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
     * @return array[]
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
        if (empty($authorityHints)) {
            throw new EntityStatementException('Empty Authority Hints claim encountered.');
        }

        // It MUST NOT be present in Subordinate Statements.
        if (!$this->isConfiguration()) {
            throw new EntityStatementException('Authority Hints claim encountered in non-configuration statement.');
        }

        return $this->ensureNonEmptyStrings($authorityHints, $claimKey);
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
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function isConfiguration(): bool
    {
        return $this->getIssuer() === $this->getSubject();
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
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
        );
    }
}
