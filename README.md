# OpenID Tools Library

[![Build Status](https://github.com/simplesamlphp/openid/actions/workflows/php.yml/badge.svg)](https://github.com/simplesamlphp/openid/actions/workflows/php.yml)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/openid/branch/master/graph/badge.svg)](https://app.codecov.io/gh/simplesamlphp/openid)

The library is under development, and you can expect braking changes along the way.

The library provides some common tools that you might find useful when working with OpenID family of specifications.

## Installation

Library can be installed by using Composer:

```shell
composer require simplesamlphp/openid
```

## OpenID Federation (draft 42)

The initial functionality of the library revolves around the OpenID Federation specification. To use it, create an
instance of the class `\SimpleSAML\OpenID\Federation`

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
        // * 'RS256' as supported algorithm
        // * no caching support (not recommended for production environment)
        // * no logging support
        $federationTools = new Federation();
        
        // Instantiation example by injecting some of the dependencies 
        // Define the supported signature algorithms:
        $supportedAlgorithms = new SupportedAlgorithms(
            new SignatureAlgorithmBag(
                SignatureAlgorithmEnum::RS256,
                // ... if needed, add other supported signature algorithms here
            )
        );
        
        // Define the maximum cache Time-To-Live (TTL) for federation artifacts. This will be used together with 'exp'
        // claim to resolve the maximum cache time for trust chains, entity statements, etc.
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

### Trust chain resolver

Once you have a `\SimpleSAML\OpenID\Federation` instantiated, you can continue with using available tools. The first
tool we will take a look at is trust chain resolver. This tool can be used to try and resolve the (shortest) trust chain
for given leaf entity (subject) and trusted anchors:

```php

// ... 

try {
    /** @var \SimpleSAML\OpenID\Federation $federationTools */
    /** @var \SimpleSAML\OpenID\Federation\TrustChainBag $trustChainBag */
    $trustChainBag = $federationTools->trustChainResolver()->for(
        'https://leaf-entity-id.example.org/', // Trust chain subject (leaf entity).
        [ 
            // List of valid trust anchors.
            'https://trust-achor-id.example.org/',
            'https://other-trust-achor-id.example.org/',
        ],
    );
} catch (\Throwable $exception) {
    $this->logger->error('Could not resolve trust chain: ' . $exception->getMessage())
    return;
}

```

If the trust chain is successfully resolved, this will return an instance of
`\SimpleSAML\OpenID\Federation\TrustChainBag`. Otherwise, exception will be thrown.
From the TrustChainBag you can get the TrustChain using several methods.

```php

// ... 

try {
    /** @var \SimpleSAML\OpenID\Federation\TrustChain $trustChain */
    /** @var \SimpleSAML\OpenID\Federation\TrustChainBag $trustChainBag */
    // Simply get the shortest available chain.
    $trustChain = $trustChainBag->getShortest();
    // Get the shortest chain, but take into account the Trust Anchor priority.
    $trustChain = $trustChainBag->getShortestByTrustAnchorPriority(
        'https://other-trust-achor-id.example.org/', // Get chain for this Trust Anchor even if the chain is longer.
        'https://trust-achor-id.example.org/',
    );
} catch (\Throwable $exception) {
    $this->logger->error('Could not resolve trust chain: ' . $exception->getMessage())
    return;
}

```

Once you have the Trust Chain, you can try and get the resolved metadata for particular entity type. Resolved metadata
means that all metadata policies from all intermediates have been successfully applied. Here is one example for trying
to get metadata for OpenID RP, which will return an array (or null if no metadata is available for given entity type):

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

If getting metadata results in an exception, the metadata is considered invalid and is to be discarded.

### Additional verification of signatures

The whole trust chain (each entity statement) has been verified using public keys from JWKS claims in configuration /
subordinate statements. As per specification recommendation, you can also validate the signature of the Trust Chain
Configuration Statement by using the Trust Anchor public keys (JWKS) that you have acquired in some secure out-of-band
way (so to not only rely on TLS protection while fetching Trust Anchor Configuration Statement):

```php

// ... 

// Get entity statement for the resolved Trust Anchor:
/** @var \SimpleSAML\OpenID\Federation\TrustChain $trustChain */
$trustAnchorConfigurationStatement = $trustChain->getResolvedTrustAnchor();
// Get data that you need to prepare appropriate public keys, for example, the entity ID:
$trustAnchorEntityId = $trustAnchorConfigurationStatement->getIssuer();

// Prepare JWKS array containing Trust Anchor public keys that you have acquired in secure out-of-band way ...
/** @var array $trustAnchorJwks */

try {    
    $trustAnchorConfigurationStatement->verifyWithKeySet($trustAnchorJwks);
} catch (\Throwable $exception) {
    $this->logger->error('Could not verify trust anchor configuration statement signature: ' .
    $exception->getMessage());
    return;
}

```

### Fetching Trust Marks

Federation tools expose Trust Mark Fetcher which you can use to dynamically fetch or refresh (short-living) Trust Marks.

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
    $trustMarkEntity = $federationTools->trustMarkFetcher()->fromCacheOrFederationTrustMarkEndpoint(
        $trustMarkType,
        $subjectId,
        $trustMarkIssuerConfigurationStatement
    );
    
} catch (\Throwable $exception) {
    $this->logger->error('Trust Mark fetch failed. Error was: ' . $exception->getMessage());
    return;
}

```

### Validating Trust Marks

Federation tools expose Trust Mark Validator with several methods for validating Trust Marks, with the most common
one being the one to validate Trust Mark for some entity simply based on the Trust Mark Type.

If cache is utilized, Trust Mark validation will be cached with cache TTL being the minimum expiration
time of Trust Mark, Leaf Entity Statement or `maxCacheDuration`, whatever is smaller.

```php
// ...

/** @var \SimpleSAML\OpenID\Federation $federationTools */
/** @var \SimpleSAML\OpenID\Federation\TrustChain $trustChain */


// Trust Mark Type that you want to validate. 
$trustMarkType = 'https://example.com/trust-mark/member';
// Leaf for which you want to validate the Trust Mark with ID above.
$leafEntityConfigurationStatement = $trustChain->getResolvedLeaf();
// Trust Anchor under which you want to validate Trust Mark.
$trustAnchorConfigurationStatement = $trustChain->getResolvedTrustAnchor();

try {
    // Example which queries cache for previously validated Trust Mark, and does formal validation if not cached.
    $federationTools->trustMarkValidator()->fromCacheOrDoForTrustMarkType(
        $trustMarkType,
        $leafEntityConfigurationStatement,
        $trustAnchorConfigurationStatement,
        $expectedJwtType = \SimpleSAML\OpenID\Codebooks\JwtTypesEnum::TrustMarkJwt,
    );
    
    // Example which always does formal validation (does not use cache).
    $federationTools->trustMarkValidator()->doForTrustMarkType(
        $trustMarkType,
        $leafEntityConfigurationStatement,
        $trustAnchorConfigurationStatement,
        $expectedJwtType = \SimpleSAML\OpenID\Codebooks\JwtTypesEnum::TrustMarkJwt,
    );
} catch (\Throwable $exception) {
    $this->logger->error('Trust Mark validation failed. Error was: ' . $exception->getMessage());
    return;
}

```
