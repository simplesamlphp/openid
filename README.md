# OpenID Tools Library

WARNING: this library is under heavy development and should not be used in production!

This library provides some common tools that you might find useful when working with OpenID family of specifications.

## Installation

Library can be installed by using Composer:

```shell
composer require simplesamlphp/openid
```

## OpenID Federation

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
        
        // Define the maximum cache TTL for entity statements. This will be used together with 'exp'
        // claim to resolve the maximum time for entity statement caching.
        $maxEntityStatementCacheDuration = new DateInterval('PT6H');
        
        // Instantiate by injecting own options / dependencies:
        $federationTools = new Federation(
            supportedAlgorithms: $supportedAlgorithms,
            maxEntityStatementCacheDuration: $maxEntityStatementCacheDuration,
            cache: $this->cache, // \Psr\SimpleCache\CacheInterface
            logger: $this->logger, // \Psr\Log\LoggerInterface
        );
        
        // Continue with using available tools ...
        
        return new Response();
    }
}
```

### Trust chain fetcher

Once you have a `\SimpleSAML\OpenID\Federation` instantiated, you can continue with using available tools. The first
tool we will take a look at is trust chain fetcher. This tool can be used to try and resolve the (shortest) trust chain
for given leaf entity (subject) and trusted anchors:

```php

// ... 

try {
    /** @var \SimpleSAML\OpenID\Federation $federationTools */
    /** @var \SimpleSAML\OpenID\Federation\TrustChain $trustChain */
    $trustChain = $federationTools->trustChainFetcher()->for(
        'https://leaf-entity-id.example.org/', // Trust chain subject (leaf entity).
        [
            'https://trust-achor-id.example.org/', // List of valid trust anchors.
        ],
    );
} catch (\Throwable $exception) {
    $this->loggerService->error('Could not resolve trust chain: ' . $exception->getMessage())
}

```

If the trust chain is successfully resolved, this will return an instance of `\SimpleSAML\OpenID\Federation\TrustChain`. 
