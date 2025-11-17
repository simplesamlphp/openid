<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\Factories;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\CredentialOfferException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsBag;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsValue;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferParameters;
use SimpleSAML\OpenID\VerifiableCredentials\Factories\CredentialOfferFactory;

#[\PHPUnit\Framework\Attributes\CoversClass(CredentialOfferFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CredentialOffer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CredentialOfferParameters::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CredentialOfferGrantsBag::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CredentialOfferGrantsValue::class)]
final class CredentialOfferFactoryTest extends TestCase
{
    protected MockObject $helpersMock;

    protected array $sampleParameters = [
        'credential_issuer' => 'https://example.com/issuer',
        'credential_configuration_ids' => ['credential-1', 'credential-2'],
        'grants' => [
            'urn:ietf:params:oauth:grant-type:pre-authorized_code' => [
                "pre-authorized_code" => "oaKazRN8I0IbtZ0C7JuMn5",
                "tx_code" => [
                    "length" => 4,
                    "input_mode" => "numeric",
                    "description" => "Please provide the one-time code that was sent via e-mail",
                ],
            ],
        ],
    ];


    protected function setUp(): void
    {
        $this->helpersMock = $this->createMock(Helpers::class);
    }


    protected function sut(
        ?Helpers $helpers = null,
    ): CredentialOfferFactory {
        $helpers ??= $this->helpersMock;

        return new CredentialOfferFactory($helpers);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(CredentialOfferFactory::class, $this->sut());
    }


    public function testCanBuildFromParameters(): void
    {
        $this->assertInstanceOf(
            CredentialOffer::class,
            $this->sut()->from($this->sampleParameters),
        );
    }


    public function testFromThrowsForInvalidGrantData(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage('Grants');

        $parameters = $this->sampleParameters;
        $parameters['grants']['urn:ietf:params:oauth:grant-type:pre-authorized_code'] = 123;
        $this->sut()->from($parameters);
    }


    public function testFromUri(): void
    {
        $this->assertInstanceOf(
            CredentialOffer::class,
            $this->sut()->from(uri: 'https://example.com/offer'),
        );
    }


    public function testFromThrowsIfBothParametersAndUriIsProvided(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage('Only one');

        $this->sut()->from($this->sampleParameters, 'https://example.com/offer');
    }
}
