<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\SdJwt;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Codebooks\SdJwtDisclosureType;
use SimpleSAML\OpenID\Exceptions\SdJwtException;
use SimpleSAML\OpenID\Helpers;

class Disclosure implements JsonSerializable
{
    /**
     * @var non-empty-array<string|int>
     */
    public const FORBIDDEN_NAMES = [
        ClaimsEnum::_SdAlg->value,
        ClaimsEnum::_Sd->value,
        ClaimsEnum::DotDotDot->value,
    ];


    /**
     * @var non-empty-string|null
     */
    protected ?string $encoded = null;

    /**
     * @var non-empty-string|null
     */
    protected ?string $digest = null;


    /**
     * @param array<non-empty-string> $path
     * @throws \SimpleSAML\OpenID\Exceptions\SdJwtException
     */
    public function __construct(
        protected readonly Helpers $helpers,
        protected readonly string $salt,
        protected readonly mixed $value,
        protected readonly ?string $name = null,
        protected readonly array $path = [],
        protected readonly HashAlgorithmsEnum $selectiveDisclosureAlgorithm = HashAlgorithmsEnum::SHA_256,
    ) {
        if ($this->name !== null && in_array($this->name, self::FORBIDDEN_NAMES, true)) {
            throw new SdJwtException('Disclosure name cannot be one of the forbidden names.');
        }

        if ($this->name === null && $this->path === []) {
            throw new SdJwtException('Disclosure name and path cannot be both empty.');
        }
    }


    public function getSalt(): string
    {
        return $this->salt;
    }


    public function getValue(): mixed
    {
        return $this->value;
    }


    public function getName(): ?string
    {
        return $this->name;
    }


    /**
     * @return array<non-empty-string>
     */
    public function getPath(): array
    {
        return $this->path;
    }


    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        if ($this->name === null) {
            return [$this->salt, $this->value];
        }

        return [$this->salt, $this->name, $this->value];
    }


    public function getType(): SdJwtDisclosureType
    {
        if ($this->name === null) {
            return SdJwtDisclosureType::ArrayElement;
        }

        return SdJwtDisclosureType::ObjectProperty;
    }


    public function getEncoded(): string
    {
        return $this->encoded ??= $this->helpers->type()->ensureNonEmptyString(
            $this->helpers->base64Url()->encode(
                $this->helpers->json()->encode(
                    $this->jsonSerialize(),
                ),
            ),
        );
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function getDigest(): string
    {
        return $this->digest ??= $this->helpers->type()->ensureNonEmptyString(
            $this->helpers->base64Url()->encode(
                $this->helpers->hash()->for(
                    $this->selectiveDisclosureAlgorithm->phpName(),
                    $this->getEncoded(),
                    true,
                ),
            ),
        );
    }


    /**
     * @return non-empty-string|array{"...": non-empty-string}
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function getDigestRepresentation(): string|array
    {
        if ($this->getType() === SdJwtDisclosureType::ArrayElement) {
            return [
                ClaimsEnum::DotDotDot->value => $this->getDigest(),
            ];
        }

        return $this->getDigest();
    }
}
