<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Did;
use SimpleSAML\OpenID\Did\DidJwkDocumentResolver;
use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidKeyDocumentResolver;
use SimpleSAML\OpenID\Did\DidKeyJwkResolver;
use SimpleSAML\OpenID\Did\DidWebResolver;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Network\AddressPinner;
use SimpleSAML\OpenID\Network\AddressResolver;
use SimpleSAML\OpenID\Network\AddressValidator;
use SimpleSAML\OpenID\Network\DestinationGuardMiddleware;
use SimpleSAML\OpenID\Network\DestinationPolicy;

#[CoversClass(Did::class)]
#[UsesClass(Did\AbstractDidResolver::class)]
#[UsesClass(Did\DidKeyJwkResolver::class)]
#[UsesClass(Did\DidJwkResolver::class)]
#[UsesClass(Did\DidJwkDocumentResolver::class)]
#[UsesClass(Did\DidKeyDocumentResolver::class)]
#[UsesClass(Did\DidWebResolver::class)]
#[UsesClass(Did\DidDocument::class)]
#[UsesClass(Did\DidUrl::class)]
#[UsesClass(Did\MultibaseKeyDecoder::class)]
#[UsesClass(Did\PublicJwkValidator::class)]
#[UsesClass(Did\ResolvedVerificationMethod::class)]
#[UsesClass(Did\VerificationMethod::class)]
#[UsesClass(Did\Factories\DidDocumentFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
#[UsesClass(HttpClientDecoratorFactory::class)]
#[UsesClass(DestinationPolicy::class)]
#[UsesClass(DestinationGuardMiddleware::class)]
#[UsesClass(AddressPinner::class)]
#[UsesClass(AddressResolver::class)]
#[UsesClass(AddressValidator::class)]
#[UsesClass(VerificationRelationshipEnum::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Base64Url::class)]
final class DidTest extends TestCase
{
    /** A real Ed25519 did:key multibase value. */
    private const ED25519_MULTIBASE = 'z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK';

    private const DID_KEY = 'did:key:' . self::ED25519_MULTIBASE;

    private const JWK = [
        'kty' => 'EC',
        'crv' => 'P-256',
        'x' => 'dDfIibQM-949qf4jj-8mBY4Azq34ygSGhzd8AT2mx6s',
        'y' => 'VUyI8G-OYirMrrsCB9lvUbr6Wjq2ef73ne_paBqLPxw',
    ];


    protected function sut(
        ?DateInterval $maxCacheDuration = null,
        ?DestinationPolicy $destinationPolicy = null,
    ): Did {
        $maxCacheDuration ??= new DateInterval('PT6H');

        return new Did(
            $maxCacheDuration,
            null,
            null,
            HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
            $destinationPolicy,
        );
    }


    protected function didJwk(): string
    {
        $helpers = new Helpers();

        return 'did:jwk:' . $helpers->base64Url()->encode($helpers->json()->encode(self::JWK));
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Did::class, $this->sut());
    }


    public function testCanBuildTools(): void
    {
        $this->assertInstanceOf(
            DidKeyJwkResolver::class,
            $this->sut()->didKeyResolver(),
        );

        $this->assertInstanceOf(
            DidJwkResolver::class,
            $this->sut()->didJwkResolver(),
        );

        $this->assertInstanceOf(
            MultibaseKeyDecoder::class,
            $this->sut()->multibaseKeyDecoder(),
        );

        $this->assertInstanceOf(
            Did\PublicJwkValidator::class,
            $this->sut()->publicJwkValidator(),
        );

        $this->assertInstanceOf(
            Did\Factories\DidDocumentFactory::class,
            $this->sut()->didDocumentFactory(),
        );

        $this->assertInstanceOf(
            Helpers::class,
            $this->sut()->helpers(),
        );
    }


    public function testCanBuildResolvers(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(DidJwkDocumentResolver::class, $sut->didJwkDocumentResolver());
        $this->assertInstanceOf(DidKeyDocumentResolver::class, $sut->didKeyDocumentResolver());
        $this->assertInstanceOf(DidWebResolver::class, $sut->didWebResolver());
    }


    /**
     * Resolving a did:jwk or did:key identifier needs nothing but the identifier. Guzzle refuses to build
     * a client at all where neither cURL nor allow_url_fopen is available, so an eagerly built registry
     * would make an entirely local resolution fail over a transport it never uses.
     */
    public function testALocalMethodNeverBuildsTheHttpClient(): void
    {
        $sut = new class extends Did {
            protected function httpClientDecorator(): HttpClientDecorator
            {
                throw new RuntimeException('The HTTP client was built for a local resolution.');
            }
        };

        $this->assertSame(['did:jwk', 'did:key', 'did:web'], $sut->supportedMethods());
        $this->assertSame(self::DID_KEY, $sut->resolveDocument(self::DID_KEY)->getId());
    }


    /**
     * The key a resolver is registered under and the method it accepts are two statements of the same
     * thing. A resolver reached under the wrong key would resolve an identifier of one method with the
     * rules of another, so they are compared rather than assumed to agree.
     */
    public function testRefusesAResolverRegisteredUnderAnotherMethod(): void
    {
        $sut = new class extends Did {
            /**
             * @return array<string, callable(): \SimpleSAML\OpenID\Did\DidResolverInterface>
             */
            protected function didResolverBuilders(): array
            {
                return [
                    'web' => $this->didJwkDocumentResolver(...),
                ];
            }
        };

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('is registered to a resolver that handles');

        $sut->resolveDocument('did:web:example.org');
    }


    /**
     * The registry is what resolution dispatches over, so what it can be asked to publish and what it can
     * actually resolve are the same list.
     */
    public function testSupportedMethodsNamesEveryRegisteredMethod(): void
    {
        $this->assertSame(['did:jwk', 'did:key', 'did:web'], $this->sut()->supportedMethods());
    }


    public function testResolvesAVerificationMethodForDidJwk(): void
    {
        $did = $this->didJwk();

        $resolved = $this->sut()->resolveVerificationMethod(
            $did . '#0',
            VerificationRelationshipEnum::Authentication,
        );

        $this->assertSame($did, $resolved->getDid());
        $this->assertSame($did . '#0', $resolved->getId()->getValue());
        $this->assertSame(self::JWK, $resolved->getPublicJwk());
    }


    public function testResolvesAVerificationMethodForDidKey(): void
    {
        $resolved = $this->sut()->resolveVerificationMethod(
            self::DID_KEY . '#' . self::ED25519_MULTIBASE,
        );

        $this->assertSame(self::DID_KEY, $resolved->getDid());
        $this->assertSame('Ed25519', $resolved->getPublicJwk()['crv']);
    }


    /**
     * A method present in the document but absent from the relationship being asked for is a rejection,
     * never a fallback.
     */
    public function testRefusesAVerificationMethodOutsideTheRequestedRelationship(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('keyAgreement relationship');

        $this->sut()->resolveVerificationMethod(
            self::DID_KEY . '#' . self::ED25519_MULTIBASE,
            VerificationRelationshipEnum::KeyAgreement,
        );
    }


    public function testResolvesADocument(): void
    {
        $this->assertSame(self::DID_KEY, $this->sut()->resolveDocument(self::DID_KEY)->getId());
    }


    /**
     * A caller asking for a document by the id of one of its keys meant resolveVerificationMethod().
     */
    public function testResolveDocumentRefusesADidUrl(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('bare DID');

        $this->sut()->resolveDocument(self::DID_KEY . '#' . self::ED25519_MULTIBASE);
    }


    public function testRefusesAnUnsupportedMethod(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('is not supported');

        $this->sut()->resolveVerificationMethod('did:example:123#key-1');
    }


    public function testRefusesAMalformedDidUrl(): void
    {
        $this->expectException(DidException::class);

        $this->sut()->resolveVerificationMethod('not-a-did');
    }


    /**
     * Public destinations only, https only, pinning required: what a fetch driven by whoever is being
     * authenticated has to run under.
     */
    public function testDestinationPolicyDefaultsToRequiredPinningOverPublicDestinationsOnly(): void
    {
        $destinationPolicy = $this->sut()->destinationPolicy();

        $this->assertSame(AddressPinningModeEnum::Required, $destinationPolicy->getAddressPinningMode());
        $this->assertSame(['https'], $destinationPolicy->getAllowedSchemes());
        $this->assertSame([], $destinationPolicy->getAllowedHosts());
        $this->assertSame([], $destinationPolicy->getAllowedCidrs());
    }


    /**
     * Disabled is for the deployment that can not pin and is not thereby unprotected: one behind a forward
     * proxy, where the proxy resolves the destination and is doing the egress control pinning approximates.
     * Without a way to say so, such a deployment finds every did:web resolution refused and its only way
     * out is constructing the resolver by hand, which skips every other guarantee the facade makes.
     */
    public function testHonoursTheAddressPinningMode(): void
    {
        $sut = new Did(addressPinningMode: AddressPinningModeEnum::Disabled);

        $this->assertSame(
            AddressPinningModeEnum::Disabled,
            $sut->destinationPolicy()->getAddressPinningMode(),
        );
    }


    public function testRefusesPreferredPinningPassedAsAnArgument(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('preferred address pinning');

        new Did(addressPinningMode: AddressPinningModeEnum::Preferred);
    }


    public function testKeepsASuppliedDestinationPolicy(): void
    {
        $destinationPolicy = new DestinationPolicy(
            allowedHosts: ['idp.internal.example.org'],
            addressPinningMode: AddressPinningModeEnum::Disabled,
        );

        $this->assertSame($destinationPolicy, $this->sut(null, $destinationPolicy)->destinationPolicy());
    }


    /**
     * Checked on a supplied policy too, so that assembling one directly is not a way around the rule.
     * Preferred proceeds unpinned wherever the connection can not be pinned.
     */
    public function testRefusesASuppliedPolicyWithPreferredPinning(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('preferred address pinning');

        $this->sut(null, new DestinationPolicy(addressPinningMode: AddressPinningModeEnum::Preferred));
    }
}
