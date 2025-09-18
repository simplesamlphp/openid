<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\OpenId4VciProofException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class OpenId4VciProof extends ParsedJws
{
    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenId4VciProofException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAlgorithm(): string
    {
        $alg = parent::getAlgorithm() ?? throw new OpenId4VciProofException('No Algorithm header claim found.');

        $algEnum = SignatureAlgorithmEnum::tryFrom($alg) ?? throw new OpenId4VciProofException(
            'Invalid Algorithm header claim.',
        );

        if ($algEnum->isNone()) {
            throw new OpenId4VciProofException('Invalid Algorithm header claim (none).');
        }

        return $alg;
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenId4VciProofException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getType(): string
    {
        $typ = parent::getType() ?? throw new OpenId4VciProofException('No Type header claim found.');

        if ($typ !== JwtTypesEnum::OpenId4VciProofJwt->value) {
            throw new OpenId4VciProofException('Invalid Type header claim.');
        }

        return $typ;
    }


    /**
     * @return ?mixed[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getJsonWebKey(): ?array
    {
        $claimKey = ClaimsEnum::Jwk->value;

        $jwk = $this->getHeaderClaim($claimKey);

        return is_null($jwk) ? null : $this->helpers->type()->ensureArray($jwk, $claimKey);
    }


    /**
     * @return ?non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getX509CertificateChain(): ?array
    {
        $claimKey = ClaimsEnum::X5c->value;

        $x5c = $this->getHeaderClaim($claimKey);

        return is_null($x5c) ? null : $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($x5c, $claimKey);
    }


    /**
     * @return string[]
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenId4VciProofException
     */
    public function getAudience(): array
    {
        return parent::getAudience() ?? throw new OpenId4VciProofException('No Audience claim found.');
    }


    public function getIssuedAt(): int
    {
        return parent::getIssuedAt() ?? throw new OpenId4VciProofException('No IssuedAt claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getNonce(): ?string
    {
        $claimKey = ClaimsEnum::Nonce->value;

        $nonce = $this->getHeaderClaim($claimKey);

        return is_null($nonce) ? null : $this->helpers->type()->ensureNonEmptyString($nonce, $claimKey);
    }

    // TODO
    // key_attestation: OPTIONAL
    // trust_chain: OPTIONAL

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getAlgorithm(...),
            $this->getType(...),
            $this->getKeyId(...),
            $this->getJsonWebKey(...),
            $this->getX509CertificateChain(...),
            $this->getIssuer(...),
            $this->getAudience(...),
            $this->getIssuedAt(...),
            $this->getExpirationTime(...),
        );
    }
}
