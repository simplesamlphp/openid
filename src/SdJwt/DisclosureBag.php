<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\SdJwt;

use SimpleSAML\OpenID\Exceptions\SdJwtException;

class DisclosureBag
{
    /**
     * @var \SimpleSAML\OpenID\SdJwt\Disclosure[]
     */
    protected array $disclosures;


    public function __construct(Disclosure ...$disclosures)
    {
        $this->disclosures = $disclosures;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\SdJwtException
     */
    public function add(Disclosure ...$disclosures): void
    {
        foreach ($disclosures as $disclosure) {
            if (array_key_exists($disclosure->getSalt(), $this->disclosures)) {
                throw new SdJwtException('Disclosure with the same salt already exists in the bag.');
            }

            $this->disclosures[$disclosure->getSalt()] = $disclosure;
        }
    }


    /**
     * @return \SimpleSAML\OpenID\SdJwt\Disclosure[]
     */
    public function all(): array
    {
        return $this->disclosures;
    }


    /**
     * @return string[]
     */
    public function salts(): array
    {
        return array_keys($this->disclosures);
    }
}
