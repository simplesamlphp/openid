# Federation Discovery and Entity Collection

This library provides tools for discovering entities within an OpenID Federation
and for working with the Entity Collection Endpoint. The functionality is split
into two main areas:

1. **Federation Discovery** — Top-down traversal of a federation hierarchy to
   collect all entity IDs.
2. **Entity Collection** — Client-side fetching from a remote
   `federation_collection_endpoint`, and server-side building blocks (filtering,
   sorting, pagination) for implementing your own collection endpoint.

All components are accessible through the `\SimpleSAML\OpenID\Federation` facade.

## Setup

Federation discovery extends the standard `Federation` instantiation with two
additional constructor parameters:

```php
<?php

declare(strict_types=1);

use SimpleSAML\OpenID\Federation;

$federationTools = new Federation(
    cache: $cache,          // \Psr\SimpleCache\CacheInterface
    logger: $logger,        // \Psr\Log\LoggerInterface
    maxDiscoveryDepth: 10,  // Maximum recursion depth for federation traversal
);
```

The `maxDiscoveryDepth` parameter limits how deep the traversal will recurse
when following `federation_list_endpoint` links. The default is `10`.

### Custom Entity Collection Store

By default, discovered entity IDs are stored using the PSR-16 cache
(`CacheEntityCollectionStore`). If no cache is configured, an
`InMemoryEntityCollectionStore` is used instead. You can provide your own
implementation of `EntityCollectionStoreInterface`:

```php
use SimpleSAML\OpenID\Federation;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionStoreInterface;

// Your custom store implementation.
$customStore = new YourCustomEntityCollectionStore();

$federationTools = new Federation(
    cache: $cache,
    logger: $logger,
    entityCollectionStore: $customStore, // Custom store
);
```

The store interface is minimal:

```php
interface EntityCollectionStoreInterface
{
    /**
     * Persist discovered entity IDs for a given Trust Anchor.
     */
    public function storeEntityIds(string $trustAnchorId, array $entityIds, int $ttl): void;

    /**
     * Retrieve previously discovered entity IDs.
     * Return null when not found or expired.
     */
    public function getEntityIds(string $trustAnchorId): ?array;

    /**
     * Remove stored entity IDs (for force re-discovery).
     */
    public function clearEntityIds(string $trustAnchorId): void;
}
```

> **Note**: The store tracks only the list of entity IDs per Trust Anchor, not
> the Entity Configurations themselves. Entity Configurations are fetched
> dynamically through `EntityStatementFetcher::fromCacheOrWellKnownEndpoint()`,
> which already handles JWS-level caching and respects expiry.

## Federation Discovery

Federation Discovery performs a top-down traversal of the federation hierarchy.
Starting from a Trust Anchor, it follows `federation_list_endpoint` links on
each entity to collect all subordinate entity IDs recursively.

### Discovering Entity IDs

```php
/** @var \SimpleSAML\OpenID\Federation $federationTools */

$trustAnchorId = 'https://trust-anchor.example.org/';

try {
    // Discover all entity IDs in the federation.
    $entityIds = $federationTools->federationDiscovery()
        ->discoverEntities($trustAnchorId);

    // $entityIds is an array of entity ID strings, e.g.:
    // ['https://trust-anchor.example.org/', 'https://intermediate.example.org/', ...]
} catch (\Throwable $exception) {
    $logger->error('Federation discovery failed: ' . $exception->getMessage());
}
```

The discovery algorithm:

1. Fetches the Entity Configuration of the Trust Anchor.
2. Extracts the `federation_list_endpoint` from its metadata.
3. Calls the subordinate listing endpoint to get immediate subordinate IDs.
4. For each subordinate, fetches its Entity Configuration and, if it has its own
   `federation_list_endpoint`, recurses (up to `maxDiscoveryDepth`).
5. Deduplicates all collected entity IDs.
6. Persists the ID list in the store with a TTL based on the Trust Anchor's
   expiry and the configured `maxCacheDuration`.

### Applying Filters During Discovery

You can pass filter parameters (e.g. `entity_type`) to the subordinate listing
endpoint:

```php
$entityIds = $federationTools->federationDiscovery()
    ->discoverEntities(
        $trustAnchorId,
        filters: ['entity_type' => 'openid_relying_party'],
    );
```

### Discovering and Fetching Entity Configurations

The convenience method `discoverAndFetch()` performs discovery and then fetches
the Entity Configuration for each discovered entity:

```php
try {
    // Returns array<string, EntityStatement> keyed by entity ID.
    $entities = $federationTools->federationDiscovery()
        ->discoverAndFetch($trustAnchorId);

    foreach ($entities as $entityId => $entityStatement) {
        $metadata = $entityStatement->getMetadata();
        // ...
    }
} catch (\Throwable $exception) {
    $logger->error('Discovery failed: ' . $exception->getMessage());
}
```

> **Note**: Entity Configurations are fetched through the existing
> `EntityStatementFetcher`, which caches JWS at the network level. If a cached
> configuration has expired, a fresh one is fetched automatically.

### Periodic Refresh (Cron / Background Jobs)

Use the `forceRefresh` parameter to clear the stored entity ID list and
re-traverse the federation. This is the intended pattern for cron or background
refresh jobs:

```php
// In a scheduled task / cron job:
$federationTools->federationDiscovery()
    ->discoverAndFetch($trustAnchorId, forceRefresh: true);
```

When `forceRefresh` is `true`:

- The full federation traversal is re-executed.
- The new entity ID list is stored.
- Entity Configurations that haven't expired in the JWS cache are served from
  cache; only stale or new ones trigger network requests.

## Entity Collection Client

The Entity Collection Client fetches from a remote
`federation_collection_endpoint` and deserializes the response into typed
objects.

### Fetching from a Remote Endpoint

```php
/** @var \SimpleSAML\OpenID\Federation $federationTools */

$collectionEndpointUri = 'https://trust-anchor.example.org/federation_collection';

try {
    $response = $federationTools->entityCollectionFetcher()
        ->fetch($collectionEndpointUri);

    // Iterate over the entries.
    foreach ($response->entities as $entry) {
        echo $entry->entityId . PHP_EOL;
        echo 'Types: ' . implode(', ', $entry->entityTypes) . PHP_EOL;

        if ($entry->uiInfos !== null) {
            echo 'Display: ' . ($entry->uiInfos['display_name'] ?? 'N/A') . PHP_EOL;
        }
    }

    // Check if there are more pages.
    if ($response->next !== null) {
        // Fetch next page using the cursor.
        $nextPage = $federationTools->entityCollectionFetcher()
            ->fetch($collectionEndpointUri, ['from' => $response->next]);
    }
} catch (\Throwable $exception) {
    $logger->error('Entity collection fetch failed: ' . $exception->getMessage());
}
```

### Applying Filters

The `fetch()` method accepts filter parameters as defined by the Entity
Collection Endpoint specification:

```php
$response = $federationTools->entityCollectionFetcher()->fetch(
    $collectionEndpointUri,
    [
        'entity_type' => ['openid_provider', 'openid_relying_party'],
        'trust_mark_type' => 'https://example.com/trust-mark/member',
        'query' => 'university',
        'limit' => 20,
    ],
);
```

Multi-value parameters (like `entity_type`) are serialized as repeated query
keys (`?entity_type=openid_provider&entity_type=openid_relying_party`) per the
specification.

### Response Objects

- **`EntityCollectionResponse`** — Contains the `entities` array,
  an optional `next` cursor for pagination, and an optional `lastUpdated`
  timestamp. Implements `JsonSerializable`.
- **`EntityCollectionEntry`** — Represents a single entity in the collection.
  Contains `entityId`, `entityTypes`, optional `uiInfos`, and optional
  `trustMarks`. Implements `JsonSerializable`.

## Server-Side Building Blocks

If you want to implement and serve your own `federation_collection_endpoint`,
this library provides building-block components that handle the core logic. You
only need to wire them into your HTTP framework's controller.

### Overview

The server-side pipeline follows this order:

1. **Discover** — Collect entities from the federation.
2. **Filter** — Apply client-requested filters (entity type, trust mark, query).
3. **Sort** — Order by a metadata claim (e.g. `display_name`).
4. **Project** — Select only the requested UI claims.
5. **Paginate** — Slice the result set and produce a cursor.
6. **Serialize** — Return a `JsonSerializable` response.

### Using EntityCollectionResponseFactory

The `EntityCollectionResponseFactory` is a convenience orchestrator that wires
all the above steps into a single call:

```php
/** @var \SimpleSAML\OpenID\Federation $federationTools */

$trustAnchorId = 'https://trust-anchor.example.org/';

// In your controller, pass the incoming request parameters directly.
$requestParams = $request->getQueryParams();

$response = $federationTools->entityCollectionResponseFactory()
    ->build($trustAnchorId, $requestParams);

// The response implements JsonSerializable.
return new JsonResponse(json_encode($response));
```

Supported request parameters:

| Parameter | Type | Description |
|---|---|---|
| `entity_type` | `string[]` | Filter by entity type keys (e.g. `openid_provider`) |
| `trust_mark_type` | `string` | Filter by Trust Mark type |
| `query` | `string` | Free-text search on entity ID, `display_name`, `organization_name` |
| `trust_anchor` | `string` | Filter by Trust Anchor (via `authority_hints`) |
| `sort_by` | `string` | Dot-separated claim path (e.g. `federation_entity.display_name`) |
| `sort_dir` | `'asc'\|'desc'` | Sort direction, defaults to `asc` |
| `ui_claims` | `string[]` | Claims to include in the `ui_infos` projection |
| `limit` | `int` | Maximum entries per page (default 100) |
| `from` | `string` | Opaque cursor from a previous response's `next` field |

### Using Individual Components

You can also use each building block independently for maximum control.

#### EntityCollectionFilter

Filters entity configurations by various criteria:

```php
use SimpleSAML\OpenID\Federation\EntityCollection;

/** @var \SimpleSAML\OpenID\Federation $federationTools */

// Prepare a collection from discovery or any other source.
$entities = $federationTools->federationDiscovery()
    ->discoverAndFetch($trustAnchorId);
$collection = new EntityCollection($entities);

// Filter by entity type and text query.
$filtered = $federationTools->entityCollectionFilter()->filter(
    $collection,
    [
        'entity_type' => ['openid_provider'],
        'query' => 'university',
    ],
);

// $filtered is array<string, EntityStatement> keyed by entity ID.
```

#### EntityCollectionSorter

Sorts entities by a metadata claim value:

```php
/** @var \SimpleSAML\OpenID\Federation $federationTools */

// Sort by display_name under the federation_entity metadata.
$sorted = $federationTools->entityCollectionSorter()->sortByMetadataClaim(
    $filtered, // array<string, EntityStatement>
    ['federation_entity', 'display_name'],
    'asc',
);

// Sort by organization_name under the openid_provider metadata.
$sorted = $federationTools->entityCollectionSorter()->sortByMetadataClaim(
    $filtered,
    ['openid_provider', 'organization_name'],
    'desc',
);
```

Entities missing the specified claim are placed at the end of the result set.

#### EntityCollectionPaginator

Slices a pre-sorted result set into a page with an opaque cursor:

```php
/** @var \SimpleSAML\OpenID\Federation $federationTools */

$paginated = $federationTools->entityCollectionPaginator()->paginate(
    $sorted, // Pre-sorted array<string, EntityStatement|EntityCollectionEntry>
    20,      // Limit (page size)
    null,    // Cursor from a previous response's 'next' value, or null
);

$pageEntities = $paginated['entities']; // array<string, ...>
$nextCursor = $paginated['next'];       // ?string — null when on the last page
```

The `next` cursor is an opaque base64url-encoded pointer. Pass it as the `from`
parameter in the next request to continue pagination.

## Full Server-Side Example

Here is a complete example of wiring the building blocks into a controller
action:

```php
<?php

declare(strict_types=1);

namespace Your\App\Controllers;

use SimpleSAML\OpenID\Federation;
use Psr\Http\Message\ServerRequestInterface;

class FederationCollectionController
{
    public function __construct(
        private readonly Federation $federationTools,
        private readonly string $trustAnchorId,
    ) {
    }


    public function __invoke(ServerRequestInterface $request): string
    {
        $response = $this->federationTools
            ->entityCollectionResponseFactory()
            ->build($this->trustAnchorId, $request->getQueryParams());

        // EntityCollectionResponse implements JsonSerializable.
        return json_encode($response, JSON_THROW_ON_ERROR);
    }
}
```

Example request:

```
GET /federation_collection?entity_type=openid_provider&query=university&sort_by=federation_entity.display_name&limit=10
```

Example response:

```json
{
    "entities": [
        {
            "entity_id": "https://idp.university-a.example.org/",
            "entity_types": ["openid_provider"],
            "ui_infos": {
                "display_name": "University A Identity Provider"
            }
        },
        {
            "entity_id": "https://idp.university-b.example.org/",
            "entity_types": ["openid_provider"],
            "ui_infos": {
                "display_name": "University B Identity Provider"
            }
        }
    ],
    "next": "aHR0cHM6Ly9pZHAudW5pdmVyc2l0eS1iLmV4YW1wbGUub3JnLw",
    "last_updated": 1745410000
}
```
