<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use JsonException;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Network\DestinationPolicy;
use Throwable;

/**
 * Resolves a did:web identifier to its DID document.
 *
 * The identifier is supplied by whoever is being authenticated, and it decides the URL this deployment then
 * fetches, which is what makes an unguarded resolver a way into the deployment's own network. So the transform
 * is deliberately literal: the only percent-encoded triplet the method specification decodes is the port
 * separator, and it is therefore the only one accepted anywhere in the identifier. Everything else that could
 * restructure the URL - an encoded slash, question mark, hash or at sign, a relative path segment, an IP
 * literal host - is refused rather than normalized into something fetchable.
 *
 * The client this is given is expected to carry the destination policy from {@see buildDestinationPolicy()}.
 * No request options are accepted from callers, because per-request options replace the client configuration
 * rather than merging with it, so accepting any would be accepting a way to undo the hardening.
 *
 * Failures are reported as a single generic message. A policy refusal, a name that does not resolve and a
 * connection that times out each tell whoever supplied the DID something different about this deployment's
 * network, and together they turn resolution into a map of it. The detail goes to the log instead.
 *
 * @see https://w3c-ccg.github.io/did-method-web/
 * @see \SimpleSAML\Test\OpenID\Did\DidWebResolverTest
 */
class DidWebResolver extends AbstractDidResolver
{
    /** The DID method this resolver handles. */
    public const METHOD = 'web';

    /**
     * How deeply a DID document may nest. Real ones reach about five levels, so this is generous while still
     * refusing a document written to make the parser do the work.
     */
    public const DEFAULT_MAX_JSON_DEPTH = 32;

    /**
     * How long a failed resolution is remembered, so that a DID which cannot be resolved does not turn every
     * request naming it into another outbound fetch. Deliberately short: whoever owns the DID may be in the
     * middle of fixing it. Zero disables it.
     */
    public const DEFAULT_NEGATIVE_CACHE_DURATION_SECONDS = 60;

    /** What every retrieval failure says, whatever actually went wrong. */
    protected const RETRIEVAL_FAILURE_MESSAGE = 'Could not retrieve the DID document.';

    /**
     * Longest failure message this resolver repeats, remembers or logs.
     *
     * A document failure names what was wrong with the document, and the parser builds that message by
     * interpolating values out of the document itself: a key type, a member name, a curve. Those arrive from
     * the network, so without a bound the message is exactly as long as whoever published the document chose,
     * and it travels into the cache, into the caller, and into the log. Every message this code produces is
     * comfortably shorter than this.
     */
    protected const MAX_FAILURE_MESSAGE_LENGTH = 256;

    protected const DOCUMENT_FILENAME = 'did.json';

    protected const WELL_KNOWN_PATH_SEGMENT = '.well-known';

    protected const CACHE_KEY_PREFIX = 'openid.did.web';

    protected const CACHE_ELEMENT_DOCUMENT = 'document';

    protected const CACHE_ELEMENT_FAILURE = 'failure';

    /** Longest a domain name may be, per RFC 1035. */
    protected const MAX_HOST_LENGTH = 253;

    protected const MAX_PORT = 65535;

    /** One DNS label: alphanumeric at both ends, hyphens allowed between, at most 63 characters. */
    protected const HOST_LABEL = '[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?';

    /**
     * A domain name of at least two labels. Two rather than one because a single label is an internal name,
     * which is the kind of destination this policy exists to keep a wallet away from.
     *
     * The top level label is not required to be alphabetic: an internationalized one in A-label form, such as
     * the xn--p1ai that carries .рф, is a perfectly ordinary DNS host and carries hyphens and digits.
     */
    protected const HOST_PATTERN = '/^(?:' . self::HOST_LABEL . '\.)+' . self::HOST_LABEL . '$/D';

    /**
     * A top level label made only of digits, which is what an IP address written in an unusual base ends in
     * and what no domain name ends in. The method specification prohibits an IP literal host, and this
     * catches the spellings that filter_var does not recognise as one.
     */
    protected const NUMERIC_TOP_LABEL_PATTERN = '/\.[0-9]+$/D';

    /**
     * A label that a resolver may read as a number rather than as a name: decimal, or a hex literal. A host
     * whose labels are all of this shape is an IP address in disguise, and inet_aton will read it as one even
     * though filter_var does not recognise the spelling.
     */
    protected const IP_LIKE_LABEL_PATTERN = '/^(?:[0-9]+|0[xX][0-9A-Fa-f]+)$/D';

    /** A decimal port, without a leading zero. */
    protected const PORT_PATTERN = '/^[1-9][0-9]{0,4}$/D';


    /**
     * @param \SimpleSAML\OpenID\Decorators\HttpClientDecorator $httpClientDecorator A client built with the
     *        destination policy from {@see buildDestinationPolicy()} and with the library's own redirect and
     *        timeout defaults. This resolver adds no request options of its own beyond a duration ceiling, so
     *        whatever bounds that client carries are the bounds a fetch runs under.
     * @param ?\SimpleSAML\OpenID\Decorators\CacheDecorator $cacheDecorator Where resolved documents are kept.
     *        The cache is addressed directly rather than through ArtifactFetcher, whose own cache helpers
     *        debug-log the whole artifact, which a DID document must not be.
     * @param ?int $maxDocumentSizeBytes Overrides the client's configured maximum for this resolver's
     *        fetches. Null keeps whatever the client was built with.
     * @param bool $requireControllerToMatchSubject Whether every verification method in a resolved document
     *        must be controlled by the document subject. A profile policy rather than a syntax rule, so it is
     *        overridable, but it defaults to on.
     */
    public function __construct(
        protected readonly HttpClientDecorator $httpClientDecorator,
        protected readonly DidDocumentFactory $didDocumentFactory,
        protected readonly Helpers $helpers,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly ?int $maxDocumentSizeBytes = null,
        protected readonly int $maxJsonDepth = self::DEFAULT_MAX_JSON_DEPTH,
        protected readonly int $negativeCacheDurationSeconds = self::DEFAULT_NEGATIVE_CACHE_DURATION_SECONDS,
        protected readonly bool $requireControllerToMatchSubject = true,
    ) {
    }


    /**
     * The destination policy did:web resolution has to run under.
     *
     * Public destinations only and https only, with exemptions that belong to DID resolution alone. What
     * matters is not that there are no exemptions but that they are not the deployment's federation ones:
     * those were granted so it could reach addresses it operates itself, and handing them to DID resolution
     * would let whoever supplies a DID name any of them. Never build this from the federation configuration.
     *
     * @param \SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum $addressPinningMode Required by default.
     *        Preferred is refused rather than defaulted away from: it proceeds unpinned wherever the cURL
     *        handler is missing, which leaves the rebinding window open on exactly the fetches that are
     *        driven from outside. A deployment that controls egress by other means may say so with Disabled.
     * @param list<string> $allowedHosts Non-public destinations this deployment nonetheless permits DID
     *        resolution to reach, whatever they resolve to. Empty by default, and it belongs empty in
     *        production. The case it exists for is a deployment whose own hostname resolves to an address
     *        inside a container network, which is how a development or test environment is usually built,
     *        and where a public-only policy would otherwise make did:web untestable rather than safe.
     * @param list<string> $allowedCidrs Ranges permitted alongside the public ones, as CIDR, with the same
     *        warning. Use the narrowest range that covers the destination.
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public static function buildDestinationPolicy(
        ?LoggerInterface $logger = null,
        AddressPinningModeEnum $addressPinningMode = AddressPinningModeEnum::Required,
        array $allowedHosts = [],
        array $allowedCidrs = [],
    ): DestinationPolicy {
        self::assertAddressPinningModeIsUsable($addressPinningMode);

        if ($allowedHosts !== [] || $allowedCidrs !== []) {
            // Worth a line in the log of any deployment that has one: an exemption here is a destination a
            // wallet is allowed to send this deployment to, so it should be visible that one is in force.
            $logger?->notice(
                'DID resolution is permitted to reach destinations that are not public. Every exemption ' .
                'here is a destination that whoever supplies a DID may send this deployment to.',
                ['allowedHosts' => $allowedHosts, 'allowedCidrs' => $allowedCidrs],
            );
        }

        return new DestinationPolicy(
            allowedSchemes: DestinationPolicy::DEFAULT_ALLOWED_SCHEMES,
            allowedHosts: $allowedHosts,
            allowedCidrs: $allowedCidrs,
            addressPinningMode: $addressPinningMode,
            logger: $logger,
        );
    }


    /**
     * Refuse a pinning mode DID resolution can not run under.
     *
     * Preferred proceeds unpinned wherever the cURL handler is missing, which leaves the DNS rebinding
     * window open on exactly the fetches that are driven from outside. Checked here rather than only in
     * {@see buildDestinationPolicy()}, so that a caller assembling a DestinationPolicy of its own does not
     * walk around the rule by not using the helper.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public static function assertAddressPinningModeIsUsable(AddressPinningModeEnum $addressPinningMode): void
    {
        if ($addressPinningMode === AddressPinningModeEnum::Preferred) {
            throw new DidException(
                'DID resolution can not run under preferred address pinning, since that proceeds unpinned ' .
                'where the connection can not be pinned. Use required, or disabled where egress is ' .
                'controlled by other means.',
            );
        }
    }


    public function methodName(): string
    {
        return self::METHOD;
    }


    /**
     * Resolve a did:web DID to its document.
     *
     * @param string $did A bare did:web DID. Resolution is of the document, so a DID URL naming something
     *        inside it is not what is being asked for here.
     * @param ?float $deadlineTimestamp The point in time by which the whole operation this fetch belongs to
     *        has to be finished, as a unix timestamp with fractions. A per-request timeout on the client can
     *        not bound an operation made of several fetches, so a caller holding such a budget passes it.
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function resolveDocument(string $did, ?float $deadlineTimestamp = null): DidDocument
    {
        // Before the cache and before the network, so a malformed identifier costs neither.
        $documentUrl = $this->buildDocumentUrl($did);

        $document = $this->fromCache($did);

        if ($document instanceof DidDocument) {
            $this->logger?->debug('Resolved did:web document from cache.', ['did' => $did]);

            return $document;
        }

        $this->assertNotRecentlyFailed($did);
        $this->assertDeadlineNotExhausted($did, $deadlineTimestamp);

        try {
            $documentJson = $this->fetchDocumentJson($documentUrl, $deadlineTimestamp);
            $document = $this->buildDocument($did, $documentJson);
        } catch (DidException $didException) {
            $message = $this->boundedFailureMessage($didException->getMessage());
            $this->rememberFailure($did, $message, $deadlineTimestamp);

            if ($message === $didException->getMessage()) {
                throw $didException;
            }

            throw new DidException($message, $didException->getCode(), $didException);
        }

        $this->cacheDocumentJson($did, $documentJson);
        $this->logger?->debug('Resolved did:web document from network.', ['did' => $did]);

        return $document;
    }


    /**
     * The URL a did:web DID says its document is published at.
     *
     * Public because a deployment publishing its own did:web document has to be able to check that the DID it
     * was configured with resolves to the URL it actually serves.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function buildDocumentUrl(string $did): string
    {
        $didUrl = $this->requireBareDid($did);

        // Step 1 of the method's transform: every colon becomes a slash, so the segments between them are the
        // authority followed by the path the document sits under.
        $segments = explode(':', $didUrl->getMethodSpecificId());
        $authority = $this->buildAuthority(array_shift($segments));

        $pathSegments = array_map($this->requireLiteralPathSegment(...), $segments);

        // With no path of its own, the document lives in the well-known location for the host.
        if ($pathSegments === []) {
            $pathSegments = [self::WELL_KNOWN_PATH_SEGMENT];
        }

        return 'https://' . $authority . '/' . implode('/', $pathSegments) . '/' . self::DOCUMENT_FILENAME;
    }


    /**
     * The host, and the port where one was encoded into the identifier.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function buildAuthority(string $rawAuthority): string
    {
        if ($rawAuthority === '') {
            throw new DidException('did:web identifier does not name a host.');
        }

        // Step 2: the port separator is the one triplet the method decodes. Splitting on it rather than
        // decoding the identifier keeps every other triplet encoded, and therefore refusable below.
        $parts = preg_split('/%3A/i', $rawAuthority);

        if ($parts === false || count($parts) > 2) {
            throw new DidException('did:web host and port must be separated by a single encoded colon.');
        }

        $host = $this->requireHost($parts[0]);

        if (!isset($parts[1])) {
            return $host;
        }

        return $host . ':' . $this->requirePort($parts[1]);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function requireHost(string $host): string
    {
        $this->assertNotPercentEncoded($host, 'host');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            throw new DidException('did:web does not permit an IP address as the host.');
        }

        if (strlen($host) > self::MAX_HOST_LENGTH) {
            throw new DidException('did:web host is longer than a domain name may be.');
        }

        if (preg_match(self::HOST_PATTERN, $host) !== 1) {
            throw new DidException('did:web host is not a syntactically valid domain name.');
        }

        if (preg_match(self::NUMERIC_TOP_LABEL_PATTERN, $host) === 1 || $this->isIpLikeHost($host)) {
            throw new DidException('did:web does not permit an IP address as the host.');
        }

        return $host;
    }


    /**
     * Whether every label of this host is something a resolver may read as a number, which makes the host an
     * IP address written in a spelling filter_var does not recognise.
     *
     * The destination policy resolves and checks the address either way, so this is a second line rather than
     * the only one. It is here because the method specification prohibits an IP literal host outright, and a
     * prohibition worth stating is worth enforcing on more than the one spelling.
     */
    protected function isIpLikeHost(string $host): bool
    {
        foreach (explode('.', $host) as $label) {
            if (preg_match(self::IP_LIKE_LABEL_PATTERN, $label) !== 1) {
                return false;
            }
        }

        return true;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function requirePort(string $port): string
    {
        if (preg_match(self::PORT_PATTERN, $port) !== 1 || (int)$port > self::MAX_PORT) {
            throw new DidException('did:web port is not a valid port number.');
        }

        return $port;
    }


    /**
     * A path segment that means what it says, rather than one that restructures the URL it is placed into.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function requireLiteralPathSegment(string $segment): string
    {
        if ($segment === '') {
            throw new DidException('did:web identifier contains an empty path segment.');
        }

        if ($segment === '.' || $segment === '..') {
            throw new DidException('did:web path segments must not be relative references.');
        }

        $this->assertNotPercentEncoded($segment, 'path segment');

        return $segment;
    }


    /**
     * Refuse every percent-encoded triplet, since the only one the method decodes has already been taken out.
     *
     * This is what stops an encoded slash, question mark, hash or at sign from moving the document to a
     * different place on the host, and a doubly encoded port separator from surviving as one.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function assertNotPercentEncoded(string $value, string $context): void
    {
        if (str_contains($value, '%')) {
            throw new DidException(
                sprintf(
                    'did:web %s must not be percent encoded, since the port separator is the only triplet ' .
                    'the method decodes.',
                    $context,
                ),
            );
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function fetchDocumentJson(string $documentUrl, ?float $deadlineTimestamp): string
    {
        // The only request options passed, and only when there is a budget to hold: per-request options
        // replace client configuration rather than merging into it, so anything else set here would take
        // the client's hardened redirect and timeout handling with it.
        $options = is_null($deadlineTimestamp) ?
        [] :
        $this->httpClientDecorator->timeoutCeilingOptions($deadlineTimestamp);

        try {
            $response = $this->httpClientDecorator->request(
                HttpMethodsEnum::GET,
                $documentUrl,
                $options,
                $this->maxDocumentSizeBytes,
            );

            return $this->httpClientDecorator->readResponseBodyAsString($response, $this->maxDocumentSizeBytes);
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Could not retrieve did:web document: ' . $throwable->getMessage(),
                ['documentUrl' => $documentUrl],
            );

            // The cause is attached for whoever is debugging the deployment, not for whoever supplied the
            // DID. Only the message above is meant to travel outward; a caller rendering the chain would
            // undo the whole point of keeping the reason out of it.
            throw new DidException(self::RETRIEVAL_FAILURE_MESSAGE, (int)$throwable->getCode(), $throwable);
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function buildDocument(string $did, string $documentJson): DidDocument
    {
        try {
            $data = $this->helpers->json()->decode($documentJson, $this->maxJsonDepth);
        } catch (JsonException $jsonException) {
            // The document body stays out of the log: it came from the network and can be anything.
            $this->logger?->error(
                'Could not decode did:web document: ' . $jsonException->getMessage(),
                ['did' => $did],
            );

            throw new DidException(
                'DID document is not valid JSON.',
                $jsonException->getCode(),
                $jsonException,
            );
        }

        if (!is_array($data)) {
            throw new DidException('DID document must be a JSON object.');
        }

        // The DID is handed to the factory so that the document it returns is bound to the DID it was
        // retrieved for. Without it, a redirect could substitute another party's document.
        return $this->didDocumentFactory->fromData($did, $data, $this->requireControllerToMatchSubject);
    }


    /**
     * A previously resolved document, where one is cached and still parses.
     *
     * There is deliberately no path from here to a stale document: a cached entry that has expired, or that
     * no longer satisfies the rules, is a miss and is refetched. Serving an old document because a refresh
     * failed would be serving keys the subject may have retired.
     */
    protected function fromCache(string $did): ?DidDocument
    {
        $documentJson = $this->cachedString(self::CACHE_ELEMENT_DOCUMENT, $did);

        if (is_null($documentJson)) {
            return null;
        }

        try {
            return $this->buildDocument($did, $documentJson);
        } catch (DidException $didException) {
            // The rules a document is checked against can tighten between the write and the read, so one
            // that no longer passes them is treated as absent rather than as a failure of this resolution.
            $this->logger?->warning(
                'Discarding cached did:web document: ' . $this->boundedFailureMessage($didException->getMessage()),
                ['did' => $did],
            );

            return null;
        }
    }


    /**
     * Refuse a DID whose resolution failed recently, without going back out to the network for it.
     *
     * The remembered message is the one the failed attempt reported, which is already free of anything that
     * would say where the fetch went or why it did not arrive.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function assertNotRecentlyFailed(string $did): void
    {
        $message = $this->cachedString(self::CACHE_ELEMENT_FAILURE, $did);

        if (is_null($message)) {
            return;
        }

        $this->logger?->debug('Refusing did:web resolution that failed recently.', ['did' => $did]);

        throw new DidException($message);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function assertDeadlineNotExhausted(string $did, ?float $deadlineTimestamp): void
    {
        if (is_null($deadlineTimestamp) || microtime(true) < $deadlineTimestamp) {
            return;
        }

        $this->logger?->error(
            'Not fetching did:web document, the deadline for this operation has passed.',
            ['did' => $did],
        );

        throw new DidException(self::RETRIEVAL_FAILURE_MESSAGE);
    }


    /**
     * A failure message cut down to what this resolver is willing to carry.
     *
     * Truncating rather than replacing keeps the useful part of a parser message, which names what was wrong
     * with the document, while refusing to pass on however much the document's publisher decided to append.
     */
    protected function boundedFailureMessage(string $message): string
    {
        // Two separate hazards, so two steps. mb_strcut rather than substr, because a byte-wise cut lands
        // inside a multibyte sequence; and a scrub afterwards, because the message may already have been
        // malformed before it got here - a parser reporting one offending byte of a multibyte character
        // produces exactly that. Either way the result would be a message json_encode refuses, which turns
        // a report about a bad document into a failure while reporting it.
        $repaired = mb_convert_encoding(
            mb_strcut($message, 0, self::MAX_FAILURE_MESSAGE_LENGTH, 'UTF-8'),
            'UTF-8',
            'UTF-8',
        );

        // Cut again, because repairing can grow the string: a deployment that has set the substitute
        // character to U+FFFD spends three bytes on every one-byte sequence it replaces.
        return mb_strcut($repaired, 0, self::MAX_FAILURE_MESSAGE_LENGTH, 'UTF-8');
    }


    protected function rememberFailure(string $did, string $message, ?float $deadlineTimestamp): void
    {
        // A budget of ours that ran out says nothing about the DID. Holding it against the DID would let one
        // slow request make a working identifier unresolvable for every request after it.
        if (!is_null($deadlineTimestamp) && microtime(true) >= $deadlineTimestamp) {
            return;
        }

        $this->store(self::CACHE_ELEMENT_FAILURE, $did, $message, $this->negativeCacheDurationSeconds);
    }


    protected function cacheDocumentJson(string $did, string $documentJson): void
    {
        $this->store(
            self::CACHE_ELEMENT_DOCUMENT,
            $did,
            $documentJson,
            $this->maxCacheDurationDecorator->getInSeconds(),
        );
    }


    /**
     * The cached string for one DID under one element, or null where there is none to be had.
     */
    protected function cachedString(string $element, string $did): ?string
    {
        try {
            $value = $this->cacheDecorator?->get(null, self::CACHE_KEY_PREFIX, $element, $did);
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error reading did:web resolution cache: ' . $throwable->getMessage(),
                ['did' => $did, 'element' => $element],
            );

            return null;
        }

        return is_string($value) ? $value : null;
    }


    protected function store(string $element, string $did, string $value, int $ttl): void
    {
        if ($ttl <= 0) {
            return;
        }

        try {
            $this->cacheDecorator?->set($value, $ttl, self::CACHE_KEY_PREFIX, $element, $did);
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error writing did:web resolution cache: ' . $throwable->getMessage(),
                ['did' => $did, 'element' => $element],
            );
        }
    }
}
