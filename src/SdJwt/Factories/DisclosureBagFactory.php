<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\SdJwt\Factories;

use SimpleSAML\OpenID\SdJwt\Disclosure;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;

/**
 * @see \SimpleSAML\Test\OpenID\SdJwt\Factories\DisclosureBagFactoryTest
 */
class DisclosureBagFactory
{
    public function build(Disclosure ...$disclosures): DisclosureBag
    {
        return new DisclosureBag(...$disclosures);
    }
}
