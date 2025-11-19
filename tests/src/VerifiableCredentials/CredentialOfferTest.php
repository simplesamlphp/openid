<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\CredentialOfferException;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferParameters;

#[\PHPUnit\Framework\Attributes\CoversClass(CredentialOffer::class)]
final class CredentialOfferTest extends TestCase
{
    protected MockObject $credentialOfferParametersMock;

    protected string $uri = 'https://example.com/credential-offer';


    protected function setUp(): void
    {
        $this->credentialOfferParametersMock = $this->createMock(CredentialOfferParameters::class);
    }


    protected function sut(
        CredentialOfferParameters|false|null $credentialOfferParameters = null,
        string|false|null $uri = null,
    ): CredentialOffer {
        $credentialOfferParameters = $credentialOfferParameters === false ?
        null :
        $credentialOfferParameters ?? $this->credentialOfferParametersMock;

        $uri = $credentialOfferParameters === null ?
        ($uri === false ? null : $uri ?? $this->uri) :
        null;

        return new CredentialOffer($credentialOfferParameters, $uri);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(CredentialOffer::class, $this->sut());
    }


    public function testCanJsonSerialize(): void
    {
        $this->assertIsArray($this->sut($this->credentialOfferParametersMock)->jsonSerialize());
        $this->assertIsString($this->sut(false, uri: $this->uri)->jsonSerialize());
    }


    public function testThrowsIfNoParametersAndNoUri(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage('Invalid');

        $this->sut(false, false)->jsonSerialize();
    }
}
