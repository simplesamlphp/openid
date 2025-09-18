<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\AtContextsEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;

class VcAtContextClaimValue implements ClaimInterface
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
        return $this->getValue();
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


    public function getName(): string
    {
        return ClaimsEnum::AtContext->name;
    }


    /**
     * @return mixed[]
     */
    public function getValue(): array
    {
        return[
            $this->baseContext,
            ...$this->otherContexts,
        ];
    }
}
