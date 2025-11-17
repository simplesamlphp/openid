<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\CredentialOffer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsBag;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferParameters;

#[\PHPUnit\Framework\Attributes\CoversClass(CredentialOfferParameters::class)]
final class CredentialOfferParametersTest extends TestCase
{
    protected string $credentialIssuer = 'https://example.com/issuer';

    protected array $credentialConfigurationIds = ['credential-1', 'credential-2'];

    protected MockObject $credentialOfferGrantsBag;


    protected function setUp(): void
    {
        $this->credentialOfferGrantsBag = $this->createMock(CredentialOfferGrantsBag::class);
    }


    protected function sut(
        ?string $credentialIssuer = null,
        ?array $credentialConfigurationIds = null,
        false|null|CredentialOfferGrantsBag $credentialOfferGrantsBag = null,
    ): CredentialOfferParameters {
        $credentialIssuer ??= $this->credentialIssuer;
        $credentialConfigurationIds ??= $this->credentialConfigurationIds;
        $credentialOfferGrantsBag = $credentialOfferGrantsBag === false ?
        null :
        $credentialOfferGrantsBag ?? $this->credentialOfferGrantsBag;

        return new CredentialOfferParameters(
            $credentialIssuer,
            $credentialConfigurationIds,
            $credentialOfferGrantsBag,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(CredentialOfferParameters::class, $this->sut());
    }


    public function testCanJsonSerialize(): void
    {
        $this->credentialOfferGrantsBag->expects($this->once())->method('jsonSerialize');

        $this->assertNotEmpty($this->sut()->jsonSerialize());
    }
}
