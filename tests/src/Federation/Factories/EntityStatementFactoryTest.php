<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimBagFactory;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimFactory;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

#[CoversClass(EntityStatementFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(EntityStatement::class)]
class EntityStatementFactoryTest extends TestCase
{
    protected MockObject $signatureMock;
    protected MockObject $jwsMock;
    protected MockObject $jwsDecoratorMock;
    protected MockObject $jwsParserMock;
    protected MockObject $jwsVerifierMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $jwsSerializerManagerMock;
    protected MockObject $dateIntervalDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $trustMarkClaimFactoryMock;
    protected MockObject $trustMarkClaimBagFactoryMock;
    protected MockObject $jsonHelperMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'entity-statement+jwt',
        'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
    ];

    protected array $expiredPayload = [
        'iat' => 1734009487,
        'nbf' => 1734009487,
        'exp' => 1734009487,
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'sub' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'jwks' => [
            'keys' => [
                0 => [
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
                    0 => 'https://74-dap.localhost.markoivancic.from.hr/oidc/oidc-php-app-demo/callback.php',
                ],
                'response_types' => [
                    0 => 'code',
                ],
                'scope' => 'openid profile',
                'token_endpoint_auth_method' => 'self_signed_tls_client_auth',
                'contacts' => [
                    0 => 'rp_admins@rp.example.org',
                ],
                'jwks_uri' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/jwks',
                'signed_jwks_uri' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/signed-jwks',
            ],
        ],
        'authority_hints' => [
            0 => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/AIntermediate/',
        ],
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

        $this->jwsParserMock = $this->createMock(JwsParser::class);
        $this->jwsParserMock->method('parse')->willReturn($this->jwsDecoratorMock);

        $this->jwsVerifierMock = $this->createMock(JwsVerifier::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManager::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->trustMarkClaimFactoryMock = $this->createMock(TrustMarkClaimFactory::class);
        $this->trustMarkClaimBagFactoryMock = $this->createMock(TrustMarkClaimBagFactory::class);

        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
    }

    protected function sut(
        ?JwsParser $jwsParser = null,
        ?JwsVerifier $jwsVerifier = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManager $jwsSerializerManager = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?TrustMarkClaimFactory $trustMarkClaimFactory = null,
        ?TrustMarkClaimBagFactory $trustMarkClaimBagFactory = null,
    ): EntityStatementFactory {
        $jwsParser ??= $this->jwsParserMock;
        $jwsVerifier ??= $this->jwsVerifierMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManager ??= $this->jwsSerializerManagerMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $trustMarkClaimFactory ??= $this->trustMarkClaimFactoryMock;
        $trustMarkClaimBagFactory ??= $this->trustMarkClaimBagFactoryMock;

        return new EntityStatementFactory(
            $jwsParser,
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
        $this->assertInstanceOf(EntityStatementFactory::class, $this->sut());
    }

    public function testCanBuildFromToken(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            EntityStatement::class,
            $this->sut()->fromToken('token'),
        );
    }
}
