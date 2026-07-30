<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList\Factories;

use DateInterval;
use DateTimeImmutable;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\StatusListTokenException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\TokenStatusList\StatusList;
use SimpleSAML\OpenID\TokenStatusList\StatusListToken;

/**
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\Factories\StatusListTokenFactoryTest
 */
class StatusListTokenFactory extends ParsedJwsFactory
{
    /**
     * JWS Compact Serialization: three base64url encoded segments, dot separated, and nothing besides.
     *
     * Anchored with \z rather than $, which would also match immediately before a trailing newline and so let a
     * token through with one appended.
     */
    protected const COMPACT_SERIALIZATION_PATTERN = '#^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\z#';


    public function __construct(
        JwsDecoratorBuilder $jwsDecoratorBuilder,
        JwsVerifierDecorator $jwsVerifierDecorator,
        JwksDecoratorFactory $jwksDecoratorFactory,
        JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        DateIntervalDecorator $timestampValidationLeeway,
        Helpers $helpers,
        ClaimFactory $claimFactory,
        protected readonly StatusListFactory $statusListFactory,
    ) {
        parent::__construct(
            $jwsDecoratorBuilder,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $timestampValidationLeeway,
            $helpers,
            $claimFactory,
        );
    }


    /**
     * Parses a Status List Token from its serialized form.
     *
     * The specification serves the JWT representation as a JWS Compact Serialization, so a JSON serialized JWS
     * is not a Status List Token even where a deployment has enabled those serializers library-wide for other
     * token types. Held to that here rather than left to whichever serializers happen to be configured, since a
     * JSON serialized JWS can carry several signatures and unprotected headers, none of which anything below
     * accounts for: only the first signature would be verified, and only its protected header read.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): StatusListToken
    {
        if (preg_match(self::COMPACT_SERIALIZATION_PATTERN, $token) !== 1) {
            throw new StatusListTokenException('Status List Token is not a JWS Compact Serialization.');
        }

        return new StatusListToken(
            $this->jwsDecoratorBuilder->fromToken($token),
            $this->jwsVerifierDecorator,
            $this->jwksDecoratorFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->claimFactory,
            $this->statusListFactory,
        );
    }


    /**
     * @param array<non-empty-string,mixed> $payload
     * @param array<non-empty-string,mixed> $header
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromData(
        JwkDecorator $signingKey,
        SignatureAlgorithmEnum $signatureAlgorithm,
        array $payload,
        array $header,
    ): StatusListToken {
        $header[ClaimsEnum::Typ->value] = JwtTypesEnum::StatusListJwt->value;

        return new StatusListToken(
            $this->jwsDecoratorBuilder->fromData(
                $signingKey,
                $signatureAlgorithm,
                $payload,
                $header,
            ),
            $this->jwsVerifierDecorator,
            $this->jwksDecoratorFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->claimFactory,
            $this->statusListFactory,
        );
    }


    /**
     * Builds and signs a Status List Token for the given Status List.
     *
     * Both the key ID and the issuer are left to the caller: the specification mandates no key resolution method,
     * so which key identifier a Relying Party can resolve, and whether an `iss` claim is even meaningful, are
     * properties of a deployment's chosen profile rather than of the specification.
     *
     * @param string $uri The URI identifying this Status List Token. It becomes the `sub` claim, and must be the
     * same string the Referenced Tokens carry in their `uri` claim, byte for byte.
     * @param ?\DateInterval $timeToLive Maximum time the token may be cached before a fresh copy is retrieved,
     * emitted as a whole number of seconds.
     * @param array<non-empty-string,mixed> $payload Any additional claims to include.
     * @param array<non-empty-string,mixed> $header Any additional header claims to include, such as `kid`.
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    public function forStatusList(
        StatusList $statusList,
        string $uri,
        JwkDecorator $signingKey,
        SignatureAlgorithmEnum $signatureAlgorithm,
        ?DateTimeImmutable $issuedAt = null,
        ?DateTimeImmutable $expiresAt = null,
        ?DateInterval $timeToLive = null,
        ?string $issuer = null,
        array $payload = [],
        array $header = [],
    ): StatusListToken {
        $issuedAt ??= new DateTimeImmutable();

        $payload[ClaimsEnum::Sub->value] = $uri;
        $payload[ClaimsEnum::Iat->value] = $issuedAt->getTimestamp();
        $payload[ClaimsEnum::StatusList->value] = $statusList->jsonSerialize();

        if ($expiresAt instanceof DateTimeImmutable) {
            $payload[ClaimsEnum::Exp->value] = $expiresAt->getTimestamp();
        }

        if ($timeToLive instanceof DateInterval) {
            $payload[ClaimsEnum::Ttl->value] = (new DateIntervalDecorator($timeToLive))->getInSeconds();
        }

        if ($issuer !== null) {
            $payload[ClaimsEnum::Iss->value] = $issuer;
        }

        return $this->fromData($signingKey, $signatureAlgorithm, $payload, $header);
    }
}
