<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Did\DidDocument;
use SimpleSAML\OpenID\Did\DidJwkDocumentResolver;
use SimpleSAML\OpenID\Did\DidJwkResolver;
use SimpleSAML\OpenID\Did\DidKeyDocumentResolver;
use SimpleSAML\OpenID\Did\DidKeyJwkResolver;
use SimpleSAML\OpenID\Did\DidResolverInterface;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\DidWebResolver;
use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;
use SimpleSAML\OpenID\Did\MultibaseKeyDecoder;
use SimpleSAML\OpenID\Did\PublicJwkValidator;
use SimpleSAML\OpenID\Did\ResolvedVerificationMethod;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Network\DestinationPolicy;

/**
 * Entry point for Decentralized Identifiers.
 *
 * Resolution is dispatched over a registry of one resolver per supported method, so a caller hands over a
 * DID URL rather than deciding which method it names and reaching for the matching tool. The registry is
 * enumerable, since a deployment publishing which binding methods it accepts has to say so from the same
 * list resolution actually uses.
 *
 * Unlike {@see Federation} and {@see TokenStatusList}, this accepts neither a pre-built HTTP client nor
 * free-form client configuration. Both are ways to undo the hardening a did:web fetch depends on: a
 * pre-built client is returned unguarded by the destination policy, and supplied configuration is merged
 * over the timeout and redirect defaults. Since the identifier deciding where a fetch goes is supplied by
 * whoever is being authenticated, neither is offered here.
 *
 * @see \SimpleSAML\Test\OpenID\DidTest
 */
class Did
{
    protected DateIntervalDecorator $maxCacheDurationDecorator;

    protected ?CacheDecorator $cacheDecorator;

    protected int $maxFetchSizeBytes;

    protected DestinationPolicy $destinationPolicy;

    protected ?DidKeyJwkResolver $didKeyResolver = null;

    protected ?DidJwkResolver $didJwkResolver = null;

    protected ?DidJwkDocumentResolver $didJwkDocumentResolver = null;

    protected ?DidKeyDocumentResolver $didKeyDocumentResolver = null;

    protected ?DidWebResolver $didWebResolver = null;

    protected ?MultibaseKeyDecoder $multibaseKeyDecoder = null;

    protected ?PublicJwkValidator $publicJwkValidator = null;

    protected ?DidDocumentFactory $didDocumentFactory = null;

    protected ?HttpClientDecorator $httpClientDecorator = null;

    protected ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null;

    protected ?CacheDecoratorFactory $cacheDecoratorFactory = null;

    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected ?Helpers $helpers = null;


    /**
     * @param \DateInterval $maxCacheDuration Ceiling for how long a resolved DID document is cached. A DID
     *        document carries no expiry of its own, so this is the whole of its freshness rule.
     * @param ?\Psr\SimpleCache\CacheInterface $cache Where resolved documents are kept. Without one, every
     *        request naming a did:web identifier is another outbound fetch.
     * @param int $maxFetchSizeBytes Maximum response body size read for a DID document.
     * @param ?\SimpleSAML\OpenID\Network\DestinationPolicy $destinationPolicy Where DID document fetches may
     *        be sent. Defaults to {@see DidWebResolver::buildDestinationPolicy()}: public destinations only,
     *        https only, address pinning required. Supply one only to add exemptions belonging to DID
     *        resolution alone, and never the deployment's federation ones - those were granted so it could
     *        reach addresses it operates itself, and handing them to DID resolution would let whoever
     *        supplies a DID name any of them.
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function __construct(
        DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        int $maxFetchSizeBytes = HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
        ?DestinationPolicy $destinationPolicy = null,
    ) {
        $this->maxCacheDurationDecorator = $this->dateIntervalDecoratorFactory()->build($maxCacheDuration);
        $this->cacheDecorator = is_null($cache) ? null : $this->cacheDecoratorFactory()->build($cache);
        $this->maxFetchSizeBytes = max(1, $maxFetchSizeBytes);

        if ($destinationPolicy instanceof DestinationPolicy) {
            // A supplied policy goes through the same refusal the helper makes, so that assembling one
            // directly is not a way around the rule that DID resolution never runs unpinned.
            DidWebResolver::assertAddressPinningModeIsUsable($destinationPolicy->getAddressPinningMode());
        }

        $this->destinationPolicy = $destinationPolicy ??
        DidWebResolver::buildDestinationPolicy(logger: $this->logger);
    }


    /**
     * Resolve a DID URL to the single verification method it names.
     *
     * @param string $didUrl The verification method id, which for a proof is what the `kid` header carries.
     *        The document is resolved for the DID that id sits under.
     * @param ?\SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum $relationship The relationship the
     *        method has to appear in. Passing none searches only the document's own verificationMethod
     *        entries, which is deliberately not the same as searching every relationship.
     * @param ?float $deadlineTimestamp The point in time by which the whole operation this belongs to has to
     *        be finished, as a unix timestamp with fractions. A request validating several proofs bounds
     *        them together by passing one.
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function resolveVerificationMethod(
        string $didUrl,
        ?VerificationRelationshipEnum $relationship = null,
        ?float $deadlineTimestamp = null,
    ): ResolvedVerificationMethod {
        $parsedDidUrl = new DidUrl($didUrl);

        $didDocument = $this->resolverFor($parsedDidUrl)->resolveDocument(
            $parsedDidUrl->getDid(),
            $deadlineTimestamp,
        );

        return $didDocument->resolveVerificationMethod($parsedDidUrl, $relationship);
    }


    /**
     * Resolve a bare DID to its document.
     *
     * A DID URL naming something inside a document is refused rather than reduced to the DID it sits under,
     * since a caller asking for a document by the id of one of its keys meant
     * {@see resolveVerificationMethod()}.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function resolveDocument(string $did, ?float $deadlineTimestamp = null): DidDocument
    {
        return $this->resolverFor(new DidUrl($did))->resolveDocument($did, $deadlineTimestamp);
    }


    /**
     * The DID methods this instance can resolve, spelled as the DID Specification Registries spell them,
     * which is also the spelling OpenID4VCI's cryptographic_binding_methods_supported uses.
     *
     * @return list<string>
     */
    public function supportedMethods(): array
    {
        return array_map(
            static fn(string $methodName): string => DidUrl::PREFIX . $methodName,
            array_keys($this->didResolverBuilders()),
        );
    }


    /**
     * The policy deciding where DID document fetches may be sent, so that the same rules can be applied
     * before a destination is ever fetched.
     */
    public function destinationPolicy(): DestinationPolicy
    {
        return $this->destinationPolicy;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function resolverFor(DidUrl $didUrl): DidResolverInterface
    {
        $didResolverBuilder = $this->didResolverBuilders()[$didUrl->getMethod()] ?? null;

        if (!is_callable($didResolverBuilder)) {
            throw new DidException(
                sprintf('DID method "%s" is not supported.', $didUrl->getMethod()),
            );
        }

        $didResolver = $didResolverBuilder();

        // The key a resolver is registered under and the method it accepts are two statements of the same
        // thing, so they are compared rather than assumed to agree. A resolver reached under the wrong key
        // would resolve an identifier of one method with the rules of another.
        if ($didResolver->methodName() !== $didUrl->getMethod()) {
            throw new DidException(
                sprintf(
                    'DID method "%s" is registered to a resolver that handles "%s".',
                    $didUrl->getMethod(),
                    $didResolver->methodName(),
                ),
            );
        }

        return $didResolver;
    }


    /**
     * How to build the resolver for each method this instance supports, keyed by that method.
     *
     * Builders rather than instances, so that enumerating the methods - or resolving a did:jwk or did:key
     * identifier, which needs nothing but the identifier - never constructs the HTTP client did:web needs.
     * Guzzle refuses to build a client at all where neither cURL nor allow_url_fopen is available, so an
     * eager registry would make an entirely local resolution fail over a transport it never uses.
     *
     * @return array<string, callable(): \SimpleSAML\OpenID\Did\DidResolverInterface>
     */
    protected function didResolverBuilders(): array
    {
        return [
            DidJwkDocumentResolver::METHOD => $this->didJwkDocumentResolver(...),
            DidKeyDocumentResolver::METHOD => $this->didKeyDocumentResolver(...),
            DidWebResolver::METHOD => $this->didWebResolver(...),
        ];
    }


    public function didJwkDocumentResolver(): DidJwkDocumentResolver
    {
        return $this->didJwkDocumentResolver ??= new DidJwkDocumentResolver(
            $this->didDocumentFactory(),
        );
    }


    public function didKeyDocumentResolver(): DidKeyDocumentResolver
    {
        return $this->didKeyDocumentResolver ??= new DidKeyDocumentResolver(
            $this->didDocumentFactory(),
        );
    }


    public function didWebResolver(): DidWebResolver
    {
        return $this->didWebResolver ??= new DidWebResolver(
            $this->httpClientDecorator(),
            $this->didDocumentFactory(),
            $this->helpers(),
            $this->maxCacheDurationDecorator,
            $this->cacheDecorator,
            $this->logger,
        );
    }


    public function didKeyResolver(): DidKeyJwkResolver
    {
        return $this->didKeyResolver ??= new DidKeyJwkResolver(
            $this->helpers(),
            $this->multibaseKeyDecoder(),
        );
    }


    public function didJwkResolver(): DidJwkResolver
    {
        return $this->didJwkResolver ??= new DidJwkResolver(
            $this->helpers(),
        );
    }


    public function multibaseKeyDecoder(): MultibaseKeyDecoder
    {
        return $this->multibaseKeyDecoder ??= new MultibaseKeyDecoder(
            $this->helpers(),
        );
    }


    public function publicJwkValidator(): PublicJwkValidator
    {
        return $this->publicJwkValidator ??= new PublicJwkValidator();
    }


    public function didDocumentFactory(): DidDocumentFactory
    {
        return $this->didDocumentFactory ??= new DidDocumentFactory(
            $this->multibaseKeyDecoder(),
            $this->didJwkResolver(),
            $this->publicJwkValidator(),
        );
    }


    /**
     * Built on first use rather than in the constructor, so that a caller resolving only self describing
     * identifiers never pays for an HTTP client. {@see didResolverBuilders()} is what makes that hold, by
     * leaving the did:web resolver unbuilt until an identifier of that method is dispatched.
     *
     * Neither a client nor client configuration is accepted, so what this builds is the only client a
     * did:web fetch can run under, and it is not handed out.
     */
    protected function httpClientDecorator(): HttpClientDecorator
    {
        return $this->httpClientDecorator ??= $this->httpClientDecoratorFactory()->build(
            maxFetchSizeBytes: $this->maxFetchSizeBytes,
            destinationPolicy: $this->destinationPolicy,
        );
    }


    public function httpClientDecoratorFactory(): HttpClientDecoratorFactory
    {
        return $this->httpClientDecoratorFactory ??= new HttpClientDecoratorFactory($this->logger);
    }


    public function cacheDecoratorFactory(): CacheDecoratorFactory
    {
        return $this->cacheDecoratorFactory ??= new CacheDecoratorFactory();
    }


    public function dateIntervalDecoratorFactory(): DateIntervalDecoratorFactory
    {
        return $this->dateIntervalDecoratorFactory ??= new DateIntervalDecoratorFactory();
    }


    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }
}
