<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\CredentialOffer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsBag;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsValue;

#[\PHPUnit\Framework\Attributes\CoversClass(CredentialOfferGrantsBag::class)]
final class CredentialOfferGrantsBagTest extends TestCase
{
    protected MockObject $credentialOfferGrantsValue;


    protected function setUp(): void
    {
        $this->credentialOfferGrantsValue = $this->createMock(CredentialOfferGrantsValue::class);
    }


    protected function sut(
        CredentialOfferGrantsValue ...$credentialOfferGrantsValues,
    ): CredentialOfferGrantsBag {
        return new CredentialOfferGrantsBag(...$credentialOfferGrantsValues);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(CredentialOfferGrantsBag::class, $this->sut($this->credentialOfferGrantsValue));
    }


    public function testCanJsonSerialize(): void
    {
        $this->credentialOfferGrantsValue->expects($this->once())->method('jsonSerialize');

        $this->sut($this->credentialOfferGrantsValue)->jsonSerialize();
    }
}
