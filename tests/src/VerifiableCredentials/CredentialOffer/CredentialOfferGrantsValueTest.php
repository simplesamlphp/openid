<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\CredentialOffer;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\GrantTypesEnum;
use SimpleSAML\OpenID\Exceptions\CredentialOfferException;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsValue;

#[\PHPUnit\Framework\Attributes\CoversClass(CredentialOfferGrantsValue::class)]
final class CredentialOfferGrantsValueTest extends TestCase
{
    protected array $samplePreAuthCode = [
        "pre-authorized_code" => "oaKazRN8I0IbtZ0C7JuMn5",
        "tx_code" => [
            "length" => 4,
            "input_mode" => "numeric",
            "description" => "Please provide the one-time code that was sent via e-mail",
        ],
    ];

    protected array $sampleAuthCode = [
        "issuer_state" => "eyJhbGciOiJSU0Et...FYUaBy",
        "authorization_server" => "https://example.com/oidc/auth",
    ];

    protected string $grantType;


    protected function setUp(): void
    {
        $this->grantType = GrantTypesEnum::PreAuthorizedCode->value;
    }


    protected function sut(
        ?string $grantType = null,
        ?array $claims = null,
    ): CredentialOfferGrantsValue {
        $grantType ??= $this->grantType;
        $claims ??= $this->samplePreAuthCode;

        return new CredentialOfferGrantsValue($grantType, $claims);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            CredentialOfferGrantsValue::class,
            $this->sut(),
        );
    }


    public function testThrowsOnInvalidAuthCodeIssuerState(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage("Issuer State");

        $claims = $this->sampleAuthCode;
        $claims['issuer_state'] = 123;
        $this->sut(GrantTypesEnum::AuthorizationCode->value, $claims);
    }


    public function testThrowsOnInvalidAuthCodeAuthorizationServer(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage("Authorization Server");

        $claims = $this->sampleAuthCode;
        $claims['authorization_server'] = 123;
        $this->sut(GrantTypesEnum::AuthorizationCode->value, $claims);
    }


    public function testThrowsOnInvalidPreAuthCodeValue(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage("Pre-authorized Code");

        $claims = $this->samplePreAuthCode;
        $claims['pre-authorized_code'] = 123;
        $this->sut(GrantTypesEnum::PreAuthorizedCode->value, $claims);
    }


    public function testThrowsOnInvalidPreAuthCodeTxCodeValue(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage("TxCode");

        $claims = $this->samplePreAuthCode;
        $claims['tx_code'] = 123;
        $this->sut(GrantTypesEnum::PreAuthorizedCode->value, $claims);
    }


    public function testThrowsOnInvalidPreAuthCodeTxCodeInputModeValue(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage("Input Mode");

        $claims = $this->samplePreAuthCode;
        $claims['tx_code']['input_mode'] = 123;
        $this->sut(GrantTypesEnum::PreAuthorizedCode->value, $claims);
    }


    public function testThrowsOnInvalidPreAuthCodeTxCodeLengthValue(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage("Length");

        $claims = $this->samplePreAuthCode;
        $claims['tx_code']['length'] = "123";
        $this->sut(GrantTypesEnum::PreAuthorizedCode->value, $claims);
    }


    public function testThrowsOnInvalidPreAuthCodeTxCodeDescriptionValue(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage("Description");

        $claims = $this->samplePreAuthCode;
        $claims['tx_code']['description'] = 123;
        $this->sut(GrantTypesEnum::PreAuthorizedCode->value, $claims);
    }


    public function testThrowsOnInvalidPreAuthCodeAuthorizationServerValue(): void
    {
        $this->expectException(CredentialOfferException::class);
        $this->expectExceptionMessage("Authorization Server");

        $claims = $this->samplePreAuthCode;
        $claims['authorization_server'] = 123;
        $this->sut(GrantTypesEnum::PreAuthorizedCode->value, $claims);
    }


    public function testJsonSerialize(): void
    {
        $sut = $this->sut(GrantTypesEnum::PreAuthorizedCode->value, $this->samplePreAuthCode);
        $this->assertSame(
            [GrantTypesEnum::PreAuthorizedCode->value => $this->samplePreAuthCode],
            $sut->jsonSerialize(),
        );
    }
}
