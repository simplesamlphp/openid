<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWSBuilder;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use Throwable;

class JwsDecoratorBuilder
{
    public function __construct(
        protected readonly JwsSerializerManagerDecorator $serializerManagerDecorator,
        protected readonly JWSBuilder $jwsBuilder,
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): JwsDecorator
    {
        try {
            return $this->serializerManagerDecorator->unserialize($token);
        } catch (Throwable $throwable) {
            throw new JwsException('Unable to parse token.', (int)$throwable->getCode(), $throwable);
        }
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
    ): JwsDecorator {
        $header = array_merge(
            $header,
            [ClaimsEnum::Alg->value => $signatureAlgorithm->value],
        );

        try {
            return new JwsDecorator(
                $this->jwsBuilder->create()->withPayload(
                    $this->helpers->json()->encode($payload),
                )->addSignature(
                    $signingKey->jwk(),
                    $header,
                )->build(),
            );
        } catch (Throwable $throwable) {
            throw new JwsException('Unable to build JWS.', (int)$throwable->getCode(), $throwable);
        }
    }
}
