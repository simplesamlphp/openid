<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;

class SupportedAlgorithms
{
    public function __construct(
        protected SignatureAlgorithmBag $signatureAlgorithmBag = new SignatureAlgorithmBag(SignatureAlgorithmEnum::RS256),
    ) {
    }

    public function getSignatureAlgorithmBag(): SignatureAlgorithmBag
    {
        return $this->signatureAlgorithmBag;
    }
}