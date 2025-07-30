<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\SdJwt\Factories;

use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Codebooks\SdJwtDisclosureType;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\SdJwt\Disclosure;

class DisclosureFactory
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * @param array<non-empty-string> $path
     * @param string[] $saltBlacklist
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function build(
        mixed $value,
        ?string $name = null,
        ?string $salt = null,
        array $path = [],
        HashAlgorithmsEnum $selectiveDisclosureAlgorithm = HashAlgorithmsEnum::SHA_256,
        array $saltBlacklist = [],
    ): Disclosure {

        $salt ??= $this->helpers->random()->string(
            blacklist: $saltBlacklist,
        );

        return new Disclosure(
            $this->helpers,
            $salt,
            $value,
            $name,
            $path,
            $selectiveDisclosureAlgorithm,
        );
    }

    /**
     * @param array<non-empty-string> $path
     * @param string[] $saltBlacklist
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function buildDecoy(
        SdJwtDisclosureType $sdJwtDisclosureType,
        array $path,
        HashAlgorithmsEnum $selectiveDisclosureAlgorithm = HashAlgorithmsEnum::SHA_256,
        array $saltBlacklist = [],
    ): Disclosure {
        $salt = $this->helpers->random()->string(blacklist: $saltBlacklist);
        $value = $this->helpers->random()->string();
        $name = $this->helpers->random()->string();

        if ($sdJwtDisclosureType === SdJwtDisclosureType::ArrayElement) {
            $name = null;
            if ($path === []) {
                $path = [
                    $this->helpers->random()->string(),
                ];
            }
        }

        return $this->build(
            $value,
            $name,
            $salt,
            $path,
            $selectiveDisclosureAlgorithm,
        );
    }
}
