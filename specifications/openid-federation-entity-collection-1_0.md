---
author: Gabriel Zachmann
description:  This specification acts as an extension to . It defines an additional federation endpoint to retrieve a filterable list of Entities in a (sub-)federation.
generator: xml2rfc 3.16.0
ietf.draft: openid-federation-entity-collection
keyword:
- security
- openid
- federation
lang: en
scripts: Common,Latin
title: OpenID Federation Entity Collection Endpoint 1.0 - draft 00
viewport: initial-scale=1.0
---

             openid-federation-entity-collection   April 2026
  ---------- ------------------------------------- ------------
  Zachmann   Standards Track                       \[Page\]

Workgroup:
:   individual

Published:
:   28 April 2026

Author:

:   G. Zachmann

    Karlsruhe Institute of Technology

# OpenID Federation Entity Collection Endpoint 1.0 - draft 00

## [Abstract](#abstract)

This specification acts as an extension to \[[OpenID.Federation](#OpenID.Federation)\]. It defines an additional federation endpoint to retrieve a filterable list of Entities in a (sub-)federation.[¶](#section-abstract-1)

[▲](#)

## [Table of Contents](#name-table-of-contents)

-   [1](#section-1).  [Introduction](#name-introduction)

    -   [1.1](#section-1.1).  [Rationale and Benefits](#name-rationale-and-benefits)

    -   [1.2](#section-1.2).  [Requirements Notation and Conventions](#name-requirements-notation-and-c)

-   [2](#section-2).  [Terminology](#name-terminology)

-   [3](#section-3).  [Entity Collection Endpoint](#name-entity-collection-endpoint)

    -   [3.1](#section-3.1).  [Pagination](#name-pagination)

        -   [3.1.1](#section-3.1.1).  [Ordering](#name-ordering)

        -   [3.1.2](#section-3.1.2).  [Response Limits](#name-response-limits)

    -   [3.2](#section-3.2).  [Endpoint Location](#name-endpoint-location)

    -   [3.3](#section-3.3).  [Entity Collection Request](#name-entity-collection-request)

        -   [3.3.1](#section-3.3.1).  [Request Format](#name-request-format)

    -   [3.4](#section-3.4).  [Entity Collection Response](#name-entity-collection-response)

        -   [3.4.1](#section-3.4.1).  [Response Format](#name-response-format)

        -   [3.4.2](#section-3.4.2).  [Error Response Format](#name-error-response-format)

-   [4](#section-4).  [Claims Languages and Scripts](#name-claims-languages-and-script)

-   [5](#section-5).  [Implementation Considerations](#name-implementation-consideratio)

    -   [5.1](#section-5.1).  [Collecting Entities](#name-collecting-entities)

    -   [5.2](#section-5.2).  [Mapping Entity Configuration Claims to UI Info Response Claims](#name-mapping-entity-configuratio)

    -   [5.3](#section-5.3).  [Entity Collection Endpoint Scope](#name-entity-collection-endpoint-)

-   [6](#section-6).  [Security Considerations](#name-security-considerations)

    -   [6.1](#section-6.1).  [Unsigned Response](#name-unsigned-response)

-   [7](#section-7).  [Normative References](#name-normative-references)

-   [Appendix A](#appendix-A).  [Notices](#name-notices)

-   [Appendix B](#appendix-B).  [Acknowledgements](#name-acknowledgements)

-   [Appendix C](#appendix-C).  [Document History](#name-document-history)

-   [](#appendix-D)[Author\'s Address](#name-authors-address)

## [1.](#section-1) [Introduction](#name-introduction)

This specification introduces a new federation endpoint that provides a single, queryable interface for discovering Entities within a federation subtree anchored at a Trust Anchor. In contrast to the Subordinate Listing Endpoint defined in \[[OpenID.Federation](#OpenID.Federation)\], which enumerates only direct subordinates, this endpoint enables discovery of Entities that are subordinate either directly or indirectly to the selected Trust Anchor.[¶](#section-1-1)

The endpoint is intended to support discovery and selection use cases in which a client presents a curated set of Entities to end users or administrators. Examples include selection interfaces (for example, login pickers) and administrative views. To support such use cases, responses MAY include user interface--oriented information (for example, display names, logos, and descriptions) and the endpoint MAY support filtering (for example, by entity type, trust mark type, or query text), as defined in this specification. Usage of the endpoint for other use cases that require a list of entities in a federation is possible.[¶](#section-1-2)

Information returned by this endpoint is informational. It is intended to facilitate presentation and efficient narrowing of the result set. It does not replace trust validation. When an Entity is to be relied upon, trust validation MUST be performed according to OpenID Federation.[¶](#section-1-3)

### [1.1.](#section-1.1) [Rationale and Benefits](#name-rationale-and-benefits)

This specification complements the federation core functionality by providing an endpoint optimized for discovery, filtering, and presentation of federation entities at scale. The following benefits motivate implementation:[¶](#section-1.1-1)

-   Coverage beyond direct subordinates: Enables discovery across multiple federation levels without requiring clients to recursively traverse intermediates.[¶](#section-1.1-2.1)
-   UI-oriented metadata: Supports inclusion of human-readable attributes (for example, display name, description, logo URI, policy URI) to directly power selection and catalog views without separate metadata lookups.[¶](#section-1.1-2.2)
-   Server-side filtering: Allows responders to filter by entity type, trust mark type, and free-form query, reducing client-side processing and network round-trips.[¶](#section-1.1-2.3)
-   Pagination and incremental retrieval: Supports efficient list pagination using `from` and `limit`, suitable for large federations.[¶](#section-1.1-2.4)
-   Reduced coupling to resolution: Provides a lightweight, list-oriented surface distinct from the Resolve and Fetch flows, allowing clients to defer resolution and full trust validation until a concrete Entity is selected.[¶](#section-1.1-2.5)

These properties enable relying parties, portals, and administrative tools to implement consistent, efficient, and user-friendly discovery experiences while preserving the core trust validation model defined by OpenID Federation.[¶](#section-1.1-3)

### [1.2.](#section-1.2) [Requirements Notation and Conventions](#name-requirements-notation-and-c)

The keywords \"MUST\", \"MUST NOT\", \"REQUIRED\", \"SHALL\", \"SHALL NOT\", \"SHOULD\", \"SHOULD NOT\", \"RECOMMENDED\", \"NOT RECOMMENDED\", \"MAY\", and \"OPTIONAL\" in this document are to be interpreted as described in BCP 14 \[[RFC2119](#RFC2119)\] \[[RFC8174](#RFC8174)\] when, and only when, they appear in all capitals, as shown here.[¶](#section-1.2-1)

## [2.](#section-2) [Terminology](#name-terminology)

This specification uses the terms \"Entity\" as defined by OpenID Connect Core \[[OpenID.Core](#OpenID.Core)\], \"Client\" as defined by \[[RFC6749](#RFC6749)\], and \"Trust Mark\", \"Federation Entity\", \"Federation Entity Key\", \"Trust Anchor\", \"Intermediate\", and \"Subordinate Statement\" defined in \[[OpenID.Federation](#OpenID.Federation)\].[¶](#section-2-1)

## [3.](#section-3) [Entity Collection Endpoint](#name-entity-collection-endpoint)

The Federation Entity Collection Endpoint is an optional endpoint that MAY be published by Federation Entities. It MUST use the `https` scheme and MAY include port, path, and query parameter components encoded in `application/x-www-form-urlencoded` format. It MUST NOT contain a fragment component. Federation Entities publishing this endpoint SHOULD also publish a `federation_resolve_endpoint`.[¶](#section-3-1)

### [3.1.](#section-3.1) [Pagination](#name-pagination)

By segmenting the data into pages, the endpoint facilitates the efficient transmission and processing of data and also adds to the client\'s ability to navigate through the information. The selected method of pagination offers a mix of consistency and performance characteristics appropriate for the intended use of the endpoint.[¶](#section-3.1-1)

#### [3.1.1.](#section-3.1.1) [Ordering](#name-ordering)

As pagination enables consumers of this endpoint to retrieve a subset of the full dataset, the responding Entity MUST ensure consistent ordering is implemented across all returned responses. No recommendation is made on which key the ordering is based upon and is left up to the choice of implementing Entities.[¶](#section-3.1.1-1)

#### [3.1.2.](#section-3.1.2) [Response Limits](#name-response-limits)

This endpoint defines the limit query parameter, allowing consumers to specify a desired maximum number of Entities returned in a given response set. However, this number may, in some cases, be impractical or not feasible for the issuing Entity to return. To address this, it is RECOMMENDED that implementations define a practical upper limit for the response size that can be served. This defined limit MUST be set to a value that ensures if no limit is specified in a request, or if the implementation deems the requested limit impractical, the response can be returned successfully with all requested additional parameters.[¶](#section-3.1.2-1)

### [3.2.](#section-3.2) [Endpoint Location](#name-endpoint-location)

The location of the Federation Entity Collection Endpoint is published in the `federation_entity` metadata, using the `federation_collection_endpoint` parameter.[¶](#section-3.2-1)

The following is a non-normative example of an Entity Configuration payload, for a Trust Anchor that includes the `federation_collection_endpoint`:[¶](#section-3.2-2)

    {
      "iss": "https://ta.example.org",
      "sub": "https://ta.example.org",
      "iat": 1590000000,
      "exp": 1590086400,
      "jwks": {
        "keys": [
          {
            "kty": "RSA",
            "kid": "key1",
            "use": "sig",
            "n": "n4EPtAOCc9AlkeQHPzHStgAbgs7bTZLwUBZdR8_KuKPEHLd4rHVTeT",
            "e": "AQAB"
          }
        ]
      },
      "metadata": {
        "federation_entity": {
          "federation_fetch_endpoint": "https://ta.example.org/fetch",
          "federation_list_endpoint": "https://ta.example.org/list",
          "federation_resolve_endpoint": "https://ta.example.org/resolve",
          "federation_collection_endpoint": "https://ta.example.org/collect"
        }
      }
    }

[¶](#section-3.2-3)

### [3.3.](#section-3.3) [Entity Collection Request](#name-entity-collection-request)

#### [3.3.1.](#section-3.3.1) [Request Format](#name-request-format)

When client authentication is not used, the request to the `federation_collection_endpoint` MUST be an HTTP request using the GET method with the following query parameters, encoded in `application/x-www-form-urlencoded` format:[¶](#section-3.3.1-1)

-   **from**: (OPTIONAL) If this parameter is present, the resulting list MUST be the subset of the overall ordered response starting from this pointer. This parameter MUST be copied from the `next` response parameter of a previous request. If the pointer in this parameter is not or not longer known to the responder, it MUST return an error response with the error code `page_not_found` as defined in [Error Response Format](#error-response-format).\
    If the responder does not support this feature, it MUST return an error response with the error code `unsupported_parameter` as defined in [Error Response Format](#error-response-format).[¶](#section-3.3.1-2.1.1)

-   **limit**: (OPTIONAL) Requested number of results included in the response. If this parameter is present, the number of results in the returned list MUST NOT be greater than the minimum of the responder\'s upper limit and the value of this parameter. If this parameter is not present the server MUST fall back on the upper limit.\
    If the responder does not support this feature, it MUST return an error response with the error code `unsupported_parameter` as defined in [Error Response Format](#error-response-format).[¶](#section-3.3.1-2.2.1)

-   **entity_type**: (OPTIONAL) The value of this parameter is an Entity Type Identifier. The result MUST be filtered to include only those entities that include the specified Entity Type. When multiple `entity_type` parameters are present, for example `entity_type=openid_provider&entity_type=openid_relying_party`, the result MUST be filtered to include all Entities that include any of the specified Entity Types. If the responder does not support this feature, it MUST return an error response with the error code `unsupported_parameter` as defined in [Error Response Format](#error-response-format).[¶](#section-3.3.1-2.3.1)

-   **trust_mark_type**: (OPTIONAL) The value of this parameter is a Trust Mark Type Identifier. The result MUST be filtered to include only Entities that publish a Trust Mark of this Trust Mark Type in their Entity Configuration and that Trust Mark MUST be verified by the responder. The responder SHOULD verify the Trust Mark using the same Trust Anchor that is used to collect the Entities. When multiple `trust_mark_type` parameters are present, the result MUST be filtered to include only Entities that have a Trust Mark for all the specified Trust Mark Types.\
    If the responder does not support this feature, it MUST return an error response with the error code `unsupported_parameter` as defined in [Error Response Format](#error-response-format).[¶](#section-3.3.1-2.4.1)

-   **trust_anchor**: (RECOMMENDED) The Trust Anchor that the collection endpoint MUST use when collecting Entities. The value is an Entity Identifier. If omitted, the responder sets this parameter to its own Entity Identifier. If the responder does not have a defined Entity Identifier, it MUST return an error response with the error code `invalid_request` as defined in [Error Response Format](#error-response-format).[¶](#section-3.3.1-2.5.1)

-   **query**: (OPTIONAL) The value of this parameter is used by the responder to filter down the list of returned Entities to only entities that match this parameter value. It is entirely up to the responder to define when an Entity matches the query.\
    If the responder does not support this feature, it MUST return an error response with the error code `unsupported_parameter` as defined in [Error Response Format](#error-response-format).[¶](#section-3.3.1-2.6.1)

-   **entity_claims**: (OPTIONAL) Array of claims to be included in the Entity Info Object included in the response for each collected Entity.\
    If this parameter is NOT present it is at the discretion of the responder which claims are included or not.\
    If this parameter is present and it is NOT an empty array, each Entity Info Object that represents an Entity MUST include the requested claims unless a specific claim is not available for that Entity. Also Claims that are optional to return and not present in the array MUST NOT be included in the Entity Info.\
    If the responder does not support a requested claim, it MUST return an error response with the error code `unsupported_parameter` as defined in [Error Response Format](#error-response-format).[¶](#section-3.3.1-2.7.1)

-   **ui_claims**: (OPTIONAL) Array of claims to be included in the Entity Type UI Info Object included in the response for each returned Entity.\
    If this parameter is NOT present it is at the discretion of the responder which claims are included or not.\
    If this parameter is present and it is NOT an empty array, each Entity Type UI Info Object MUST include the requested claims unless a specific claim is not available for that Entity and Entity Type.\
    If the responder does not support a requested claim, it MUST return an error response with the error code `unsupported_parameter` as defined in [Error Response Format](#error-response-format).[¶](#section-3.3.1-2.8.1)

When Client authentication is used, the request MUST be an HTTP request using the POST method, with the parameters passed in the POST body.[¶](#section-3.3.1-3)

##### [3.3.1.1.](#section-3.3.1.1) [Example Request](#name-example-request)

The following is a non-normative example of a collection request:[¶](#section-3.3.1.1-1)

    GET /collection?entity_type=openid_provider&trust_mark_type=https%3A%2F%2Frp%2Erefeds.org%2Fsitfi&trust_anchor=https%3A%2F%2Fswamid.se HTTP/1.1
    Host: openid.sunet.se

[¶](#section-3.3.1.1-2)

### [3.4.](#section-3.4) [Entity Collection Response](#name-entity-collection-response)

#### [3.4.1.](#section-3.4.1) [Response Format](#name-response-format)

A successful response MUST use the HTTP status code 200 and the content type `application/json`.[¶](#section-3.4.1-1)

The response is a JSON object with the following claims:[¶](#section-3.4.1-2)

-   **entities**: (REQUIRED) Array of JSON objects, each representing a Federation Entity as described in [Entity Info](#entity-info). The list of Entities MUST only contain Entities that are in line with the requested parameters. The responder MAY also filter down the list further at its own discretion.[¶](#section-3.4.1-3.1)
-   **next**: (OPTIONAL) An opaque pointer to the next page in the result list. This attribute is REQUIRED when additional results are available beyond those included in the `entities` array. To content of this attribute is entirely up to the responder and its pagination implementation strategy.[¶](#section-3.4.1-3.2)
-   **last_updated**: (RECOMMENDED) Number. Time when the responder last updated the result list. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\]. If the `last_updated` time changes between paginated calls, this might be an indication for the client that it might have received outdated information in a previous call.[¶](#section-3.4.1-3.3)

Additional claims MAY be defined and used in conjunction with the claims above.[¶](#section-3.4.1-4)

##### [3.4.1.1.](#section-3.4.1.1) [Entity Info](#name-entity-info)

Each JSON Object in the returned `entities` array MAY contain the following claims:[¶](#section-3.4.1.1-1)

-   **entity_id**: (REQUIRED) The Entity Identifier for the subject entity of the current record.[¶](#section-3.4.1.1-2.1)

-   **entity_types**: (RECOMMENDED) Array of string Entity Type Identifiers. If present this claim MUST contain all Entity Type Identifiers of the subject\'s Entity the responder knows about.[¶](#section-3.4.1.1-2.2)

-   **ui_infos**: (OPTIONAL) JSON Object containing information intended to be displayed to the user for each entity type as described in [UI Infos](#ui-infos).\
    If the request contains the `entity_type` parameter, the UI Infos Object MUST only contain Entity Type Identifiers that are among the ones requested, with the exception of the `federation_entity` Entity Type Identifier, which MAY also appear if not explicitly requested.[¶](#section-3.4.1.1-2.3.1)

-   **trust_marks**: (OPTIONAL) Array of objects, each representing a Trust Mark, as defined in Section 3 of \[[OpenID.Federation](#OpenID.Federation)\].[¶](#section-3.4.1.1-2.4.1)

Additional claims MAY be defined and used in conjunction with the claims above.[¶](#section-3.4.1.1-3)

###### [3.4.1.1.1.](#section-3.4.1.1.1) [UI Infos](#name-ui-infos)

UI Infos is a JSON Object containing UI-related information about a single Entity, but differentiated by its Entity Types.[¶](#section-3.4.1.1.1-1)

Each member name of the JSON object is an Entity Type Identifier and each value is an Entity Type UI Info Object as defined in [Entity Type UI Info](#entity-type-ui-info).[¶](#section-3.4.1.1.1-2)

###### [3.4.1.1.1.1.](#section-3.4.1.1.1.1) [Entity Type UI Info](#name-entity-type-ui-info)

Entity Type UI Info is a JSON Object containing UI-related information about a single Entity Type of an Entity.[¶](#section-3.4.1.1.1.1-1)

All Claims specified in section 5.2.2 \"Informational Metadata Extensions\" of \[[OpenID.Federation](#OpenID.Federation)\] MAY be used.[¶](#section-3.4.1.1.1.1-2)

Additional Claims MAY be defined and used in conjunction with the Claims above.[¶](#section-3.4.1.1.1.1-3)

##### [3.4.1.2.](#section-3.4.1.2) [Example Response](#name-example-response)

    {
      "entities": [
        {
          "entity_id": "https://green.example.com",
          "entity_types": [
            "federation_entity"
          ],
          "ui_infos": {
            "federation_entity": {
              "display_name": "The green organization",
              "logo_uri": "https://green.example.com/logo.png"
            }
          }
        },
        {
          "entity_id": "https://red.example.com",
          "entity_types": [
            "openid_relying_party",
            "federation_entity"
          ],
          "ui_infos": {
            "federation_entity": {
              "display_name": "Red Organization",
              "logo_uri": "https://red.example.com/logo.png"
            },
            "openid_relying_party": {
              "display_name": "Red RP",
              "logo_uri": "https://red.example.com/logo.png"
            }
          }
        },
        {
          "entity_id": "https://op.example.com",
          "entity_types": [
            "openid_provider"
          ]
        }
      ]
    }

[¶](#section-3.4.1.2-1)

#### [3.4.2.](#section-3.4.2) [Error Response Format](#name-error-response-format)

If the request was malformed or an error occurred during the processing of the request, the response body MUST be a JSON object with the content type `application/json`. In compliance with \[[RFC6749](#RFC6749)\] and \[[OpenID.Federation](#OpenID.Federation)\], the following standardized error format MUST be used:[¶](#section-3.4.2-1)

-   **error**: (REQUIRED) Error codes in the IANA \"OAuth Extensions Error Registry\" \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\] MAY be used. In particular, these existing error codes are used by this specification:[¶](#section-3.4.2-2.1.1)

    -   **unsupported_parameter**: The server does not support a requested parameter. The HTTP response status code SHOULD be 400 (Bad Request).[¶](#section-3.4.2-2.1.2.1)
    -   **invalid_request**: The request is incomplete or does not comply with current specifications. The HTTP response status code SHOULD be 400 (Bad Request).\
        \
        In addition the following error codes defined by this specification MAY be used:[¶](#section-3.4.2-2.1.2.2)
    -   **page_not_found**: The pagination pointer provided in the `from` parameter is not or no longer known to the responder. The HTTP response status code SHOULD be 404 (Not Found).[¶](#section-3.4.2-2.1.2.3)

-   **error_description**: (REQUIRED) Human-readable text providing additional information used to assist the developer in understanding the error that occurred.[¶](#section-3.4.2-2.2)

The following is a non-normative example of an error response:[¶](#section-3.4.2-3)

    400 Bad Request
    Content-Type: application/json

    {
      "error": "unsupported_parameter",
      "error_description": "The 'limit' parameter is not supported by this endpoint."
    }

[¶](#section-3.4.2-4)

## [4.](#section-4) [Claims Languages and Scripts](#name-claims-languages-and-script)

Human-readable claim values and claim values that reference human-readable values MAY be represented in multiple languages and scripts. This specification enables such representations in the same manner as defined in Section 14 of OpenID Federation \[[OpenID.Federation](#OpenID.Federation)\] and Section 5.2 of OpenID Connect Core 1.0 \[[OpenID.Core](#OpenID.Core)\].[¶](#section-4-1)

As described in OpenID Connect Core, to specify the languages and scripts, BCP47 \[[RFC5646](#RFC5646)\] language tags are added to member names, delimited by a `#` character. For example, `family_name#ja-Kana-JP` expresses the Family Name in Katakana in Japanese, which is commonly used to index and represent the phonetics of the Kanji representation of the same name represented as `family_name#ja-Hani-JP`.[¶](#section-4-2)

The following is an example of an Entity Type UI Info Object with claims represented in multiple languages:[¶](#section-4-3)

    {
      "description": "Karlsruhe Institute of Technology - The Research University in the Helmholtz Association",
      "description#de": "Karlsruher Institut für Technologie - Die Forschungsuniversität in der Helmholtz-Gemeinschaft",
      "description#en": "Karlsruhe Institute of Technology - The Research University in the Helmholtz Association",
      "display_name": "Karlsruhe Institute of Technology (KIT)",
      "display_name#de": "Karlsruher Institut für Technologie (KIT)",
      "display_name#en": "Karlsruhe Institute of Technology (KIT)"
    }

[¶](#section-4-4)

## [5.](#section-5) [Implementation Considerations](#name-implementation-consideratio)

### [5.1.](#section-5.1) [Collecting Entities](#name-collecting-entities)

It is up to the implementation to decide how entities are collected. A general and straightforward approach is described in section 17.2.2 of \[[OpenID.Federation](#OpenID.Federation)\]. Implementations MAY use other approaches to collect entities. Those MAY also be based on additional information available to the responder. An example could be when the entity collection response should only include entities with a certain Trust Mark which is issued by the same Entity that provides the Entity Collection Endpoint.[¶](#section-5.1-1)

### [5.2.](#section-5.2) [Mapping Entity Configuration Claims to UI Info Response Claims](#name-mapping-entity-configuratio)

It is up to the implementation to decide how the claims of UI Info Objects in the response are populated. Implementations SHOULD consider the information published by entities in their Entity Configuration and MAY consider additional information.[¶](#section-5.2-1)

The following mapping between Claims in the `ui_infos` response Claim and Metadata Claims in the Entity Configuration SHOULD be considered:[¶](#section-5.2-2)

-   `display_name`: The `display_name` Claim is a common Metadata Claim useable with all Entity Types. If set it SHOULD be copied to the response. If the `display_name` Claim is not set other Claims MAY be considered, such as:[¶](#section-5.2-3.1.1)

    -   For the `openid_relying_party` Entity Type: `client_name`[¶](#section-5.2-3.1.2.1)
    -   For the `oauth_client` Entity Type: `client_name`[¶](#section-5.2-3.1.2.2)
    -   For the `oauth_resource` Entity Type: `resource_name`[¶](#section-5.2-3.1.2.3)

-   `description`: The `description` Claim is a common Metadata Claim useable with all Entity Types. It SHOULD be copied to the response.[¶](#section-5.2-3.2)

-   `keywords`: The `keywords` Claim is a common Metadata Claim useable with all Entity Types. It SHOULD be copied to the response.[¶](#section-5.2-3.3)

-   `logo_uri`: The `logo_uri` Claim is a common Metadata Claim useable with all Entity Types. It SHOULD be copied to the response.[¶](#section-5.2-3.4)

-   `policy_uri`: The `policy_uri` Claim is a common Metadata Claim useable with all Entity Types. It SHOULD be copied to the response.[¶](#section-5.2-3.5)

-   `information_uri`: The `information_uri` Claim is a common Metadata Claim useable with all Entity Types. It SHOULD be copied to the response.[¶](#section-5.2-3.6)

### [5.3.](#section-5.3) [Entity Collection Endpoint Scope](#name-entity-collection-endpoint-)

The responder is free to restrict the scope of its Entity Collection Endpoint, such as, but not limited to:[¶](#section-5.3-1)

-   Only supporting a limited set of Trust Anchors.[¶](#section-5.3-2.1)

-   Filter out Entities from the response at their own discretion. Such additional filters MAY be:[¶](#section-5.3-2.2.1)

    -   Only Entities that have a certain Trust Mark.[¶](#section-5.3-2.2.2.1)
    -   Only Entities that have a valid Trust Chain to the Trust Anchor.[¶](#section-5.3-2.2.2.2)
    -   Only Entities that are resolvable at the Resolve Endpoint of the Entity providing the Entity Collection Endpoint.[¶](#section-5.3-2.2.2.3)

## [6.](#section-6) [Security Considerations](#name-security-considerations)

In addition to the considerations below, the security considerations of OpenID Federation 1.0 \[[OpenID.Federation](#OpenID.Federation)\] apply to this specification.[¶](#section-6-1)

### [6.1.](#section-6.1) [Unsigned Response](#name-unsigned-response)

The response from the Entity Collection Endpoint is not signed and the obtained information MUST be considered as informational. To verify an Entity, proper trust validation according to OpenID Federation 1.0 \[[OpenID.Federation](#OpenID.Federation)\] still MUST be done.[¶](#section-6.1-1)

It is also noted that Trust Marks returned in the response MAY not be verified and clients MUST consider them as not yet verified.[¶](#section-6.1-2)

## [7.](#section-7) [Normative References](#name-normative-references)

\[IANA.OAuth.Parameters\]
:   IANA, \"OAuth Parameters\", 25 March 2026, \<<https://www.iana.org/assignments/oauth-parameters>\>.
:   

\[OpenID.Core\]
:   Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, \"OpenID Connect Core 1.0 incorporating errata set 2\", 15 December 2023, \<<http://openid.net/specs/openid-connect-core-1_0.html>\>.
:   

\[OpenID.Federation\]
:   Ed., R. H., Jones, M. B., Solberg, A., Bradley, J., Marco, G. D., and V. Dzhuvinov, \"OpenID Federation 1.0\", 17 February 2026, \<<https://openid.net/specs/openid-federation-1_0.html>\>.
:   

\[RFC2119\]
:   Bradner, S., \"Key words for use in RFCs to Indicate Requirement Levels\", BCP 14, RFC 2119, DOI 10.17487/RFC2119, March 1997, \<<https://www.rfc-editor.org/info/rfc2119>\>.
:   

\[RFC5646\]
:   Phillips, A., Ed. and M. Davis, Ed., \"Tags for Identifying Languages\", BCP 47, RFC 5646, DOI 10.17487/RFC5646, September 2009, \<<https://www.rfc-editor.org/info/rfc5646>\>.
:   

\[RFC6749\]
:   Hardt, D., Ed., \"The OAuth 2.0 Authorization Framework\", RFC 6749, DOI 10.17487/RFC6749, October 2012, \<<https://www.rfc-editor.org/info/rfc6749>\>.
:   

\[RFC7519\]
:   Jones, M., Bradley, J., and N. Sakimura, \"JSON Web Token (JWT)\", RFC 7519, DOI 10.17487/RFC7519, May 2015, \<<https://www.rfc-editor.org/info/rfc7519>\>.
:   

\[RFC8174\]
:   Leiba, B., \"Ambiguity of Uppercase vs Lowercase in RFC 2119 Key Words\", BCP 14, RFC 8174, DOI 10.17487/RFC8174, May 2017, \<<https://www.rfc-editor.org/info/rfc8174>\>.
:   

## [Appendix A.](#appendix-A) [Notices](#name-notices)

Copyright (c) 2026 The OpenID Foundation.[¶](#appendix-A-1)

The OpenID Foundation (OIDF) grants to any Contributor, developer, implementer, or other interested party a non-exclusive, royalty free, worldwide copyright license to reproduce, prepare derivative works from, distribute, perform and display, this Implementers Draft, Final Specification, or Final Specification Incorporating Errata Corrections solely for the purposes of (i) developing specifications, and (ii) implementing Implementers Drafts, Final Specifications, and Final Specification Incorporating Errata Corrections based on such documents, provided that attribution be made to the OIDF as the source of the material, but that such attribution does not indicate an endorsement by the OIDF.[¶](#appendix-A-2)

The technology described in this specification was made available from contributions from various sources, including members of the OpenID Foundation and others. Although the OpenID Foundation has taken steps to help ensure that the technology is available for distribution, it takes no position regarding the validity or scope of any intellectual property or other rights that might be claimed to pertain to the implementation or use of the technology described in this specification or the extent to which any license under such rights might or might not be available; neither does it represent that it has made any independent effort to identify any such rights. The OpenID Foundation and the contributors to this specification make no (and hereby expressly disclaim any) warranties (express, implied, or otherwise), including implied warranties of merchantability, non-infringement, fitness for a particular purpose, or title, related to this specification, and the entire risk as to implementing this specification is assumed by the implementer. The OpenID Intellectual Property Rights policy (found at openid.net) requires contributors to offer a patent promise not to assert certain patent claims against other contributors and against implementers. OpenID invites any interested party to bring to its attention any copyrights, patents, patent applications, or other proprietary rights that may cover technology that may be required to practice this specification.[¶](#appendix-A-3)

## [Appendix B.](#appendix-B) [Acknowledgements](#name-acknowledgements)

We would like to thank the following individuals for their contributions to this specification: Niels van Dijk, Michael Fraser, Marko Ivančić, Łukasz Jaromin, Michael B. Jones, Giuseppe De Marco, Stefan Santesson, Phil Smart, Zacharias Törnblom, and the Geant Trust & Identity Incubator of Geant5-2.[¶](#appendix-B-1)

## [Appendix C.](#appendix-C) [Document History](#name-document-history)

\[\[ To be removed from the final specification \]\][¶](#appendix-C-1)

-00[¶](#appendix-C-2)

-   Initial version, fixing OpenID Federation issue #56.[¶](#appendix-C-3.1)

## [Author\'s Address](#name-authors-address)

Gabriel Zachmann

Karlsruhe Institute of Technology

Email: <gabriel.zachmann@kit.edu>
