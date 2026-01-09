<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

interface ClaimInterface extends \JsonSerializable
{
    public function getName(): string;


    public function getValue(): mixed;
}
