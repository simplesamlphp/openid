<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(ParsedJws::class)]
final class ParsedJwsTest extends TestCase
{
    protected MockObject $jwsDecoratorMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksDecoratorFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $timestampValidationLeewayMock;

    protected MockObject $helpersMock;

    protected MockObject $jwsMock;

    protected MockObject $signatureMock;

    protected MockObject $jsonHelperMock;


    protected MockObject $claimFactoryMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'entity-statement+jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];

    protected array $expiredPayload = [
        'iat' => 1731175727,
        'nbf' => 1731175727,
        'exp' => 1731175727,
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'sub' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'jwks' => [
            'keys' => [
                [
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kty' => 'RSA',
                    // phpcs:ignore
                    'n' => 'pJgG9F_lwc2cFEC1l6q0fjJYxKPbtVGqJpDggDpDR8MgfbH0jUZP_RvhJGpl_09Bp-PfibLiwxchHZlrCx-fHQyGMaBRivUfq_p12ECEXMaFUcasCP6cyNrDfa5Uchumau4WeC21nYI1NMawiMiWFcHpLCQ7Ul8NMaCM_dkeruhm_xG0ZCqfwu30jOyCsnZdE0izJwPTfBRLpLyivu8eHpwjoIzmwqo8H-ZsbqR0vdRu20-MNS78ppTxwK3QmJhU6VO2r730F6WH9xJd_XUDuVeM4_6Z6WVDXw3kQF-jlpfcssPP303nbqVmfFZSUgS8buToErpMqevMIKREShsjMQ',
                    'e' => 'AQAB',
                    'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
                ],
            ],
        ],
        'metadata' => [
            'federation_entity' => [
                'organization_name' => 'Org ALeaf',
            ],
            'openid_relying_party' => [
                'redirect_uris' => [
                    'https://74-dap.localhost.markoivancic.from.hr/oidc/oidc-php-app-demo/callback.php',
                ],
                'response_types' => [
                    'code',
                ],
                'scope' => 'openid profile',
                'token_endpoint_auth_method' => 'self_signed_tls_client_auth',
                'contacts' => [
                    'rp_admins@rp.example.org',
                ],
                'jwks_uri' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/jwks',
                'signed_jwks_uri' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/signed-jwks',
            ],
        ],
        'authority_hints' => [
            'https://08-dap.localhost.markoivancic.from.hr/openid/entities/AIntermediate/',
        ],
        'trust_marks' => [
            [
                'id' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ABTrustAnchor/trust-mark/member',
                // phpcs:ignore
                'trust_mark' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6InRydXN0LW1hcmsrand0Iiwia2lkIjoiZnNRNDVGMEQ5MTZSZEtFZVRqdGE4RFlXaW9kanRob3VIclZXZ09YQnJrayJ9.eyJpYXQiOjE3MzQwMTcyMTcsIm5iZiI6MTczNDAxNzIxNywiZXhwIjoxNzM0MDIwODE3LCJpZCI6Imh0dHBzOlwvXC8wOC1kYXAubG9jYWxob3N0Lm1hcmtvaXZhbmNpYy5mcm9tLmhyXC9vcGVuaWRcL2VudGl0aWVzXC9BQlRydXN0QW5jaG9yXC90cnVzdC1tYXJrXC9tZW1iZXIiLCJpc3MiOiJodHRwczpcL1wvMDgtZGFwLmxvY2FsaG9zdC5tYXJrb2l2YW5jaWMuZnJvbS5oclwvb3BlbmlkXC9lbnRpdGllc1wvQUJUcnVzdEFuY2hvclwvIiwic3ViIjoiaHR0cHM6XC9cLzA4LWRhcC5sb2NhbGhvc3QubWFya29pdmFuY2ljLmZyb20uaHJcL29wZW5pZFwvZW50aXRpZXNcL0FMZWFmXC8ifQ.hbpq2-oPbn56WwDGLLcYaM7t8wZbipa_0FMlFT7nmRi6OZRibid5TGIBYs3Zk9nmNVZhzOCYO3inOIws6yJhpg6ogD32KpXet4oz8xeYftyw-xddb_sMf3gBPK5GChnqNsj71QJHZDYIUL3nILTySpnR2u7UK6gtmoosjxNINawM-teg0tIsOGaHuqDlAu9wSBI3PFxvXJJvi4mmMF3TosudexrpIHIBnNY_bvaSKJdzlmuSssWVAmIKp7O1IZLhn6eOzrhuktlGH5iltd77CnFxhdMyFjZrUOcT2MXIhZqWqpy-Uj-H2Bia63CwvmZ5DQa-WVYUSbxCEqJeeRqI0Q',
            ],
        ],
    ];

    protected array $validPayload;

    protected function setUp(): void
    {
        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createMock(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->timestampValidationLeewayMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);

        $this->jwsMock = $this->createMock(JWS::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($this->jwsMock);

        $this->signatureMock = $this->createMock(Signature::class);
        $this->jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureArrayWithValuesAsStrings')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
    }

    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $timestampValidationLeewayMock = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): ParsedJws {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $timestampValidationLeewayMock ??= $this->timestampValidationLeewayMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new ParsedJws(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $timestampValidationLeewayMock,
            $helpers,
            $claimFactory,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(ParsedJws::class, $this->sut());
    }

    public function testCanValidateByCallbacks(): void
    {
        $sut = new class (
            $this->jwsDecoratorMock,
            $this->jwsVerifierDecoratorMock,
            $this->jwksDecoratorFactoryMock,
            $this->jwsSerializerManagerDecoratorMock,
            $this->timestampValidationLeewayMock,
            $this->helpersMock,
            $this->claimFactoryMock,
        ) extends ParsedJws {
            protected function validate(): void
            {
                $this->validateByCallbacks($this->simulateOk(...));
            }

            protected function simulateOk(): void
            {
            }
        };

        $this->assertInstanceOf(ParsedJws::class, $sut);
    }

    public function testThrowsOnValidateByCallbacksError(): void
    {
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('not valid');

        /** @phpstan-ignore expr.resultUnused (Validation is invoked from constructor.) */
        new class (
            $this->jwsDecoratorMock,
            $this->jwsVerifierDecoratorMock,
            $this->jwksDecoratorFactoryMock,
            $this->jwsSerializerManagerDecoratorMock,
            $this->timestampValidationLeewayMock,
            $this->helpersMock,
            $this->claimFactoryMock,
        ) extends ParsedJws {
            protected function validate(): void
            {
                $this->validateByCallbacks($this->simulateError(...));
            }

            protected function simulateError(): never
            {
                throw new \Exception('Error');
            }
        };
    }

    public function testCanGetHeader(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertSame($this->sampleHeader, $this->sut()->getHeader());
    }

    public function testCanGetHeaderClaims(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertSame($this->sampleHeader['kid'], $this->sut()->getHeaderClaim('kid'));
        $this->assertSame($this->sampleHeader['kid'], $this->sut()->getKeyId());
        $this->assertSame($this->sampleHeader['typ'], $this->sut()->getType());
    }

    public function testThrowsOnGetHeaderError(): void
    {
        $this->jwsMock->method('getSignature')->willThrowException(new \Exception('Error'));

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('header');

        $this->sut()->getHeader();
    }

    public function testCanGetPayload(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();

        $this->assertSame($this->validPayload, $sut->getPayload());
        // Second call so that we verify that decoding happens only once.
        $this->assertSame($this->validPayload, $sut->getPayload());
    }

    public function testCanGetEmptyPayload(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('');
        $this->jsonHelperMock->expects($this->never())->method('decode');

        $this->sut()->getPayload();
    }

    public function testThrowsOnPayloadDecodingError(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->willThrowException(new \JsonException('Error'));

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('decode');

        $this->sut()->getPayload();
    }

    public function testCanGetPayloadClaims(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();

        $this->assertSame($this->validPayload['iss'], $sut->getPayloadClaim('iss'));
        $this->assertSame($this->validPayload['iss'], $sut->getIssuer());
        $this->assertSame($this->validPayload['sub'], $sut->getSubject());
        $this->assertSame($this->validPayload['exp'], $sut->getExpirationTime());
        $this->assertSame($this->validPayload['iat'], $sut->getIssuedAt());
    }

    public function testCanGetEmptyPayloadClaims(): void
    {

        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn([]);

        $sut = $this->sut();
        $this->assertNull($sut->getAudience());
        $this->assertNull($sut->getJwtId());
        $this->assertNull($sut->getExpirationTime());
        $this->assertNull($sut->getIssuedAt());
        $this->assertNull($sut->getIdentifier());
        $this->assertNull($sut->getIssuer());
    }

    public function testCanGetAudienceArrayFromString(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->willReturn(['aud' => 'sample']);

        $this->assertSame(['sample'], $this->sut()->getAudience());
    }

    public function testCanGetAudienceArrayFromArray(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->willReturn(['aud' => ['sample']]);

        $this->assertSame(['sample'], $this->sut()->getAudience());
    }

    public function testThrowsOnInvalidAudienceValue(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->willReturn(['aud' => 123]);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('audience');

        $this->sut()->getAudience();
    }

    public function testCanSerializeToToken(): void
    {
        $this->jwsSerializerManagerDecoratorMock->expects($this->once())->method('serialize')
            ->willReturn('token');

        $sut = $this->sut();

        $this->assertSame('token', $sut->getToken());
        // Ensure that serialization happens only once.
        $this->assertSame('token', $sut->getToken());
    }

    public function testCanVerifyWithKeySet(): void
    {
        $this->jwsVerifierDecoratorMock->expects($this->once())->method('verifyWithKeySet')
            ->willReturn(true);

        $this->sut()->verifyWithKeySet(['jwks']);
    }

    public function testThrowsOnVerifyWithKeySetError(): void
    {
        $this->jwsVerifierDecoratorMock->expects($this->once())->method('verifyWithKeySet')
            ->willReturn(false);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('signature');

        $this->sut()->verifyWithKeySet(['jwks']);
    }

    public function testThrowsIfExpired(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($this->expiredPayload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Expiration');

        $this->sut()->getExpirationTime();
    }

    public function testThrowsIfIssuedAtInTheFuture(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $payload = $this->validPayload;
        $payload['iat'] = time() + 60;
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Issued At');

        $this->sut()->getIssuedAt();
    }
}
