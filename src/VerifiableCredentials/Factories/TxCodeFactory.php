<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\Factories;

use SimpleSAML\OpenID\VerifiableCredentials\TxCode;

class TxCodeFactory
{
    public function build(
        int|string $txCode,
        string $description,
    ): TxCode {
        return new TxCode($txCode, $description);
    }
}
