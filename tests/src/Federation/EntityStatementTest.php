<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimBagFactory;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

#[CoversClass(EntityStatement::class)]
#[UsesClass(ParsedJws::class)]
class EntityStatementTest extends TestCase
{
    protected MockObject $signatureMock;
    protected MockObject $jwsMock;
    protected MockObject $jwsDecoratorMock;
    protected MockObject $jwsVerifierMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $jwsSerializerManagerMock;
    protected MockObject $dateIntervalDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $jsonHelperMock;
    protected MockObject $trustMarkClaimFactoryMock;
    protected MockObject $trustMarkClaimBagFactoryMock;
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

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'entity-statement+jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];

    protected array $validPayload;

    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $this->jwsMock = $this->createMock(JWS::class);
        $this->jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.
        $this->jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($this->jwsMock);

        $this->jwsVerifierMock = $this->createMock(JwsVerifier::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManager::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);

        $this->trustMarkClaimFactoryMock = $this->createMock(TrustMarkClaimFactory::class);
        $this->trustMarkClaimBagFactoryMock = $this->createMock(TrustMarkClaimBagFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
    }

    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifier $jwsVerifier = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManager $jwsSerializerManager = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?TrustMarkClaimFactory $trustMarkClaimFactory = null,
        ?TrustMarkClaimBagFactory $trustMarkClaimBagFactory = null,
    ): EntityStatement {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifier ??= $this->jwsVerifierMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManager ??= $this->jwsSerializerManagerMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $trustMarkClaimFactory ??= $this->trustMarkClaimFactoryMock;
        $trustMarkClaimBagFactory ??= $this->trustMarkClaimBagFactoryMock;

        return new EntityStatement(
            $jwsDecorator,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $dateIntervalDecorator,
            $helpers,
            $trustMarkClaimFactory,
            $trustMarkClaimBagFactory,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(
            EntityStatement::class,
            $this->sut(),
        );
    }

    public function testThrowsOnInvalidJwks(): void
    {
        $this->validPayload['jwks'] = [];

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('JWKS');

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->sut();
    }

    public function testIsConfiguration(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertTrue($this->sut()->isConfiguration());
    }

    public function testIsNotConfiguration(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->validPayload['iss'] = 'something-else';
        // Authority hints should not be present if not configuration.
        unset($this->validPayload['authority_hints']);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertFalse($this->sut()->isConfiguration());
    }

    public function testVerifyWithKeySetRuns(): void
    {
        $this->jwsVerifierMock->expects($this->once())->method('verifyWithKeySet')
            ->willReturn(true);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->sut()->verifyWithKeySet();
    }

    public function testThrowsOnInvalidAuthorityHints(): void
    {
        $this->validPayload['authority_hints'] = 'invalid';

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Invalid');

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->sut();
    }

    public function testThrowsOnEmptyAuthorityHints(): void
    {
        $this->validPayload['authority_hints'] = [];

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Empty');

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->sut();
    }

    public function testThrowsIfAuthorityHintsNotInConfigurationStatement(): void
    {
        $this->validPayload['iss'] = 'something-else';

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('non-configuration');

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->sut();
    }

    public function testTrustMarksAreOptional(): void
    {
        unset($this->validPayload['trust_marks']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(
            EntityStatement::class,
            $this->sut(),
        );
    }

    public function testThrowsOnInvalidTrustMarks(): void
    {
        $this->validPayload['trust_marks'] = 'invalid';

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Invalid Trust Marks');

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->sut();
    }

    public function testThrowsOnInvalidTypeHeader(): void
    {
        $this->sampleHeader['typ'] = 'invalid';

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Invalid Type header');

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->sut();
    }

    public function testCanGetFederationFetchEndpoint(): void
    {
        $payload = $this->validPayload;
        $payload['metadata']['federation_entity']['federation_fetch_endpoint'] = 'uri';


        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertSame('uri', $this->sut()->getFederationFetchEndpoint());
    }

    public function testFederationFetchEndpointIsOptional(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertNull($this->sut()->getFederationFetchEndpoint());
    }

    public function testMetadataIsOptional(): void
    {
        $payload = $this->validPayload;
        unset($payload['metadata']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertNull($this->sut()->getMetadata());
    }

    public function testThrowsForInvalidMetadataClaim(): void
    {
        $payload = $this->validPayload;
        $payload['metadata'] = 'invalid';

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Metadata');

        $this->sut()->getMetadata();
    }

    public function testCanGetMetadataPolicyClaim(): void
    {
        $payload = $this->validPayload;
        $payload['sub'] = 'something-else';
        unset($payload['authority_hints']);
        $payload['metadata_policy'] = [
            'openid_relying_party' => [
                'contacts' => [
                    'add' => ['helpdesk@subordinate.org'],
                ],
            ],
        ];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertSame($payload['metadata_policy'], $this->sut()->getMetadataPolicy());
    }

    public function testThrowsForInvalidMetadataPolicyClaim(): void
    {
        $payload = $this->validPayload;
        $payload['sub'] = 'something-else';
        unset($payload['authority_hints']);
        $payload['metadata_policy'] = 'invalid';

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Metadata Policy');

        $this->sut()->getMetadataPolicy();
    }

    public function testThrowsIfMetadataPolicyIsSetInConfigurationStatement(): void
    {
        $payload = $this->validPayload;
        unset($payload['authority_hints']);
        $payload['metadata_policy'] = [
            'openid_relying_party' => [
                'contacts' => [
                    'add' => ['helpdesk@subordinate.org'],
                ],
            ],
        ];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('configuration');

        $this->sut()->getMetadataPolicy();
    }
}
