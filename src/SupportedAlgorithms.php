<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;

/**
 * Supported signature (and in the future encryption) algorithms.
 */
class SupportedAlgorithms
{
    public function __construct(
        protected SignatureAlgorithmBag $signatureAlgorithmBag = new SignatureAlgorithmBag(
            SignatureAlgorithmEnum::RS256,
        ),
    ) {
    }


    public function getSignatureAlgorithmBag(): SignatureAlgorithmBag
    {
        return $this->signatureAlgorithmBag;
    }
}
