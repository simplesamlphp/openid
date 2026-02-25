<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use DateTimeImmutable;
use DateTimeInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;

class ValidFromClaimValue implements ClaimInterface
{
    public function __construct(
        protected readonly DateTimeImmutable $validFrom,
    ) {
    }


    public function getName(): string
    {
        return ClaimsEnum::ValidFrom->value;
    }


    public function getValue(): DateTimeImmutable
    {
        return $this->validFrom;
    }


    public function jsonSerialize(): string
    {
        return $this->validFrom->format(DateTimeInterface::ATOM);
    }
}
