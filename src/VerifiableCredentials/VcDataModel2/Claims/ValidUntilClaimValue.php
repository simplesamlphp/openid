<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use DateTimeImmutable;
use DateTimeInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;

class ValidUntilClaimValue implements ClaimInterface
{
    public function __construct(
        protected readonly DateTimeImmutable $validUntil,
    ) {
    }


    public function getName(): string
    {
        return ClaimsEnum::ValidUntil->value;
    }


    public function getValue(): DateTimeImmutable
    {
        return $this->validUntil;
    }


    public function jsonSerialize(): string
    {
        return $this->validUntil->format(DateTimeInterface::ATOM);
    }
}
