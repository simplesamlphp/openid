# OpenID Federation Tools (draft 45)

To use it, create an instance of the class `\SimpleSAML\OpenID\Federation`.

```php
<?php

declare(strict_types=1);

namespace Your\Super\App;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\SupportedAlgorithms;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Federation;
use Symfony\Component\HttpFoundation\Response;

class Test
{
    public function __construct(
        protected CacheInterface $cache,
        protected LoggerInterface $logger,
    ) {
    }
    
    public function __invoke(): Response
    {
        // Instantiation example by using default options.
        // * 'RS256' as a supported algorithm 
        // * no caching support (not recommended for production environment)
        // * no logging support
        $federationTools = new Federation();
        
        // Instantiation example by injecting some dependencies. 
        // Define the supported signature algorithms:
        $supportedAlgorithms = new SupportedAlgorithms(
            new SignatureAlgorithmBag(
                SignatureAlgorithmEnum::RS256,
                // ... if needed, add other supported signature algorithms here
            )
        );
        
        // Define the maximum cache Time-To-Live (TTL) for federation artifacts.
        // This will be used together with the 'exp' claim to resolve the
        // maximum cache time for trust chains, entity statements, etc.
        $maxCacheDuration = new DateInterval('PT6H');
        
        // Instantiate by injecting own options / dependencies:
        $federationTools = new Federation(
            supportedAlgorithms: $supportedAlgorithms,
            maxCacheDuration: $maxCacheDuration,
            cache: $this->cache, // \Psr\SimpleCache\CacheInterface
            logger: $this->logger, // \Psr\Log\LoggerInterface
        );
        
        // Continue with using available tools ...
        
        return new Response();
    }
}
```

## Trust Chain Resolver

Once you have a `\SimpleSAML\OpenID\Federation` instantiated, you can continue
with using available tools. The first tool we will take a look at is the Trust
Chain Resolver. This tool can be used to try and resolve the (shortest) trust
chain for a given leaf entity (subject) and trusted anchors:

```php

// ... 

try {
    /** @var \SimpleSAML\OpenID\Federation $federationTools */
    /** @var \SimpleSAML\OpenID\Federation\TrustChainBag $trustChainBag */
    $trustChainBag = $federationTools->trustChainResolver()->for(
        'https://leaf-entity-id.example.org/', // Trust chain subject (leaf).
        [ 
            // List of valid trust anchors.
            'https://trust-achor-id.example.org/',
            'https://other-trust-achor-id.example.org/',
        ],
    );
} catch (\Throwable $exception) {
    $this->logger->error('Could not resolve trust chain: ' .
    $exception->getMessage())
    return;
}

```

If the trust chain is successfully resolved, this will return an instance of
`\SimpleSAML\OpenID\Federation\TrustChainBag`. Otherwise, an exception will
 be thrown. From the TrustChainBag you can get the TrustChain using several
methods.

```php

// ... 

try {
    /** @var \SimpleSAML\OpenID\Federation\TrustChain $trustChain */
    /** @var \SimpleSAML\OpenID\Federation\TrustChainBag $trustChainBag */
    // Simply get the shortest available chain.
    $trustChain = $trustChainBag->getShortest();
    // Get the shortest chain, but take into account the Trust Anchor priority.
    $trustChain = $trustChainBag->getShortestByTrustAnchorPriority(
        // Get a chain for this Trust Anchor even if the chain is longer.
        'https://other-trust-achor-id.example.org/',
        'https://trust-achor-id.example.org/',
    );
} catch (\Throwable $exception) {
    $this->logger->error('Could not resolve trust chain: ' .
    $exception->getMessage())
    return;
}

```

Once you have the Trust Chain, you can try and get the resolved metadata for
a particular entity type. Resolved metadata means that all metadata policies
from all intermediates have been successfully applied. Here is one example
for trying to get metadata for OpenID RP, which will return an array
(or null if no metadata is available for a given entity type):

```php
// ... 

$entityType = \SimpleSAML\OpenID\Codebooks\EntityTypesEnum::OpenIdRelyingParty;

try {
    /** @var \SimpleSAML\OpenID\Federation\TrustChain $trustChain */
    $metadata = $trustChain->getResolvedMetadata($entityType);
} catch (\Throwable $exception) {
    $this->logger->error(
        sprintf(
            'Error resolving metadata for entity type %s. Error: %s.',
            $entityType->value,
            $exception->getMessage(),        
        ),   
    );
    return;
}

if (is_null($metadata)) {
    $this->logger->error(
        sprintf(
            'No metadata available for entity type %s.',
            $entityType->value,      
        ),
    );
    return;
}
```

If getting metadata results in an exception, the metadata is considered invalid
and is to be discarded.

## Additional Verification of Signatures

The whole trust chain (each entity statement) has been verified using public
keys from JWKS claims in configuration / subordinate statements. As per
specification recommendation, you can also validate the signature of the
Trust Chain Configuration Statement by using the Trust Anchor public
keys (JWKS) that you have acquired in some secure out-of-band way
(so to not only rely on TLS protection while fetching Trust Anchor
Configuration Statement):

```php

// ... 

// Get entity statement for the resolved Trust Anchor:
/** @var \SimpleSAML\OpenID\Federation\TrustChain $trustChain */
$trustAnchorConfigurationStatement = $trustChain->getResolvedTrustAnchor();
// Get data that you need to prepare appropriate public keys, for example,
// the entity ID:
$trustAnchorEntityId = $trustAnchorConfigurationStatement->getIssuer();

// Prepare a JWKS array containing Trust Anchor public keys that you have
// acquired in a secure out-of-band way...
/** @var array $trustAnchorJwks */

try {    
    $trustAnchorConfigurationStatement->verifyWithKeySet($trustAnchorJwks);
} catch (\Throwable $exception) {
    $this->logger->error(
        'Could not verify trust anchor configuration statement signature: ' .
        $exception->getMessage(),
    );
    return;
}

```

## Fetching Trust Marks

Federation tools expose Trust Mark Fetcher, which you can use to dynamically
fetch or refresh (short-living) Trust Marks.

```php
// ...

/** @var \SimpleSAML\OpenID\Federation $federationTools */

// Trust Mark Type that you want to fetch. 
$trustMarkType = 'https://example.com/trust-mark/member';
// ID of Subject for which to fetch the Trust Mark.
$subjectId = 'https://leaf-entity.org'
// ID of the Trust Mark Issuer from which to fetch the Trust Mark.
$trustMarkIssuerEntityId = 'https://trust-mark-issuer.org'

try {
    // First, fetch the Configuration Statement for Trust Mark Issuer.
    $trustMarkIssuerConfigurationStatement = $this->federation
        ->entityStatementFetcher()
        ->fromCacheOrWellKnownEndpoint($trustMarkIssuerEntityId);

    // Fetch the Trust Mark from Issuer.
    $trustMarkEntity = $federationTools->trustMarkFetcher()
    ->fromCacheOrFederationTrustMarkEndpoint(
        $trustMarkType,
        $subjectId,
        $trustMarkIssuerConfigurationStatement
    );
    
} catch (\Throwable $exception) {
    $this->logger->error('Trust Mark fetch failed. Error was: ' .
    $exception->getMessage());
    return;
}

```

## Validating Trust Marks

Federation tools expose Trust Mark Validator with several methods for validating
Trust Marks, with the most common one being the one to validate Trust Mark for
some entity simply based on the Trust Mark Type.

If cache is used, Trust Mark validation will be cached with cache TTL being the
minimum expiration time of Trust Mark, Leaf Entity Statement or
`maxCacheDuration`, whatever is smaller.

```php
// ...

/** @var \SimpleSAML\OpenID\Federation $federationTools */
/** @var \SimpleSAML\OpenID\Federation\TrustChain $trustChain */


// Trust Mark Type that you want to validate. 
$trustMarkType = 'https://example.com/trust-mark/member';
// Leaf for which you want to validate the Trust Mark with ID above.
$leafEntityConfigurationStatement = $trustChain->getResolvedLeaf();
// Trust Anchor, under which you want to validate Trust Mark.
$trustAnchorConfigurationStatement = $trustChain->getResolvedTrustAnchor();

try {
    // Example which queries cache for previously validated Trust Mark and does
    // formal validation if not cached.
    $federationTools->trustMarkValidator()->fromCacheOrDoForTrustMarkType(
        $trustMarkType,
        $leafEntityConfigurationStatement,
        $trustAnchorConfigurationStatement,
        $expectedJwtType = \SimpleSAML\OpenID\Codebooks\JwtTypesEnum::TrustMarkJwt,
    );
    
    // Example which always does formal validation (does not use cache), and
    // requires usage of Trust Mark Status Endpoint for non-expiring Trust
    // Marks.
    $federationTools->trustMarkValidator()->doForTrustMarkType(
        $trustMarkType,
        $leafEntityConfigurationStatement,
        $trustAnchorConfigurationStatement,
        $expectedJwtType = \SimpleSAML\OpenID\Codebooks\JwtTypesEnum::TrustMarkJwt,
        \SimpleSAML\OpenID\Codebooks\TrustMarkStatusEndpointUsagePolicyEnum::RequiredForNonExpiringTrustMarksOnly,
    );
} catch (\Throwable $exception) {
    $this->logger->error('Trust Mark validation failed. Error was: ' . $exception->getMessage());
    return;
}

```

## Prepare Entity Statements

You can use an Entity Statement Factory to quickly create Entity Statements.
Since Entity Statements are signed JWTs (JWS), you have to have your private
key prepared which will be used to sign them.

```php

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Jwk;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;

/** @var \SimpleSAML\OpenID\Federation $federationTools */

// You can use the JWK Tools to create a JWK decorator from a private key file.
$jwkTools = new Jwk();

// Prepare a signing key decorator. Check other methods on `jwkDecoratorFactory`
// for alternative ways to create a key decorator. 
$signingKey = $jwkTools->jwkDecoratorFactory()->fromPkcs1Or8KeyFile(
    '/path/to/private/key.pem',
);

// Set the signature algorithm to use.
$signatureAlgorithm = SignatureAlgorithmEnum::ES256;

// Use any logic necessary to prepare JWT payload data.
$issuedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

$jwtPayload = [
    ClaimsEnum::Iss->value => 'https://example.com/issuer',
    ClaimsEnum::Iat->value => $issuedAt->getTimestamp(),
    ClaimsEnum::Nbf->value => $issuedAt->getTimestamp(),
    ClaimsEnum::Sub->value => 'subject-id',
    // ...
];

// Use any logic necessary to prepare JWT header data.
$jwtHeader = [
    ClaimsEnum::Kid->value 'abc123',
    //...
];

// Build Entity Statement instance.
$entityStatement = $federationTools->entityStatementFactory()->fromData(
    $signingKey,
    $signatureAlgorithm,
    $jwtPayload,
    $jwtHeader,
);

// Get Entity Statement token string (JWS). Default serialization is
// JwsSerializerEnum::Compact.
$entityStatementToken = $entityStatement->getToken();

```
