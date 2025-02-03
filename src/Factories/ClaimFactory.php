<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use SimpleSAML\OpenID\Claims\GenericClaim;
use SimpleSAML\OpenID\Claims\JwksClaim;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\JwksException;
use SimpleSAML\OpenID\Federation\Factories\FederationClaimFactory;
use SimpleSAML\OpenID\Helpers;

class ClaimFactory
{
    protected FederationClaimFactory $federationClaimFactory;

    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }

    public function forFederation(): FederationClaimFactory
    {
        return $this->federationClaimFactory ??= new FederationClaimFactory(
            $this->helpers,
            $this,
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildGeneric(mixed $value, string $name): GenericClaim
    {
        return new GenericClaim(
            $value,
            $this->helpers->type()->ensureNonEmptyString($name, 'ClaimName'),
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildJwks(mixed $jwks, string $name = ClaimsEnum::Jwks->value): JwksClaim
    {
        if (
            !is_array($jwks) ||
            !array_key_exists(ClaimsEnum::Keys->value, $jwks) ||
            !is_array($jwks[ClaimsEnum::Keys->value]) ||
            (empty($jwks[ClaimsEnum::Keys->value]))
        ) {
            throw new JwksException('Invalid JWKS encountered: ' . var_export($jwks, true));
        }

        $ensuredKeys = [];

        foreach ($jwks[ClaimsEnum::Keys->value] as $index => $key) {
            if (!is_array($key)) {
                throw new JwksException(
                    sprintf('Invalid JWKS key format: %s.', var_export($key, true)),
                );
            }

            $ensuredKeys[$index] = $this->helpers->type()->ensureArrayWithKeysAsStrings($key);
        }

        $jwks[ClaimsEnum::Keys->value] = $ensuredKeys;

        return new JwksClaim(
            $jwks,
            $this->helpers->type()->ensureNonEmptyString($name, 'ClaimName'),
        );
    }
}
