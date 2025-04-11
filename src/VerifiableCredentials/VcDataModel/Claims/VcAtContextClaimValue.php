<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\AtContextsEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;

class VcAtContextClaimValue implements JsonSerializable
{
    /**
     * @param mixed[] $otherContexts
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function __construct(
        protected readonly string $baseContext,
        protected readonly array $otherContexts,
    ) {
        if ($this->baseContext !== AtContextsEnum::W3Org2018CredentialsV1->value) {
            throw new VcDataModelException(sprintf(
                'Invalid VC @context claim. Base context should be %s, %s given.',
                AtContextsEnum::W3Org2018CredentialsV1->value,
                $this->baseContext,
            ));
        }
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            $this->baseContext,
            ...$this->otherContexts,
        ];
    }

    public function getBaseContext(): string
    {
        return $this->baseContext;
    }

    /**
     * @return mixed[]
     */
    public function getOtherContexts(): array
    {
        return $this->otherContexts;
    }
}
