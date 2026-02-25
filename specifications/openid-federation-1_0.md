---
author:
- Roland Hedberg
- Michael B. Jones
- Andreas Åkre Solberg
- John Bradley
- Giuseppe De Marco
- Vladimir Dzhuvinov
description:  A federation can be expressed as an agreement between parties that trust each other. In bilateral federations, direct trust can be established between two organizations belonging to the same federation. In a multilateral federation, bilateral agreements might not be practical, in which case, trust can be mediated by a third party. That is the model used in this specification. An Entity in the federation must be able to trust that other Entities it interacts with belong to the same federation. It must also be able to trust that the information the other Entities publish about themselves has not been tampered with during transport and that it adheres to the federation\'s policies. This specification defines basic components to build multilateral federations. It also defines how to apply them in the contexts of OpenID Connect and OAuth 2.0. These components can be used by other application protocols for the purpose of establishing trust.
generator: xml2rfc 3.31.0
ietf.draft: openid-federation-1_0
keyword:
- OpenID
- OpenID Connect
- Federation
- Multilateral Federation
- Federation Entity
- Federation Operator
- Trust Anchor
- Trust Chain
- Trust Establishment
- Trust Mark
lang: en
scripts: Common,Latin
title: OpenID Federation 1.0
viewport: initial-scale=1.0
---

                    OpenID Federation   February 2026
  ----------------- ------------------- ---------------
  Hedberg, et al.   Standards Track     \[Page\]

Workgroup:
:   OpenID Connect Working Group

Published:
:   17 February 2026

Status:
:   Final

Authors:

:   R. Hedberg, Ed.

    independent

    M.B. Jones

    Self-Issued Consulting

    A.Å. Solberg

    Sikt

    J. Bradley

    Yubico

    G. De Marco

    independent

    V. Dzhuvinov

    Connect2id

# OpenID Federation 1.0

## [Abstract](#abstract)

A federation can be expressed as an agreement between parties that trust each other. In bilateral federations, direct trust can be established between two organizations belonging to the same federation. In a multilateral federation, bilateral agreements might not be practical, in which case, trust can be mediated by a third party. That is the model used in this specification.[¶](#section-abstract-1)

An Entity in the federation must be able to trust that other Entities it interacts with belong to the same federation. It must also be able to trust that the information the other Entities publish about themselves has not been tampered with during transport and that it adheres to the federation\'s policies.[¶](#section-abstract-2)

This specification defines basic components to build multilateral federations. It also defines how to apply them in the contexts of OpenID Connect and OAuth 2.0. These components can be used by other application protocols for the purpose of establishing trust.[¶](#section-abstract-3)

[▲](#)

## [Table of Contents](#name-table-of-contents)

-   [1](#section-1).  [Introduction](#name-introduction)

    -   [1.1](#section-1.1).  [Requirements Notation and Conventions](#name-requirements-notation-and-c)

    -   [1.2](#section-1.2).  [Terminology](#name-terminology)

-   [2](#section-2).  [Overall Architecture](#name-overall-architecture)

    -   [2.1](#section-2.1).  [Cryptographic Trust Mechanism](#name-cryptographic-trust-mechani)

-   [3](#section-3).  [Entity Statement](#name-entity-statement)

    -   [3.1](#section-3.1).  [Entity Statement Claims](#name-entity-statement-claims)

        -   [3.1.1](#section-3.1.1).  [Claims that MUST or MAY Appear in both Entity Configurations and Subordinate Statements](#name-claims-that-must-or-may-app)

        -   [3.1.2](#section-3.1.2).  [Claims that MUST or MAY Appear in Entity Configurations but Not in Subordinate Statements](#name-claims-that-must-or-may-appe)

        -   [3.1.3](#section-3.1.3).  [Claims that MUST or MAY Appear in Subordinate Statements but Not in Entity Configurations](#name-claims-that-must-or-may-appea)

        -   [3.1.4](#section-3.1.4).  [Claims Used in Explicit Registration Requests](#name-claims-used-in-explicit-reg)

        -   [3.1.5](#section-3.1.5).  [Claims Used in Explicit Registration Responses](#name-claims-used-in-explicit-regi)

    -   [3.2](#section-3.2).  [Entity Statement Validation](#name-entity-statement-validation)

    -   [3.3](#section-3.3).  [Entity Statement Example](#name-entity-statement-example)

-   [4](#section-4).  [Trust Chain](#name-trust-chain)

    -   [4.1](#section-4.1).  [Beginning and Ending Trust Chains](#name-beginning-and-ending-trust-)

    -   [4.2](#section-4.2).  [Trust Chain Example](#name-trust-chain-example)

    -   [4.3](#section-4.3).  [Trust Chain Header Parameter](#name-trust-chain-header-paramete)

    -   [4.4](#section-4.4).  [Peer Trust Chain Header Parameter](#name-peer-trust-chain-header-par)

-   [5](#section-5).  [Metadata](#name-metadata)

    -   [5.1](#section-5.1).  [Entity Type Identifiers](#name-entity-type-identifiers)

        -   [5.1.1](#section-5.1.1).  [Federation Entity](#name-federation-entity)

        -   [5.1.2](#section-5.1.2).  [OpenID Connect Relying Party](#name-openid-connect-relying-part)

        -   [5.1.3](#section-5.1.3).  [OpenID Connect OpenID Provider](#name-openid-connect-openid-provi)

        -   [5.1.4](#section-5.1.4).  [OAuth Authorization Server](#name-oauth-authorization-server)

        -   [5.1.5](#section-5.1.5).  [OAuth Client](#name-oauth-client)

        -   [5.1.6](#section-5.1.6).  [OAuth Protected Resource](#name-oauth-protected-resource)

    -   [5.2](#section-5.2).  [Common Metadata Parameters](#name-common-metadata-parameters)

        -   [5.2.1](#section-5.2.1).  [Parameters for JWK Sets in Entity Metadata](#name-parameters-for-jwk-sets-in-)

            -   [5.2.1.1](#section-5.2.1.1).  [Usage of jwks, jwks_uri, and signed_jwks_uri in Entity Metadata](#name-usage-of-jwks-jwks_uri-and-)

        -   [5.2.2](#section-5.2.2).  [Informational Metadata Parameters](#name-informational-metadata-para)

-   [6](#section-6).  [Federation Policy](#name-federation-policy)

    -   [6.1](#section-6.1).  [Metadata Policy](#name-metadata-policy)

        -   [6.1.1](#section-6.1.1).  [Principles](#name-principles)

        -   [6.1.2](#section-6.1.2).  [Structure](#name-structure)

        -   [6.1.3](#section-6.1.3).  [Operators](#name-operators)

            -   [6.1.3.1](#section-6.1.3.1).  [Standard Operators](#name-standard-operators)

                -   [6.1.3.1.1](#section-6.1.3.1.1).  [value](#name-value)

                -   [6.1.3.1.2](#section-6.1.3.1.2).  [add](#name-add)

                -   [6.1.3.1.3](#section-6.1.3.1.3).  [default](#name-default)

                -   [6.1.3.1.4](#section-6.1.3.1.4).  [one_of](#name-one_of)

                -   [6.1.3.1.5](#section-6.1.3.1.5).  [subset_of](#name-subset_of)

                -   [6.1.3.1.6](#section-6.1.3.1.6).  [superset_of](#name-superset_of)

                -   [6.1.3.1.7](#section-6.1.3.1.7).  [essential](#name-essential)

                -   [6.1.3.1.8](#section-6.1.3.1.8).  [Notes on Operators](#name-notes-on-operators)

            -   [6.1.3.2](#section-6.1.3.2).  [Additional Operators](#name-additional-operators)

        -   [6.1.4](#section-6.1.4).  [Enforcement](#name-enforcement)

            -   [6.1.4.1](#section-6.1.4.1).  [Resolution](#name-resolution)

            -   [6.1.4.2](#section-6.1.4.2).  [Application](#name-application)

        -   [6.1.5](#section-6.1.5).  [Metadata Policy Example](#name-metadata-policy-example)

    -   [6.2](#section-6.2).  [Constraints](#name-constraints)

        -   [6.2.1](#section-6.2.1).  [Max Path Length Constraint](#name-max-path-length-constraint)

        -   [6.2.2](#section-6.2.2).  [Naming Constraints](#name-naming-constraints)

        -   [6.2.3](#section-6.2.3).  [Entity Type Constraints](#name-entity-type-constraints)

-   [7](#section-7).  [Trust Marks](#name-trust-marks)

    -   [7.1](#section-7.1).  [Trust Mark Claims](#name-trust-mark-claims)

    -   [7.2](#section-7.2).  [Trust Mark Delegation](#name-trust-mark-delegation)

        -   [7.2.1](#section-7.2.1).  [Trust Mark Delegation JWT](#name-trust-mark-delegation-jwt)

        -   [7.2.2](#section-7.2.2).  [Validating a Trust Mark Delegation](#name-validating-a-trust-mark-del)

    -   [7.3](#section-7.3).  [Validating a Trust Mark](#name-validating-a-trust-mark)

    -   [7.4](#section-7.4).  [Trust Mark Examples](#name-trust-mark-examples)

    -   [7.5](#section-7.5).  [Trust Mark Delegation Example](#name-trust-mark-delegation-examp)

-   [8](#section-8).  [Federation Endpoints](#name-federation-endpoints)

    -   [8.1](#section-8.1).  [Fetching a Subordinate Statement](#name-fetching-a-subordinate-stat)

        -   [8.1.1](#section-8.1.1).  [Fetch Subordinate Statement Request](#name-fetch-subordinate-statement)

        -   [8.1.2](#section-8.1.2).  [Fetch Subordinate Statement Response](#name-fetch-subordinate-statement-)

    -   [8.2](#section-8.2).  [Subordinate Listing](#name-subordinate-listing)

        -   [8.2.1](#section-8.2.1).  [Subordinate Listing Request](#name-subordinate-listing-request)

        -   [8.2.2](#section-8.2.2).  [Subordinate Listing Response](#name-subordinate-listing-respons)

    -   [8.3](#section-8.3).  [Resolve Entity](#name-resolve-entity)

        -   [8.3.1](#section-8.3.1).  [Resolve Request](#name-resolve-request)

        -   [8.3.2](#section-8.3.2).  [Resolve Response](#name-resolve-response)

        -   [8.3.3](#section-8.3.3).  [Trust Considerations](#name-trust-considerations)

    -   [8.4](#section-8.4).  [Trust Mark Status](#name-trust-mark-status)

        -   [8.4.1](#section-8.4.1).  [Trust Mark Status Request](#name-trust-mark-status-request)

        -   [8.4.2](#section-8.4.2).  [Trust Mark Status Response](#name-trust-mark-status-response)

    -   [8.5](#section-8.5).  [Trust Marked Entities Listing](#name-trust-marked-entities-listi)

        -   [8.5.1](#section-8.5.1).  [Trust Marked Entities Listing Request](#name-trust-marked-entities-listin)

        -   [8.5.2](#section-8.5.2).  [Trust Marked Entities Listing Response](#name-trust-marked-entities-listing-)

    -   [8.6](#section-8.6).  [Trust Mark Endpoint](#name-trust-mark-endpoint)

        -   [8.6.1](#section-8.6.1).  [Trust Mark Request](#name-trust-mark-request)

        -   [8.6.2](#section-8.6.2).  [Trust Mark Response](#name-trust-mark-response)

    -   [8.7](#section-8.7).  [Federation Historical Keys Endpoint](#name-federation-historical-keys-)

        -   [8.7.1](#section-8.7.1).  [Federation Historical Keys Request](#name-federation-historical-keys-r)

        -   [8.7.2](#section-8.7.2).  [Federation Historical Keys Response](#name-federation-historical-keys-res)

        -   [8.7.3](#section-8.7.3).  [Federation Historical Keys Revocation Reasons](#name-federation-historical-keys-rev)

        -   [8.7.4](#section-8.7.4).  [Rationale for the Federation Historical Keys Endpoint](#name-rationale-for-the-federatio)

    -   [8.8](#section-8.8).  [Client Authentication at Federation Endpoints](#name-client-authentication-at-fe)

        -   [8.8.1](#section-8.8.1).  [Client Authentication Metadata for Federation Endpoints](#name-client-authentication-metad)

    -   [8.9](#section-8.9).  [Error Responses](#name-error-responses)

-   [9](#section-9).  [Obtaining Federation Entity Configuration Information](#name-obtaining-federation-entity)

    -   [9.1](#section-9.1).  [Federation Entity Configuration Request](#name-federation-entity-configura)

    -   [9.2](#section-9.2).  [Federation Entity Configuration Response](#name-federation-entity-configurat)

-   [10](#section-10). [Resolving the Trust Chain and Metadata](#name-resolving-the-trust-chain-a)

    -   [10.1](#section-10.1).  [Fetching Entity Statements to Establish a Trust Chain](#name-fetching-entity-statements-)

    -   [10.2](#section-10.2).  [Validating a Trust Chain](#name-validating-a-trust-chain)

    -   [10.3](#section-10.3).  [Choosing One of the Valid Trust Chains](#name-choosing-one-of-the-valid-t)

    -   [10.4](#section-10.4).  [Calculating the Expiration Time of a Trust Chain](#name-calculating-the-expiration-)

    -   [10.5](#section-10.5).  [Transient Trust Chain Validation Errors](#name-transient-trust-chain-valid)

    -   [10.6](#section-10.6).  [Resolving the Trust Chain and Metadata with a Resolver](#name-resolving-the-trust-chain-an)

-   [11](#section-11). [Updating Metadata, Key Rollover, and Revocation](#name-updating-metadata-key-rollo)

    -   [11.1](#section-11.1).  [Federation Key Rollover](#name-federation-key-rollover)

    -   [11.2](#section-11.2).  [Key Rollover for a Trust Anchor](#name-key-rollover-for-a-trust-an)

    -   [11.3](#section-11.3).  [Redundant Retrieval of Trust Anchor Keys](#name-redundant-retrieval-of-trus)

    -   [11.4](#section-11.4).  [Revocation](#name-revocation)

-   [12](#section-12). [OpenID Connect Client Registration](#name-openid-connect-client-regis)

    -   [12.1](#section-12.1).  [Automatic Registration](#name-automatic-registration)

        -   [12.1.1](#section-12.1.1).  [Authentication Request](#name-authentication-request)

            -   [12.1.1.1](#section-12.1.1.1).  [Using a Request Object](#name-using-a-request-object)

                -   [12.1.1.1.1](#section-12.1.1.1.1).  [Authorization Request with a Trust Chain](#name-authorization-request-with-)

                -   [12.1.1.1.2](#section-12.1.1.1.2).  [Processing the Authentication Request](#name-processing-the-authenticati)

            -   [12.1.1.2](#section-12.1.1.2).  [Using Pushed Authorization](#name-using-pushed-authorization)

                -   [12.1.1.2.1](#section-12.1.1.2.1).  [Processing the Pushed Authentication Request](#name-processing-the-pushed-authe)

        -   [12.1.2](#section-12.1.2).  [Successful Authentication Response](#name-successful-authentication-r)

        -   [12.1.3](#section-12.1.3).  [Authentication Error Response](#name-authentication-error-respon)

        -   [12.1.4](#section-12.1.4).  [Automatic Registration and Client Authentication](#name-automatic-registration-and-)

        -   [12.1.5](#section-12.1.5).  [Possible Other Uses of Automatic Registration](#name-possible-other-uses-of-auto)

    -   [12.2](#section-12.2).  [Explicit Registration](#name-explicit-registration)

        -   [12.2.1](#section-12.2.1).  [Explicit Client Registration Request](#name-explicit-client-registratio)

        -   [12.2.2](#section-12.2.2).  [Processing Explicit Client Registration Request by OP](#name-processing-explicit-client-)

        -   [12.2.3](#section-12.2.3).  [Successful Explicit Client Registration Response](#name-successful-explicit-client-)

        -   [12.2.4](#section-12.2.4).  [Explicit Client Registration Error Response](#name-explicit-client-registration)

        -   [12.2.5](#section-12.2.5).  [Processing Explicit Client Registration Response by RP](#name-processing-explicit-client-r)

        -   [12.2.6](#section-12.2.6).  [Explicit Client Registration Lifetime](#name-explicit-client-registration-)

    -   [12.3](#section-12.3).  [Registration Validity and Trust Reevaluation](#name-registration-validity-and-t)

    -   [12.4](#section-12.4).  [Differences between Automatic Registration and Explicit Registration](#name-differences-between-automat)

    -   [12.5](#section-12.5).  [Rationale for Trust Chains in the Request](#name-rationale-for-trust-chains-)

-   [13](#section-13). [General-Purpose JWT Claims](#name-general-purpose-jwt-claims)

    -   [13.1](#section-13.1).  [\"jwks\" (JSON Web Key Set) Claim](#name-jwks-json-web-key-set-claim)

    -   [13.2](#section-13.2).  [\"metadata\" Claim](#name-metadata-claim)

    -   [13.3](#section-13.3).  [\"constraints\" Claim](#name-constraints-claim)

    -   [13.4](#section-13.4).  [\"crit\" (Critical) Claim](#name-crit-critical-claim)

    -   [13.5](#section-13.5).  [\"ref\" (Reference) Claim](#name-ref-reference-claim)

    -   [13.6](#section-13.6).  [\"delegation\" Claim](#name-delegation-claim)

    -   [13.7](#section-13.7).  [\"logo_uri\" (Logo URI) Claim](#name-logo_uri-logo-uri-claim)

-   [14](#section-14). [Claims Languages and Scripts](#name-claims-languages-and-script)

-   [15](#section-15). [Media Types](#name-media-types)

    -   [15.1](#section-15.1).  [\"application/entity-statement+jwt\" Media Type](#name-application-entity-statemen)

    -   [15.2](#section-15.2).  [\"application/trust-mark+jwt\" Media Type](#name-application-trust-markjwt-m)

    -   [15.3](#section-15.3).  [\"application/resolve-response+jwt\" Media Type](#name-application-resolve-respons)

    -   [15.4](#section-15.4).  [\"application/trust-chain+json\" Media Type](#name-application-trust-chainjson)

    -   [15.5](#section-15.5).  [\"application/trust-mark-delegation+jwt\" Media Type](#name-application-trust-mark-dele)

    -   [15.6](#section-15.6).  [\"application/jwk-set+jwt\" Media Type](#name-application-jwk-setjwt-medi)

    -   [15.7](#section-15.7).  [\"application/trust-mark-status-response+jwt\" Media Type](#name-application-trust-mark-stat)

    -   [15.8](#section-15.8).  [\"application/explicit-registration-response+jwt\" Media Type](#name-application-explicit-regist)

-   [16](#section-16). [String Operations](#name-string-operations)

-   [17](#section-17). [Implementation Considerations](#name-implementation-consideratio)

    -   [17.1](#section-17.1).  [Federation Topologies](#name-federation-topologies)

    -   [17.2](#section-17.2).  [Federation Discovery and Trust Chain Resolution Patterns](#name-federation-discovery-and-tr)

        -   [17.2.1](#section-17.2.1).  [Bottom-Up Trust Chain Resolution](#name-bottom-up-trust-chain-resol)

        -   [17.2.2](#section-17.2.2).  [Top-Down Discovery](#name-top-down-discovery)

        -   [17.2.3](#section-17.2.3).  [Single Point of Trust Resolution](#name-single-point-of-trust-resol)

    -   [17.3](#section-17.3).  [Trust Anchors and Resolvers Go Together](#name-trust-anchors-and-resolvers)

    -   [17.4](#section-17.4).  [One Entity, One Service](#name-one-entity-one-service)

    -   [17.5](#section-17.5).  [Trust Mark Policies](#name-trust-mark-policies)

    -   [17.6](#section-17.6).  [Related Specifications](#name-related-specifications)

-   [18](#section-18). [Security Considerations](#name-security-considerations)

    -   [18.1](#section-18.1).  [Denial-of-Service Attack Prevention](#name-denial-of-service-attack-pr)

    -   [18.2](#section-18.2).  [Unsigned Error Messages](#name-unsigned-error-messages)

-   [19](#section-19). [Privacy Considerations](#name-privacy-considerations)

    -   [19.1](#section-19.1).  [Entity Statement Privacy Considerations](#name-entity-statement-privacy-co)

    -   [19.2](#section-19.2).  [Trust Mark Status Privacy Considerations](#name-trust-mark-status-privacy-c)

    -   [19.3](#section-19.3).  [Fetch Endpoint Privacy Considerations](#name-fetch-endpoint-privacy-cons)

-   [20](#section-20). [IANA Considerations](#name-iana-considerations)

    -   [20.1](#section-20.1).  [OAuth Dynamic Client Registration Metadata Registration](#name-oauth-dynamic-client-regist)

    -   [20.2](#section-20.2).  [OAuth Authorization Server Metadata Registration](#name-oauth-authorization-server-)

    -   [20.3](#section-20.3).  [OAuth Protected Resource Metadata Registration](#name-oauth-protected-resource-me)

    -   [20.4](#section-20.4).  [OAuth Parameters Registration](#name-oauth-parameters-registrati)

    -   [20.5](#section-20.5).  [OAuth Extensions Error Registration](#name-oauth-extensions-error-regi)

    -   [20.6](#section-20.6).  [JSON Web Signature and Encryption Header Parameters Registration](#name-json-web-signature-and-encr)

    -   [20.7](#section-20.7).  [JSON Web Key Parameters Registration](#name-json-web-key-parameters-reg)

    -   [20.8](#section-20.8).  [JSON Web Token Claims Registration](#name-json-web-token-claims-regis)

    -   [20.9](#section-20.9).  [Well-Known URI Registration](#name-well-known-uri-registration)

    -   [20.10](#section-20.10). [Media Type Registration](#name-media-type-registration)

-   [21](#section-21). [References](#name-references)

    -   [21.1](#section-21.1).  [Normative References](#name-normative-references)

    -   [21.2](#section-21.2).  [Informative References](#name-informative-references)

-   [Appendix A](#appendix-A).  [Examples Building and Using Trust Chains](#name-examples-building-and-using)

    -   [A.1](#appendix-A.1).  [Setting Up a Federation](#name-setting-up-a-federation)

    -   [A.2](#appendix-A.2).  [The LIGO Wiki Discovers the OP\'s Metadata](#name-the-ligo-wiki-discovers-the)

        -   [A.2.1](#appendix-A.2.1).  [Entity Configuration for https://op.umu.se](#name-entity-configuration-for-ht)

        -   [A.2.2](#appendix-A.2.2).  [Entity Configuration for https://umu.se](#name-entity-configuration-for-htt)

        -   [A.2.3](#appendix-A.2.3).  [Subordinate Statement Published by https://umu.se about https://op.umu.se](#name-subordinate-statement-publi)

        -   [A.2.4](#appendix-A.2.4).  [Entity Configuration for https://swamid.se](#name-entity-configuration-for-http)

        -   [A.2.5](#appendix-A.2.5).  [Subordinate Statement Published by https://swamid.se about https://umu.se](#name-subordinate-statement-publis)

        -   [A.2.6](#appendix-A.2.6).  [Entity Configuration for https://edugain.geant.org](#name-entity-configuration-for-https)

        -   [A.2.7](#appendix-A.2.7).  [Subordinate Statement Published by https://edugain.geant.org about https://swamid.se](#name-subordinate-statement-publish)

        -   [A.2.8](#appendix-A.2.8).  [OP Resolved Metadata for https://op.umu.se](#name-op-resolved-metadata-for-ht)

    -   [A.3](#appendix-A.3).  [Client Registration Method Examples](#name-client-registration-method-)

        -   [A.3.1](#appendix-A.3.1).  [RP Sends Authentication Request (Automatic Client Registration)](#name-rp-sends-authentication-req)

            -   [A.3.1.1](#appendix-A.3.1.1).  [OP Fetches Entity Statements](#name-op-fetches-entity-statement)

            -   [A.3.1.2](#appendix-A.3.1.2).  [OP Evaluates the RP Metadata](#name-op-evaluates-the-rp-metadat)

        -   [A.3.2](#appendix-A.3.2).  [RP Starts with Client Registration (Explicit Client Registration)](#name-rp-starts-with-client-regis)

-   [Appendix B](#appendix-B).  [Notices](#name-notices)

-   [](#appendix-C)[Acknowledgements](#name-acknowledgements)

-   [](#appendix-D)[Authors\' Addresses](#name-authors-addresses)

## [1.](#section-1) [Introduction](#name-introduction)

This specification describes how two Entities that would like to interact can establish trust between them by means of a trusted third party called a Trust Anchor. A Trust Anchor is an Entity whose main purpose is to issue statements about Entities. An identity federation can be realized using this specification using one or more levels of authorities. Examples of authorities are federation operators, organizations, departments within organizations, and individual sites. This specification provides the basic technical trust infrastructure building blocks needed to create a dynamic and distributed trust network, such as a federation.[¶](#section-1-1)

Note that this specification only concerns itself with how Entities in a federation get to know about each other. An organization MAY be represented by more than one Entity in a federation. An Entity MAY also belong to more than one federation. Determining that two Entities belong to the same federation is the basis for establishing trust between them in this specification.[¶](#section-1-2)

Of course, the word \"trust\" is also used in the vernacular to encompass confidence in the security, reliability, and integrity of entities and their actions. This kind of trust is often established through empirical proof, such as past performance, security certifications, or transparent operational practices, which demonstrate a track record of adherence to security standards and ethical conduct. To be clear, this broader meaning of trust, while important, is largely beyond the scope of what this specification accomplishes.[¶](#section-1-3)

Below is an example of two federations rooted at two different Trust Anchors with some members in common. Every Entity is able to establish mutual trust with any other Entity by means of having at least one common Trust Anchor between them. These abbreviations are used in the diagram: OpenID Provider (OP), Relying Party (RP), Resource Server (RS), and Authorization Server (AS).[¶](#section-1-4)

    .-----------------.            .-----------------.
    |  Trust Anchor A |            |  Trust Anchor B |
    '------.--.-------'            '----.--.--.------'
           |  |                         |  |  |
        .--'  '---. .-------------------'  |  |
        |         | |                      |  |
    .---v.  .-----v-v------.   .-----------'  |
    | OP |  | Intermediate |   |              |
    '----'  '--.--.--.-----'   |    .---------v----.
               |  |  |         |    | Intermediate |
       .-------'  |  '------.  |    '---.--.--.----'
       |          |         |  |        |  |  |
    .--v-.      .-v--.     .v--v.   .---'  |  '----.
    | RP |      | RS |     | OP |   |      |       |
    '----'      '----'     '----'   |   .--v-.   .-v--.
                                    |   | RP |   | RP |
                                    |   '----'   '----'
                                    |
                            .-------v------.
                            | Intermediate |
                            '----.--.--.---'
                                 |  |  |
                           .-----'  |  '----.
                           |        |       |
                        .--v-.   .--v-.   .-v--.
                        | OP |   | RP |   | AS |
                        '----'   '----'   '----'

[Figure 1](#figure-1): [Two Coexisting Federations with Some Members in Common](#name-two-coexisting-federations-)

### [1.1.](#section-1.1) [Requirements Notation and Conventions](#name-requirements-notation-and-c)

The key words \"MUST\", \"MUST NOT\", \"REQUIRED\", \"SHALL\", \"SHALL NOT\", \"SHOULD\", \"SHOULD NOT\", \"RECOMMENDED\", \"NOT RECOMMENDED\", \"MAY\", and \"OPTIONAL\" in this document are to be interpreted as described in BCP 14 \[[RFC2119](#RFC2119)\] \[[RFC8174](#RFC8174)\] when, and only when, they appear in all capitals, as shown here.[¶](#section-1.1-1)

All uses of JSON Web Signature (JWS) \[[RFC7515](#RFC7515)\] and JSON Web Encryption (JWE) \[[RFC7516](#RFC7516)\] data structures in this specification utilize the JWS Compact Serialization or the JWE Compact Serialization; the JWS JSON Serialization and the JWE JSON Serialization are not used.[¶](#section-1.1-2)

### [1.2.](#section-1.2) [Terminology](#name-terminology)

This specification uses the terms \"Claim\", \"Claim Name\", \"Claim Value\", \"JSON Web Token (JWT)\", and \"JWT Claims Set\" defined by [JSON Web Token (JWT)](#RFC7519) \[[RFC7519](#RFC7519)\], the terms \"OpenID Provider (OP)\" and \"Relying Party (RP)\" defined by [OpenID Connect Core 1.0](#OpenID.Core) \[[OpenID.Core](#OpenID.Core)\], and the terms \"Authorization Endpoint\", \"Authorization Server (AS)\", \"Client\", \"Client Authentication\", \"Client Identifier\", \"Client Secret\", \"Protected Resource\", \"Redirection URI\", \"Refresh Token\", \"Resource Server (RS)\", and \"Token Endpoint\" defined by [OAuth 2.0](#RFC6749) \[[RFC6749](#RFC6749)\].[¶](#section-1.2-1)

This specification also defines the following terms:[¶](#section-1.2-2)

Entity
:   Something that has a separate and distinct existence and that can be identified in a context.[¶](#section-1.2-3.2)
:   

Entity Identifier
:   A globally unique string identifier that is bound to one Entity. All Entity Identifiers defined by this specification are URLs that use the `https` scheme, have a host component, and MAY contain port and path components. It MUST NOT contain query parameter or fragment components. Profiles of this specification MAY define other kinds of Entity Identifiers and processing rules that accompany them.[¶](#section-1.2-3.4)
:   

Trust Anchor
:   An Entity that represents a trusted third party.[¶](#section-1.2-3.6)
:   

Federation Entity
:   An Entity for which it is possible to construct a Trust Chain from the Entity to a Trust Anchor.[¶](#section-1.2-3.8)
:   

Entity Statement
:   A signed JWT that contains the information needed for an Entity to participate in federation(s), including metadata about itself and policies that apply to other Entities for which it is authoritative.[¶](#section-1.2-3.10)
:   

Entity Configuration
:   An Entity Statement issued by an Entity about itself. It contains the Entity\'s signing keys and further data used to control the Trust Chain resolution process, such as authority hints.[¶](#section-1.2-3.12)
:   

Subordinate Statement
:   An Entity Statement issued by a Superior Entity about an Entity that is its Immediate Subordinate.[¶](#section-1.2-3.14)
:   

Entity Type
:   A role and function that an Entity plays within a federation. An Entity MUST be of at least one type and MAY be of many types. For example, an Entity can be both an OpenID Provider and Relying Party at the same time.[¶](#section-1.2-3.16)
:   

Entity Type Identifier
:   String identifier for an Entity Type.[¶](#section-1.2-3.18)
:   

Federation Operator
:   An organization that is authoritative for a federation. A federation operator administers the Trust Anchor(s) for Entities in its federation.[¶](#section-1.2-3.20)
:   

Intermediate Entity
:   An Entity that issues an Entity Statement appearing somewhere in between those issued by the Trust Anchor and the subject of a Trust Chain (which is typically a Leaf Entity). The terms Intermediate Entity and Intermediate are used interchangeably in this specification.[¶](#section-1.2-3.22)
:   

Leaf Entity
:   An Entity with no Subordinate Entities. Leaf Entities typically play a protocol role, such as an OpenID Connect Relying Party or OpenID Provider. The terms Leaf Entity and Leaf are used interchangeably in this specification.[¶](#section-1.2-3.24)
:   

Subordinate Entity
:   An Entity that is somewhere below a Superior Entity (a Trust Anchor or Intermediate) in the trust hierarchy, possibly with Intermediates between them. The terms Subordinate Entity and Subordinate are used interchangeably in this specification.[¶](#section-1.2-3.26)
:   

Superior Entity
:   An Entity that is somewhere above one or more Entities (a Leaf or Intermediate) in the trust hierarchy, possibly with Intermediates between them. The terms Superior Entity and Superior are used interchangeably in this specification.[¶](#section-1.2-3.28)
:   

Immediate Subordinate Entity
:   An Entity that is immediately below a Superior Entity in the trust hierarchy, with no Intermediates between them. The terms Immediate Subordinate Entity and Immediate Subordinate are used interchangeably in this specification.[¶](#section-1.2-3.30)
:   

Immediate Superior Entity
:   An Entity that is immediately above one or more Subordinate Entities in the trust hierarchy, with no Intermediates between them. The terms Immediate Superior Entity and Immediate Superior are used interchangeably in this specification.[¶](#section-1.2-3.32)
:   

Federation Entity Discovery
:   A process that starts with the Entity Identifier for the subject of the Trust Chain and collects Entity Statements until the chosen Trust Anchor is reached. From the collected Entity Statements, a Trust Chain is constructed and verified. The result of the Federation Entity Discovery is that the metadata for the Trust Chain subject is constructed from the Trust Chain.[¶](#section-1.2-3.34)
:   

Trust Chain
:   A sequence of Entity Statements that represents a chain starting at an Entity Configuration that is the subject of the chain (typically of a Leaf Entity) and ending in a Trust Anchor.[¶](#section-1.2-3.36)
:   

Trust Mark
:   Statement of conformance to a well-scoped set of trust and/or interoperability requirements as determined by an accreditation authority. Each Trust Mark has a Trust Mark type identifier.[¶](#section-1.2-3.38)
:   

Trust Mark Issuer
:   A Federation Entity that issues Trust Marks.[¶](#section-1.2-3.40)
:   

Trust Mark Owner
:   An Entity that owns the right to a Trust Mark type identifier.[¶](#section-1.2-3.42)
:   

Federation Entity Keys
:   Keys used for the cryptographic signatures required by the trust mechanisms defined in this specification. Every participant in a Federation publishes its public Federation Entity Keys in its Entity Configuration.[¶](#section-1.2-3.44)
:   

Resolved Metadata
:   The metadata that results from applying the metadata policy in the Trust Chain to the metadata in the Entity Configuration for the subject of the Trust Chain. The Resolved Metadata is the metadata that is used when interacting with the Entity.[¶](#section-1.2-3.46)
:   

## [2.](#section-2) [Overall Architecture](#name-overall-architecture)

The basic component is the Entity Statement, which is a cryptographically signed [JSON Web Token (JWT)](#RFC7519) \[[RFC7519](#RFC7519)\]. A set of Entity Statements can form a path from an Entity (typically a Leaf Entity) to a Trust Anchor. Entity Configurations issued by Entities about themselves control the Trust Chain resolution process.[¶](#section-2-1)

The Entity Configuration of a Leaf or Intermediate Entity contains one or more references to its Immediate Superiors in the `authority_hints` parameter described in [Section 3.1.2](#authority_hints). These references can be used to download the Entity Configuration of each Immediate Superior. One or more Entity Configurations are traversed during the Federation Entity Discovery until the Trust Anchor is reached.[¶](#section-2-2)

The Trust Anchor and its Intermediates issue Entity Statements about their Immediate Subordinate Entities called Subordinate Statements. The sequence of Entity Configurations and Subordinate Statements that validate the relationship between a Superior and a Subordinate, along a path towards the Trust Anchor, forms the proof that the subject of Trust Chain (typically a Leaf Entity) is a member of the federation rooted at the Trust Anchor.[¶](#section-2-3)

The Trust Chain that links the Entity Configurations to one another is verified with the signature of each Entity Configuration, as described in [Section 4](#trust_chain).[¶](#section-2-4)

Once there is a verified Trust Chain, the federation policy is applied and the metadata for the Trust Chain subject within the Federation is derived, as described in [Section 6](#federation_policy).[¶](#section-2-5)

This specification deals with trust operations; it does not cover or touch protocol operations other than metadata derivation and exchange. In OpenID Connect terms, these are the protocol operations specified in [OpenID Connect Discovery 1.0](#OpenID.Discovery) \[[OpenID.Discovery](#OpenID.Discovery)\] and [OpenID Connect Dynamic Client Registration 1.0](#OpenID.Registration) \[[OpenID.Registration](#OpenID.Registration)\].[¶](#section-2-6)

OpenID Connect constructs are used in many of the examples in this specification, however this does not mean that this specification can only be used with OpenID Connect. On the contrary, it can also be used to build federations for other protocols.[¶](#section-2-7)

### [2.1.](#section-2.1) [Cryptographic Trust Mechanism](#name-cryptographic-trust-mechani)

The objects defined by this specification that are used to establish cryptographic trust between participants are secured as signed JWTs using public key cryptography. In particular, the keys used for securing these objects are managed by the Entities controlling those objects, with the public keys securing them being distributed through those objects themselves. This kind of trust mechanism has been utilized by research and academic federations for over a decade.[¶](#section-2.1-1)

Note that this cryptographic trust mechanism intentionally does not rely on Web PKI / TLS \[[RFC9525](#RFC9525)\] certificates for signing keys. Which TLS certificates are considered trusted can vary considerably between systems depending upon which certificate authorities are considered trusted and there have been notable examples of ostensibly trusted certificates being compromised. For those reasons, this specification explicitly eschews reliance on Web PKI in favor of self-managed public keys, in this case, keys represented as JSON Web Keys (JWKs) \[[RFC7517](#RFC7517)\].[¶](#section-2.1-2)

## [3.](#section-3) [Entity Statement](#name-entity-statement)

An Entity Statement contains the information needed for the Entity that is the subject of the Entity Statement to participate in federation(s). An Entity Statement is a signed JWT. The subject of the JWT is the Entity itself. The issuer of the JWT is the party that issued the Entity Statement. All Entities in a federation publish an Entity Statement about themselves called an Entity Configuration. Superior Entities in a federation publish Entity Statements about their Immediate Subordinate Entities called Subordinate Statements.[¶](#section-3-1)

Entity Statement JWTs MUST be explicitly typed, by setting the `typ` header parameter to `entity-statement+jwt` to prevent cross-JWT confusion, per Section 3.11 of \[[RFC8725](#RFC8725)\]. Entity Statements without a `typ` header parameter or with a different `typ` value MUST be rejected.[¶](#section-3-2)

The Entity Statement is signed using one of the private keys of the issuer Entity in the form of a [JSON Web Signature (JWS)](#RFC7515) \[[RFC7515](#RFC7515)\]. Implementations SHOULD support signature verification with the RSA SHA-256 algorithm because OpenID Connect Core requires support for it (`alg` value of `RS256`). Federations MAY also specify different mandatory-to-implement algorithms. Note that a Trust Chain can contain Entity Statements signed using different signing algorithms, as long as each signature uses a signature algorithm supported by the trust framework and implementations in use.[¶](#section-3-3)

Entity Statement JWTs MUST include the `kid` (Key ID) header parameter with its value being the Key ID of the signing key used.[¶](#section-3-4)

### [3.1.](#section-3.1) [Entity Statement Claims](#name-entity-statement-claims)

The Claims in an Entity Statement are listed below. Applications and protocols utilizing Entity Statements MAY specify and use additional Claims.[¶](#section-3.1-1)

#### [3.1.1.](#section-3.1.1) [Claims that MUST or MAY Appear in both Entity Configurations and Subordinate Statements](#name-claims-that-must-or-may-app)

iss
:   REQUIRED. The Entity Identifier of the issuer of the Entity Statement. If the `iss` and the `sub` are identical, the issuer is making an Entity Statement about itself called an Entity Configuration.[¶](#section-3.1.1-1.2)
:   

sub
:   REQUIRED. The Entity Identifier of the subject.[¶](#section-3.1.1-1.4)
:   

iat
:   REQUIRED. Number. Time when this statement was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-3.1.1-1.6)
:   

exp
:   REQUIRED. Number. Expiration time after which the statement MUST NOT be accepted for processing. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-3.1.1-1.8)
:   

jwks

:   REQUIRED. A [JSON Web Key Set (JWKS)](#RFC7517) \[[RFC7517](#RFC7517)\] representing the public part of the subject\'s Federation Entity signing keys. The corresponding private key is used by the Entity to sign the Entity Configuration about itself, by Trust Anchors and Intermediate Entities to sign Subordinate Statements about their Immediate Subordinates, and for other signatures made by Federation Entities, such as Trust Mark signatures. However, this claim is OPTIONAL for the Entity Statement returned as an Explicit Registration Response, as defined in [Section 12.2.3](#cliregresp); in all other cases, it is REQUIRED. Every JWK in the JWK Set MUST have a unique `kid` (Key ID) value. It is RECOMMENDED that the Key ID be the JWK Thumbprint \[[RFC7638](#RFC7638)\] using the SHA-256 hash function of the key.[¶](#section-3.1.1-1.10.1)

    These Federation Entity Keys SHOULD NOT be used in other protocols. (Keys to be used in other protocols, such as OpenID Connect, are conveyed in the `metadata` elements for the protocol\'s Entity Type Identifiers, such as the metadata under the `openid_provider` and `openid_relying_party` Entity Type Identifiers.)[¶](#section-3.1.1-1.10.2)

:   

metadata

:   OPTIONAL. JSON object that declares roles that the Entity plays - its Entity Types - and that contains metadata for those Entity Types. Each member name of the JSON object is an Entity Type Identifier, and each value MUST be a JSON object containing metadata parameters according to the metadata schema of the Entity Type.[¶](#section-3.1.1-1.12.1)

    When an Entity participates in a federation or federations with one or more Entity Types, its Entity Configuration MUST contain a `metadata` Claim with JSON object values for each of the corresponding Entity Type Identifiers, even if the values are the empty JSON object `{}` (when the Entity Type has no associated metadata or Immediate Superiors supply any needed metadata).[¶](#section-3.1.1-1.12.2)

    An Immediate Superior MAY provide selected or all metadata parameters for an Immediate Subordinate, by using the `metadata` Claim in a Subordinate Statement. When `metadata` is used in a Subordinate Statement, it applies only to those Entity Types that are present in the subject\'s Entity Configuration. Furthermore, the metadata applies only to the subject of the Subordinate Statement and has no effect on the subject\'s Subordinates. Metadata parameters in a Subordinate Statement have precedence and override identically named parameters under the same Entity Type in the subject\'s Entity Configuration. If both `metadata` and `metadata_policy` appear in a Subordinate Statement, then the stated `metadata` MUST be applied before the `metadata_policy`, as described in [Section 6.1.4.2](#metadata_policy_application).[¶](#section-3.1.1-1.12.3)

:   

crit
:   OPTIONAL. Entity Statements require that the `crit` (critical) Claim defined in [Section 13.4](#critClaim) be understood and processed. Claims in the Entity Statement whose Claim Names are in the array that is the value of this Claim MUST be understood and processed. Claims specified for use in Entity Statements by this specification MUST NOT be included in the list.[¶](#section-3.1.1-1.14)
:   

#### [3.1.2.](#section-3.1.2) [Claims that MUST or MAY Appear in Entity Configurations but Not in Subordinate Statements](#name-claims-that-must-or-may-appe)

authority_hints

:   OPTIONAL. An array of strings representing the Entity Identifiers of Intermediate Entities or Trust Anchors that are Immediate Superiors of the Entity. This Claim is REQUIRED in Entity Configurations of the Entities that have at least one Superior above them, such as Leaf and Intermediate Entities. Its value MUST contain the Entity Identifiers of its Immediate Superiors and MUST NOT be the empty array `[]`. This Claim MUST NOT be present in Entity Configurations of Trust Anchors with no Superiors.[¶](#authority_hints)

:   

trust_anchor_hints

:   OPTIONAL. An array of strings representing the Entity Identifiers of Trust Anchors trusted by the Entity. Its value MUST NOT be the empty array `[]`. This Claim MUST NOT be present in Entity Configurations of Trust Anchors with no Superiors.[¶](#trust_anchor_hints)

:   

trust_marks

:   OPTIONAL. An array of JSON objects, each representing a Trust Mark.[¶](#section-3.1.2-1.6.1)

    trust_mark_type
    :   REQUIRED. Identifier for the type of the Trust Mark. The value of this Claim MUST be the same as the value of the `trust_mark_type` Claim contained in the Trust Mark JWT that is the value of the `trust_mark` Claim in this object.[¶](#section-3.1.2-1.6.2.2)
    :   

    trust_mark
    :   REQUIRED. A signed JSON Web Token that represents a Trust Mark.[¶](#section-3.1.2-1.6.2.4)
    :   

    Trust Marks are described in [Section 7](#trust_marks).[¶](#section-3.1.2-1.6.3)

:   

trust_mark_issuers
:   OPTIONAL. A Trust Anchor MAY use this Claim to tell which combination of Trust Mark type identifiers and issuers are trusted by the federation. This Claim MUST be ignored if present in an Entity Configuration for an Entity that is not a Trust Anchor. It is a JSON object with member names that are Trust Mark type identifiers and each corresponding value being an array of Entity Identifiers that are trusted to represent the accreditation authority for Trust Marks with that identifier. If the array following a Trust Mark type identifier is empty, anyone MAY issue Trust Marks with that identifier. Trust Marks are described in [Section 7](#trust_marks).[¶](#section-3.1.2-1.8)
:   

trust_mark_owners

:   OPTIONAL. If a Federation Operator knows that a Trust Mark type identifier is owned by an Entity different from the Trust Mark Issuer, then that knowledge MUST be expressed in this Claim. This Claim MUST be ignored if present in an Entity Configuration for an Entity that is not a Trust Anchor. It is a JSON object with member names that are Trust Mark type identifiers and each corresponding value being a JSON object with these members:[¶](#section-3.1.2-1.10.1)

    sub
    :   REQUIRED Identifier of the Trust Mark Owner.[¶](#section-3.1.2-1.10.2.2)
    :   

    jwks
    :   REQUIRED [JSON Web Key Set (JWKS)](#RFC7517) \[[RFC7517](#RFC7517)\] containing the owner\'s Federation Entity Keys used for signing.[¶](#section-3.1.2-1.10.2.4)
    :   

    Other members MAY also be defined and used.[¶](#section-3.1.2-1.10.3)

:   

#### [3.1.3.](#section-3.1.3) [Claims that MUST or MAY Appear in Subordinate Statements but Not in Entity Configurations](#name-claims-that-must-or-may-appea)

constraints
:   OPTIONAL. JSON object that defines Trust Chain constraints, as described in [Section 6.2](#chain_constraints). The constraints apply to the Entity that is the subject of this Subordinate Statement as well as to all Entities that are Subordinate to it.[¶](#section-3.1.3-1.2)
:   

metadata_policy
:   OPTIONAL. JSON object that defines a metadata policy, as described in [Section 6.1](#metadata_policy). The metadata policy applies to the Entity that is the subject of this Subordinate Statement as well as to all Entities that are Subordinate to it. Applying to Subordinate Entities distinguishes `metadata_policy` from `metadata`, which only applies to the subject itself.[¶](#section-3.1.3-1.4)
:   

metadata_policy_crit
:   OPTIONAL. Array of strings specifying critical metadata policy operators other than the standard ones defined in [Section 6.1.3.1](#standard_metadata_policy_operators) that MUST be understood and processed. When included its value MUST NOT be the empty array `[]`. If any of the listed policy operators are not understood and supported, then the Subordinate Statement and thus the Trust Chain that includes it MUST be considered invalid.[¶](#section-3.1.3-1.6)
:   

source_endpoint
:   OPTIONAL. String containing the fetch endpoint URL from which the Entity Statement was issued, as specified in [Section 8.1](#fetch_endpoint). This parameter enables an optimized refresh of the Subordinate Statements and hence the Trust Chain, by skipping the request to the Entity Configuration that is normally required to discover the `federation_fetch_endpoint` of the issuing authority. If an Entity Statement cannot be retrieved from the `source_endpoint`, which can occur if the endpoint URL has changed, the current `federation_fetch_endpoint` location can be determined by retrieving the Entity Configuration of the issuer.[¶](#section-3.1.3-1.8)
:   

#### [3.1.4.](#section-3.1.4) [Claims Used in Explicit Registration Requests](#name-claims-used-in-explicit-reg)

aud
:   OPTIONAL. The \"aud\" (audience) value MUST be the Entity Identifier of the OP and MUST NOT include any other values. This Claim is used in Explicit Registration requests; it is not a general Entity Statement Claim.[¶](#section-3.1.4-1.2)
:   

#### [3.1.5.](#section-3.1.5) [Claims Used in Explicit Registration Responses](#name-claims-used-in-explicit-regi)

aud
:   OPTIONAL. The \"aud\" (audience) value MUST be the Entity Identifier of the RP and MUST NOT include any other values. This Claim is used in Explicit Registration responses; it is not a general Entity Statement Claim.[¶](#section-3.1.5-1.2)
:   

trust_anchor
:   OPTIONAL. Its value MUST be the Entity Identifier of the Trust Anchor that the OP selected to process the Explicit Registration request, as specified in [Section 12.2.3](#trust_anchor-claim). This Claim is specific to Explicit Registration responses; it is not a general Entity Statement Claim.[¶](#section-3.1.5-1.4)
:   

### [3.2.](#section-3.2) [Entity Statement Validation](#name-entity-statement-validation)

Entity Statements MUST be validated in the following manner. These steps MAY be performed in a different order, provided that the result - accepting or rejecting the Entity Statement - is the same.[¶](#section-3.2-1)

1.  The Entity Statement MUST be a signed JWT.[¶](#section-3.2-2.1.1)

2.  The Entity Statement MUST have a `typ` header parameter with the value `entity-statement+jwt`.[¶](#section-3.2-2.2.1)

3.  The Entity Statement MUST have an `alg` (algorithm) header parameter with a value that is an acceptable JWS signing algorithm; it MUST NOT be `none`.[¶](#section-3.2-2.3.1)

4.  The Entity Identifier of the Entity to which the Entity Statement refers MUST match the value of the `sub` (subject) Claim.[¶](#section-3.2-2.4.1)

5.  The Entity Statement MUST have an `iss` (issuer) Claim with a value that is a valid Entity Identifier.[¶](#section-3.2-2.5.1)

6.  When the `iss` (issuer) Claim Value matches the `sub` (subject) Claim Value, then the Entity Statement is this Entity\'s Entity Configuration. When they do not match, the Entity Statement is a Subordinate Statement. When the Entity Statement is a Subordinate Statement, the `iss` Claim Value MUST match one of the values in the `authority_hints` array in the Entity Configuration for the Entity whose Entity Identifier is the value of the `sub` Claim; otherwise, the Federation graph is not well-formed.[¶](#section-3.2-2.6.1)

7.  The current time MUST be after the time represented by the `iat` (issued at) Claim (possibly allowing for some small leeway to account for clock skew).[¶](#section-3.2-2.7.1)

8.  The current time MUST be before the time represented by the `exp` (expiration) Claim (possibly allowing for some small leeway to account for clock skew).[¶](#section-3.2-2.8.1)

9.  The `jwks` (JWK Set) Claim MUST be present, with a value that is a valid JWK Set \[[RFC7517](#RFC7517)\].[¶](#section-3.2-2.9.1)

10. Obtain the Entity Configuration for the issuing Entity - the Entity with the Issuer Identifier found in the Entity Statement\'s `iss` (issuer) Claim. When the `iss` and `sub` Claim Values match, this is the Entity Statement being validated itself. Otherwise, this can be obtained either from a Trust Chain or by retrieving it as described in [Section 9](#federation_configuration).[¶](#section-3.2-2.10.1)

11. The Entity Statement\'s `kid` (Key ID) header parameter value MUST be a non-zero length string and MUST exactly match the `kid` value for a key in the `jwks` (JWK Set) Claim of the Entity Configuration of the issuing Entity.[¶](#section-3.2-2.11.1)

12. The Entity Statement\'s signature MUST validate using the issuing Entity\'s key identified by the `kid` value.[¶](#section-3.2-2.12.1)

13. If the `crit` Claim is present, then each array element in this Claim\'s value MUST be a string representing an Entity Statement Claim that is not defined by this specification and that Claim MUST be understood and be able to be processed by the implementation.[¶](#section-3.2-2.13.1)

14. If the `authority_hints` Claim is present, the Entity Statement MUST be an Entity Configuration. Verify that its value is syntactically correct, as specified in [Section 3.1.2](#authority_hints). Implementations MAY also validate that the Entity is a Subordinate of each Entity whose Entity Identifier is listed in the `authority_hints` array.[¶](#section-3.2-2.14.1)

15. If the `trust_anchor_hints` Claim is present, the Entity Statement MUST be an Entity Configuration. Verify that its value is syntactically correct, as specified in [Section 3.1.2](#trust_anchor_hints).[¶](#section-3.2-2.15.1)

16. If the `metadata` Claim is present, verify that its value is syntactically correct, not using `null` as metadata values, as specified in [Section 5](#metadata).[¶](#section-3.2-2.16.1)

17. If the `metadata_policy` Claim is present, the Entity Statement MUST be a Subordinate Statement. Verify that its value is syntactically correct, as specified in [Section 6.1](#metadata_policy).[¶](#section-3.2-2.17.1)

18. If the `metadata_policy_crit` Claim is present, the Entity Statement MUST be a Subordinate Statement. Each array element in this Claim\'s value MUST be a string representing a Metadata Policy operator that is not defined by this specification and that operator MUST be understood and be able to be processed by the implementation.[¶](#section-3.2-2.18.1)

19. If the `constraints` Claim is present, the Entity Statement MUST be a Subordinate Statement. Verify that its value is syntactically correct, as specified in [Section 6.2](#chain_constraints).[¶](#section-3.2-2.19.1)

20. If the `trust_marks` Claim is present, the Entity Statement MUST be an Entity Configuration. Validate that the syntax of this Claim Value conforms to the Claim definition. In particular, for each element of the array that is the Claim Value, validate that there is a `trust_mark_type` member whose value matches the `trust_mark_type` Claim Value in the Trust Mark JWT that is the value of the `trust_mark` member. Validating the syntax is separate from evaluating whether particular Trust Marks are issued by a trusted party and are trusted; that process is described in [Section 7.3](#trust-mark-validation) and MAY be performed as a separate step from syntactic validation.[¶](#section-3.2-2.20.1)

21. If the `trust_mark_issuers` Claim is present, the Entity Statement MUST be an Entity Configuration. Validate that its Claim Value is a JSON object with Trust Mark type identifiers as the member names and arrays of Entity Identifiers as the values.[¶](#section-3.2-2.21.1)

22. If the `trust_mark_owners` Claim is present, the Entity Statement MUST be an Entity Configuration. Validate that its Claim Value is a JSON object with Trust Mark type identifiers as the member names and values that are JSON objects containing a `sub` member with a value that is an Entity Identifier and a `jwks` member with a value that is a JSON Web Key Set.[¶](#section-3.2-2.22.1)

23. If the `source_endpoint` Claim is present, the Entity Statement MUST be a Subordinate Statement. Validate that its Claim Value is a URL. Implementations MAY also make a fetch call to the URL to validate that this is the fetch endpoint from which the Entity Statement was issued.[¶](#section-3.2-2.23.1)

24. If the `trust_chain` header parameter is present, validate that its value is a syntactically valid Trust Chain, as specified in [Section 4](#trust_chain). The first entry in the Trust Chain MUST be an Entity Configuration for this Entity. Implementations SHOULD validate that the Entity Identifier for the Trust Anchor at the end of the Trust Chain matches one of the Trust Anchors configured for the deployment.[¶](#section-3.2-2.24.1)

25. If the `peer_trust_chain` header parameter is present, validate that its value is a syntactically valid Trust Chain, as specified in [Section 4](#trust_chain). Implementations SHOULD validate that the Entity Identifier for the Trust Anchor at the end of the Trust Chain matches one of the Trust Anchors configured for the deployment.[¶](#section-3.2-2.25.1)

26. If the `aud` Claim is present, if the Entity Statement is an Explicit Registration request, validate that the value is the Entity Identifier for an OP, or if the Entity Statement is an Explicit Registration response, validate that the value is the Entity Identifier for an RP. This Claim MUST NOT be present in Entity Statements that are not Explicit Registration requests or responses unless its use is otherwise specified in an extension being employed.[¶](#section-3.2-2.26.1)

27. If the `trust_anchor` Claim is present, validate that its value is a URL using the `https` scheme. Implementations SHOULD validate that the Entity Identifier matches one of the Trust Anchors configured for the deployment. Furthermore, implementations SHOULD validate that the Entity Configuration for the Entity Identifier contains information compatible with the configured Trust Anchor information - especially the keys. This Claim MUST NOT be present in Entity Statements that are not Explicit Registration responses unless its use is otherwise specified in an extension being employed.[¶](#section-3.2-2.27.1)

If any of these validation steps fail, the Entity Statement MUST be rejected.[¶](#section-3.2-3)

### [3.3.](#section-3.3) [Entity Statement Example](#name-entity-statement-example)

The following is a non-normative example of the JWT Claims Set for an Entity Statement. The example contains a critical extension `jti` (JWT ID) to the Entity Statement and one critical extension to the policy language `regexp` (Regular expression).[¶](#section-3.3-1)

    {
      "iss": "https://feide.no",
      "sub": "https://ntnu.no",
      "iat": 1516239022,
      "exp": 1516843822,
      "jwks": {
        "keys": [
          {
            "kty": "RSA",
            "alg": "RS256",
            "use": "sig",
            "kid": "NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs",
            "n": "vHOJrp-zLST7FwvzAwelR9Vo...",
            "e": "AQAB"
          }
        ]
      },
      "metadata": {
        "openid_provider": {
          "issuer": "https://ntnu.no",
          "organization_name": "NTNU"
        },
        "oauth_client": {
          "organization_name": "NTNU"
        }
      },
      "metadata_policy": {
        "openid_provider": {
          "id_token_signing_alg_values_supported":
            {"subset_of": ["RS256", "RS384", "RS512"]},
          "op_policy_uri": {
            "regexp":
              "^https:\/\/[\\w-]+\\.example\\.com\/[\\w-]+\\.html"}
        },
        "oauth_client": {
          "grant_types": {
            "one_of": ["authorization_code", "client_credentials"]
          }
        }
      },
      "constraints": {
        "max_path_length": 2
      },
      "crit": ["jti"],
      "metadata_policy_crit": ["regexp"],
      "source_endpoint": "https://feide.no/federation_api/fetch",
      "jti": "7l2lncFdY6SlhNia"
    }

[Figure 2](#figure-2): [Example Entity Statement JWT Claims Set](#name-example-entity-statement-jw)

## [4.](#section-4) [Trust Chain](#name-trust-chain)

Entities whose statements build a Trust Chain are categorized as:[¶](#section-4-1)

Trust Anchor
:   An Entity that represents a trusted third party.[¶](#section-4-2.2)
:   

Leaf
:   An Entity with no Subordinate Entities, which typically plays a protocol role. For instance, in an OpenID Connect identity federation, an RP or an OP, or in an OAuth 2.0 federation, a Client, Authorization Server, or Protected Resource.[¶](#section-4-2.4)
:   

Intermediate
:   Neither a Leaf Entity nor a Trust Anchor.[¶](#section-4-2.6)
:   

A Trust Chain begins with an Entity Configuration that is the subject of the Trust Chain, which is typically a Leaf Entity. The Trust Chain has zero or more Subordinate Statements issued by Intermediates about their Immediate Subordinates, and includes the Subordinate Statement issued by the Trust Anchor about the top-most Intermediate (if there are Intermediates) or the Trust Chain subject (if there are no Intermediates). The Trust Chain logically always ends with the Entity Configuration of the Trust Anchor, even though it MAY be omitted from the JSON array representing the Trust Chain in some cases.[¶](#section-4-3)

The Trust Chain contains the configuration of the federation as it applies to the Trust Chain subject at the time of the evaluation of the chain.[¶](#section-4-4)

A simple example: If we have an RP that belongs to Organization A that is a member of Federation F, the Trust Chain for such a setup will contain the following Entity Statements:[¶](#section-4-5)

1.  an Entity Configuration about the RP published by itself,[¶](#section-4-6.1.1)

2.  a Subordinate Statement about the RP published by Organization A,[¶](#section-4-6.2.1)

3.  a Subordinate Statement about Organization A published by the Trust Anchor for F,[¶](#section-4-6.3.1)

4.  an Entity Configuration about the Trust Anchor for F published by itself.[¶](#section-4-6.4.1)

Let us refer to the Entity Statements in the Trust Chain as ES\[j\], where j = 0,\...,i, with 0 being the index of the first Entity Statement and i being the zero-based index of the last. Then:[¶](#section-4-7)

-   ES\[0\] (the Entity Configuration of the Trust Chain subject) is signed using a key in ES\[0\]\[\"jwks\"\].[¶](#section-4-8.1.1)

-   The `iss` Claim in an Entity Statement is equal to the `sub` Claim in the next. Restating this symbolically, for each j = 0,\...,i-1, ES\[j\]\[\"iss\"\] == ES\[j+1\]\[\"sub\"\].[¶](#section-4-8.2.1)

-   An Entity Statement is signed using a key from the `jwks` Claim in the next. Restating this symbolically, for each j = 0,\...,i-1, ES\[j\] is signed by a key in ES\[j+1\]\[\"jwks\"\].[¶](#section-4-8.3.1)

-   ES\[i\] (the Trust Anchor\'s Entity Configuration in the Trust Chain) is signed using a key in ES\[i\]\[\"jwks\"\].[¶](#section-4-8.4.1)

The Trust Anchor\'s public keys are used to verify the signatures on ES\[i\] (the Trust Anchor\'s Entity Configuration) and ES\[i-1\] (the Trust Anchor\'s Subordinate Statement about its Immediate Subordinate in the Trust Chain). The Trust Anchor\'s public keys are distributed to Entities that need to verify a Trust Chain in some secure out-of-band way not described in this document.[¶](#section-4-9)

### [4.1.](#section-4.1) [Beginning and Ending Trust Chains](#name-beginning-and-ending-trust-)

A Trust Chain begins with the Entity Configuration of an Entity for which trust is being established. This is the subject of the Trust Chain. This Entity typically plays a protocol role, such as being an OpenID Provider or OpenID Relying Party.[¶](#section-4.1-1)

While such Entities are typically Leaf Entities, other topologies are possible. For instance, an Entity might simultaneously be an OpenID Provider and an Intermediate Entity, with OpenID Relying Parties and/or other Intermediates being Subordinate to it. This is a case in which the subject of a Trust Chain would not be a Leaf Entity.[¶](#section-4.1-2)

A Trust Chain ends with the Entity Configuration of a Trust Anchor. While Trust Anchors typically have no Superiors, a Trust Anchor will have a Superior when the Trust Anchor also acts as an Intermediate Entity in another federation. This will be the case when there is a hierarchy of federations.[¶](#section-4.1-3)

Thus, while it is typical for a Trust Chain to end with a Trust Anchor with no Superiors, there are situations in which the Trust Chain will end with a Trust Anchor that nonetheless has Superior Entities.[¶](#section-4.1-4)

### [4.2.](#section-4.2) [Trust Chain Example](#name-trust-chain-example)

The following is an example of a Trust Chain consisting of a Leaf\'s Entity Configuration and Subordinate Statements issued by an Intermediate Entity and a Trust Anchor. It shows the relationship between the three Entities, their Entity Configurations, and their Subordinate Statements. The Subordinate Statements are obtained from the `federation_fetch_endpoint` of the subject\'s Immediate Superior. The URL of the `federation_fetch_endpoint` is discovered in the Immediate Superior\'s Entity Configuration. Note that the first member of the Trust Chain (the Leaf) is depicted at the bottom of the diagram and that the last member (the Trust Anchor) is depicted at the top.[¶](#section-4.2-1)

    .----------------.  .---------------------------.         .---------------------------.
    | Role           |  | .well-known/              |         | Trust Chain               |
    |                |  | openid-federation         |         |                           |
    .----------------.  .---------------------------.         .---------------------------.
    | .------------. |  | .-----------------------. |         | .-----------------------. |
    | |            | |  | | Entity Configuration  | |         | | Entity Configuration  | |
    | |Trust Anchor+-+--+->                       +-+---------+->                       | |
    | |            | |  | | Federation Entity Keys| |         | | Federation Entity Keys| |
    | '-----.------' |  | | Metadata              | |         | | Metadata              | |
    |       |        |  | | Trust Mark Issuers    | |         | | Trust Mark Issuers    | |
    |       |        |  | |                       | |         | |                       | |
    |       |        |  | '-----------------------' |         | '-----------------------' |
    |       |        |  |                           |         |                           |
    |       |        |  |                           |Fetch    | .-----------------------. |
    |       |        |  |                           |Endpoint | | Subordinate Statement | |
    |       +--------+--+---------------------------+---------+->                       | |
    |                |  |                           |         | | Federation Entity Keys| |
    |                |  |                           |         | | Metadata Policy       | |
    |                |  |                           |         | | Metadata              | |
    |                |  |                           |         | | Constraints           | |
    |                |  |                           |         | |                       | |
    |                |  |                           |         | '-----------.-----------' |
    | .------------. |  | .-----------------------. |         |             |             |
    | |            | |  | | Entity Configuration  | |         |             |             |
    | |Intermediate+-+--+->                       | |         |             |sub and key  |
    | |            | |  | | Federation Entity Keys| |         |             | binding     |
    | '------.-----' |  | | Metadata              | |         | .-----------v-----------. |
    |        |       |  | | Trust Marks           | |         | | Subordinate Statement | |
    |        |       |  | |                       | |         | |                       | |
    |        |       |  | |                       | |         | | Federation Entity Keys| |
    |        |       |  | '-----------------------' |Fetch    | | Metadata Policy       | |
    |        |       |  |                           |Endpoint | | Metadata              | |
    |        +-------+--+---------------------------+---------+->                       | |
    |                |  |                           |         | '-----------.-----------' |
    |                |  |                           |         |             |sub and key  |
    |                |  |                           |         |             | binding     |
    | .------------. |  | .-----------------------. |         | .-----------v-----------. |
    | |            | |  | | Entity Configuration  | |         | | Entity Configuration  | |
    | | Leaf       +-+--+->                       +-+---------+->                       | |
    | |            | |  | | Federation Entity Keys| |         | | Federation Entity Keys| |
    | '------------' |  | | Metadata              | |         | | Metadata              | |
    |                |  | | Trust Marks           | |         | | Trust Marks           | |
    |                |  | |                       | |         | |                       | |
    |                |  | |                       | |         | |                       | |
    |                |  | '-----------------------' |         | '-----------------------' |
    '----------------'  '---------------------------'         '---------------------------'

[Figure 3](#figure-3): [Relationships between Federation Entities and Statements Issued in a Trust Chain](#name-relationships-between-feder)

### [4.3.](#section-4.3) [Trust Chain Header Parameter](#name-trust-chain-header-paramete)

The `trust_chain` JWS header parameter is a JSON array containing the sequence of Entity Statements that comprise the Trust Chain between an Entity and the selected Trust Anchor. The Trust Chain typically begins with the Entity Configuration of the subject of the JWT in which this header parameter occurs; however, in some cases, the Trust Chain will begin with the Entity Configuration of a different Entity, such as the issuer, which is the case for the Resolve Response defined in [Section 8.3.2](#resolve-response). The issuer of the JWT SHOULD select a Trust Anchor that the Entity at the beginning of the Trust Chain has in common with the audience of the JWT. Otherwise, the issuer is free to select the Trust Anchor to use.[¶](#section-4.3-1)

Most signed JWTs MAY include the `trust_chain` JWS header parameter, with a few exceptions. Entity Configurations and Subordinate Statements MUST NOT contain the `trust_chain` header parameter, as they are integral components of a Trust Chain. Use of this header parameter is OPTIONAL.[¶](#section-4.3-2)

The following is a non-normative example of a JWS header with the `trust_chain` parameter.[¶](#section-4.3-3)

    {
      "typ": "...",
      "alg": "RS256",
      "kid": "SUdtUndEWVY2cUFDeDV5NVlBWDhvOXJodVl2am1mNGNtR0pmd",
      "trust_chain": [
        "eyJ0eXAiOiJlbnRpdHktc3RhdGVtZW50K2p3dCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiWjBWRVdtUTRVVFJWZFhNeGRFVnRMVUl3VldWSVRVZDRhekpEVTBrdE5DMXdaWGR2TVRoWWJrTTRUUSJ9.eyJtZXRhZGF0YSI6IHsib3BlbmlkX2NyZWRlbnRpYWxfaXNzdWVyIjogeyJqd2tzIjogeyJrZXlzIjogW3sia3R5IjogIlJTQSIsICJraWQiOiAiUjJSelJYQTBSVkJ5ZHpGT1ZHMWZkV1JUTVRaM1lUUm1ObkUxVjNGZk1FMW9NVVpMZWtsaVkxTllPQSIsICJlIjogIkFRQUIiLCAibiI6ICI1SF9YaDd4Z0RXVHhRVmJKcW1PR3Vyb2tFOGtyMmUxS2dNV2NZT0E3NE9fMVBYZDJ1Z2p5SXE5dDFtVlBTdXd4LXR5U2syUEtwanAtLVdySG4zQTRVS0prdVIxMXpobWRMQnNVOFRPQkJ1NU1aOGF0RHVqZlJ3SUxYZEtzRVhrbHZhQjZQTFQ0emRab2RnQ3MwNUt5MmU1c2I1ejZfQ2lEcWdVVm5XUG1KTE1rZ3BCdFota01kX2xiOVNvb1psbGZVR2xUa3NhdUoyX2dWUS1WcEZVTVhZam9Kak54OTdldWthWW5SRW9DQzNUYV8tOGJjUm9zbHgyeHJJYnVfVUdWcWlwZU4zTlAtbWVmZjlWVFpXWU0zZ21vbHd1cG5NQ1hYaWlrY1I1VVNMVmcwZV9nejZPZm9SVkdLQVdJcFJSTFR6MmFpcXVrVkhaZFpYOXRObXowbXcifV19fSwgImZlZGVyYXRpb25fZW50aXR5IjogeyJvcmdhbml6YXRpb25fbmFtZSI6ICJPcGVuSUQgQ3JlZGVudGlhbCBJc3N1ZXIgZXhhbXBsZSIsICJvcmdhbml6YXRpb25fdXJpIjogImh0dHBzOi8vY3JlZGVudGlhbF9pc3N1ZXIuZXhhbXBsZS5vcmcvaG9tZSIsICJwb2xpY3lfdXJpIjogImh0dHBzOi8vY3JlZGVudGlhbF9pc3N1ZXIuZXhhbXBsZS5vcmcvcG9saWN5IiwgImxvZ29fdXJpIjogImh0dHBzOi8vY3JlZGVudGlhbF9pc3N1ZXIuZXhhbXBsZS5vcmcvc3RhdGljL2xvZ28uc3ZnIiwgImNvbnRhY3RzIjogWyJ0ZWNoQGNyZWRlbnRpYWxfaXNzdWVyLmV4YW1wbGUub3JnIl19fSwgImF1dGhvcml0eV9oaW50cyI6IFsiaHR0cHM6Ly9pbnRlcm1lZGlhdGUuZWlkYXMuZXhhbXBsZS5vcmciXSwgImp3a3MiOiB7ImtleXMiOiBbeyJrdHkiOiAiUlNBIiwgImtpZCI6ICJaMFZFV21RNFVUUlZkWE14ZEVWdExVSXdWV1ZJVFVkNGF6SkRVMGt0TkMxd1pYZHZNVGhZYmtNNFRRIiwgIm4iOiAib0FBcU9wcHc2U3drZzNMZTJESlIzMHh0ZzBuOENrNmtiTDhxMzViZFVGdVBrMDBZaUNLZE9sN2JHQzVGdlBuMnIyZ2lfQkZ3djhscHdreU1ObnJYMHczd0hXTUtVMmdwWEpxLVNnZGVyQXVQZmxHQzgxY04tTGhJM04tQmQwcjF1YThCVktGeXFWdkNpcms3YWNGeVdQa2ZPcGstbTRlVG4tcmtNMzREMUZ1VFdOUDctZXp6T1g3OWx2YXJBVGl5S1Jva205WGRYN05pQ1hPNnkzSDhjRV9BUVE5a2JvSmxkNklHb3gwUFY3dVFvczlkWURlLTFaWDFzWDhKaklMT2IwTDVBeC1VOHI5V3lIX0VkUXVEVW9QMzZMdFN1YlBYaGo5NVpUa0ZrZnhZd3NHUWxmMjJRVmFQWVh1VnVlSmJtejZ1Zk5NSXBpNG1LaDU3Q2JtcmN3IiwgImUiOiAiQVFBQiJ9XX0sICJzdWIiOiAiaHR0cHM6Ly9jcmVkZW50aWFsX2lzc3Vlci5leGFtcGxlLm9yZyIsICJpc3MiOiAiaHR0cHM6Ly9jcmVkZW50aWFsX2lzc3Vlci5leGFtcGxlLm9yZyIsICJpYXQiOiAxNzY3NzEwOTg0LCAiZXhwIjogMTc2ODAxMDk4NH0.KmJD9GmgRppKSX9kqoDVrZHb4nD7W0wvPJru3Iz4U6y-o5IMqlOxtq4LGDYRi2s7qsSCBn8qz1NIADuLnkc9IWAP5CUPXrnhAmqNBI05KqMdVIb_y0RtDwY7YK0fpBubxCcCiwaZBlTPHGBY72oRnUq3ZU4bOZ9LKNvqBxe9zINMUzCIykh7JUaeMCPP3RW0jBYSjkJwFu-uDKTztVRYTpzmblxTOXGO8djsjruIsNoRiM5uaiUL_JLiaJPZdQWorKa-PH9RRjTtB88hDSbL7We9oKlRLnOLCG4r6OJoJ1mqDRpbraYz_RsFqT9IXy5y5hmVcHS0Gc94BSfcgxVxDg",
        "eyJ0eXAiOiJlbnRpdHktc3RhdGVtZW50K2p3dCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiYTB0cmVuUmhMWEV5ZUROWmFEa3lXRzQxTmtFMFUyWlNTVWxTUTA0M05rRm5NVkJsWVhWQ1FqVlhhdyJ9.eyJzdWIiOiAiaHR0cHM6Ly9jcmVkZW50aWFsX2lzc3Vlci5leGFtcGxlLm9yZyIsICJqd2tzIjogeyJrZXlzIjogW3sia3R5IjogIlJTQSIsICJraWQiOiAiWjBWRVdtUTRVVFJWZFhNeGRFVnRMVUl3VldWSVRVZDRhekpEVTBrdE5DMXdaWGR2TVRoWWJrTTRUUSIsICJuIjogIm9BQXFPcHB3NlN3a2czTGUyREpSMzB4dGcwbjhDazZrYkw4cTM1YmRVRnVQazAwWWlDS2RPbDdiR0M1RnZQbjJyMmdpX0JGd3Y4bHB3a3lNTm5yWDB3M3dIV01LVTJncFhKcS1TZ2RlckF1UGZsR0M4MWNOLUxoSTNOLUJkMHIxdWE4QlZLRnlxVnZDaXJrN2FjRnlXUGtmT3BrLW00ZVRuLXJrTTM0RDFGdVRXTlA3LWV6ek9YNzlsdmFyQVRpeUtSb2ttOVhkWDdOaUNYTzZ5M0g4Y0VfQVFROWtib0psZDZJR294MFBWN3VRb3M5ZFlEZS0xWlgxc1g4SmpJTE9iMEw1QXgtVThyOVd5SF9FZFF1RFVvUDM2THRTdWJQWGhqOTVaVGtGa2Z4WXdzR1FsZjIyUVZhUFlYdVZ1ZUpibXo2dWZOTUlwaTRtS2g1N0NibXJjdyIsICJlIjogIkFRQUIifV19LCAiaXNzIjogImh0dHBzOi8vaW50ZXJtZWRpYXRlLmVpZGFzLmV4YW1wbGUub3JnIiwgImlhdCI6IDE3Njc3MTA5ODQsICJleHAiOiAxNzY4MDEwOTg0fQ.fUcQI29xFaxfvhOAZINWz3V9J1p-Ju6yyfyVQUmYpOc2j5VKz-jn1CG106gnyYzEPS5D3LMkwvuW1QADmFezgQj9iPpYpCIXn3gVdZbAHUP8RsseTQhm5EGiC11X-7zCA4RqQTuqQTh27fYYYoKkxcmkWeWteqzrFD4Tjw-Ryk0Eey7u9SFErZcZG8pNdpvopSUcUbWg14KG58DU64ssR4KJsZwPRZPfC_xr5CK4oQeyZF2ds8N-5cGAPRRuN68wlCT4IeYRByxmBZDxhX4e81qJT7eiIB95h-Ka6nY-R63A38IMBSN68MlX1Bt8gE_rfinTJhL6Y20LWKmeDyhWFg",
        "eyJ0eXAiOiJlbnRpdHktc3RhdGVtZW50K2p3dCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiT1ZwU2JHUnVlWE5UWmtrek5FNUJjVkF6TFRsRFVIZHBka05CZVZZM2NYbzNhV1paTm00NFJUZGFXUSJ9.eyJzdWIiOiAiaHR0cHM6Ly9pbnRlcm1lZGlhdGUuZWlkYXMuZXhhbXBsZS5vcmciLCAiandrcyI6IHsia2V5cyI6IFt7Imt0eSI6ICJSU0EiLCAia2lkIjogImEwdHJlblJoTFhFeWVETlphRGt5V0c0MU5rRTBVMlpTU1VsU1EwNDNOa0ZuTVZCbFlYVkNRalZYYXciLCAibiI6ICJ6RmpzV3hBbE1ORFkzZUlqT2djaHVhX2ZRb3ZKeUNxbTM4M3dBV2Q5SDV0YU95QW5XbGx0UlJCV18zOHRycUZTWjl6UW0zdHdvZHllcHM2ZnFSNTdKMHF1NzVPWmdseXAyNUloMnBPWlBOWU5GSEFqSXRhbnNqWHI0RjRtakNfVVBMWmpRc3lHNjA1eGtrWUR4MjlqRF9pa0REN0lwUlA1Yk01ZU5vQVF1d1NMbktSWk43d0lMRjU3eDlSZVZZYnp1TEVUVzZOdzU0Y1Q3aDNMb3ljZ2dxQXdoR0dSR09QUEFlOEFsYjhtUDJBeGE4TWdNLUhhUENISHMwamswVy1zOUNDQ0djdzBfWmV5OWQ3eHFzT21KNnQ0QWUxWnhBTlJ1LU9FanhDSVd2WDl6aEJkeXNtVmp4RWVFY2xYSVltajFsd0JtZ0s0U1VmcVBMUGdYUGc5aVEiLCAiZSI6ICJBUUFCIn1dfSwgImlzcyI6ICJodHRwczovL3RydXN0LWFuY2hvci5leGFtcGxlLm9yZyIsICJpYXQiOiAxNzY3NzEwOTg0LCAiZXhwIjogMTc2ODAxMDk4NH0.IHTiWH2H2n2fVtkn7K_4GcP-VoSmuz4BWqOn6CtUNaLxUgIWR6FVeaKHdxdabkkMd_zQk6bG_K5adF2H45ojoJIFVY8uuwJCOfakfsYbOnKRmsWD4qiINXjFPLg8jVutg0lTNGjxjJRm6I-7bs5hGRJUAwhOGGrfdtnO7qtlBfg6ciHCuyr8Jq0tmYooKrvpbmObxQ7-cPqqnHy0J3My2TDlxtbMarqpSfXHEavjCEV0dXz00JXAuu0Iop2XfrMvZJaQXeLWtZM_CsBv1-FhVpslLYvlbktwCnV55nnEa06BItcW2ZdpVEVC-GrBcYc2wXIBXLxKjThZ67Z3SMdqpg",
        "eyJ0eXAiOiJlbnRpdHktc3RhdGVtZW50K2p3dCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiT1ZwU2JHUnVlWE5UWmtrek5FNUJjVkF6TFRsRFVIZHBka05CZVZZM2NYbzNhV1paTm00NFJUZGFXUSJ9.eyJtZXRhZGF0YSI6IHsiZmVkZXJhdGlvbl9lbnRpdHkiOiB7ImZlZGVyYXRpb25fZmV0Y2hfZW5kcG9pbnQiOiAiaHR0cHM6Ly90cnVzdC1hbmNob3IuZXhhbXBsZS5vcmcvZmV0Y2giLCAiZmVkZXJhdGlvbl9yZXNvbHZlX2VuZHBvaW50IjogImh0dHBzOi8vdHJ1c3QtYW5jaG9yLmV4YW1wbGUub3JnL3Jlc29sdmUiLCAiZmVkZXJhdGlvbl9saXN0X2VuZHBvaW50IjogImh0dHBzOi8vdHJ1c3QtYW5jaG9yLmV4YW1wbGUub3JnL2xpc3QiLCAib3JnYW5pemF0aW9uX25hbWUiOiAiVEEgZXhhbXBsZSIsICJvcmdhbml6YXRpb25fdXJpIjogImh0dHBzOi8vdHJ1c3QtYW5jaG9yLmV4YW1wbGUub3JnL2hvbWUiLCAicG9saWN5X3VyaSI6ICJodHRwczovL3RydXN0LWFuY2hvci5leGFtcGxlLm9yZy9wb2xpY3kiLCAibG9nb191cmkiOiAiaHR0cHM6Ly90cnVzdC1hbmNob3IuZXhhbXBsZS5vcmcvc3RhdGljL2xvZ28uc3ZnIiwgImNvbnRhY3RzIjogWyJ0ZWNoQHRydXN0LWFuY2hvci5leGFtcGxlLm9yZyJdfX0sICJqd2tzIjogeyJrZXlzIjogW3sia3R5IjogIlJTQSIsICJraWQiOiAiT1ZwU2JHUnVlWE5UWmtrek5FNUJjVkF6TFRsRFVIZHBka05CZVZZM2NYbzNhV1paTm00NFJUZGFXUSIsICJuIjogInhDOXBkUHJwMURHNzlQb0wyTmsybGpoTVpnc3dyMUFqTFprcC1KZHN2MTVHYTY3S0FhMUNId0RMQ0JrblZKcHZNb2g0dnFyM0RzYlh1NEFobThFUzY3RlEwUmpUeUNTZXBvREVoNXVtd0dSakQxblhEMTI2dTlGQi10S0xqUFpTaFl5aW5Kd1BaSGp0ekF0U2xjalNfcFg3WjRDQUh1OEhkeHpZb0JDODhOUEszZjBhZmRBY3JxcnM3LVVFWlphODhycVpGZjRYemVZMGdZUVc4cG5TN0h6cU9aNEhLaU1Ld0ZXUnE1WXFVQ2stVkUyZlpsdjIzR3pxd3VyenllaTd2cVZwWXhodFRlTnBmMjZqSWV5T2JhWm5QYTMzUmFVVDNCd2p2bXBROGJEZ0EzUlF3UFhXMFIzTlpzTElfd0Nqc095TVdkMVh6MlJHellRcjVVV2psUSIsICJlIjogIkFRQUIifV19LCAic3ViIjogImh0dHBzOi8vdHJ1c3QtYW5jaG9yLmV4YW1wbGUub3JnIiwgImlzcyI6ICJodHRwczovL3RydXN0LWFuY2hvci5leGFtcGxlLm9yZyIsICJpYXQiOiAxNzY3NzEwOTg0LCAiZXhwIjogMTc2ODAxMDk4NH0.gbpT-NSUz4Psl3wysrNqXXLZ5sYFnH8yB7Mtb6x5Vbwqre5UsHzyS7S9F86hWSfPQT19jofWT4uLFC_R_Ct4xa3OZvGSfVEtv9rYtJ8Q2tCM2JYZO1hFCaV6BM9pa5yU93H5SoOVLuK4YxQzmGE7cuHsjOXHbGCexJ0rcC1CTOwK3q1o8YEOa_0X2KbkEmeQKR1P50oN8c_GK0NkxT3XLCxb-cVwlG4nsHDe28KBwxjX2DObXXgh5SXj1Mz7dXuv-AtfKcHQ9i479mWVJn86rE4IjHRSp5IIEMsZ5Pn72QVWHGxkwno0TOFTBLtUp9FN9Y36gXiZpfMq9RdyvYbl2w"
      ]
    }

[Figure 4](#figure-4): [Example JWS Header with a `trust_chain` Parameter](#name-example-jws-header-with-a-t)

### [4.4.](#section-4.4) [Peer Trust Chain Header Parameter](#name-peer-trust-chain-header-par)

The `peer_trust_chain` JWS header parameter is a JSON array containing the sequence of Entity Statements that comprise the Trust Chain between an Entity that this Entity is establishing trust with and the selected Trust Anchor. If the `trust_chain` header parameter is also present, the Trust Anchor for both Trust Chains SHOULD be the same. Inclusion of both Trust Chains enables achieving the Federation Integrity and Metadata Integrity properties, as defined in \[[App-Fed-Linkage](#App-Fed-Linkage)\].[¶](#section-4.4-1)

Entity Configurations and Subordinate Statements MUST NOT contain the `peer_trust_chain` header parameter, as they are integral components of a Trust Chain. Use of this header parameter is OPTIONAL.[¶](#section-4.4-2)

## [5.](#section-5) [Metadata](#name-metadata)

This section defines how to represent and use metadata about Entities. It uses existing OpenID Connect and OAuth 2.0 metadata standards that are applicable to each Entity Type.[¶](#section-5-1)

As described in [Section 3.1.1](#common-claims), Entity metadata is located in the `metadata` Claim of an Entity Statement, whose value is a JSON object. The member names of this object are Entity Type Identifiers, as specified in [Section 5.1](#entity_types). The metadata data structure following each Entity Type Identifier is a JSON object; it MAY be the empty JSON object `{}`, which may be the case when Superiors supply any needed metadata values.[¶](#section-5-2)

Top-level JSON object members in the metadata data structures MAY use any JSON value other than `null`; the use of `null` is prohibited to prevent likely implementation errors caused by confusing members having `null` values with omitted members.[¶](#section-5-3)

### [5.1.](#section-5.1) [Entity Type Identifiers](#name-entity-type-identifiers)

The Entity Type Identifier uniquely identifies the Entity Type of a federation participant and the metadata format for that Entity Type. This section defines the `federation_entity` Entity Type Identifier as well as identifiers for OpenID Connect and OAuth 2.0 Federation Entities.[¶](#section-5.1-1)

Additional Entity Type Identifiers MAY be defined to support use cases for other protocols.[¶](#section-5.1-2)

#### [5.1.1.](#section-5.1.1) [Federation Entity](#name-federation-entity)

The Entity Type Identifier is `federation_entity`.[¶](#section-5.1.1-1)

The Entities that contain any of Federation Entity properties defined below MUST use this Entity Type. The following Federation Entity properties are defined:[¶](#section-5.1.1-2)

federation_fetch_endpoint
:   OPTIONAL. The fetch endpoint described in [Section 8.1](#fetch_endpoint). This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components; it MUST NOT contain a fragment component. Intermediate Entities and Trust Anchors MUST publish a `federation_fetch_endpoint`. Leaf Entities MUST NOT.[¶](#section-5.1.1-3.2)
:   

federation_list_endpoint
:   OPTIONAL. The list endpoint described in [Section 8.2](#entity_listing). This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components; it MUST NOT contain a fragment component. Intermediate Entities and Trust Anchors MUST publish a `federation_list_endpoint`. Leaf Entities MUST NOT.[¶](#section-5.1.1-3.4)
:   

federation_resolve_endpoint
:   OPTIONAL. The resolve endpoint described in [Section 8.3](#resolve). This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components; it MUST NOT contain a fragment component. Any Federation Entity MAY publish a `federation_resolve_endpoint`.[¶](#section-5.1.1-3.6)
:   

federation_trust_mark_status_endpoint
:   OPTIONAL. The Trust Mark Status endpoint described in [Section 8.4](#status_endpoint). Trust Mark Issuers SHOULD publish a `federation_trust_mark_status_endpoint`. This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components; it MUST NOT contain a fragment component.[¶](#section-5.1.1-3.8)
:   

federation_trust_mark_list_endpoint
:   OPTIONAL. The endpoint described in [Section 8.5](#tm_listing). This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components; it MUST NOT contain a fragment component. Trust Mark Issuers MAY publish a `federation_trust_mark_list_endpoint`.[¶](#section-5.1.1-3.10)
:   

federation_trust_mark_endpoint
:   OPTIONAL. The endpoint described in [Section 8.6](#tm_endpoint). This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components; it MUST NOT contain a fragment component. Trust Mark Issuers MAY publish a `federation_trust_mark_endpoint`.[¶](#section-5.1.1-3.12)
:   

federation_historical_keys_endpoint
:   OPTIONAL. The endpoint described in [Section 8.7](#historical_keys). This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components; it MUST NOT contain a fragment component. All Federation Entities MAY publish a `federation_historical_keys_endpoint`.[¶](#section-5.1.1-3.14)
:   

endpoint_auth_signing_alg_values_supported
:   OPTIONAL. JSON array containing a list of the supported [JWS](#RFC7515) \[[RFC7515](#RFC7515)\] algorithms (`alg` values) for signing the [JWT](#RFC7519) \[[RFC7519](#RFC7519)\] used for `private_key_jwt` when authenticating to federation endpoints, as described in [Section 8.8](#ClientAuthentication). No default algorithms are implied if this entry is omitted. Servers SHOULD support `RS256`. The value `none` MUST NOT be used.[¶](#section-5.1.1-3.16)
:   

Additional Federation Entity properties MAY be defined and used.[¶](#section-5.1.1-4)

It is RECOMMENDED that each Federation Entity contain an `organization_name` Claim, as defined in [Section 5.2.2](#informational_metadata).[¶](#section-5.1.1-5)

The following is a non-normative example of metadata for the `federation_entity` Entity Type:[¶](#section-5.1.1-6)

    "federation_entity": {
      "federation_fetch_endpoint":
        "https://amanita.caesarea.example.com/federation_fetch",
      "federation_list_endpoint":
        "https://amanita.caesarea.example.com/federation_list",
      "federation_trust_mark_status_endpoint": "https://amanita.caesarea.example.com/status",
      "federation_trust_mark_list_endpoint": "https://amanita.caesarea.example.com/trust_marked_list",
      "organization_name": "Ovulo Mushroom",
      "organization_uri": "https://amanita.caesarea.example.com"
    }

[Figure 5](#figure-5): [Example of `federation_entity` Entity Type](#name-example-of-federation_entit)

#### [5.1.2.](#section-5.1.2) [OpenID Connect Relying Party](#name-openid-connect-relying-part)

The Entity Type Identifier is `openid_relying_party`.[¶](#section-5.1.2-1)

All parameters defined in Section 2 of [OpenID Connect Dynamic Client Registration 1.0](#OpenID.Registration) \[[OpenID.Registration](#OpenID.Registration)\], in \[[OpenID.RP.Choices](#OpenID.RP.Choices)\], and in [Section 5.2](#common_metadata) are applicable, as well as additional parameters registered in the IANA \"OAuth Dynamic Client Registration Metadata\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\], excluding those parameters that are defined in the registry solely for use in registration responses (for example, `client_secret`).[¶](#section-5.1.2-2)

In addition, the following RP metadata parameter is defined:[¶](#section-5.1.2-3)

client_registration_types
:   RECOMMENDED. An array of strings specifying the client registration types the RP supports. Values defined by this specification are `automatic` and `explicit`. Additional values MAY be defined and used, without restriction by this specification.[¶](#section-5.1.2-4.2)
:   

The following is a non-normative example of the JWT Claims Set for an RP\'s Entity Configuration:[¶](#section-5.1.2-5)

        {
          "iss": "https://rp.sunet.se",
          "sub": "https://rp.sunet.se",
          "iat": 1516239022,
          "exp": 1516843822,
          "metadata": {
            "openid_relying_party": {
              "application_type": "web",
              "redirect_uris": [
                "https://rp.sunet.se/callback"
              ],
              "organization_name": "SUNET",
              "logo_uri": "https://www.sunet.se/sunet/images/32x32.png",
              "grant_types": [
                "authorization_code",
                "implicit"
              ],
              "signed_jwks_uri": "https://rp.sunet.se/signed_jwks.jose",
              "jwks_uri": "https://rp.sunet.se/jwks.json",
              "client_registration_types": ["automatic"]
            }
          },
          "jwks": {
            "keys": [
              {
                "alg": "RS256",
                "e": "AQAB",
                "kid": "Ge7hs3smulgio8glvURJadmj...",
                "kty": "RSA",
                "n": "iE0ZGqI4TNopx52dGHm0EYhl...",
                "use": "sig"
              }
            ]
          },
          "authority_hints": [
            "https://edugain.org/federation"
          ]
        }

[Figure 6](#figure-6): [Example Relying Party Entity Configuration JWT Claims Set](#name-example-relying-party-entit)

#### [5.1.3.](#section-5.1.3) [OpenID Connect OpenID Provider](#name-openid-connect-openid-provi)

The Entity Type Identifier is `openid_provider`.[¶](#section-5.1.3-1)

All parameters defined in Section 3 of [OpenID Connect Discovery 1.0](#OpenID.Discovery) \[[OpenID.Discovery](#OpenID.Discovery)\] and [Section 5.2](#common_metadata) are applicable, as well as additional parameters registered in the IANA \"OAuth Authorization Server Metadata\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\]. For instance, the `require_signed_request_object` and `require_pushed_authorization_requests` metadata parameters can be used.[¶](#section-5.1.3-2)

The `issuer` parameter value in the `openid_provider` metadata MUST match the Federation Entity Identifier (the `iss` parameter within the Entity Configuration).[¶](#section-5.1.3-3)

In addition, the following OP metadata parameters are defined:[¶](#section-5.1.3-4)

client_registration_types_supported
:   RECOMMENDED. An array of strings specifying the client registration types the OP supports. Values defined by this specification are `automatic` and `explicit`. Additional values MAY be defined and used, without restriction by this specification.[¶](#section-5.1.3-5.2)
:   

federation_registration_endpoint
:   OPTIONAL. URL of the OP\'s federation-specific Dynamic Client Registration Endpoint. If the OP supports Explicit Client Registration Endpoint this URL MUST use the `https` scheme and MAY contain port, path, and query parameter components; it MUST NOT contain a fragment component. If the OP supports Explicit Client Registration as described in [Section 12.2](#explicit), then this Claim is REQUIRED.[¶](#section-5.1.3-5.4)
:   

The following is a non-normative example of the JWT Claims Set for an OP\'s Entity Configuration:[¶](#section-5.1.3-6)

    {
       "iss": "https://op.umu.se",
       "sub": "https://op.umu.se",
       "exp": 1568397247,
       "iat": 1568310847,
       "metadata":{
          "openid_provider":{
             "issuer": "https://op.umu.se",
             "signed_jwks_uri": "https://op.umu.se/signed_jwks.jose",
             "authorization_endpoint": "https://op.umu.se/authorization",
             "client_registration_types_supported":[
                "automatic",
                "explicit"
             ],
             "grant_types_supported":[
                "authorization_code",
                "implicit",
                "urn:ietf:params:oauth:grant-type:jwt-bearer"
             ],
             "id_token_signing_alg_values_supported":[
                "ES256",
                "RS256"
             ],
             "logo_uri": "https://www.umu.se/img/umu-logo-left-neg-SE.svg",
             "op_policy_uri": "https://www.umu.se/en/legal-information/",
             "response_types_supported":[
                "code",
                "code id_token",
                "token"
             ],
             "subject_types_supported":[
                "pairwise",
                "public"
             ],
             "token_endpoint": "https://op.umu.se/token",
             "federation_registration_endpoint": "https://op.umu.se/fedreg",
             "token_endpoint_auth_methods_supported":[
                "client_secret_post",
                "client_secret_basic",
                "client_secret_jwt",
                "private_key_jwt"
             ],
             "pushed_authorization_request_endpoint": "https://op.umu.se/par",
             "request_object_signing_alg_values_supported": [
                "ES256",
                "RS256"
             ],
             "token_endpoint_auth_signing_alg_values_supported": [
                "ES256",
                "RS256"
             ]
          }
       },
       "authority_hints":[
          "https://umu.se"
       ],
       "jwks":{
          "keys":[
             {
                "e": "AQAB",
                "kid": "dEEtRjlzY3djcENuT01wOGxrZlkxb3RIQVJlMTY0...",
                "kty": "RSA",
                "n": "x97YKqc9Cs-DNtFrQ7_vhXoH9bwkDWW6En2jJ044yH..."
             }
          ]
       }
    }

[Figure 7](#figure-7): [Example OpenID Provider Entity Configuration JWT Claims Set](#name-example-openid-provider-ent)

#### [5.1.4.](#section-5.1.4) [OAuth Authorization Server](#name-oauth-authorization-server)

The Entity Type Identifier is `oauth_authorization_server`.[¶](#section-5.1.4-1)

All parameters defined in Section 2 of \[[RFC8414](#RFC8414)\] and [Section 5.2](#common_metadata) are applicable, as well as additional parameters registered in the IANA \"OAuth Authorization Server Metadata\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\].[¶](#section-5.1.4-2)

The `issuer` parameter value in the `oauth_authorization_server` metadata MUST match the Federation Entity Identifier (the `iss` Claim in the Entity Configuration).[¶](#section-5.1.4-3)

#### [5.1.5.](#section-5.1.5) [OAuth Client](#name-oauth-client)

The Entity Type Identifier is `oauth_client`.[¶](#section-5.1.5-1)

All parameters defined in Section 2 of [OAuth 2.0 Dynamic Client Registration Protocol](#RFC7591) \[[RFC7591](#RFC7591)\] and [Section 5.2](#common_metadata) are applicable, as well as additional parameters registered in the IANA \"OAuth Dynamic Client Registration Metadata\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\].[¶](#section-5.1.5-2)

#### [5.1.6.](#section-5.1.6) [OAuth Protected Resource](#name-oauth-protected-resource)

The Entity Type Identifier is `oauth_resource`. The parameters defined in [Section 5.2](#common_metadata) are applicable. In addition, deployments MAY use the protected resource metadata parameters defined in \[[RFC9728](#RFC9728)\].[¶](#section-5.1.6-1)

### [5.2.](#section-5.2) [Common Metadata Parameters](#name-common-metadata-parameters)

This section defines additional metadata parameters that MAY be used with all the Entity Types above, with the exception for JWK Sets noted below.[¶](#section-5.2-1)

#### [5.2.1.](#section-5.2.1) [Parameters for JWK Sets in Entity Metadata](#name-parameters-for-jwk-sets-in-)

The following metadata parameters define ways of obtaining JWK Sets for an Entity Type of the Entity. Note that these keys are distinct from the Federation Entity Keys used to sign Entity Statements, which are in the `jwks` Claim of the Entity Statement, and not within the `metadata` Claim. These parameters for JWK Sets MUST NOT be used in `federation_entity` Entity Type metadata.[¶](#section-5.2.1-1)

signed_jwks_uri
:   OPTIONAL. URL referencing a signed JWT having the Entity\'s JWK Set document for that Entity Type as its payload. This URL MUST use the `https` scheme. The JWT MUST be signed using a Federation Entity Key. A successful response from the URL MUST use the HTTP status code 200 with the content type `application/jwk-set+jwt`. When both signing and encryption keys are present, a `use` (public key use) parameter value is REQUIRED for all keys in the referenced JWK Set to indicate each key\'s intended usage.[¶](#section-5.2.1-2.2)
:   

:   Signed JWK Set JWTs are explicitly typed by setting the `typ` header parameter to `jwk-set+jwt` to prevent cross-JWT confusion, per Section 3.11 of \[[RFC8725](#RFC8725)\]. Signed JWK Set JWTs without a `typ` header parameter or with a different `typ` value MUST be rejected.[¶](#section-5.2.1-2.4)
:   

:   Signed JWK Set JWTs MUST include the `kid` (Key ID) header parameter with its value being the Key ID of the signing key used.[¶](#section-5.2.1-2.6)
:   

:   The following Claims are specified for use in the payload, all of which except `keys` are defined in \[[RFC7519](#RFC7519)\]:[¶](#section-5.2.1-2.8.1)

    keys
    :   REQUIRED. Array of JWK values in the JWK Set, as specified in Section 5.1 of \[[RFC7517](#RFC7517)\].[¶](#section-5.2.1-2.8.2.2)
    :   

    iss
    :   REQUIRED. The \"iss\" (issuer) Claim identifies the principal that issued the JWT.[¶](#section-5.2.1-2.8.2.4)
    :   

    sub
    :   REQUIRED. This Claim identifies the owner of the keys. It SHOULD be the same as the issuer.[¶](#section-5.2.1-2.8.2.6)
    :   

    iat
    :   OPTIONAL. Number. Time when this signed JWK Set was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-5.2.1-2.8.2.8)
    :   

    exp
    :   OPTIONAL. Number. This Claim identifies the time when the JWT is no longer valid. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-5.2.1-2.8.2.10)
    :   

    More Claims are defined in \[[RFC7519](#RFC7519)\]; of these, `aud` SHOULD NOT be used since the issuer cannot know who the audience is. `nbf` and `jti` are not particularly useful in this context and SHOULD be omitted.[¶](#section-5.2.1-2.8.3)

:   

:   Additional Claims MAY be defined and used in conjunction with the Claims above.[¶](#section-5.2.1-2.10.1)

    The following is a non-normative example of the JWT Claims Set for a signed JWK Set.[¶](#section-5.2.1-2.10.2)

            {
              "keys": [
                {
                  "kty": "RSA",
                  "kid": "SUdtUndEWVY2cUFDeDV5NVlBWDhvOXJodVl2am1mNGNtR0pmd",
                  "n": "y_Zc8rByfeRIC9fFZrDZ2MGH2ZnxLrc0ZNNwkNet5rwCPYeRF3Sv
                        5nihZA9NHkDTEX97dN8hG6ACfeSo6JB2P7heJtmzM8oOBZbmQ90n
                        EA_JCHszkejHaOtDDfxPH6bQLrMlItF4JSUKua301uLB7C8nzTxm
                        tF3eAhGCKn8LotEseccxsmzApKRNWhfKDLpKPe9i9PZQhhJaurwD
                        kMwbWTAeZbqCScU1o09piuK1JDf2PaDFevioHncZcQO74Obe4nN3
                        oNPNAxrMClkZ9s9GMEd5vMqOD4huXlRpHwm9V3oJ3LRutOTxqQLV
                        yPucu7eHA7her4FOFAiUk-5SieXL9Q",
                  "e": "AQAB"
                },
                {
                  "kty": "EC",
                  "kid": "MFYycG1raTI4SkZvVDBIMF9CNGw3VEZYUmxQLVN2T21nSWlkd3",
                  "crv": "P-256",
                  "x": "qAOdPQROkHfZY1daGofOmSNQWpYK8c9G2m2Rbkpbd4c",
                  "y": "G_7fF-T8n2vONKM15Mzj4KR_shvHBxKGjMosF6FdoPY"
                }
              ],
              "iss": "https://example.org/op",
              "sub": "https://example.org/op",
              "iat": 1618410883
            }

    [Figure 8](#figure-8): [Example JWT Claims Set for a Signed JWK Set](#name-example-jwt-claims-set-for-)

:   

jwks_uri
:   OPTIONAL. URL referencing a JWK Set document containing the Entity\'s keys for that Entity Type. This URL MUST use the `https` scheme. When both signing and encryption keys are present, a `use` (public key use) parameter value is REQUIRED for all keys in the referenced JWK Set to indicate each key\'s intended usage.[¶](#section-5.2.1-2.12)
:   

jwks
:   OPTIONAL. JSON Web Key Set document, passed by value, containing the Entity\'s keys for that Entity Type. When both signing and encryption keys are present, a `use` (public key use) parameter value is REQUIRED for all keys in the JWK Set to indicate each key\'s intended usage. This parameter is intended to be used by participants that, for some reason, cannot use the `signed_jwks_uri` parameter. An upside of using `jwks` is that the Entity\'s keys for the Entity Type are recorded in Trust Chains.[¶](#section-5.2.1-2.14)
:   

##### [5.2.1.1.](#section-5.2.1.1) [Usage of jwks, jwks_uri, and signed_jwks_uri in Entity Metadata](#name-usage-of-jwks-jwks_uri-and-)

It is RECOMMENDED that an Entity Configuration use only one of `jwks`, `jwks_uri`, and `signed_jwks_uri` in its OpenID Connect or OAuth 2.0 metadata. However, there may be circumstances in which it is desirable to use multiple JWK Set representations, such as when an Entity is in multiple federations and the federations have different policies about the JWK Set representation to be used. Also note that some implementations might not understand all these representations. For instance, while `jwks_uri` will certainly be understood in OpenID Connect OP metadata, `signed_jwks_uri` might not be understood by all OpenID Connect implementations, and so a JWK Set representation that is understood also needs to be present.[¶](#section-5.2.1.1-1)

When multiple JWK Set representations are used, the keys present in each representation SHOULD be the same. Even if they are not completely the same at a given instant in time (which may be the case during key rollover operations), implementations MUST make them consistent in a timely manner.[¶](#section-5.2.1.1-2)

#### [5.2.2.](#section-5.2.2) [Informational Metadata Parameters](#name-informational-metadata-para)

The following metadata parameters define ways of obtaining information about the Entity for an Entity Type.[¶](#section-5.2.2-1)

organization_name
:   OPTIONAL. A human-readable name representing the organization owning this Entity. If the owner is a physical person, this MAY be, for example, the person\'s name. Note that this information will be publicly available.[¶](#section-5.2.2-2.2)
:   

display_name
:   OPTIONAL. A human-readable name of the Entity to be presented to the End-User.[¶](#section-5.2.2-2.4)
:   

description
:   OPTIONAL. A human-readable brief description of this Entity presentable to the End-User.[¶](#section-5.2.2-2.6)
:   

keywords
:   OPTIONAL. JSON array with one or more strings representing search keywords, tags, categories, or labels that apply to this Entity.[¶](#section-5.2.2-2.8)
:   

contacts
:   OPTIONAL. JSON array with one or more strings representing contact persons at the Entity. These MAY contain names, e-mail addresses, descriptions, phone numbers, etc.[¶](#section-5.2.2-2.10)
:   

logo_uri
:   OPTIONAL. String. A URL that points to the logo of this Entity. The file containing the logo SHOULD be published in a format that can be viewed via the web.[¶](#section-5.2.2-2.12)
:   

policy_uri
:   OPTIONAL. URL of the documentation of conditions and policies relevant to this Entity.[¶](#section-5.2.2-2.14)
:   

information_uri
:   OPTIONAL. URL for documentation of additional information about this Entity viewable by the End-User.[¶](#section-5.2.2-2.16)
:   

organization_uri
:   OPTIONAL. URL of a Web page for the organization owning this Entity.[¶](#section-5.2.2-2.18)
:   

These metadata parameters MAY be present in the Entity\'s metadata for any Entity Types that it uses.[¶](#section-5.2.2-3)

## [6.](#section-6) [Federation Policy](#name-federation-policy)

### [6.1.](#section-6.1) [Metadata Policy](#name-metadata-policy)

Trust Anchors and Intermediate Entities MAY define policies that apply to the metadata of their Subordinates.[¶](#section-6.1-1)

A federation may utilize metadata policies to achieve specific objectives. For example, in a federation of [OpenID Connect](#OpenID.Core) \[[OpenID.Core](#OpenID.Core)\] Entities, one objective may be to ensure the metadata published by OpenID Providers and Relying Parties are interoperable with one another. Another objective may be to ensure the Entity metadata complies with a security profile, for example, [FAPI](#FAPI) \[[FAPI](#FAPI)\].[¶](#section-6.1-2)

Note that the `metadata_policy` is not intended to check and validate the JSON value types of metadata parameters. Such checks SHOULD be performed at the application layer, after obtaining the Entity metadata from a successfully resolved Trust Chain.[¶](#section-6.1-3)

#### [6.1.1.](#section-6.1.1) [Principles](#name-principles)

OpenID Federation enables the definition of metadata policies with the following properties:[¶](#section-6.1.1-1)

Hierarchy

:   Once applied to a metadata parameter, a metadata policy cannot be repealed or made more permissive by Intermediate Entities that are subordinate in the Trust Chain.[¶](#section-6.1.1-2.2.1)

    The hierarchy of policies is preserved in nested federations where a Trust Anchor in one federation acts as an Intermediate Entity in another federation.[¶](#section-6.1.1-2.2.2)

:   

Equal Opportunity
:   All Superior Entities in a Trust Chain can contribute metadata policies on an equal basis, provided their contributions result in a combined metadata policy that is logically sound. For instance, any Intermediate can further restrict the metadata of its Subordinates relative to what its Superiors specified. An Intermediate that introduces a conflict among the metadata policies causes the Trust Chain to be deemed invalid.[¶](#section-6.1.1-2.4)
:   

Specificity and Granularity

:   Just like metadata, a metadata policy is bound to a specific Entity Type. This ensures policies for different Entity Types are independent and isolated from one another.[¶](#section-6.1.1-2.6.1)

    Policies are expressed at the level of individual metadata parameters. The policies for a given Entity Type metadata parameter are thus independent and isolated from those for other parameters.[¶](#section-6.1.1-2.6.2)

    When a Trust Anchor or an Intermediate Entity defines a metadata policy for an Entity Type, it applies to the metadata of all Subordinate Entities of that type in the Trust Chain.[¶](#section-6.1.1-2.6.3)

    Because the place to define a policy is the Subordinate Statement and every Entity Statement is issued for a specific subject, a federation authority can choose to define a common Entity Type metadata policy for all its Subordinates, or specific Entity Type metadata policies for specific Subordinates.[¶](#section-6.1.1-2.6.4)

:   

Operation
:   A policy operates by performing a check, a modification, or both on a given metadata parameter. This specification defines a set of standard operators, described in [Section 6.1.3.1](#standard_metadata_policy_operators). A federation MAY specify and use additional operators, provided they comply with the principles laid out in this section and with [Section 6.1.3](#metadata_policy_operators) and [Section 6.1.3.2](#additional_metadata_policy_operators).[¶](#section-6.1.1-2.8)
:   

Integral Metadata Enforcement

:   The resolution and application of metadata policies is an integral part of the Trust Chain resolution process, as described in [Section 10](#resolving_trust).[¶](#section-6.1.1-2.10.1)

    This means:[¶](#section-6.1.1-2.10.2)

    -   A Trust Chain for which the metadata policy resolution fails due to an error, for example, due to an Intermediate Entity\'s policy conflicting with a Superior\'s policy, is deemed invalid.[¶](#section-6.1.1-2.10.3.1.1)

    -   A Trust Chain with Entity metadata that does not comply with the Resolved Metadata policies is deemed invalid.[¶](#section-6.1.1-2.10.3.2.1)

:   

Determinism
:   The resolution and application of metadata policies in a Trust Chain is deterministic. Trust Anchors and Intermediate Entities are thus able to formulate policies that exhibit predictable and reproducible outcomes.[¶](#section-6.1.1-2.12)
:   

#### [6.1.2.](#section-6.1.2) [Structure](#name-structure)

Metadata policies are expressed in the `metadata_policy` Claim of a Subordinate Statement, as described in [Section 3.1.3](#ss-specific). The Claim Value is a JSON object that has a data structure consisting of three levels:[¶](#section-6.1.2-1)

1.  Metadata policies for Entity Types.[¶](#section-6.1.2-2.1.1)

    The top level contains one or more members, each representing the metadata policy for an Entity Type. Each member name is an Entity Type Identifier, as specified in [Section 5.1](#entity_types), for example, `openid_relying_party`. The member value is a JSON object that contains metadata parameter policies.[¶](#section-6.1.2-2.1.2)

2.  Metadata parameter policies for the Entity Type.[¶](#section-6.1.2-2.2.1)

    The second level contains one or more members, each representing a policy for a metadata parameter for the Entity Type. Each member name is a metadata parameter name, for example `id_token_signed_response_alg`. The name MAY include a language tag, as described in [Section 14](#ClaimsLanguagesAndScripts), in which case the metadata parameter policy applies only to the metadata parameter with the specified language tag. The member value is a JSON object that contains policy operators.[¶](#section-6.1.2-2.2.2)

3.  Operators for the metadata parameter policy for the Entity Type.[¶](#section-6.1.2-2.3.1)

    The third level contains one or more members, each representing an operator that checks or modifies the metadata parameter, as described in [Section 6.1.3](#metadata_policy_operators). Only operators that are allowed to be combined with one another, as defined in the specifications for each operator, can be included together here.[¶](#section-6.1.2-2.3.2)

Duplicate JSON object member names MUST NOT be present at any of the three levels of the `metadata_policy` Claim data structure.[¶](#section-6.1.2-3)

The following is a non-normative example of a metadata policy for an OpenID Relying Party that consists of a single policy for the `id_token_signed_response_alg` metadata parameter and uses two operators, `default` and `one_of`:[¶](#section-6.1.2-4)

    "metadata_policy" : {
      "openid_relying_party": {
        "id_token_signed_response_alg": {
          "default": "ES256",
          "one_of": ["ES256", "ES384", "ES512"]
        }
      }
    }

[Figure 9](#figure-9): [Example `metadata_policy` Claim](#name-example-metadata_policy-cla)

#### [6.1.3.](#section-6.1.3) [Operators](#name-operators)

A metadata policy operator:[¶](#section-6.1.3-1)

-   Is identified by a unique case-sensitive name.[¶](#section-6.1.3-2.1.1)

-   Acts on a single metadata parameter. The operator definition MUST specify the metadata parameter JSON value types that are mandatory to support, and MAY also specify JSON value types that are optional to support. When the metadata parameter has a JSON value type that is not supported, the operator MUST produce a policy error.[¶](#section-6.1.3-2.2.1)

-   The action on the metadata parameter can be a value check, a value modification, or both. When the operator\'s action is a value modification, it MAY remove the metadata parameter.[¶](#section-6.1.3-2.3.1)

-   The action of the operator is configured by a JSON value. The operator definition MUST specify the JSON value types that are mandatory to support, and MAY also specify JSON value types that are optional to support. When the operator is configured with a JSON value type that is not supported, the operator MUST produce a policy error.[¶](#section-6.1.3-2.4.1)

-   MUST declare what other operators it may be combined with, which applies to both individual as well as merged metadata parameter policies, as described in [Section 6.1.2](#metadata_policy_structure) and [Section 6.1.4](#metadata_policy_enforcement). A combination may be unconditional, or conditional, requiring the configured values of the two operators to meet certain criteria. Combinations that are not allowed MUST produce a policy error.[¶](#section-6.1.3-2.5.1)

-   MUST declare in what order it is to be applied to a metadata parameter, absolute or relative to other operators in the metadata parameter policy. Value check operators SHOULD generally be applied after operators that perform value modifications.[¶](#section-6.1.3-2.6.1)

-   MUST specify, when more than one Subordinate Statement in a Trust Chain has a policy for an Entity Type metadata parameter that uses the same operator, whether the operator values are allowed to be merged to produce an identical or more restrictive policy, and if so, under what conditions. The order of the result of such an operator value merge is not defined. If the operator does not allow such a merge, it MUST produce a policy error.[¶](#section-6.1.3-2.7.1)

-   An operator MUST NOT output a metadata parameter with the `null` value.[¶](#section-6.1.3-2.8.1)

Note that metadata parameters and policies that conform to the JSON grammar but do not represent interoperable uses of JSON, as per Sections 4 and 8 of \[[RFC8259](#RFC8259)\], can cause unpredictable behaviors.[¶](#section-6.1.3-3)

##### [6.1.3.1.](#section-6.1.3.1) [Standard Operators](#name-standard-operators)

This specification defines the following metadata policy operators:[¶](#section-6.1.3.1-1)

###### [6.1.3.1.1.](#section-6.1.3.1.1) [value](#name-value)

Name: `value`[¶](#section-6.1.3.1.1-1)

Action: The metadata parameter MUST be assigned the value of the operator. When the value of the operator is `null`, the metadata parameter MUST be removed.[¶](#section-6.1.3.1.1-2)

Metadata parameter JSON values:[¶](#section-6.1.3.1.1-3)

-   Mandatory to support: string, number, boolean, array[¶](#section-6.1.3.1.1-4.1.1)

Operator JSON values:[¶](#section-6.1.3.1.1-5)

-   Mandatory to support: string, number, boolean, array, null[¶](#section-6.1.3.1.1-6.1.1)

Combination with other operators in a metadata parameter policy:[¶](#section-6.1.3.1.1-7)

-   MAY be combined with `add`, in which case the values of `add` MUST be a subset of the values of `value`.[¶](#section-6.1.3.1.1-8.1.1)

-   MAY be combined with `default` if the value of `value` is not `null`.[¶](#section-6.1.3.1.1-8.2.1)

-   MAY be combined with `one_of`, in which case the value of `value` MUST be among the `one_of` values.[¶](#section-6.1.3.1.1-8.3.1)

-   MAY be combined with `subset_of`, in which case the values of `value` MUST be a subset of the values of `subset_of`.[¶](#section-6.1.3.1.1-8.4.1)

-   MAY be combined with `superset_of`, in which case the values of `value` MUST be a superset of the values of `superset_of`.[¶](#section-6.1.3.1.1-8.5.1)

-   MAY be combined with `essential`, except when `value` is `null` and `essential` is true.[¶](#section-6.1.3.1.1-8.6.1)

Order of application: First[¶](#section-6.1.3.1.1-9)

Operator value merge: Allowed only when the operator values are equal. If not, this MUST produce a policy error.[¶](#section-6.1.3.1.1-10)

###### [6.1.3.1.2.](#section-6.1.3.1.2) [add](#name-add)

Name: `add`[¶](#section-6.1.3.1.2-1)

Action: The value or values of this operator MUST be added to the metadata parameter. Values that are already present in the metadata parameter MUST NOT be added another time. If the metadata parameter is absent, it MUST be initialized with the value of this operator.[¶](#section-6.1.3.1.2-2)

Metadata parameter JSON values:[¶](#section-6.1.3.1.2-3)

-   Mandatory to support: array of strings[¶](#section-6.1.3.1.2-4.1.1)

-   Optional to support: array of objects, array of numbers[¶](#section-6.1.3.1.2-4.2.1)

Operator JSON values:[¶](#section-6.1.3.1.2-5)

-   Mandatory to support: array of strings[¶](#section-6.1.3.1.2-6.1.1)

-   Optional to support: array of objects, array of numbers[¶](#section-6.1.3.1.2-6.2.1)

Combination with other operators in a metadata parameter policy:[¶](#section-6.1.3.1.2-7)

-   MAY be combined with `value`, in which case the values of `add` MUST be a subset of the values of `value`.[¶](#section-6.1.3.1.2-8.1.1)

-   MAY be combined with `default`.[¶](#section-6.1.3.1.2-8.2.1)

-   MAY be combined with `subset_of`, in which case the values of `add` MUST be a subset of the values of `subset_of`.[¶](#section-6.1.3.1.2-8.3.1)

-   MAY be combined with `superset_of`.[¶](#section-6.1.3.1.2-8.4.1)

-   MAY be combined with `essential`.[¶](#section-6.1.3.1.2-8.5.1)

Order of application: After `value`.[¶](#section-6.1.3.1.2-9)

Operator value merge: The result of merging the values of two `add` operators is the union of the values.[¶](#section-6.1.3.1.2-10)

###### [6.1.3.1.3.](#section-6.1.3.1.3) [default](#name-default)

Name: `default`[¶](#section-6.1.3.1.3-1)

Action: If the metadata parameter is absent, it MUST be set to the value of the operator. If the metadata parameter is present, this operator has no effect.[¶](#section-6.1.3.1.3-2)

Metadata parameter JSON values:[¶](#section-6.1.3.1.3-3)

-   Mandatory to support: string, number, boolean, array[¶](#section-6.1.3.1.3-4.1.1)

Operator JSON values:[¶](#section-6.1.3.1.3-5)

-   Mandatory to support: string, number, boolean, array[¶](#section-6.1.3.1.3-6.1.1)

Combination with other operators in a metadata parameter policy:[¶](#section-6.1.3.1.3-7)

-   MAY be combined with `value` if the value of `value` is not `null`.[¶](#section-6.1.3.1.3-8.1.1)

-   MAY be combined with `add`.[¶](#section-6.1.3.1.3-8.2.1)

-   MAY be combined with `one_of`.[¶](#section-6.1.3.1.3-8.3.1)

-   MAY be combined with `subset_of`.[¶](#section-6.1.3.1.3-8.4.1)

-   MAY be combined with `superset_of`.[¶](#section-6.1.3.1.3-8.5.1)

-   MAY be combined with `essential`.[¶](#section-6.1.3.1.3-8.6.1)

Order of application: After `add`.[¶](#section-6.1.3.1.3-9)

Operator value merge: The operator values MUST be equal. If the values are not equal this MUST produce a policy error.[¶](#section-6.1.3.1.3-10)

###### [6.1.3.1.4.](#section-6.1.3.1.4) [one_of](#name-one_of)

Name: `one_of`[¶](#section-6.1.3.1.4-1)

Action: If the metadata parameter is present, its value MUST be one of those listed in the operator value.[¶](#section-6.1.3.1.4-2)

Metadata parameter JSON values:[¶](#section-6.1.3.1.4-3)

-   Mandatory to support: string[¶](#section-6.1.3.1.4-4.1.1)

-   Optional to support: object, number[¶](#section-6.1.3.1.4-4.2.1)

Operator JSON values:[¶](#section-6.1.3.1.4-5)

-   Mandatory to support: array of strings[¶](#section-6.1.3.1.4-6.1.1)

-   Optional to support: array of objects, array of numbers[¶](#section-6.1.3.1.4-6.2.1)

Combination with other operators in a metadata parameter policy:[¶](#section-6.1.3.1.4-7)

-   MAY be combined with `value`, in which case the value of `value` MUST be among the `one_of` values.[¶](#section-6.1.3.1.4-8.1.1)

-   MAY be combined with `default`.[¶](#section-6.1.3.1.4-8.2.1)

-   MAY be combined with `essential`.[¶](#section-6.1.3.1.4-8.3.1)

Order of application: After `default`.[¶](#section-6.1.3.1.4-9)

Operator value merge: The result of merging the values of two `one_of` operators is the intersection of the operator values. If the intersection is empty, this MUST result in a policy error.[¶](#section-6.1.3.1.4-10)

###### [6.1.3.1.5.](#section-6.1.3.1.5) [subset_of](#name-subset_of)

Name: `subset_of`[¶](#section-6.1.3.1.5-1)

Action: If the metadata parameter is present, it is assigned the intersection between the values of the operator and the metadata parameter. Note that the resulting intersection may thus be an empty array `[]`. Also note that `subset_of` is a potential value modifier in addition to it being a value check.[¶](#section-6.1.3.1.5-2)

Metadata parameter JSON values:[¶](#section-6.1.3.1.5-3)

-   Mandatory to support: array of strings[¶](#section-6.1.3.1.5-4.1.1)

-   Optional to support: array of objects, array of numbers[¶](#section-6.1.3.1.5-4.2.1)

Operator JSON values:[¶](#section-6.1.3.1.5-5)

-   Mandatory to support: array of strings[¶](#section-6.1.3.1.5-6.1.1)

-   Optional to support: array of objects, array of numbers[¶](#section-6.1.3.1.5-6.2.1)

Combination with other operators in a metadata parameter policy:[¶](#section-6.1.3.1.5-7)

-   MAY be combined with `value`, in which case the values of `value` MUST be a subset of the values of `subset_of`.[¶](#section-6.1.3.1.5-8.1.1)

-   MAY be combined with `add`, in which case the values of `add` MUST be a subset of the values of `subset_of`.[¶](#section-6.1.3.1.5-8.2.1)

-   MAY be combined with `default`.[¶](#section-6.1.3.1.5-8.3.1)

-   MAY be combined with `superset_of`, in which case the values of `subset_of` MUST be a superset of the values of `superset_of`.[¶](#section-6.1.3.1.5-8.4.1)

-   MAY be combined with `essential`.[¶](#section-6.1.3.1.5-8.5.1)

Order of application: After `one_of`.[¶](#section-6.1.3.1.5-9)

Operator value merge: The result of merging the values of two `subset_of` operators is the intersection of the operator values. Note that the resulting intersection may thus be an empty array `[]`.[¶](#section-6.1.3.1.5-10)

###### [6.1.3.1.6.](#section-6.1.3.1.6) [superset_of](#name-superset_of)

Name: `superset_of`[¶](#section-6.1.3.1.6-1)

Action: If the metadata parameter is present, its values MUST contain those specified in the operator value. By mathematically defining supersets, equality is included.[¶](#section-6.1.3.1.6-2)

Metadata parameter JSON values:[¶](#section-6.1.3.1.6-3)

-   Mandatory to support: array of strings[¶](#section-6.1.3.1.6-4.1.1)

-   Optional to support: array of objects, array of numbers[¶](#section-6.1.3.1.6-4.2.1)

Operator JSON values:[¶](#section-6.1.3.1.6-5)

-   Mandatory to support: array of strings[¶](#section-6.1.3.1.6-6.1.1)

-   Optional to support: array of objects, array of numbers[¶](#section-6.1.3.1.6-6.2.1)

Combination with other operators in a metadata parameter policy:[¶](#section-6.1.3.1.6-7)

-   MAY be combined with `value`, in which case the values of `value` MUST be a superset of the values of `superset_of`.[¶](#section-6.1.3.1.6-8.1.1)

-   MAY be combined with `add`.[¶](#section-6.1.3.1.6-8.2.1)

-   MAY be combined with `default`.[¶](#section-6.1.3.1.6-8.3.1)

-   MAY be combined with `subset_of`, in which case the values of `subset_of` MUST be a superset of the values of `superset_of`.[¶](#section-6.1.3.1.6-8.4.1)

-   MAY be combined with `essential`.[¶](#section-6.1.3.1.6-8.5.1)

Order of application: After `subset_of`.[¶](#section-6.1.3.1.6-9)

Operator value merge: The result of merging the values of two `superset_of` operators is the union of the operator values.[¶](#section-6.1.3.1.6-10)

###### [6.1.3.1.7.](#section-6.1.3.1.7) [essential](#name-essential)

Name: `essential`[¶](#section-6.1.3.1.7-1)

Action: If the value of this operator is `true`, then the metadata parameter MUST be present. If `false`, the metadata parameter is voluntary and may be absent. If the `essential` operator is omitted, this is equivalent to including it with a value of `false`.[¶](#section-6.1.3.1.7-2)

Metadata parameter JSON values:[¶](#section-6.1.3.1.7-3)

-   Mandatory to support: string, number, boolean, object, array[¶](#section-6.1.3.1.7-4.1.1)

Operator JSON values:[¶](#section-6.1.3.1.7-5)

-   Mandatory to support: boolean[¶](#section-6.1.3.1.7-6.1.1)

Combination with other operators in a metadata parameter policy:[¶](#section-6.1.3.1.7-7)

-   MAY be combined with `value`, except when `value` is `null` and `essential` is true.[¶](#section-6.1.3.1.7-8.1.1)

-   MAY be combined with any other operator.[¶](#section-6.1.3.1.7-8.2.1)

Order of application: Last[¶](#section-6.1.3.1.7-9)

Operator value merge: The result of merging the values of two `essential` operators is the logical disjunction (`OR`) of the operator values.[¶](#section-6.1.3.1.7-10)

###### [6.1.3.1.8.](#section-6.1.3.1.8) [Notes on Operators](#name-notes-on-operators)

A \"set equals\" metadata parameter policy can be expressed by combining the operators `subset_of` and `superset_of` with identical array values.[¶](#section-6.1.3.1.8-1)

Some JSON libraries may have issues comparing JSON objects. For this reason, support for applying metadata policy to metadata values that are JSON objects is mandatory only for the `essential` operator, which does not require comparison of values. It is OPTIONAL for the `add`, `one_of`, `subset_of`, and `superset_of` operators, which operate on JSON arrays and therefore require comparison of values. And it is OPTIONAL for the `value` and `default` operators, since merging values and defaults requires comparing the operators\' values with existing ones.[¶](#section-6.1.3.1.8-2)

The `scope` OAuth 2.0 client metadata parameter, defined in \[[RFC7591](#RFC7591)\] and represented by a string of space-separated string values, is to be regarded and processed as a string array by policy operators, such as the operators `default`, `subset_of` and `superset_of`. The resulting `scope` metadata parameter is a space-separated string of individual scope values, where the scope values present are taken from the array of values produced by applying the metadata operators to the `scope` parameter.[¶](#section-6.1.3.1.8-3)

The following table is a map of the outputs produced by combinations of the `essential` and `subset_of` policy operators with different input metadata parameter values. Note, the `subset_of` check is skipped when the metadata parameter is absent and designated as voluntary, as shown in the last row.[¶](#section-6.1.3.1.8-4)

  Policy                  Metadata Parameter   
  ----------- ----------- -------------------- --------------
  essential   subset_of   input                output
  true        \[a,b,c\]   \[a,e\]              \[a\]
  false       \[a,b,c\]   \[a,e\]              \[a\]
  true        \[a,b,c\]   \[d,e\]              \[\]
  false       \[a,b,c\]   \[d,e\]              \[\]
  true        \[a,b,c\]   no parameter         error
  false       \[a,b,c\]   no parameter         no parameter

  : [Table 1](#table-1): [Examples of Outputs with Combinations of `essential` and `subset_of` for Different Inputs](#name-examples-of-outputs-with-co)

##### [6.1.3.2.](#section-6.1.3.2) [Additional Operators](#name-additional-operators)

Federations MAY specify and use additional metadata policy operators that conform with the principles in [Section 6.1.1](#metadata_policy_principles) and in [Section 6.1.3](#metadata_policy_operators).[¶](#section-6.1.3.2-1)

Additional operators MUST observe the following rules in regard to the order of their application relative to the standard operators defined in [Section 6.1.3.1](#standard_metadata_policy_operators):[¶](#section-6.1.3.2-2)

-   Additional operators that modify metadata parameters MUST be applied after the `value` operator that is specified in [Section 6.1.3.1.1](#value_operator).[¶](#section-6.1.3.2-3.1.1)

-   Additional operators that check metadata parameters MUST be applied before the `essential` operator that is specified in [Section 6.1.3.1.7](#essential_operator).[¶](#section-6.1.3.2-3.2.1)

Implementations MUST ignore additional operators that are not understood, unless the operator name is included in the `metadata_policy_crit` Subordinate Statement Claim, in which case the operator MUST be understood and processed. If an additional operator listed in `metadata_policy_crit` is not understood or cannot be processed, then this MUST produce a policy error and the Trust Chain MUST be considered invalid.[¶](#section-6.1.3.2-4)

#### [6.1.4.](#section-6.1.4) [Enforcement](#name-enforcement)

This section describes the resolution of the metadata policy for a Trust Chain and its application to the metadata of the Federation Entity that is the Trust Chain subject.[¶](#section-6.1.4-1)

If a policy error or another error is encountered during the metadata policy resolution or its application, the Trust Chain MUST be considered invalid.[¶](#section-6.1.4-2)

##### [6.1.4.1.](#section-6.1.4.1) [Resolution](#name-resolution)

The metadata policy for a Trust Chain is determined by the sequence of the present `metadata_policy` Claims of the Subordinate Statements that make up the chain.[¶](#section-6.1.4.1-1)

The resolution process MUST first gather the names of all policy operators other than the standard ones defined in [Section 6.1.3.1](#standard_metadata_policy_operators) that are declared as critical. This is done by checking each Subordinate Statement in the Trust Chain for the optional `metadata_policy_crit` Claim, described in [Section 3.1.3](#ss-specific), and collecting any operator names that are found in it.[¶](#section-6.1.4.1-2)

The resolution process proceeds by iterating through the Subordinate Statements. The sequence of this iteration is crucial - it MUST begin with the Subordinate Statement issued by the most Superior Entity and end with the Subordinate Statement issued by the Immediate Superior of the Trust Chain subject.[¶](#section-6.1.4.1-3)

An important task during the iteration is the `metadata_policy` validation. It MUST ensure the data structure is compliant and that every metadata parameter policy contains only allowed operator combinations, as described in [Section 6.1.3](#metadata_policy_operators), and in accordance with the specifications of the operators. It MUST also be ensured that the `metadata_policy` contains no operators that cannot be understood and processed whose names are among the collected `metadata_policy_crit` values. An unsuccessful validation MUST produce a policy error.[¶](#section-6.1.4.1-4)

At each iteration step, the Subordinate Statement MUST be checked for the presence of a `metadata_policy` Claim. The first encountered `metadata_policy` MUST be validated as described above, after which it becomes the current metadata policy.[¶](#section-6.1.4.1-5)

If the iteration yields a next subordinate `metadata_policy` Claim, it MUST be validated as described above, then merged into the current metadata policy.[¶](#section-6.1.4.1-6)

The merge is performed at each of the three levels of the `metadata_policy` data structure described in [Section 6.1.2](#metadata_policy_structure), by starting from the top level:[¶](#section-6.1.4.1-7)

1.  The metadata policies for Entity Types.[¶](#section-6.1.4.1-8.1.1)

2.  The metadata parameter policies for an Entity Type.[¶](#section-6.1.4.1-8.2.1)

3.  The operators for a metadata parameter policy for an Entity Type.[¶](#section-6.1.4.1-8.3.1)

At the level of metadata policies for Entity Types, the merge proceeds as follows:[¶](#section-6.1.4.1-9)

-   If the next subordinate `metadata_policy` Claim contains a metadata policy for an Entity Type that is already present in the current metadata policy, it MUST be merged according to the rules of the next lower level (the metadata parameter policies).[¶](#section-6.1.4.1-10.1.1)

-   Entity Type metadata policies in the next subordinate `metadata_policy` Claim that are not present in the current metadata policy MUST be copied to it.[¶](#section-6.1.4.1-10.2.1)

At the level of metadata parameter policies, the merge proceeds as follows:[¶](#section-6.1.4.1-11)

-   If a metadata parameter policy is already present in the current Entity Type metadata policy, it MUST be merged according to the rules of the next lower level (the operators for the metadata parameter policy). If the resulting metadata parameter policy contains combinations that are not allowed, as described in [Section 6.1.3](#metadata_policy_operators) and in accordance with the specifications of the operators, this MUST produce a policy error.[¶](#section-6.1.4.1-12.1.1)

-   Subordinate metadata parameter policies that are not present in the current Entity Type metadata policy MUST be copied to it.[¶](#section-6.1.4.1-12.2.1)

At the level of operators, the merge proceeds as follows:[¶](#section-6.1.4.1-13)

-   If an operator is already present in the current metadata parameter policy, the values of the subordinate operator MUST be merged, as described in [Section 6.1.3](#metadata_policy_operators) and in accordance with the operator specification. If an operator value merge is not allowed or otherwise unsuccessful this MUST produce a policy error.[¶](#section-6.1.4.1-14.1.1)

-   Subordinate operators that are not present in the current metadata parameter policy MUST be copied to it.[¶](#section-6.1.4.1-14.2.1)

If no further Subordinate Statements with a `metadata_policy` Claim are found, the current metadata policy becomes the resolved one for the Trust Chain.[¶](#section-6.1.4.1-15)

##### [6.1.4.2.](#section-6.1.4.2) [Application](#name-application)

If the Subordinate Statement about the Trust Chain subject contains a `metadata` Claim, this MUST first be applied, as described in the Claim definition in [Section 3.1.1](#common-claims), and only then it can be proceeded with applying the Resolved Metadata policy.[¶](#section-6.1.4.2-1)

If the process described in [Section 6.1.4.1](#metadata_policy_resolution) found no Subordinate Statements in the Trust Chain with a `metadata_policy` Claim, the metadata of the Trust Chain subject resolves simply to the `metadata` found in its Entity Configuration, with any `metadata` parameters provided by the Immediate Superior applied to it.[¶](#section-6.1.4.2-2)

If a metadata policy was resolved for the Trust Chain, for every Entity Type metadata and metadata parameter for which a corresponding metadata parameter policy is present, the included policy operators MUST be applied as described in [Section 6.1.3](#metadata_policy_operators) and in accordance with the specifications of the operators. The operators MUST be applied to the metadata parameter in a sequence that is determined by the absolute or relative order specified for each operator.[¶](#section-6.1.4.2-3)

If the application of metadata policies results in illegal or otherwise incorrect Resolved Metadata, then the metadata MUST be regarded as broken and MUST NOT be used.[¶](#section-6.1.4.2-4)

The Trust Chain subject is responsible to verify that it is able to support and comply with the Resolved Metadata that results from the application of federation metadata policies. For instance, this may involve a check that cryptographic algorithms required by the resulting Resolved Metadata are supported. Likewise, the Trust Chain subject MUST verify that all the required metadata parameters for its Entity Types are present and all the metadata values in the Resolved Metadata are valid. When metadata policies change, Trust Chain subjects may need to reevaluate their support and compliance.[¶](#section-6.1.4.2-5)

#### [6.1.5.](#section-6.1.5) [Metadata Policy Example](#name-metadata-policy-example)

The following is a non-normative example of resolving and applying Trust Chain metadata policies for an OpenID relying party.[¶](#section-6.1.5-1)

We start with a federation Trust Anchor\'s `metadata_policy` for RPs:[¶](#section-6.1.5-2)

    "metadata_policy": {
      "openid_relying_party": {
        "grant_types": {
           "default": [
            "authorization_code"
          ],
          "subset_of": [
            "authorization_code",
            "refresh_token"
          ],
          "superset_of": [
            "authorization_code"
          ]
        },
        "token_endpoint_auth_method": {
          "one_of": [
            "private_key_jwt",
            "self_signed_tls_client_auth"
          ],
          "essential": true
        },
        "token_endpoint_auth_signing_alg" : {
          "one_of": [
            "PS256",
            "ES256"
          ]
        },
        "subject_type": {
          "value": "pairwise"
        },
        "contacts": {
          "add": [
            "helpdesk@federation.example.org"
          ]
        }
      }
    }

[Figure 10](#figure-10): [Example Metadata Policy of a Trust Anchor for RPs](#name-example-metadata-policy-of-)

Next, we have an Intermediate organization\'s `metadata_policy` for Subordinate RPs together with `metadata` values for its Immediate Subordinate RPs:[¶](#section-6.1.5-4)

    {
      "metadata_policy": {
        "openid_relying_party": {
          "grant_types": {
            "subset_of": [
              "authorization_code"
            ]
          },
          "token_endpoint_auth_method": {
            "one_of": [
              "self_signed_tls_client_auth"
            ]
          },
          "contacts": {
            "add": [
              "helpdesk@org.example.org"
            ]
          }
        }
      },
      "metadata": {
        "openid_relying_party": {
          "sector_identifier_uri": "https://org.example.org/sector-ids.json",
          "policy_uri": "https://org.example.org/policy.html"
        }
      }
    }

[Figure 11](#figure-11): [Example Metadata Policy and Metadata Values of an Intermediate Entity for RPs](#name-example-metadata-policy-and)

Merging the example RP metadata policy of the Intermediate Entity into the RP metadata policy of the Trust Anchor produces the following policy for the Trust Chain:[¶](#section-6.1.5-6)

    {
      "grant_types": {
        "default": [
          "authorization_code"
        ],
        "superset_of": [
          "authorization_code"
        ],
        "subset_of": [
          "authorization_code"
        ]
      },
      "token_endpoint_auth_method": {
        "one_of": [
          "self_signed_tls_client_auth"
        ],
        "essential": true
      },
      "token_endpoint_auth_signing_alg": {
        "one_of": [
          "PS256",
          "ES256"
        ]
      },
      "subject_type": {
        "value": "pairwise"
      },
      "contacts": {
        "add": [
          "helpdesk@federation.example.org",
          "helpdesk@org.example.org"
        ]
      }
    }

[Figure 12](#figure-12): [Example Merged Metadata Policy for RPs](#name-example-merged-metadata-pol)

The Trust Chain subject is a Leaf Entity, which publishes the following RP metadata in its Entity Configuration:[¶](#section-6.1.5-8)

    "metadata": {
      "openid_relying_party": {
        "redirect_uris": [
          "https://rp.example.org/callback"
        ],
        "response_types": [
          "code"
        ],
        "token_endpoint_auth_method": "self_signed_tls_client_auth",
        "contacts": [
          "rp_admins@rp.example.org"
        ]
      }
    }

[Figure 13](#figure-13): [Example Entity Configuration RP Metadata](#name-example-entity-configuratio)

The `metadata` values specified by the Intermediate Entity for its Immediate Subordinates are applied to the Trust Chain subject `metadata`. After that, the merged metadata policy is applied, to produce the following resulting RP Resolved Metadata:[¶](#section-6.1.5-10)

    {
      "redirect_uris": [
        "https://rp.example.org/callback"
      ],
      "grant_types": [
        "authorization_code"
      ],
      "response_types": [
        "code"
      ],
      "token_endpoint_auth_method": "self_signed_tls_client_auth",
      "subject_type": "pairwise",
      "sector_identifier_uri": "https://org.example.org/sector-ids.json",
      "policy_uri": "https://org.example.org/policy.html",
      "contacts": [
        "rp_admins@rp.example.org",
        "helpdesk@federation.example.org",
        "helpdesk@org.example.org"
      ]
    }

[Figure 14](#figure-14): [The Resulting RP Resolved Metadata for the Trust Chain Subject](#name-the-resulting-rp-resolved-m)

### [6.2.](#section-6.2) [Constraints](#name-constraints)

Trust Anchors and Intermediate Entities MAY define constraining criteria that apply to their Subordinates. They are expressed in the `constraints` Claim of a Subordinate Statement, as described in [Section 3.1.3](#ss-specific).[¶](#section-6.2-1)

The following constraint parameters are defined:[¶](#section-6.2-2)

max_path_length
:   OPTIONAL. Integer specifying the maximum number of Intermediate Entities between the Entity setting the constraint and the Trust Chain subject.[¶](#section-6.2-3.2)
:   

naming_constraints
:   OPTIONAL. JSON object specifying restrictions on the URIs of the Entity Identifiers of Subordinate Entities. Restrictions are defined in terms of permitted and excluded URI name subtrees.[¶](#section-6.2-3.4)
:   

allowed_entity_types
:   OPTIONAL. Array of string Entity Type Identifiers. Entity Type Identifiers are defined in [Section 5.1](#entity_types). This constraint specifies the Entity Types and hence the metadata that Subordinate Entities are allowed to have.[¶](#section-6.2-3.6)
:   

Additional constraint parameters MAY be defined and used. If they are not understood, they MUST be ignored.[¶](#section-6.2-4)

The following is a non-normative example of a set of constraints:[¶](#section-6.2-5)

    {
      "max_path_length": 2,
      "naming_constraints": {
        "permitted": [
          ".example.com"
        ],
        "excluded": [
          "east.example.com"
        ]
      },
      "allowed_entity_types": [
        "openid_provider",
        "openid_relying_party"
      ]
    }

[Figure 15](#figure-15): [Example Set of Constraints](#name-example-set-of-constraints)

When resolving the Trust Chain for an Entity the `constraints` Claim in each Subordinate Statement MUST be independently applied, if present. If any of the `constraints` checks fails, the Trust Chain MUST be considered invalid.[¶](#section-6.2-7)

#### [6.2.1.](#section-6.2.1) [Max Path Length Constraint](#name-max-path-length-constraint)

The `max_path_length` constraint specifies the maximum allowed number of Intermediate Entities in a Trust Chain between a Trust Anchor or Intermediate that sets the constraint and the Trust Chain subject.[¶](#section-6.2.1-1)

A `max_path_length` constraint of zero indicates that no Intermediates MAY appear between this Entity and the Trust Chain subject. The `max_path_length` constraint MUST have a value greater than or equal to zero.[¶](#section-6.2.1-2)

Omitting the `max_path_length` constraint means that there are no additional constraints apart from those already in effect.[¶](#section-6.2.1-3)

Assuming that we have a Trust Chain with four Entity Statements:[¶](#section-6.2.1-4)

1.  Entity Configuration of a Leaf Entity (LE)[¶](#section-6.2.1-5.1.1)

2.  Subordinate Statement by an Intermediate 1 (I1) about LE[¶](#section-6.2.1-5.2.1)

3.  Subordinate Statement by an Intermediate 2 (I2) about I1[¶](#section-6.2.1-5.3.1)

4.  Subordinate Statement by a Trust Anchor (TA) about I2[¶](#section-6.2.1-5.4.1)

Then the Trust Chain fulfills the constraints if, for instance:[¶](#section-6.2.1-6)

-   The TA specifies a `max_path_length` that is greater than or equal to 2.[¶](#section-6.2.1-7.1.1)

-   TA specifies a `max_path_length` of 2, I2 specifies a `max_path_length` of 1, and I1 omits the `max_path_length` constraint.[¶](#section-6.2.1-7.2.1)

-   Neither TA nor I2 specifies any `max_path_length` constraint while I1 sets `max_path_length` to 0.[¶](#section-6.2.1-7.3.1)

The Trust Chain does not fulfill the constraints if, for instance, the:[¶](#section-6.2.1-8)

-   The TA sets the `max_path_length` to 1.[¶](#section-6.2.1-9.1.1)

#### [6.2.2.](#section-6.2.2) [Naming Constraints](#name-naming-constraints)

The `naming_constraints` member specifies a URI namespace within which the Entity Identifiers of Subordinate Entities in a Trust Chain MUST be located.[¶](#section-6.2.2-1)

Restrictions are defined in terms of URI name subtrees, using `permitted` and/or `excluded` members within the `naming_constraints` member, each of which contains an array of names to be permitted or excluded. Any name matching a restriction in the excluded list is invalid, regardless of the information appearing in the permitted list.[¶](#section-6.2.2-2)

This specification uses the syntax of domain name constraints specified in Section 4.2.1.10 of \[[RFC5280](#RFC5280)\]. As stated there, a domain name constraint MUST be specified as a fully qualified domain name and MAY specify a host or a domain. Examples are \"host.example.com\" and \".example.com\". When the domain name constraint begins with a period, it MAY be expanded with one or more labels. That is, the domain name constraint \".example.com\" is satisfied by both host.example.com and my.host.example.com. However, the domain name constraint \".example.com\" is not satisfied by \"example.com\". When the domain name constraint does not begin with a period, it specifies a host. As in RFC 5280, domain name constraints apply to the host part of the URI.[¶](#section-6.2.2-3)

#### [6.2.3.](#section-6.2.3) [Entity Type Constraints](#name-entity-type-constraints)

The `allowed_entity_types` constraint specifies the acceptable metadata Entity Types of Subordinate Entities in a Trust Chain. If there is no `allowed_entity_types` constraint, it means that any Entity Type is allowed. The `federation_entity` Entity Type Identifier, specified in [Section 5.1.1](#federation_entity), is always allowed and MUST NOT be included in the constraint. If the constraint is the empty array `[]`, it means that only the `federation_entity` Entity Type is allowed.[¶](#section-6.2.3-1)

To apply the `allowed_entity_types` constraint during Trust Chain Resolution all Entity Types that are not listed in the `allowed_entity_types` constraint MUST be removed from the metadata Claim in the subject\'s Entity Configuration. The `federation_entity` Entity Type MUST NOT be removed. This MUST be done before applying Metadata Policies but after applying Metadata from a direct superior\'s Subordinate Statement.[¶](#section-6.2.3-2)

## [7.](#section-7) [Trust Marks](#name-trust-marks)

Per the definition in [Section 1.2](#Terminology), Trust Marks are statements of conformance to sets of criteria determined by an accreditation authority. Trust Marks used by this specification are signed JWTs. Entity Statements MAY include Trust Marks, as described in the `trust_marks` Claim definition in [Section 3.1.2](#ec-specific).[¶](#section-7-1)

Trust Marks are signed by a federation-accredited authority called a Trust Mark Issuer. All Trust Mark Issuers MUST be represented in the federation by an Entity. The fact that a Trust Mark Issuer is accepted by the federation is expressed in the `trust_mark_issuers` Claim of the Trust Anchor\'s Entity Configuration.[¶](#section-7-2)

The key used by the Trust Mark issuer to sign its Trust Marks MUST be one of the private keys in its set of Federation Entity Keys.[¶](#section-7-3)

Trust Mark JWTs MUST include the `kid` (Key ID) header parameter with its value being the Key ID of the signing key used.[¶](#section-7-4)

Note that a federation MAY allow an Entity to self-sign Trust Marks.[¶](#section-7-5)

Trust Mark JWTs MUST be explicitly typed by using the `typ` header parameter to prevent cross-JWT confusion, per Section 3.11 of \[[RFC8725](#RFC8725)\]. The `typ` header parameter value MUST be `trust-mark+jwt` unless the trust framework in use defines a more specific media type value for the particular kind of Trust Mark. Trust Marks without a `typ` header parameter or an unrecognized `typ` value MUST be rejected.[¶](#section-7-6)

The following is a non-normative example of a `trust_mark_issuers` Claim:[¶](#section-7-7)

    "trust_mark_issuers":
    {
      "https://openid.net/certification/op": [],
      "https://refeds.org/wp-content/uploads/2016/01/Sirtfi-1.0.pdf":
        ["https://swamid.se"]
    }

[Figure 16](#figure-16): [Example `trust_mark_issuers` Claim](#name-example-trust_mark_issuers-)

### [7.1.](#section-7.1) [Trust Mark Claims](#name-trust-mark-claims)

The Claims in a Trust Mark are:[¶](#section-7.1-1)

iss
:   REQUIRED. String. The Entity Identifier of the issuer of the Trust Mark.[¶](#section-7.1-2.2)
:   

sub
:   REQUIRED. String. The Entity Identifier of the Entity this Trust Mark applies to.[¶](#section-7.1-2.4)
:   

trust_mark_type
:   REQUIRED. The `trust_mark_type` Claim is used in Trust Marks to provide the identifier of the type of the Trust Mark. The Trust Mark type identifier MUST be collision-resistant across multiple federations. It is RECOMMENDED that the identifier value is built using a URL that uniquely identifies the federation or the trust framework within which it was issued. This is required to prevent Trust Marks issued in different federations from having colliding identifiers.[¶](#section-7.1-2.6)
:   

iat
:   REQUIRED. Number. Time when this Trust Mark was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-7.1-2.8)
:   

logo_uri
:   OPTIONAL. String. URL that references a logo for the issued Trust Mark. The value of this field MUST point to a valid image file.[¶](#section-7.1-2.10)
:   

exp
:   OPTIONAL. Number. Time when this Trust Mark is no longer valid. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\]. If not present, it means that the Trust Mark does not expire.[¶](#section-7.1-2.12)
:   

ref
:   OPTIONAL. The `ref` (reference) Claim defined in [Section 13.5](#refClaim) is used in Trust Marks to provide a URL referring to human-readable information about the issuance of the Trust Mark.[¶](#section-7.1-2.14)
:   

delegation
:   OPTIONAL. The `delegation` Claim defined in [Section 13.6](#delegationClaim) is used in Trust Marks to delegate the right to issue Trust Marks with a particular identifier. Its value is a Trust Mark delegation JWT, as defined in [Section 7.2.1](#delegation_jwt).[¶](#section-7.1-2.16)
:   

Additional Claims MAY be defined and used in conjunction with the Claims above.[¶](#section-7.1-3)

### [7.2.](#section-7.2) [Trust Mark Delegation](#name-trust-mark-delegation)

There will be cases where the owner of a Trust Mark for some reason does not match the Trust Mark Issuer due to administrative or technical requirements. Take as an example vehicle inspection. Vehicle inspection is a procedure mandated by national or subnational governments in many countries, in which a vehicle is inspected to ensure that it conforms to regulations governing safety, emissions, or both. The body that mandates the inspections does not perform them; instead, there may be commercial companies performing the inspections, after which they issue the Trust Mark.[¶](#section-7.2-1)

The fact that a Trust Mark is issued by a Trust Mark Issuer that is not the owner of the Trust Mark is expressed by including a `delegation` Claim in the Trust Mark, whose value is a Trust Mark delegation JWT, as defined in [Section 7.2.1](#delegation_jwt).[¶](#section-7.2-2)

If the Federation Operator knows that Trust Marks with a certain Trust Mark type identifier may legitimately be issued by Trust Mark Issuers that are not the owner of the Trust Mark type identifier, then information about the owner and the Trust Mark type identifier MUST be included in the `trust_mark_owners` Claim in the Trust Anchor\'s Entity Configuration.[¶](#section-7.2-3)

The following is a non-normative example of a `trust_mark_owners` Claim:[¶](#section-7.2-4)

    "trust_mark_owners":
    {
      "https://refeds.org/wp-content/uploads/2016/01/Sirtfi-1.0.pdf":
      {
        "sub": "https://refeds.org/sirtfi",
        "jwks" : {
          "keys": [
            {
              "alg": "RS256",
              "e": "AQAB",
              "kid": "Jlob6qNFuSHj3sfuntz9C_9s...",
              "kty": "RSA",
              "n": "_LioMsSeycE4pELlpPYgZluB...",
              "use": "sig"
            }
          ]
        }
      }
    }

[Figure 17](#figure-17): [Example `trust_mark_owners` Claim](#name-example-trust_mark_owners-c)

#### [7.2.1.](#section-7.2.1) [Trust Mark Delegation JWT](#name-trust-mark-delegation-jwt)

A Trust Mark Delegation JWT is a signed JWT issued by a Trust Mark Owner that identifies a legitimate delegated issuer of Trust Marks with a particular identifier.[¶](#section-7.2.1-1)

A Trust Mark delegation JWT MUST be explicitly typed, by setting the `typ` header parameter to `trust-mark-delegation+jwt` to prevent cross-JWT confusion, per Section 3.11 of \[[RFC8725](#RFC8725)\]. Trust Mark delegation JWTs without a `typ` header parameter or with a different `typ` value MUST be rejected. It is signed with a Federation Entity Key.[¶](#section-7.2.1-2)

Trust Mark delegation JWTs MUST include the `kid` (Key ID) header parameter with its value being the Key ID of the signing key used.[¶](#section-7.2.1-3)

The Claims in a Trust Mark delegation JWT are:[¶](#section-7.2.1-4)

iss
:   REQUIRED. String. The owner of the Trust Mark.[¶](#section-7.2.1-5.2)
:   

sub
:   REQUIRED. String. The Entity this delegation applies to.[¶](#section-7.2.1-5.4)
:   

trust_mark_type
:   REQUIRED. String. The identifier for the type of the Trust Mark.[¶](#section-7.2.1-5.6)
:   

iat
:   REQUIRED. Number. Time when this delegation was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-7.2.1-5.8)
:   

exp
:   OPTIONAL. Number. Time when this delegation stops being valid. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\]. If not present, it means that the delegation does not expire.[¶](#section-7.2.1-5.10)
:   

ref
:   OPTIONAL. String. URL that points to human-readable information connected to the Trust Mark.[¶](#section-7.2.1-5.12)
:   

Additional Claims MAY be defined and used in conjunction with the Claims above.[¶](#section-7.2.1-6)

#### [7.2.2.](#section-7.2.2) [Validating a Trust Mark Delegation](#name-validating-a-trust-mark-del)

Validating a Trust Mark Delegation means validating a Trust Mark Delegation instance represented by a specific signed JWT.[¶](#section-7.2.2-1)

Henceforth, \"delegation\" is used as a shorthand for a Trust Mark Delegation JWT. The Trust Anchor referenced below is a Trust Anchor that has been successfully used when establishing trust in the Trust Mark Issuer.[¶](#section-7.2.2-2)

To validate a delegation, the following validation steps MUST be performed. Please note that if any of these validation checks fail, the entire validation process fails and the delegation is considered invalid.[¶](#section-7.2.2-3)

1.  The delegation MUST be a signed JWT[¶](#section-7.2.2-4.1.1)

2.  The delegation MUST have a `typ` header with the value `trust-mark-delegation+jwt`[¶](#section-7.2.2-4.2.1)

3.  The delegation MUST have an `alg` (algorithm) header parameter with a value that is an acceptable JWS signing algorithm; it MUST NOT be `none`[¶](#section-7.2.2-4.3.1)

4.  The Entity Identifier of the Trust Mark Issuer MUST match the value of `sub` in the delegation[¶](#section-7.2.2-4.4.1)

5.  The Entity Identifier of the Trust Mark Owner MUST match the value of `iss` in the delegation.[¶](#section-7.2.2-4.5.1)

6.  The current time MUST be after the time represented by the iat (issued at) Claim in the delegation (possibly allowing for some small leeway to account for clock skew).[¶](#section-7.2.2-4.6.1)

7.  If the `exp` (expiration) Claim is present in the delegation, the current time MUST be before the time it represents (possibly allowing for some small leeway to account for clock skew).[¶](#section-7.2.2-4.7.1)

8.  The value of the Claim `trust_mark_type` in the delegation MUST be the same as the value of the Claim `trust_mark_type` in the Trust Mark.[¶](#section-7.2.2-4.8.1)

9.  The delegation\'s signature MUST validate using one of the Trust Mark Owner\'s keys identified by the value of the header parameter `kid`. The Trust Mark Owner\'s keys can be found in the `trust_mark_owners` Claim in the Trust Anchor\'s Entity Configuration.[¶](#section-7.2.2-4.9.1)

### [7.3.](#section-7.3) [Validating a Trust Mark](#name-validating-a-trust-mark)

Validating a Trust Mark means validating a Trust Mark instance represented by a specific signed JWT. It is NOT about validating whether a Trust Mark of a particular kind can exist or not.[¶](#section-7.3-1)

The trust in the Trust Mark Issuer comes before the trust in the trust mark. If the Trust Mark Issuer is not trusted then the trust mark cannot be trusted. An Entity MUST therefore establish trust in the Trust Mark Issuer by following the procedure defined in [Section 10](#resolving_trust) prior to starting the Trust Mark validation process defined below.[¶](#section-7.3-2)

Henceforth, \"instance\" is used as a shorthand for Trust Mark instance. The Trust Anchor referenced below is a Trust Anchor that has been successfully used when establishing trust in the Trust Mark Issuer.[¶](#section-7.3-3)

To validate an instance, the following validation steps MUST be performed. Please note that if any of these validation checks fail, the entire validation process fails and the instance is considered invalid.[¶](#section-7.3-4)

1.  The instance MUST be a signed JWT[¶](#section-7.3-5.1.1)

2.  The instance MUST have a `typ` header with the value `trust-mark+jwt`[¶](#section-7.3-5.2.1)

3.  The instance MUST have an `alg` (algorithm) header parameter with a value that is an acceptable JWS signing algorithm; it MUST NOT be `none`.[¶](#section-7.3-5.3.1)

4.  The Entity Identifier of the Entity whose Entity Configuration contains the instance MUST match the value of the Claim `sub` in the Trust Mark.[¶](#section-7.3-5.4.1)

5.  The current time MUST be after the time represented by the `iat` (issued at) Claim (possibly allowing for some small leeway to account for clock skew).[¶](#section-7.3-5.5.1)

6.  The current time MUST be before the time represented by the `exp` (expiration) Claim (possibly allowing for some small leeway to account for clock skew).[¶](#section-7.3-5.6.1)

7.  The instance\'s signature MUST validate using the Trust Mark issuer\'s key identified by the `kid` value.[¶](#section-7.3-5.7.1)

8.  If the `trust_mark_type` of the instance appears in the `trust_mark_owners` Claim in the Trust Anchor\'s Entity Configuration, then the instance MUST contain a `delegation` Claim.[¶](#section-7.3-5.8.1)

9.  If there is a `delegation` Claim in the instance, the value of that Claim MUST be validated according to [Section 7.2.2](#trust-mark-delegation-validation).[¶](#section-7.3-5.9.1)

If Trust Marks are issued without an expiration time, it is RECOMMENDED that a mechanism be provided to validate them, such as the Trust Mark Status endpoint and/or the Trust Marked Entities Listing endpoint.[¶](#section-7.3-6)

As an alternative to the above procedure for validating Trust Marks, implementations MAY use the Trust Mark Status endpoint to verify that the Trust Mark is valid and still active, as described in [Section 8.4](#status_endpoint).[¶](#section-7.3-7)

### [7.4.](#section-7.4) [Trust Mark Examples](#name-trust-mark-examples)

A non-normative example of a `trust_marks` Claim in the JWT Claims Set for an Entity Configuration is:[¶](#section-7.4-1)

    {
      "iss": "https://rp.example.it/spid",
      "sub": "https://rp.example.it/spid",
      "iat": 1516239022,
      "exp": 1516843822,
      "trust_marks": [
        {
         "trust_mark_type": "https://www.spid.gov.it/certification/rp",
         "trust_mark":
           "eyJ0eXAiOiJ0cnVzdC1tYXJrK2p3dCIsImFsZyI6IlJTMjU2Iiwia2lkIjoia29
            zR20yd3VaaDlER21OeEF0a3VPNDBwUGpwTDMtakNmMU4tcVBPLVllVSJ9.
            eyJpc3MiOiJodHRwczovL3d3dy5hZ2lkLmdvdi5pdCIsInN1YiI6Imh0dHBzOi8
            vcnAuZXhhbXBsZS5pdC9zcGlkIiwiaWF0IjoxNTc5NjIxMTYwLCJ0cnVzdF9tYX
            JrX3R5cGUiOiJodHRwczovL3d3dy5zcGlkLmdvdi5pdC9jZXJ0aWZpY2F0aW9uL
            3JwIiwibG9nb191cmkiOiJodHRwczovL3d3dy5hZ2lkLmdvdi5pdC90aGVtZXMv
            Y3VzdG9tL2FnaWQvbG9nby5zdmciLCJyZWYiOiJodHRwczovL2RvY3MuaXRhbGl
            hLml0L2RvY3Mvc3BpZC1jaWUtb2lkYy1kb2NzL2l0L3ZlcnNpb25lLWNvcnJlbn
            RlLyJ9.
            L_pSh1InEiFAcs3E-1HBM7fNZYwF5ru3UGA_8yc80dGS3sszfA_sbj4AoW_zAJW
            QBdZpjxnHBBmybYXFrfZBcqxcedsrvUYrmbt1nPYxbUE54fRRoZK-sJmVqh1GzS
            an5nOmkxuAtMinU8k_-aWnPWj83sYe2AzT2mMgkXiz8zhda3jZm8hoxZ4jR6B0Y
            AvbMlq2pPWO5OWKdZhiFRMSprwh0GYluQkK0j1aLNMGXD3keMJd2zEoWX9D7w2f
            XShAA48W3cNhuXyBVnCoum1K4IWK3s_fx4nIkp6W-V4jCBOpxp7Yo8LZ30o_xpE
            OzGTIECGWVR86azOAlwVC8XSiAA"
        }
      ],
      "metadata": {
        "openid_relying_party": {
          "application_type": "web",
          "client_registration_types": ["automatic"],
          "client_name": "https://rp.example.it/spid",
          "contacts": [
            "ops@rp.example.it"
          ]
        }
      }
    }

[Figure 18](#figure-18): [Trust Mark in an Entity Configuration JWT Claims Set](#name-trust-mark-in-an-entity-con)

An example of a decoded Trust Mark payload issued to an RP, attesting to conformance to a national public service profile:[¶](#section-7.4-3)

    {
      "trust_mark_type": "https://mushrooms.federation.example.com/openid_relying_party/public/",
      "iss": "https://epigeo.tm-issuer.example.it",
      "sub": "https://porcino.example.com/rp",
      "iat": 1579621160,
      "organization_name": "Porcino Mushrooms & Co.",
      "policy_uri": "https://porcino.example.com/privacy_policy",
      "tos_uri": "https://porcino.example.com/info_policy",
      "service_documentation": "https://porcino.example.com/api/v1/get/services",
      "ref": "https://porcino.example.com/documentation/manuale_operativo.pdf"
    }

[Figure 19](#figure-19): [Trust Mark for a National Profile](#name-trust-mark-for-a-national-p)

An example of a decoded Trust Mark payload issued to an RP, attesting to its conformance to the rules for data management of underage users:[¶](#section-7.4-5)

    {
      "trust_mark_type": "https://mushrooms.federation.example.com/openid_relying_party/private/under-age",
      "iss": "https://trustissuer.pinarolo.example.it",
      "sub": "https://vavuso.example.com/rp",
      "iat": 1579621160,
      "organization_name": "Pinarolo Suillus luteus",
      "policy_uri": "https://vavuso.example.com/policy",
      "tos_uri": "https://vavuso.example.com/tos"
    }

[Figure 20](#figure-20): [Trust Mark Issued to an RP](#name-trust-mark-issued-to-an-rp)

An example of a decoded Trust Mark payload attesting a stipulation of an agreement between two organization\'s Entities:[¶](#section-7.4-7)

    {
      "trust_mark_type": "https://mushrooms.federation.example.com/arrosto/agreements",
      "iss": "https://agaricaceae.example.it",
      "sub": "https://coppolino.example.com",
      "iat": 1579621160,
      "logo_uri": "https://coppolino.example.com/sgd-cmyk-150dpi-90mm.svg",
      "organization_type": "public",
      "id_code": "123456",
      "email": "info@coppolino.example.com",
      "organization_name#it": "Mazza di Tamburo",
      "policy_uri#it": "https://coppolino.example.com/privacy_policy",
      "tos_uri#it": "https://coppolino.example.com/info_policy",
      "service_documentation": "https://coppolino.example.com/api/v1/get/services",
      "ref": "https://agaricaceae.example.it/documentation/agaricaceae.pdf"
    }

[Figure 21](#figure-21): [Trust Mark Attesting to an Agreement Between Entities](#name-trust-mark-attesting-to-an-)

An example of a decoded Trust Mark payload asserting conformance to a security profile:[¶](#section-7.4-9)

    {
      "trust_mark_type": "https://mushrooms.federation.example.com/ottimo/commestibile",
      "iss": "https://cantharellus.cibarius.example.org",
      "sub": "https://gallinaccio.example.com/op",
      "iat": 1579621160,
      "logo_uri": "https://cantharellus.cibarius/static/images/cantharellus-cibarius.svg",
      "ref": "https://cantharellus.cibarius/cantharellus/cibarius"
    }

[Figure 22](#figure-22): [Trust Mark Asserting Conformance to a Security Profile](#name-trust-mark-asserting-confor)

An example of a decoded self-signed Trust Mark:[¶](#section-7.4-11)

    {
      "trust_mark_type": "https://mushrooms.federation.example.com/trust-marks/self-signed",
      "iss": "https://amanita.muscaria.example.com",
      "sub": "https://amanita.muscaria.example.com",
      "iat": 1579621160,
      "logo_uri": "https://amanita.muscaria.example.com/img/amanita-mus.svg",
      "ref": "https://amanita.muscaria.example.com/uploads/cookbook.zip"
    }

[Figure 23](#figure-23): [Self-Signed Trust Mark](#name-self-signed-trust-mark)

An example of a third-party accreditation authority for Trust Marks:[¶](#section-7.4-13)

    {
      "iss": "https://swamid.se",
      "sub": "https://umu.se/op",
      "iat": 1577833200,
      "exp": 1609369200,
      "trust_mark_type": "https://refeds.org/wp-content/uploads/2016/01/Sirtfi-1.0.pdf"
    }

[Figure 24](#figure-24): [Third-Party Accreditation Authority for Trust Marks](#name-third-party-accreditation-a)

### [7.5.](#section-7.5) [Trust Mark Delegation Example](#name-trust-mark-delegation-examp)

A non-normative example of a `trust_marks` Claim in the JWT Claims Set for an Entity Configuration in which the Trust Mark is issued by an Entity that issues Trust Marks on behalf of another Entity. The fact that a Trust Mark is issued by a Trust Mark Issuer that is not the owner of the Trust Mark is expressed by including a `delegation` Claim in the Trust Mark, whose value is a signed JWT.[¶](#section-7.5-1)

    {
      "delegation":
        "eyJ0eXAiOiJ0cnVzdC1tYXJrLWRlbGVnYXRpb24rand0IiwiYWxnIjoiUl
        MyNTYiLCJraWQiOiJrb3NHbTJ3dVpoOURHbU54QXRrdU80MHBQanBMMy1qQ
        2YxTi1xUE8tWWVVIn0.
        eyJzdWIiOiJodHRwczovL3RtaS5leGFtcGxlLm9yZyIsInRydXN0X21hcmt
        fdHlwZSI6Imh0dHBzOi8vcmVmZWRzLm9yZy9zaXJ0ZmkiLCJpc3MiOiJodH
        RwczovL3RtX293bmVyLmV4YW1wbGUub3JnIiwiaWF0IjoxNzI1MTc2MzAyfQ.
        ao0rWGpVjEgpNyFxsKawps8q71eYnp78TzRdY4P52
        CT8QX6etXt-2L2Z1Vw5A6jx2mhjpPwWi_sOxfiOSA5TugJfN0Gbwj7teTzM
        0IMciuasCWgnLrKyLZjS147ZE50I9e9P8Ot8UQwhmXcLiuwsbDxSdqM4pVp
        75lfWnmzPH0L2pDZG5COFgIgSOAlK3TVMBOR8fziF-VmWNPzAfB0lSc-hjH
        -7q66GyT43o3Exnm6DsoLxyB8bxG99BQltLxURDT90CzM6szGcF3OG64Rbe
        0I4lT_LAOfvhlrRbT56eK4sJNCsbVbGnDBfFmyfB_HIeBMGP0L7T5JPMOUU
        9bjIlA",
      "iat": 1725176302,
      "trust_mark_type": "https://refeds.org/sirtfi",
      "sub": "https://entity.example.org",
      "exp": 1727768302,
      "iss": "https://tmi.example.org"
    }

[Figure 25](#figure-25): [Example JWT Claims Set of a Trust Mark using Delegation](#name-example-jwt-claims-set-of-a)

JWS Header Parameters for the Trust Mark delegation JWT in the \"delegation\" Claim above:[¶](#section-7.5-3)

    {
      "typ": "trust-mark-delegation+jwt",
      "alg": "RS256",
      "kid": "kosGm2wuZh9DGmNxAtkuO40pPjpL3-jCf1N-qPO-YeU"
    }

[Figure 26](#figure-26): [Trust Mark Delegation JWT JWS Header Parameters](#name-trust-mark-delegation-jwt-j)

JWT Claims Set of the Trust Mark delegation JWT in the \"delegation\" Claim above:[¶](#section-7.5-5)

    {
      "sub": "https://tmi.example.org",
      "trust_mark_type": "https://refeds.org/sirtfi",
      "iss": "https://tm_owner.example.org",
      "iat": 1725176302
    }

[Figure 27](#figure-27): [Trust Mark Delegation JWT Claim Set](#name-trust-mark-delegation-jwt-c)

## [8.](#section-8) [Federation Endpoints](#name-federation-endpoints)

The federation endpoints of an Entity can be found in the configuration response as described in [Section 9](#federation_configuration) or by other means.[¶](#section-8-1)

For all federation endpoints, additional request parameters beyond those initially specified MAY be defined and used. If they are not understood, they MUST be ignored.[¶](#section-8-2)

### [8.1.](#section-8.1) [Fetching a Subordinate Statement](#name-fetching-a-subordinate-stat)

The fetch endpoint is used to collect Subordinate Statements one-by-one when assembling Trust Chains. An Entity with Subordinates MUST expose a fetch endpoint. An Entity MUST publish Subordinate Statements about its Immediate Subordinates via its fetch endpoint.[¶](#section-8.1-1)

The fetch endpoint location is published in the Entity\'s `federation_entity` metadata in the `federation_fetch_endpoint` parameter defined in [Section 5.1.1](#federation_entity). Since this endpoint is used when building and validating Trust Chains, its location MUST be available before metadata and metadata policies from Superiors can be applied. Therefore, this endpoint MUST be published directly in Entity Configuration metadata and not in Subordinate Statements.[¶](#section-8.1-2)

To fetch a Subordinate Statement, one needs to know the identifier of the Entity to ask (the issuer), the fetch endpoint of that Entity, and the Entity Identifier of the subject of the Subordinate Statement. The issuer is normally the Immediate Superior of the subject of the Subordinate Statement.[¶](#section-8.1-3)

#### [8.1.1.](#section-8.1.1) [Fetch Subordinate Statement Request](#name-fetch-subordinate-statement)

When client authentication is not used, the request MUST be an HTTP request using the GET method to a fetch endpoint with the following query parameter, encoded in `application/x-www-form-urlencoded` format. The request is made to the fetch endpoint of the specified issuer.[¶](#section-8.1.1-1)

sub
:   REQUIRED. The Entity Identifier of the subject for which the Subordinate Statement is being requested.[¶](#section-8.1.1-2.2)
:   

When client authentication is used, the request MUST be an HTTP request using the POST method, with the parameters passed in the POST body.[¶](#section-8.1.1-3)

The following is a non-normative example of an HTTP GET request for a Subordinate Statement from edugain.org about https://sunet.se:[¶](#section-8.1.1-4)

    GET /federation_fetch_endpoint?
    sub=https%3A%2F%2Fsunet%2Ese HTTP/1.1
    Host: edugain.org

[Figure 28](#figure-28): [API Request for a Subordinate Statement](#name-api-request-for-a-subordina)

#### [8.1.2.](#section-8.1.2) [Fetch Subordinate Statement Response](#name-fetch-subordinate-statement-)

A successful response MUST use the HTTP status code 200 with the content type `application/entity-statement+jwt`, to make it clear that the response contains an Entity Statement. If it is an error response, it will be a JSON object and the content type MUST be `application/json`. If the fetch endpoint cannot provide data for the requested `sub` parameter, returning the `not_found` error code is RECOMMENDED. If the `sub` parameter references the Entity Identifier of the Issuing Entity, returning the `invalid_request` error code is RECOMMENDED. See more about error responses in [Section 8.9](#error_response).[¶](#section-8.1.2-1)

The following is a non-normative example of the JWT Claims Set for a fetch response:[¶](#section-8.1.2-2)

    {
      "iss": "https://edugain.org",
      "sub": "https://sunet.se",
      "exp": 1568397247,
      "iat": 1568310847,
      "source_endpoint": "https://edugain.org/federation_fetch_endpoint",
      "jwks": {
        "keys": [
          {
            "e": "AQAB",
            "kid": "dEEtRjlzY3djcENuT01wOGxrZlkxb3RIQVJlMTY0...",
            "kty": "RSA",
            "n": "x97YKqc9Cs-DNtFrQ7_vhXoH9bwkDWW6En2jJ044yH..."
          }
        ]
      },
      "metadata":{
        "federation_entity": {
            "organization_name": "SUNET"
        }
      }
      "metadata_policy": {
        "openid_provider": {
          "subject_types_supported": {
            "value": [
              "pairwise"
            ]
          },
          "token_endpoint_auth_methods_supported": {
            "default": [
              "private_key_jwt"
            ],
            "subset_of": [
              "private_key_jwt",
              "client_secret_jwt"
            ],
            "superset_of": [
              "private_key_jwt"
            ]
          }
        }
      }
    }

[Figure 29](#figure-29): [Fetch Response JWT Claims Set](#name-fetch-response-jwt-claims-s)

### [8.2.](#section-8.2) [Subordinate Listing](#name-subordinate-listing)

The listing endpoint is exposed by Federation Entities acting as a Trust Anchor, Intermediate, or Trust Mark Issuer. The endpoint lists the Immediate Subordinates about which the Trust Anchor, Intermediate, or Trust Mark Issuer issues Entity Statements.[¶](#section-8.2-1)

As a Trust Mark Issuer, the endpoint MAY list the Immediate Subordinates for which Trust Marks have been issued and are still valid, if the issuer exposing this endpoint supports Trust Mark filtering, as defined below.[¶](#section-8.2-2)

In both cases, the list contained in the result MAY be a very large list.[¶](#section-8.2-3)

The list endpoint location is published in the Entity\'s `federation_entity` metadata in the `federation_list_endpoint` parameter defined in [Section 5.1.1](#federation_entity). This endpoint MUST be published directly in Entity Configuration metadata and not in Subordinate Statements.[¶](#section-8.2-4)

The following example shows a tree of Entities belonging to the same federation including the Trust Anchor, Intermediate Entities, and Leaf Entities, discovered and collected through the Subordinate listing endpoints:[¶](#section-8.2-5)

                           +----------------------+
                           |    Trust Anchor      |
                           +----------------------+
           +---------------+ Subordinate Listing  +--------------+
           |               +----------+-----------+              |
           |                          |                          |
           |                          |                          |
           |                          |                          |
           |                          |                          |
           |                          |                          |
    +------v-------+       +----------v-----------+       +------v-------+
    |     Leaf     |       |     Intermediate     |       |     Leaf     |
    +--------------+       +----------------------+       +--------------+
                      +----+ Subordinate Listing  |
                      |    +------------+---------+
                      |                 |
                      |                 |
                      |                 |
           +----------v-----------+     |
           |     Intermediate     |     |
           +----------------------+     |
           | Subordinate Listing  |     |
           +-+---------+----------+     |
             |         |                |
             |         |                |
     +-------v--+     +v--------+    +--v------+
     |  Leaf    |     |  Leaf   |    |  Leaf   |
     +----------+     +---------+    +---------+

[Figure 30](#figure-30): [Tree of Entities in a Federation Collected through Subordinate Listing Endpoints](#name-tree-of-entities-in-a-feder)

#### [8.2.1.](#section-8.2.1) [Subordinate Listing Request](#name-subordinate-listing-request)

When client authentication is not used, the request MUST be an HTTP request using the GET method to a list endpoint with the following query parameters, encoded in `application/x-www-form-urlencoded` format.[¶](#section-8.2.1-1)

entity_type
:   OPTIONAL. The value of this parameter is an Entity Type Identifier. If the responder knows the Entity Types of its Immediate Subordinates, the result MUST be filtered to include only those that include the specified Entity Type. When multiple `entity_type` parameters are present, for example `entity_type=openid_provider&entity_type=openid_relying_party`, the result MUST be filtered to include all specified Entity Types. If the responder does not support this feature, it MUST use the HTTP status code 400 and the content type `application/json`, with the error code `unsupported_parameter`.[¶](#section-8.2.1-2.2)
:   

trust_marked
:   OPTIONAL. Boolean. If the parameter `trust_marked` is present and set to `true`, the result contains only the Immediate Subordinates for which at least one Trust Mark has been issued and is still valid. If the responder does not support this feature, it MUST use the HTTP status code 400 and set the content type to `application/json`, with the error code `unsupported_parameter`.[¶](#section-8.2.1-2.4)
:   

trust_mark_type
:   OPTIONAL. The value of this parameter is the identifier for the type of the Trust Mark. If the responder has issued Trust Marks with the specified Trust Mark type identifier, the list in the response is filtered to include only the Immediate Subordinates for which that Trust Mark type identifier has been issued and is still valid. If the responder does not support this feature, it MUST use the HTTP status code 400 and set the content type to `application/json`, with the error code `unsupported_parameter`.[¶](#section-8.2.1-2.6)
:   

intermediate
:   OPTIONAL. Boolean. If the parameter `intermediate` is present and set to `true`, then if the responder knows whether its Immediate Subordinates are Intermediates or not, the result MUST be filtered accordingly. If the responder does not support this feature, it MUST use the HTTP status code 400 and the content type `application/json`, with the error code `unsupported_parameter`.[¶](#section-8.2.1-2.8)
:   

When client authentication is used, the request MUST be an HTTP request using the POST method, with the parameters passed in the POST body.[¶](#section-8.2.1-3)

The following is a non-normative example of an HTTP GET request for a list of Immediate Subordinates:[¶](#section-8.2.1-4)

    GET /list HTTP/1.1
    Host: sunet.se

[Figure 31](#figure-31): [Subordinate Listing Request](#name-subordinate-listing-request-2)

#### [8.2.2.](#section-8.2.2) [Subordinate Listing Response](#name-subordinate-listing-respons)

A successful response MUST use the HTTP status code 200 with the content type `application/json`, containing a JSON array with the known Entity Identifiers.[¶](#section-8.2.2-1)

An error response is as defined in [Section 8.9](#error_response).[¶](#section-8.2.2-2)

The following is a non-normative example of a response containing the Immediate Subordinate Entities:[¶](#section-8.2.2-3)

    200 OK
    Content-Type: application/json

    [
      "https://ntnu.andreas.labs.uninett.no",
      "https://blackboard.ntnu.no/openid/callback",
      "https://serviceprovider.andreas.labs.uninett.no/application17"
    ]

[Figure 32](#figure-32): [Subordinate Listing Response](#name-subordinate-listing-response)

### [8.3.](#section-8.3) [Resolve Entity](#name-resolve-entity)

An Entity MAY use a resolve endpoint to fetch Resolved Metadata, the Trust Chain used, and Trust Marks for an Entity. The resolver fetches the subject\'s Entity Configuration, assembles a Trust Chain that starts with the subject\'s Entity Configuration and ends with the specified Trust Anchor\'s Entity Configuration, verifies the Trust Chain, and then applies all the policies present in the Trust Chain to the subject\'s metadata.[¶](#section-8.3-1)

The resolver MUST verify that all present Trust Marks with identifiers recognized within the Federation are active. The response set MUST include only verified Trust Marks.[¶](#section-8.3-2)

The resolve endpoint location is published in the Entity\'s `federation_entity` metadata in the `federation_resolve_endpoint` parameter defined in [Section 5.1.1](#federation_entity).[¶](#section-8.3-3)

#### [8.3.1.](#section-8.3.1) [Resolve Request](#name-resolve-request)

When client authentication is not used, the request MUST be an HTTP request using the GET method to a resolve endpoint with the following query parameters, encoded in `application/x-www-form-urlencoded` format.[¶](#section-8.3.1-1)

sub
:   REQUIRED. The Entity Identifier of the Entity whose resolved data is requested.[¶](#section-8.3.1-2.2)
:   

trust_anchor

:   REQUIRED. The Trust Anchor that the resolve endpoint MUST use when resolving the metadata. The value is an Entity Identifier.[¶](#section-8.3.1-2.4.1)

    The `trust_anchor` request parameter MAY occur multiple times, in which case, the resolver MAY return a successful resolve response using any of the Trust Anchor values provided.[¶](#section-8.3.1-2.4.2)

:   

entity_type

:   OPTIONAL. A specific Entity Type to resolve. Its value is an Entity Type Identifier, as specified in [Section 5.1](#entity_types). If this parameter is not present, then all Entity Types are returned.[¶](#section-8.3.1-2.6.1)

    The `entity_type` request parameter MAY occur multiple times, in which case, data for each Entity Type whose Entity Type Identifier is in an `entity_type` parameter value is returned.[¶](#section-8.3.1-2.6.2)

:   

When client authentication is used, the request MUST be an HTTP request using the POST method, with the parameters passed in the POST body.[¶](#section-8.3.1-3)

The following is a non-normative example of a Resolve Request:[¶](#section-8.3.1-4)

    GET /resolve?
    sub=https%3A%2F%2Fop.example.it%2Fspid&
    entity_type=openid_provider&
    trust_anchor=https%3A%2F%2Fswamid.se HTTP/1.1
    Host: sunet.se

[Figure 33](#figure-33): [Resolve Request](#name-resolve-request-2)

#### [8.3.2.](#section-8.3.2) [Resolve Response](#name-resolve-response)

A successful response MUST use the HTTP status code 200 with the content type `application/resolve-response+jwt`, containing Resolved Metadata and verified Trust Marks.[¶](#section-8.3.2-1)

The response is a signed JWT that is explicitly typed by setting the `typ` header parameter to `resolve-response+jwt` to prevent cross-JWT confusion, per Section 3.11 of \[[RFC8725](#RFC8725)\]. Resolve responses without a `typ` header parameter or with a different `typ` value MUST be rejected. It is signed with a Federation Entity Key.[¶](#section-8.3.2-2)

The resolve response JWT MUST include the `kid` (Key ID) header parameter with its value being the Key ID of the signing key used.[¶](#section-8.3.2-3)

The resolve response JWT MUST return the Trust Chain from the subject to the Trust Anchor in its `trust_chain` parameter.[¶](#section-8.3.2-4)

The resolve response MAY also return the Trust Chain from its issuer to the Trust Anchor in the `trust_chain` JWS header parameter, as specified in [Section 4.3](#trust_chain_head_param). When this is present, the Trust Anchor in the Trust Chain MUST match the Trust Anchor requested in the related request in the `trust_anchor` parameter.[¶](#section-8.3.2-5)

An issuer that provides its Trust Chain within the resolve response makes it evident that it is part of the same federation as the subject of the response. Thus, when the Trust Chains of both the issuer and the subject are available and the Federation Historical Keys endpoint is provided by the Trust Anchor, the resolve response becomes a long-lived attestation; it can be always verified, even when the Federation Keys change in the future.[¶](#section-8.3.2-6)

The response SHOULD contain the `aud` Claim only if the requesting party is authenticated as described in [Section 8.8](#ClientAuthentication), in which case, the value MUST be the Entity Identifier of the requesting party and MUST NOT include any other values.[¶](#section-8.3.2-7)

The Claims in a resolve response are:[¶](#section-8.3.2-8)

iss
:   REQUIRED. String. Entity Identifier of the issuer of the resolve response.[¶](#section-8.3.2-9.2)
:   

sub
:   REQUIRED. String. Entity Identifier of the subject of the resolve response.[¶](#section-8.3.2-9.4)
:   

iat
:   REQUIRED. Number. Time when this resolution was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-8.3.2-9.6)
:   

exp
:   REQUIRED. Number. Time when this resolution is no longer valid. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\]. It MUST be the minimum of the `exp` value of the Trust Chain from which the resolve response was derived, as well as any Trust Mark included in the response.[¶](#section-8.3.2-9.8)
:   

metadata
:   REQUIRED. JSON object containing the subject\'s Resolved Metadata, according to the requested `type` and expressed in the `metadata` format defined in [Section 3.1.1](#common-claims).[¶](#section-8.3.2-9.10)
:   

trust_chain
:   REQUIRED. Array containing the sequence of Entity Statements that compose the Trust Chain, starting with the subject and ending with the selected Trust Anchor.[¶](#section-8.3.2-9.12)
:   

trust_marks
:   OPTIONAL. Array of objects, each representing a Trust Mark, as defined in [Section 3.1.2](#ec-specific). Only valid Trust Marks that have been issued by Trust Mark issuers trusted by the Trust Anchor to issue such Trust Marks MAY appear in the resolver response.[¶](#section-8.3.2-9.14)
:   

Additional Claims MAY be defined and used in conjunction with the Claims above.[¶](#section-8.3.2-10)

An error response is as defined in [Section 8.9](#error_response).[¶](#section-8.3.2-11)

The following is a non-normative example of the JWT Claims Set for a resolve response:[¶](#section-8.3.2-12)

    {
      "iss": "https://resolver.spid.gov.it",
      "sub": "https://op.example.it/spid",
      "iat": 1516239022,
      "exp": 1516843822,
      "metadata": {
        "openid_provider": {
          "contacts": ["legal@example.it", "technical@example.it"],
          "logo_uri":
            "https://op.example.it/static/img/op-logo.svg",
          "op_policy_uri":
            "https://op.example.it/en/about-the-website/legal-information",
          "federation_registration_endpoint": "https://op.example.it/spid/fedreg",
          "authorization_endpoint":
            "https://op.example.it/spid/authorization",
          "token_endpoint": "https://op.example.it/spid/token",
          "response_types_supported": [
            "code",
            "code id_token",
            "token"
          ],
          "grant_types_supported": [
            "authorization_code",
            "urn:ietf:params:oauth:grant-type:jwt-bearer"
          ],
          "subject_types_supported": ["pairwise"],
          "id_token_signing_alg_values_supported": ["RS256"],
          "issuer": "https://op.example.it/spid",
          "jwks": {
            "keys": [
              {
                "kty": "RSA",
                "use": "sig",
                "n": "1Ta-sE ...",
                "e": "AQAB",
                "kid": "FANFS3YnC9tjiCaivhWLVUJ3AxwGGz_98uRFaqMEEs"
              }
            ]
          }
        }
      },
      "trust_marks": [
        {"trust_mark_type": "https://www.spid.gov.it/certification/op/",
         "trust_mark":
           "eyJ0eXAiOiJ0cnVzdC1tYXJrK2p3dCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiOH
            hzdUtXaVZmd1NnSG9mMVRlNE9VZGN5NHE3ZEpyS2ZGUmxPNXhoSElhMCJ9.
            eyJpc3MiOiJodHRwczovL3d3dy5hZ2lkLmdvdi5pdCIsInN1YiI6Imh0dHBzOi
            8vb3AuZXhhbXBsZS5pdC9zcGlkIiwiaWF0IjoxNTc5NjIxMTYwLCJ0cnVzdF9t
            YXJrX3R5cGUiOiJodHRwczovL3d3dy5zcGlkLmdvdi5pdC9jZXJ0aWZpY2F0aW
            9uL29wLyIsImxvZ29fdXJpIjoiaHR0cHM6Ly93d3cuYWdpZC5nb3YuaXQvdGhl
            bWVzL2N1c3RvbS9hZ2lkL2xvZ28uc3ZnIiwicmVmIjoiaHR0cHM6Ly9kb2NzLm
            l0YWxpYS5pdC9pdGFsaWEvc3BpZC9zcGlkLXJlZ29sZS10ZWNuaWNoZS1vaWRj
            L2l0L3N0YWJpbGUvaW5kZXguaHRtbCJ9.
            xyz-PDQ_..."
        }
      ],
      "trust_chain" : [
        "eyJhbGciOiJSUzI1NiIsImtpZCI6Ims1NEhRdERpYnlHY3M5WldWTWZ2aUhm ...",
        "eyJhbGciOiJSUzI1NiIsImtpZCI6IkJYdmZybG5oQU11SFIwN2FqVW1BY0JS ...",
        "eyJhbGciOiJSUzI1NiIsImtpZCI6IkJYdmZybG5oQU11SFIwN2FqVW1BY0JS ..."
      ]
    }

[Figure 34](#figure-34): [Resolve Response JWT Claims Set](#name-resolve-response-jwt-claims)

#### [8.3.3.](#section-8.3.3) [Trust Considerations](#name-trust-considerations)

The basic assumption of this specification is that an Entity should have direct trust in no party except the Trust Anchor and its own capabilities. However, Entities MAY establish a kind of transitive trust in other Entities. For example, the Trust Anchor states who its Immediate Subordinates are, and Entities MAY choose to trust them. If a party uses the resolve service of another Entity to obtain federation data, it is trusting the resolver to perform validation of the cryptographically protected metadata correctly and to provide it with authentic results.[¶](#section-8.3.3-1)

### [8.4.](#section-8.4) [Trust Mark Status](#name-trust-mark-status)

This enables determining whether a Trust Mark Instance that has been issued to an Entity is still active. The query MUST be sent to the Trust Mark Issuer.[¶](#section-8.4-1)

The Trust Mark Status endpoint location is published in the Entity\'s `federation_entity` metadata in the `federation_trust_mark_status_endpoint` parameter defined in [Section 5.1.1](#federation_entity). This endpoint MUST be published directly in Entity Configuration metadata and not in Subordinate Statements.[¶](#section-8.4-2)

#### [8.4.1.](#section-8.4.1) [Trust Mark Status Request](#name-trust-mark-status-request)

The request MUST be an HTTP request using the POST method to a Trust Mark Status endpoint with the following parameter, encoded in `application/x-www-form-urlencoded` format.[¶](#section-8.4.1-1)

trust_mark
:   REQUIRED. The Trust Mark to be validated.[¶](#section-8.4.1-2.2)
:   

When client authentication is used, the request MUST be an HTTP request using the POST method, with the parameters passed in the POST body.[¶](#section-8.4.1-3)

The following is a non-normative example of a Trust Mark Status request:[¶](#section-8.4.1-4)

    POST /federation_trust_mark_status_endpoint HTTP/1.1
    Host: op.example.org
    Content-Type: application/x-www-form-urlencoded

    trust_mark=eyJ0eXAiOiJ0cnVzdC1tYXJrK2p3dCIsImFsZyI6 ...

[Figure 35](#figure-35): [Trust Mark Status Request](#name-trust-mark-status-request-2)

#### [8.4.2.](#section-8.4.2) [Trust Mark Status Response](#name-trust-mark-status-response)

A successful response MUST use the HTTP status code 200 with the content type `application/trust-mark-status-response+jwt`, containing a signed JWT that is a Trust Mark Status Response.[¶](#section-8.4.2-1)

The Trust Mark Status Response is a signed JWT that is explicitly typed by setting the `typ` header parameter to `trust-mark-status-response+jwt` to prevent cross-JWT confusion, per Section 3.11 of \[[RFC8725](#RFC8725)\]. Trust Mark Status Responses without a `typ` header parameter or with a different `typ` value MUST be rejected. It is signed with a Federation Entity Key.[¶](#section-8.4.2-2)

The Trust Mark Status Response JWT MUST include the `kid` (Key ID) header parameter with its value being the Key ID of the signing key used.[¶](#section-8.4.2-3)

The JWT Claims Set of the Trust Mark Status JWT is a JSON object containing the following Claims:[¶](#section-8.4.2-4)

iss
:   REQUIRED. String. Entity Identifier of the issuer of the Trust Mark Status JWT.[¶](#section-8.4.2-5.2)
:   

iat
:   REQUIRED. Number. Time when this Trust Mark Status JWT was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-8.4.2-5.4)
:   

trust_mark
:   REQUIRED. String. The Trust Mark JWT that this status response is about.[¶](#section-8.4.2-5.6)
:   

status

:   REQUIRED. Case-sensitive string value indicating the status of the Trust Mark. Values defined by this specification are:[¶](#section-8.4.2-5.8.1)

    active
    :   The Trust Mark is active[¶](#section-8.4.2-5.8.2.2)
    :   

    expired
    :   The Trust Mark has expired[¶](#section-8.4.2-5.8.2.4)
    :   

    revoked
    :   The Trust Mark was revoked[¶](#section-8.4.2-5.8.2.6)
    :   

    invalid
    :   Signature validation failed or another error was detected[¶](#section-8.4.2-5.8.2.8)
    :   

:   

:   Additional status values MAY be defined and used in addition to those above.[¶](#section-8.4.2-5.10)
:   

Additional Trust Mark Status JWT Claims MAY be defined and used in addition to those listed above.[¶](#section-8.4.2-6)

The following is a non-normative example of the JWT Claims Set for a response with the status `active`:[¶](#section-8.4.2-7)

    {
      "iss": "https://www.agid.gov.it",
      "trust_mark": "eyJ0eXAiOiJ0cnVzdC1tYXJrK2p3dCIsImFsZyI6IlJTMjU2Iiwia2
        lkIjoia29zR20yd3VaaDlER21OeEF0a3VPNDBwUGpwTDMtakNmMU4tcVBPLVllVSJ9.
        eyJpc3MiOiJodHRwczovL3d3dy5hZ2lkLmdvdi5pdCIsInN1YiI6Imh0dHBzOi8
        vcnAuZXhhbXBsZS5pdC9zcGlkIiwiaWF0IjoxNTc5NjIxMTYwLCJ0cnVzdF9tYX
        JrX3R5cGUiOiJodHRwczovL3d3dy5zcGlkLmdvdi5pdC9jZXJ0aWZpY2F0aW9uL
        3JwIiwibG9nb191cmkiOiJodHRwczovL3d3dy5hZ2lkLmdvdi5pdC90aGVtZXMv
        Y3VzdG9tL2FnaWQvbG9nby5zdmciLCJyZWYiOiJodHRwczovL2RvY3MuaXRhbGl
        hLml0L2RvY3Mvc3BpZC1jaWUtb2lkYy1kb2NzL2l0L3ZlcnNpb25lLWNvcnJlbn
        RlLyJ9.
        L_pSh1InEiFAcs3E-1HBM7fNZYwF5ru3UGA_8yc80dGS3sszfA_sbj4AoW_zAJW
        QBdZpjxnHBBmybYXFrfZBcqxcedsrvUYrmbt1nPYxbUE54fRRoZK-sJmVqh1GzS
        an5nOmkxuAtMinU8k_-aWnPWj83sYe2AzT2mMgkXiz8zhda3jZm8hoxZ4jR6B0Y
        AvbMlq2pPWO5OWKdZhiFRMSprwh0GYluQkK0j1aLNMGXD3keMJd2zEoWX9D7w2f
        XShAA48W3cNhuXyBVnCoum1K4IWK3s_fx4nIkp6W-V4jCBOpxp7Yo8LZ30o_xpE
        OzGTIECGWVR86azOAlwVC8XSiAA",
      "iat": 1759897995,
      "status": "active"
    }

[Figure 36](#figure-36): [Active Trust Mark Status Response JWT Claims Set](#name-active-trust-mark-status-re)

The following is a non-normative example of the JWT Claims Set for a response with the status `revoked`:[¶](#section-8.4.2-9)

    {
      "iss": "https://www.agid.gov.it",
      "trust_mark": "eyJ0eXAiOiJ0cnVzdC1tYXJrK2p3dCIsImFsZyI6IlJTMjU2Iiwia2
        lkIjoia29zR20yd3VaaDlER21OeEF0a3VPNDBwUGpwTDMtakNmMU4tcVBPLVllVSJ9.
        eyJpc3MiOiJodHRwczovL3d3dy5hZ2lkLmdvdi5pdCIsInN1YiI6Imh0dHBzOi8
        vcnAuZXhhbXBsZS5pdC9zcGlkIiwiaWF0IjoxNTc5NjIxMTYwLCJ0cnVzdF9tYX
        JrX3R5cGUiOiJodHRwczovL3d3dy5zcGlkLmdvdi5pdC9jZXJ0aWZpY2F0aW9uL
        3JwIiwibG9nb191cmkiOiJodHRwczovL3d3dy5hZ2lkLmdvdi5pdC90aGVtZXMv
        Y3VzdG9tL2FnaWQvbG9nby5zdmciLCJyZWYiOiJodHRwczovL2RvY3MuaXRhbGl
        hLml0L2RvY3Mvc3BpZC1jaWUtb2lkYy1kb2NzL2l0L3ZlcnNpb25lLWNvcnJlbn
        RlLyJ9.
        L_pSh1InEiFAcs3E-1HBM7fNZYwF5ru3UGA_8yc80dGS3sszfA_sbj4AoW_zAJW
        QBdZpjxnHBBmybYXFrfZBcqxcedsrvUYrmbt1nPYxbUE54fRRoZK-sJmVqh1GzS
        an5nOmkxuAtMinU8k_-aWnPWj83sYe2AzT2mMgkXiz8zhda3jZm8hoxZ4jR6B0Y
        AvbMlq2pPWO5OWKdZhiFRMSprwh0GYluQkK0j1aLNMGXD3keMJd2zEoWX9D7w2f
        XShAA48W3cNhuXyBVnCoum1K4IWK3s_fx4nIkp6W-V4jCBOpxp7Yo8LZ30o_xpE
        OzGTIECGWVR86azOAlwVC8XSiAA",
      "iat": 1759898057,
      "status": "revoked"
    }

[Figure 37](#figure-37): [Revoked Trust Mark Status Response JWT Claims Set](#name-revoked-trust-mark-status-r)

An error response to a Trust Mark Status request is as defined in [Section 8.9](#error_response).[¶](#section-8.4.2-11)

If the Trust Mark Issuer receives a request about the status of an unknown Trust Mark, something it did not issue or is not aware of, it MUST respond with an HTTP status code 404 (Not found).[¶](#section-8.4.2-12)

### [8.5.](#section-8.5) [Trust Marked Entities Listing](#name-trust-marked-entities-listi)

The Trust Marked Entities Listing endpoint is exposed by Trust Mark Issuers and lists all the Entities for which Trust Marks have been issued and are still valid.[¶](#section-8.5-1)

The Trust Marked Entities Listing endpoint location is published in the Entity\'s `federation_entity` metadata in the `federation_trust_mark_list_endpoint` parameter defined in [Section 5.1.1](#federation_entity).[¶](#section-8.5-2)

#### [8.5.1.](#section-8.5.1) [Trust Marked Entities Listing Request](#name-trust-marked-entities-listin)

When client authentication is not used, the request MUST be an HTTP request using the GET method to a list endpoint with the following query parameters, encoded in `application/x-www-form-urlencoded` format.[¶](#section-8.5.1-1)

trust_mark_type
:   REQUIRED. Identifier for the type of the Trust Mark. If the responder has issued Trust Marks with the specified Trust Mark type identifier, the list in the response is filtered to include only the Entities for which that Trust Mark type identifier has been issued and is still valid.[¶](#section-8.5.1-2.2)
:   

sub
:   OPTIONAL. The Entity Identifier of the Entity to which the Trust Mark was issued. The list obtained in the response MUST be filtered to only the Entity matching this value.[¶](#section-8.5.1-2.4)
:   

When client authentication is used, the request MUST be an HTTP request using the POST method, with the parameters passed in the POST body.[¶](#section-8.5.1-3)

The following is a non-normative example of an HTTP GET request for a list of Trust Marked Entities:[¶](#section-8.5.1-4)

    GET /trust_marked_list?trust_mark_type=https%3A%2F%2Ffederation.example.org%2Fopenid_relying_party%2Fprivate%2Funder-age HTTP/1.1
    Host: trust-mark-issuer.example.org

[Figure 38](#figure-38): [Trust Marked Entities Listing Request](#name-trust-marked-entities-listing)

#### [8.5.2.](#section-8.5.2) [Trust Marked Entities Listing Response](#name-trust-marked-entities-listing-)

A successful response MUST use the HTTP status code 200 with the content type `application/json`, containing a JSON array with Entity Identifiers.[¶](#section-8.5.2-1)

An error response is as defined in [Section 8.9](#error_response).[¶](#section-8.5.2-2)

The following is a non-normative example of a response, containing the Trust Marked Entities:[¶](#section-8.5.2-3)

    200 OK
    Content-Type: application/json

    [
      "https://blackboard.ntnu.no/openid/rp",
      "https://that-rp.example.org"
    ]

[Figure 39](#figure-39): [Trust Marked Entities Listing Response](#name-trust-marked-entities-listing-r)

### [8.6.](#section-8.6) [Trust Mark Endpoint](#name-trust-mark-endpoint)

The Trust Mark endpoint is exposed by a Trust Mark Issuer to provide Trust Marks to subjects.[¶](#section-8.6-1)

The Trust Mark endpoint location is published in the Entity\'s `federation_entity` metadata in the `federation_trust_mark_endpoint` parameter defined in [Section 5.1.1](#federation_entity).[¶](#section-8.6-2)

#### [8.6.1.](#section-8.6.1) [Trust Mark Request](#name-trust-mark-request)

When client authentication is not used, the request MUST be an HTTP request using the GET method with the following query parameters, encoded in `application/x-www-form-urlencoded` format.[¶](#section-8.6.1-1)

trust_mark_type
:   REQUIRED. Identifier for the type of the Trust Mark.[¶](#section-8.6.1-2.2)
:   

sub
:   REQUIRED. The Entity Identifier of the Entity to which the Trust Mark is issued.[¶](#section-8.6.1-2.4)
:   

When client authentication is used, the request MUST be an HTTP request using the POST method, with the parameters passed in the POST body. The Trust Mark endpoint MAY choose to allow authenticated requests from clients that are not the Trust Mark subject, as indicated by the `sub` parameter. An example use case is to let a Federation Entity retrieve the Trust Mark for another Entity.[¶](#section-8.6.1-3)

The following is a non-normative example of an HTTP request for a Trust Mark with a specific Trust Mark type identifier and subject:[¶](#section-8.6.1-4)

    GET /trust_mark?trust_mark_type=https%3A%2F%2Fwww.spid.gov.it%2Fcertification%2Frp&sub=https%3A%2F%2Frp.example.it%2Fspid HTTP/1.1
    Host: tuber.cert.example.org

[Figure 40](#figure-40): [Trust Mark Request](#name-trust-mark-request-2)

#### [8.6.2.](#section-8.6.2) [Trust Mark Response](#name-trust-mark-response)

A successful response MUST use the HTTP status code 200 with the content type `application/trust-mark+jwt`, containing the Trust Mark.[¶](#section-8.6.2-1)

If the specified Entity does not have the specified Trust Mark, the response is an error response and MUST use the HTTP status code 404.[¶](#section-8.6.2-2)

The following is a non-normative example of a response, containing the Trust Mark for the specified Entity (with line wraps within values for display purposes only):[¶](#section-8.6.2-3)

    200 OK
    Content-Type: application/trust-mark+jwt

    eyJ0eXAiOiJ0cnVzdC1tYXJrK2p3dCIsImFsZyI6IlJTMjU2Iiwia2lkIjoia29zR20yd3Va
    aDlER21OeEF0a3VPNDBwUGpwTDMtakNmMU4tcVBPLVllVSJ9.
    eyJpc3MiOiJodHRwczovL3d3dy5hZ2lkLmdvdi5pdCIsInN1YiI6Imh0dHBzOi8vcnAuZXhh
    bXBsZS5pdC9zcGlkIiwiaWF0IjoxNTc5NjIxMTYwLCJ0cnVzdF9tYXJrX3R5cGUiOiJodHRw
    czovL3d3dy5zcGlkLmdvdi5pdC9jZXJ0aWZpY2F0aW9uL3JwIiwibG9nb191cmkiOiJodHRw
    czovL3d3dy5hZ2lkLmdvdi5pdC90aGVtZXMvY3VzdG9tL2FnaWQvbG9nby5zdmciLCJyZWYi
    OiJodHRwczovL2RvY3MuaXRhbGlhLml0L2RvY3Mvc3BpZC1jaWUtb2lkYy1kb2NzL2l0L3Zl
    cnNpb25lLWNvcnJlbnRlLyJ9.
    L_pSh1InEiFAcs3E-1HBM7fNZYwF5ru3UGA_8yc80dGS3sszfA_sbj4AoW_zAJWQBdZpjxnH
    BBmybYXFrfZBcqxcedsrvUYrmbt1nPYxbUE54fRRoZK-sJmVqh1GzSan5nOmkxuAtMinU8k_
    -aWnPWj83sYe2AzT2mMgkXiz8zhda3jZm8hoxZ4jR6B0YAvbMlq2pPWO5OWKdZhiFRMSprwh
    0GYluQkK0j1aLNMGXD3keMJd2zEoWX9D7w2fXShAA48W3cNhuXyBVnCoum1K4IWK3s_fx4nI
    kp6W-V4jCBOpxp7Yo8LZ30o_xpEOzGTIECGWVR86azOAlwVC8XSiAA

[Figure 41](#figure-41): [Trust Mark Response](#name-trust-mark-response-2)

### [8.7.](#section-8.7) [Federation Historical Keys Endpoint](#name-federation-historical-keys-)

Each Federation Entity MAY publish its previously used Federation Entity Keys at the historical keys endpoint defined in [Section 5.1.1](#federation_entity). The purpose of this endpoint is to provide the list of keys previously used by the Federation Entity to provide non-repudiation of statements signed by it after key rotation. This endpoint also discloses the reason for the retraction of the keys and whether they were expired or revoked, including the reason for the revocation.[¶](#section-8.7-1)

Note that an expired key can be later additionally marked as revoked, to indicate a key compromise event discovered after the expiration of the key.[¶](#section-8.7-2)

The publishing of the historical keys guarantees that Trust Chains will remain verifiable and usable as inputs to trust decisions after the key expiration, unless the key becomes revoked for a security reason.[¶](#section-8.7-3)

#### [8.7.1.](#section-8.7.1) [Federation Historical Keys Request](#name-federation-historical-keys-r)

When client authentication is not used, the request MUST be an HTTP request using the GET method to the federation historical keys endpoint.[¶](#section-8.7.1-1)

When client authentication is used, the request MUST be an HTTP request using the POST method, with the client authentication parameters passed in the POST body.[¶](#section-8.7.1-2)

The following is a non-normative example of a historical keys request:[¶](#section-8.7.1-3)

    GET /federation_historical_keys HTTP/1.1
    Host: trust-anchor.example.com

[Figure 42](#figure-42): [Federation Historical Keys Request](#name-federation-historical-keys-re)

#### [8.7.2.](#section-8.7.2) [Federation Historical Keys Response](#name-federation-historical-keys-res)

The response is a signed JWK Set containing the historical keys. It is signed with a Federation Entity Key. A signed JWK Set is a signed JWT with a JWK Set \[[RFC7517](#RFC7517)\] as its payload. A successful response MUST use the HTTP status code 200 with the content type `application/jwk-set+jwt`.[¶](#section-8.7.2-1)

Historical keys JWTs are explicitly typed by setting the `typ` header parameter to `jwk-set+jwt` to prevent cross-JWT confusion, per Section 3.11 of \[[RFC8725](#RFC8725)\]. Historical keys JWTs without a `typ` header parameter or with a different `typ` value MUST be rejected.[¶](#section-8.7.2-2)

Historical keys JWTs MUST include the `kid` (Key ID) header parameter with its value being the Key ID of the signing key used.[¶](#section-8.7.2-3)

The Claims in a historical keys JWT are:[¶](#section-8.7.2-4)

iss
:   REQUIRED. String. The Entity\'s Entity Identifier.[¶](#section-8.7.2-5.2)
:   

iat
:   REQUIRED. Number. Time when this historical keys JWT was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-8.7.2-5.4)
:   

keys

:   REQUIRED. Array of JSON objects containing the signing keys for the Entity in JWK format.[¶](#section-8.7.2-5.6.1)

    JWKs in the `keys` Claim use the following parameters:[¶](#section-8.7.2-5.6.2)

    kid
    :   REQUIRED. Parameter used to match a specific key. It is RECOMMENDED that the Key ID be the JWK Thumbprint \[[RFC7638](#RFC7638)\] of the key using the SHA-256 hash function.[¶](#section-8.7.2-5.6.3.2)
    :   

    iat
    :   OPTIONAL. Number. Time when this key was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-8.7.2-5.6.3.4)
    :   

    exp
    :   REQUIRED. Number. Expiration time for the key. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\], After this time the key MUST NOT be considered valid.[¶](#section-8.7.2-5.6.3.6)
    :   

    revoked

    :   OPTIONAL. JSON object that contains the properties of the revocation, as defined below:[¶](#section-8.7.2-5.6.3.8.1)

        revoked_at
        :   REQUIRED. Time when the key was revoked or must be considered revoked, using the time format defined for the `iat` Claim in \[[RFC7519](#RFC7519)\].[¶](#section-8.7.2-5.6.3.8.2.2)
        :   

        reason
        :   OPTIONAL. String that identifies the reason for the key revocation, as defined in [Section 8.7.3](#hist-keys-reasons).[¶](#section-8.7.2-5.6.3.8.2.4)
        :   

        Additional members of the `revoked` object MAY be defined and used.[¶](#section-8.7.2-5.6.3.8.3)

    :   

:   

Additional Claims MAY be defined and used in conjunction with the Claims above.[¶](#section-8.7.2-6)

JWKs in the `keys` Claim MAY also contain the `nbf` parameter. For use in Historical Keys, `iat` and `exp` are sufficient to establish the key lifetime, making `nbf` typically superfluous; however, it is registered for use by profiles that may choose to issue keys that do not immediately become valid at the time of issuance. Its definition is:[¶](#section-8.7.2-7)

nbf
:   OPTIONAL. Time before which the key is not valid, using the time format defined for the `nbf` Claim in \[[RFC7519](#RFC7519)\].[¶](#section-8.7.2-8.2)
:   

The following is a non-normative example of the JWT Claims Set for a historical keys response:[¶](#section-8.7.2-9)

    {
        "iss": "https://trust-anchor.federation.example.com",
        "iat": 1666335600,
        "keys":
            [
                {
                    "kty": "RSA",
                    "n": "5s4qi ...",
                    "e": "AQAB",
                    "kid": "2HnoFS3YnC9tjiCaivhWLVUJ3AxwGGz_98uRFaqMEEs",
                    "iat": 1661151600,
                    "exp": 1677052800
                },
                {
                    "kty": "RSA",
                    "n": "ng5jr ...",
                    "e": "AQAB",
                    "kid": "8KnoFS3YnC9tjiCaivhWLVUJ3AxwGGz_98uRFaqMJJr",
                    "iat": 1647932400,
                    "exp": 1663830000,
                    "revoked": {
                      "revoked_at": 1661151600,
                      "reason": "compromised",
                    }
                }
            ]
    }

[Figure 43](#figure-43): [Federation Historical Keys Response JWT Claims Set](#name-federation-historical-keys-resp)

#### [8.7.3.](#section-8.7.3) [Federation Historical Keys Revocation Reasons](#name-federation-historical-keys-rev)

Federation Entities are strongly encouraged to use a meaningful `reason` value when indicating the revocation reason for a Federation Entity Key. The `reason` MAY be omitted instead of using the `unspecified` value.[¶](#section-8.7.3-1)

Below is the definition of the reason values.[¶](#section-8.7.3-2)

The following table defines Federation Entity Keys revocation reasons. These reasons are inspired by Section 5.3.1 of \[[RFC5280](#RFC5280)\].[¶](#section-8.7.3-3)

  Reason        Description
  ------------- ----------------------------------------------------------
  unspecified   General or unspecified reason for the JWK status change.
  compromised   The private key is believed to have been compromised.
  superseded    The JWK is no longer active.

  : [Table 2](#table-2): [Federation Entity Keys Revocation Reasons.](#name-federation-entity-keys-revo)

A federation MAY specify and utilize additional reasons depending on the trust or security framework in use.[¶](#section-8.7.3-5)

#### [8.7.4.](#section-8.7.4) [Rationale for the Federation Historical Keys Endpoint](#name-rationale-for-the-federatio)

The Federation Historical Keys endpoint solves the problem of verifying historical Trust Chains when the Federation Entity Keys have changed, either due to expiration or revocation.[¶](#section-8.7.4-1)

The Federation Historical Keys endpoint publishes the list of public keys used in the past by the Entity. These keys are needed to verify Trust Chains created in the past with Entity keys no longer published in the Entity\'s Entity Configuration.[¶](#section-8.7.4-2)

The Federation Historical Keys endpoint response contains a signed JWT that attests to all the expired and revoked Entity keys.[¶](#section-8.7.4-3)

Based on the attributes contained in the Entity Statements that form a Trust Chain, it MAY also be possible to verify the non-federation public keys used in the past by Leaf Entities for signature operations for OpenID Connect requests and responses. For example, an Entity Statement issued for a Leaf MAY also include the `jwks` Claim for the Leaf\'s Entity Types, in its `metadata` or `metadata_policy` Claims.[¶](#section-8.7.4-4)

A simple example: In the following Trust Chain, the Federation Intermediate attests to the Leaf\'s OpenID Connect RP `jwks` in the Subordinate Statement issued about the Leaf. The result is a Trust Chain that contains the Leaf\'s OpenID Connect RP JWK Set, needed to verify historical signature on Request Objects and any other signed JWT issued by the Leaf as an RP. This example Trust Chain contains:[¶](#section-8.7.4-5)

1.  an Entity Configuration about the RP published by the RP,[¶](#section-8.7.4-6.1.1)

2.  a Subordinate Statement about the RP published by Organization A, with the Claim `jwks` contained in `metadata` or `metadata_policy` attesting the Leaf\'s OpenID Connect RP `jwks`, and[¶](#section-8.7.4-6.2.1)

3.  a Subordinate Statement about Organization A published by Federation F.[¶](#section-8.7.4-6.3.1)

### [8.8.](#section-8.8) [Client Authentication at Federation Endpoints](#name-client-authentication-at-fe)

Client authentication is not used at any of the federation endpoints, by default. Federations can choose to make client authentication OPTIONAL, REQUIRED, and/or not allowed at particular federation endpoints.[¶](#section-8.8-1)

Client authentication with `private_key_jwt` is the default client authentication method to federation endpoints when client authentication is supported. This client authentication method is described in Section 9 of [OpenID Connect Core 1.0](#OpenID.Core) \[[OpenID.Core](#OpenID.Core)\]. The client authentication JWT MUST be signed with a Federation Entity Key. The audience of the JWT MUST be the Entity Identifier of the Entity whose federation endpoint is being authenticated to. The endpoint MUST NOT accept JWTs containing audience values other than its Entity Identifier. When client authentication is used, the request MUST be an HTTP request using the POST method, with the client authentication and endpoint request parameters passed in the POST body. Federations can choose to also use other client authentication methods.[¶](#section-8.8-2)

#### [8.8.1.](#section-8.8.1) [Client Authentication Metadata for Federation Endpoints](#name-client-authentication-metad)

Like other OAuth and OpenID endpoints supporting client authentication, this specification defines metadata parameters saying which client authentication methods are supported for each endpoint. These largely parallel the `token_endpoint_auth_methods_supported` metadata value defined in Section 3 of [OpenID Connect Discovery 1.0](#OpenID.Discovery) \[[OpenID.Discovery](#OpenID.Discovery)\].[¶](#section-8.8.1-1)

Specifically, for each of the federation endpoints defined in [Section 5.1.1](#federation_entity), parameters named `*_auth_methods` are defined, where the `*` represents the federation endpoint names `federation_fetch_endpoint`, `federation_list_endpoint`, \..., `federation_historical_keys_endpoint`.[¶](#section-8.8.1-2)

The `*_auth_methods` metadata parameters list supported client authentication methods for these endpoints, just as `token_endpoint_auth_methods_supported` does for the Token Endpoint. In addition, the value `none` MAY be used to indicate that client authentication is not required at the endpoint.[¶](#section-8.8.1-3)

So, for instance, this metadata declaration states that requests authenticated with `private_key_jwt` are REQUIRED at the `federation_trust_mark_endpoint`:[¶](#section-8.8.1-4)

    "federation_trust_mark_endpoint_auth_methods": ["private_key_jwt"]

[Figure 44](#figure-44): [Declaring that Client Authentication is REQUIRED at an Endpoint](#name-declaring-that-client-authe)

If omitted, the default value for these methods is `["none"]`, indicating that only unauthenticated requests are accepted.[¶](#section-8.8.1-6)

The `endpoint_auth_signing_alg_values_supported` metadata parameter lists supported client authentication signing algorithms supported for these endpoints, just as `token_endpoint_auth_signing_alg_values_supported` does for the Token Endpoint.[¶](#section-8.8.1-7)

### [8.9.](#section-8.9) [Error Responses](#name-error-responses)

If the request was malformed or an error occurred during the processing of the request, the response body SHOULD be a JSON object with the content type `application/json`. In compliance with \[[RFC6749](#RFC6749)\], the following standardized error format SHOULD be used:[¶](#section-8.9-1)

error

:   REQUIRED. Error codes in the IANA \"OAuth Extensions Error Registry\" \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\] MAY be used. In particular, these existing error codes are used by this specification:[¶](#section-8.9-2.2.1)

    invalid_request
    :   The request is incomplete or does not comply with current specifications. The HTTP response status code SHOULD be 400 (Bad Request).[¶](#section-8.9-2.2.2.2)
    :   

    server_error
    :   The server encountered an unexpected condition that prevented it from fulfilling the request. The HTTP response status code SHOULD be one in the 5xx range, like 500 (Internal Server Error).[¶](#section-8.9-2.2.2.4)
    :   

    temporarily_unavailable
    :   The server hosting the federation endpoint is currently unable to handle the request due to temporary overloading or maintenance. The HTTP response status code SHOULD be 503 (Service Unavailable).[¶](#section-8.9-2.2.2.6)
    :   

    This specification also defines the following error codes:[¶](#section-8.9-2.2.3)

    invalid_client
    :   The Client cannot be authorized or is not a valid participant of the federation. The HTTP response status code SHOULD be 401 (Unauthorized).[¶](#section-8.9-2.2.4.2)
    :   

    invalid_issuer
    :   The endpoint cannot serve the requested issuer. The HTTP response status code SHOULD be 404 (Not Found).[¶](#section-8.9-2.2.4.4)
    :   

    invalid_subject
    :   The endpoint cannot serve the requested subject. The HTTP response status code SHOULD be 404 (Not Found).[¶](#section-8.9-2.2.4.6)
    :   

    invalid_trust_anchor
    :   The Trust Anchor cannot be found or used. The HTTP response status code SHOULD be 404 (Not Found).[¶](#section-8.9-2.2.4.8)
    :   

    invalid_trust_chain
    :   The Trust Chain cannot be validated. The HTTP response status code SHOULD be 400 (Bad Request).[¶](#section-8.9-2.2.4.10)
    :   

    invalid_metadata
    :   Metadata or Metadata Policy values are invalid or conflict. The HTTP response status code SHOULD be 400 (Bad Request).[¶](#section-8.9-2.2.4.12)
    :   

    not_found
    :   The requested Entity Identifier cannot be found. The HTTP response status code SHOULD be 404 (Not Found).[¶](#section-8.9-2.2.4.14)
    :   

    unsupported_parameter
    :   The server does not support the requested parameter. The HTTP response status code SHOULD be 400 (Bad Request).[¶](#section-8.9-2.2.4.16)
    :   

:   

error_description
:   REQUIRED. Human-readable text providing additional information used to assist the developer in understanding the error that occurred.[¶](#section-8.9-2.4)
:   

The following is a non-normative example of an error response:[¶](#section-8.9-3)

    400 Bad request
    Content-Type: application/json

    {
      "error": "invalid_request",
      "error_description":
        "Required request parameter [sub] was missing."
    }

[Figure 45](#figure-45): [Example Error Response](#name-example-error-response)

## [9.](#section-9) [Obtaining Federation Entity Configuration Information](#name-obtaining-federation-entity)

The Entity Configuration of all Trust Anchor and Intermediate Federation Entities MUST be published at their configuration endpoint and the Entity Configuration for Leaf Entities SHOULD be published there. Its location is determined by concatenating the string `/.well-known/openid-federation` to the Entity Identifier (which MUST use the `https` scheme and contain a host component and MAY also contain port and path components). For instance, the configuration endpoint for the Entity Identifier `https://entity.example` is the URL `https://entity.example/.well-known/openid-federation`. If the Entity Identifier contains a trailing \"/\" character, it MUST be removed before concatenating `/.well-known/openid-federation`.[¶](#section-9-1)

Furthermore, any Entity Configuration including the Entity Type Identifier `federation_entity` MUST be published at its configuration endpoint.[¶](#section-9-2)

While Leaf Federation Entities SHOULD make an Entity Configuration document available at their configuration endpoints, an exception to this requirement is that clients that use a client registration method that results in the server having the client\'s Entity Configuration MAY omit doing so. For instance, since an RP using Explicit Registration posts its Entity Configuration to the OP during client registration, the OP has everything it needs from the RP. Profiles of this specification MAY define other exceptions for Leaf Entities that do not use the Entity Type Identifier `federation_entity` and processing rules that accompany them.[¶](#section-9-3)

### [9.1.](#section-9.1) [Federation Entity Configuration Request](#name-federation-entity-configura)

An Entity Configuration document MUST be queried using an HTTP GET request at the previously specified path.[¶](#section-9.1-1)

In this example, the requesting party would make the following request to the Entity `https://op.sunet.se` to obtain its Entity Configuration:[¶](#section-9.1-2)

      GET /.well-known/openid-federation HTTP/1.1
      Host: op.sunet.se

[Figure 46](#figure-46): [Request for Entity Configuration](#name-request-for-entity-configur)

### [9.2.](#section-9.2) [Federation Entity Configuration Response](#name-federation-entity-configurat)

The response is an Entity Configuration. If the Entity is an Intermediate Entity or a Trust Anchor, the response MUST contain metadata for a Federation Entity (`federation_entity`).[¶](#section-9.2-1)

A successful response MUST use the HTTP status code 200 with the content type `application/entity-statement+jwt`, to make it clear that the response contains an Entity Statement. In case of an error, the response is as defined in [Section 8.9](#error_response).[¶](#section-9.2-2)

The following is a non-normative example JWT Claims Set for a response from an Intermediate Entity:[¶](#section-9.2-3)

    {
      "iss": "https://sunet.se",
      "sub": "https://sunet.se",
      "iat": 1516239022,
      "exp": 1516843822,
      "metadata": {
        "federation_entity": {
          "contacts": ["ops@sunet.se"],
          "federation_fetch_endpoint": "https://sunet.se/fetch",
          "organization_uri": "https://www.sunet.se",
          "organization_name": "SUNET"
        },
      "jwks": {
        "keys": [
          {
            "alg": "RS256",
            "e": "AQAB",
            "kid": "O-wFRGNsy0-4SR2jbXYwDHy6...",
            "kty": "RSA",
            "n": "Qo3n0N0Gv7L8T9I_2sAMc0I4...",
            "use": "sig"
          }
        ]
      },
      "authority_hints": [
        "https://edugain.org"
      ]
    }

[Figure 47](#figure-47): [Entity Configuration Response JWT Claims Set for an Intermediate](#name-entity-configuration-respon)

The following is a non-normative example JWT Claims Set for a response from an OpenID Provider Entity:[¶](#section-9.2-5)

    {
      "iss": "https://op.sunet.se",
      "sub": "https://op.sunet.se",
      "iat": 1516239022,
      "exp": 1516843822,
      "metadata": {
        "openid_provider": {
          "issuer": "https://op.sunet.se",
          "signed_jwks_uri": "https://op.sunet.se/jwks.jose",
          "authorization_endpoint":
            "https://op.sunet.se/authorization",
          "client_registration_types_supported": [
            "automatic",
            "explicit"
          ],
          "grant_types_supported": [
            "authorization_code"
          ],
          "id_token_signing_alg_values_supported": [
            "ES256", "RS256"
          ],
          "contacts": ["ops@sunet.se"],
          "organization_uri": "https://www.sunet.se",
          "organization_name": "SUNET"
          "logo_uri":
            "https://www.sunet.se/sunet/images/32x32.png",
          "op_policy_uri":
            "https://www.sunet.se/en/website/legal-information/",
          "response_types_supported": [
            "code"
          ],
          "subject_types_supported": [
            "pairwise",
            "public"
          ],
          "token_endpoint": "https://op.sunet.se/token",
          "federation_registration_endpoint":
            "https://op.sunet.se/fedreg",
          "token_endpoint_auth_methods_supported": [
            "private_key_jwt"
          ]
        }
      },
      "jwks": {
        "keys": [
          {
            "alg": "RS256",
            "e": "AQAB",
            "kid": "yHozfrAd5G-pnmG4e1jCXpfc...",
            "kty": "RSA",
            "n": "HIoSq4wkZiEkJDTPHiv7torK...",
            "use": "sig"
          }
        ]
      },
      "authority_hints": [
        "https://sunet.se"
      ]
    }

[Figure 48](#figure-48): [Entity Configuration Response JWT Claims Set for an OP](#name-entity-configuration-respons)

## [10.](#section-10) [Resolving the Trust Chain and Metadata](#name-resolving-the-trust-chain-a)

An Entity (Party A) that wants to establish trust with another Entity (Party B) MUST have Party B\'s Entity Identifier and a list of Entity Identifiers of Trust Anchors and their public signing keys. Party A will first have to fetch sufficient Entity Statements to establish at least one chain of trust from Party B to one or more of the Trust Anchors. After that, Party A MUST validate the Trust Chains independently, and if there are multiple valid Trust Chains and if the application demands it, choose one to use.[¶](#section-10-1)

To delegate the Trust Chain evaluation to a trusted third party, the Entity (Party A) that wants to establish trust with another Entity (Party B) MAY use a resolve endpoint, as defined in [Section 8.3](#resolve).[¶](#section-10-2)

### [10.1.](#section-10.1) [Fetching Entity Statements to Establish a Trust Chain](#name-fetching-entity-statements-)

Depending on the circumstances, Party A MAY be handed Party B\'s Entity Configuration, or it may have to fetch it by itself. If it needs to fetch it, it will use the process described in [Section 9](#federation_configuration) based on the Entity Identifier of Party B.[¶](#section-10.1-1)

The next step is to iterate through the list of Intermediates listed in `authority_hints`, ignoring the authority hints that end in an unknown Trust Anchor, requesting an Entity Configuration from each of the Intermediates. If the received Entity Configuration contains an authority hint, this process is repeated.[¶](#section-10.1-2)

With the list of all Intermediates and the Trust Anchor, the respective fetch endpoints, as defined in [Section 8.1](#fetch_endpoint), are used to fetch Entity Statements about the Intermediates and Party B.[¶](#section-10.1-3)

Federation participants MUST NOT attempt to fetch Entity Statements they already have obtained during this process to prevent loops. If a loop is detected, the authority hint that led to it MUST NOT be used.[¶](#section-10.1-4)

A successful operation will return one or more lists of Entity Statements. Each of the lists terminating in a self-signed Entity Statement is issued by a Trust Anchor.[¶](#section-10.1-5)

If there is no path from Party B to at least one of the trusted Trust Anchors, then the list will be empty and there is no way of establishing trust in Party B\'s information. How Party A deals with this is out of scope for this specification.[¶](#section-10.1-6)

The following sequence diagram represents the interactions between the RP, the OP, and the Trust Anchor during a trust evaluation made by the OP about the RP. Relating this to the preceding description, in this diagram, Party A is the OP and Party B is the RP.[¶](#section-10.1-7)

    +-----+                         +-----+                             +--------------+
    | RP  |                         | OP  |                             | Trust Anchor |
    +-----+                         +-----+                             +--------------+
       |                               |                                        |
       | Entity Configuration Request  |                                        |
       |<------------------------------|                                        |
       |                               |                                        |
       | Entity Configuration Response |                                        |
       |------------------------------>|                                        |
       |                               |                                        |
       |                               | Evaluates authority_hints              |
       |                               |--------------------------              |
       |                               |                         |              |
       |                               |<-------------------------              |
       |                               |                                        |
       |                               | Entity Configuration Request           |
       |                               |--------------------------------------->|
       |                               |                                        |
       |                               |        Entity Configuration Response   |
       |                               |<---------------------------------------|
       |                               |                                        |
       |                               | Obtains Fetch Endpoint                 |
       |                               |-----------------------                 |
       |                               |                      |                 |
       |                               |<----------------------                 |
       |                               |                                        |
       |                               | Request Subordinate Statement about RP |
       |                               |--------------------------------------->|
       |                               |                                        |
       |                               |        Subordinate Statement about RP  |
       |                               |<---------------------------------------|
       |                               |                                        |
       |                               | Evaluates the Trust Chain              |
       |                               |--------------------------              |
       |                               |                         |              |
       |                               |<-------------------------              |
       |                               |                                        |
       |                               | Applies Metadata Policies              |
       |                               |--------------------------              |
       |                               |                         |              |
       |                               |<-------------------------              |
       |                               |                                        |
       |                               | Applies Constraints                    |
       |                               |--------------------                    |
       |                               |                   |                    |
       |                               |<-------------------                    |
       |                               |                                        |
       |                               | Derives the RP's Resolved Metadata     |
       |                               |-----------------------------------     |
       |                               |                                  |     |
       |                               |<----------------------------------     |

[Figure 49](#figure-49): [Resolving Trust Chain and Metadata from the Perspective of an OP](#name-resolving-trust-chain-and-m)

### [10.2.](#section-10.2) [Validating a Trust Chain](#name-validating-a-trust-chain)

As described in [Section 4](#trust_chain), a Trust Chain consists of an ordered list of Entity Statements. So however Party A has acquired the set of Entity Statements, it MUST now verify that it is a proper Trust Chain using the rules laid out in that section.[¶](#section-10.2-1)

Let us refer to the Entity Statements in the Trust Chain as ES\[j\], where j = 0,\...,i, with 0 being the index of the first Entity Statement and i being the zero-based index of the last. To validate the Trust Chain, the following MUST be done:[¶](#section-10.2-2)

-   For each Entity Statement ES\[j\], where j = 0,..,i:[¶](#section-10.2-3.1.1)

    -   Verify that the statement contains all the required Claims.[¶](#section-10.2-3.1.2.1.1)

    -   Verify that `iat` has a value in the past.[¶](#section-10.2-3.1.2.2.1)

    -   Verify that `exp` has a value that is in the future.[¶](#section-10.2-3.1.2.3.1)

-   For ES\[0\] (the Entity Configuration of the Trust Chain subject), verify that `iss` == `sub`.[¶](#section-10.2-3.2.1)

-   For ES\[0\], verify that its signature validates with a public key in ES\[0\]\[\"jwks\"\].[¶](#section-10.2-3.3.1)

-   For each j = 0,\...,i-1, verify that ES\[j\]\[\"iss\"\] == ES\[j+1\]\[\"sub\"\].[¶](#section-10.2-3.4.1)

-   For each j = 0,\...,i-1, verify that the signature of ES\[j\] validates with a public key in ES\[j+1\]\[\"jwks\"\].[¶](#section-10.2-3.5.1)

-   For ES\[i\] (the Trust Anchor\'s Entity Configuration), verify that the issuer matches the Entity Identifier of the Trust Anchor.[¶](#section-10.2-3.6.1)

-   For ES\[i\], verify that its signature validates with a public key of the Trust Anchor.[¶](#section-10.2-3.7.1)

Verifying the signature is a much more expensive operation than verifying the correctness of the statement and the timestamps. An implementer MAY therefore choose not to verify the signatures until all the other checks have been done.[¶](#section-10.2-4)

Federation participants MAY cache Entity Statements and signature verification results until they expire, per [Section 10.4](#trust_lifetime).[¶](#section-10.2-5)

After the preceding validation, metadata MUST be resolved to the subject of the Trust Chain, as described in [Section 6.1.4](#metadata_policy_enforcement). Furthermore, constraints MUST be enforced for each Subordinate Statement of the Trust Chain, as explained in [Section 6.2](#chain_constraints).[¶](#section-10.2-6)

### [10.3.](#section-10.3) [Choosing One of the Valid Trust Chains](#name-choosing-one-of-the-valid-t)

If multiple valid Trust Chains are found, Party A will need to decide on which one to use. One simple rule would be to prefer a shorter chain over a longer one. Federation participants MAY follow other rules according to local policy.[¶](#section-10.3-1)

### [10.4.](#section-10.4) [Calculating the Expiration Time of a Trust Chain](#name-calculating-the-expiration-)

Each Entity Statement in a Trust Chain is signed and MUST have an expiration time (`exp`). The expiration time of the whole Trust Chain is the minimum (`exp`) value within the Trust Chain.[¶](#section-10.4-1)

### [10.5.](#section-10.5) [Transient Trust Chain Validation Errors](#name-transient-trust-chain-valid)

If the federation topology is being updated, for example, when a set of Leaf Entities is moved to a new Intermediate Entity, the Trust Chain validation may fail in a transient manner. Retrying after a period of time may resolve the situation.[¶](#section-10.5-1)

### [10.6.](#section-10.6) [Resolving the Trust Chain and Metadata with a Resolver](#name-resolving-the-trust-chain-an)

Note that an alternative method for resolving a Trust Chain for an Entity (Party B) using the methods described above is to use a resolve endpoint, as described in [Section 8.3](#resolve). This lets the resolver do the work that otherwise the Entity (Party A) wanting to establish trust would have to do for itself.[¶](#section-10.6-1)

## [11.](#section-11) [Updating Metadata, Key Rollover, and Revocation](#name-updating-metadata-key-rollo)

This specification facilitates smoothly updating metadata and public keys.[¶](#section-11-1)

As described in [Section 10.4](#trust_lifetime), each Trust Chain has an expiration time. Federation participants MUST support refreshing a Trust Chain when it expires. How often a participant reevaluates the Trust Chain depends on how quickly it wants to find out that something has changed.[¶](#section-11-2)

### [11.1.](#section-11.1) [Federation Key Rollover](#name-federation-key-rollover)

The expiration time (`exp`) of an Entity Configuration is used to control how often it needs to be retrieved to fetch an updated set of Federation Keys from its `jwks` member.[¶](#section-11.1-1)

### [11.2.](#section-11.2) [Key Rollover for a Trust Anchor](#name-key-rollover-for-a-trust-an)

A Trust Anchor MUST publish an Entity Configuration about itself. The expiration time (`exp`) set on this Entity Configuration should be chosen such that it ensures that federation participants re-fetch it at reasonable intervals. When a Trust Anchor rolls over its signing keys, it needs to:[¶](#section-11.2-1)

1.  Add the new keys to the `jwks` representing the Trust Anchor\'s signing keys in its Entity Configuration.[¶](#section-11.2-2.1.1)

2.  Keep signing the Entity Configuration and the Entity Statements using the old keys for a long enough time period to allow all Subordinates to have obtained the new keys.[¶](#section-11.2-2.2.1)

3.  Switch to signing with the new keys.[¶](#section-11.2-2.3.1)

4.  After a reasonable time period, remove the old keys. What is regarded as a reasonable time is dependent on the security profile and risk assessment of the Trust Anchor.[¶](#section-11.2-2.4.1)

### [11.3.](#section-11.3) [Redundant Retrieval of Trust Anchor Keys](#name-redundant-retrieval-of-trus)

It is RECOMMENDED that Federation Operators provide a means of retrieving the public keys for the Trust Anchors it administers that is independent of those Trust Anchors\' Entity Configurations. This is intended to provide redundancy in the eventuality of the compromise of the Web PKI \[[RFC9525](#RFC9525)\] infrastructure underlying retrieval of public keys from Entity Configurations.[¶](#section-11.3-1)

The keys retrieved via the independent mechanism specified by the Federation Operator SHOULD be compared to those retrieved via the Trust Anchor\'s Entity Configuration. If they do not match, both SHOULD be retrieved again. If they still do not match, it is indicative of a security or configuration problem. The appropriate remediation steps in that eventuality SHOULD be specified by the Federation Operator.[¶](#section-11.3-2)

### [11.4.](#section-11.4) [Revocation](#name-revocation)

Since the participants in federations are expected to check the Trust Chain on a regular frequent basis, this specification does not define a revocation process. Specific federations MAY make a different choice and will then have to define their own revocation process.[¶](#section-11.4-1)

## [12.](#section-12) [OpenID Connect Client Registration](#name-openid-connect-client-regis)

This section describes how the mechanisms defined in this specification can be used to establish trust between an RP and an OP that have no prior explicit configuration or registration between them. It defines two client registration methods, Automatic Registration and Explicit Registration, that use Trust Chains, per [Section 10](#resolving_trust). Federations can use other appropriate methods for client registration.[¶](#section-12-1)

Federations with OpenID Connect Entities SHOULD agree on the supported client registration methods.[¶](#section-12-2)

Note that both Automatic Registration and Explicit Registration can also be used for OAuth 2.0 profiles other than OpenID Connect. To do so, rather than using the Entity Type Identifiers `openid_relying_party` and `openid_provider`, one would instead use the Entity Type Identifiers `oauth_client` and `oauth_authorization_server`, or possibly other Entity Type Identifiers defined for the specific OAuth 2.0 profile being used.[¶](#section-12-3)

When using both methods, `trust_anchor_hints` values can be used to determine Trust Anchors that the RP and OP share. When building Trust Chains, RPs SHOULD choose a Trust Anchor in common with the OP, when possible.[¶](#section-12-4)

### [12.1.](#section-12.1) [Automatic Registration](#name-automatic-registration)

Automatic Registration enables an RP to make Authentication Requests without a prior registration step with the OP. The OP resolves the RP\'s Entity Configuration from the Client ID in the Authentication Request, following the process defined in [Section 10](#resolving_trust).[¶](#section-12.1-1)

The RP MUST perform Trust Chain and metadata resolution for the OP, as specified in [Section 10](#resolving_trust) before it sends the Authentication Request. If the resolution is not successful, the RP MUST NOT attempt further interactions with the OP.[¶](#section-12.1-2)

Automatic Registration has the following characteristics:[¶](#section-12.1-3)

-   In all interactions with the OP, the RP employs its Entity Identifier as the Client ID. The OP retrieves the RP\'s Entity Configuration from the URL derived from the Entity Identifier, as described in [Section 9](#federation_configuration).[¶](#section-12.1-4.1.1)

-   Since there is no registration step prior to the Authentication Request, asymmetric cryptography MUST be used to authenticate requests when using Automatic Registration. Asymmetric cryptography is used to authenticate requests; therefore, the OP neither assigns a Client Secret to the RP nor returns it as a result of the registration process.[¶](#section-12.1-4.2.1)

An OP that supports Automatic Registration MUST include the `automatic` keyword in its `client_registration_types_supported` metadata parameter.[¶](#section-12.1-5)

#### [12.1.1.](#section-12.1.1) [Authentication Request](#name-authentication-request)

The Authentication Request is performed by passing a Request Object by value or by reference, as described in Section 6 of [OpenID Connect Core 1.0](#OpenID.Core) \[[OpenID.Core](#OpenID.Core)\] and [The OAuth 2.0 Authorization Framework: JWT-Secured Authorization Request (JAR)](#RFC9101) \[[RFC9101](#RFC9101)\], or using a pushed authorization request, as described in [Pushed Authorization Requests](#RFC9126) \[[RFC9126](#RFC9126)\].[¶](#section-12.1.1-1)

Authentication requests MUST demonstrate that the requesting Entity controls the Entity\'s RP keys, using one of the methods described below. Attempted authentication requests that do not do so MUST be rejected.[¶](#section-12.1.1-2)

Deployments MAY choose not to support passing the request object by reference (using the `request_uri` request parameter) because allowing this would make it easier for attackers to mount denial of service attacks against OAuth 2.0 Authorization Servers or OpenID Providers. They can do this by using the `request_uri_parameter_supported` OP metadata parameter with the value `false`. If the request parameters are too large to practically be passed by value as query parameters, the request parameters can instead be sent via HTTP POST or a [Pushed Authorization Request](#RFC9126) \[[RFC9126](#RFC9126)\], as described in [Section 12.1.1.2](#using-par).[¶](#section-12.1.1-3)

##### [12.1.1.1.](#section-12.1.1.1) [Using a Request Object](#name-using-a-request-object)

When a Request Object is used at the Authorization Endpoint or the Pushed Authorization Request Endpoint, the value of the `request` parameter is a JWT whose Claims are the request parameters specified in Section 3.1.2 in [OpenID Connect Core 1.0](#OpenID.Core) \[[OpenID.Core](#OpenID.Core)\]. The JWT MUST be signed and MAY be encrypted. The following parameters are used in the Request Object:[¶](#section-12.1.1.1-1)

aud
:   REQUIRED. The \"aud\" (audience) value MUST be the Entity Identifier of the OP and MUST NOT include any other values.[¶](#section-12.1.1.1-2.2)
:   

client_id
:   REQUIRED. The `client_id` value MUST be the RP\'s Entity Identifier.[¶](#section-12.1.1.1-2.4)
:   

iss
:   REQUIRED. The `iss` value MUST be the RP\'s Entity Identifier.[¶](#section-12.1.1.1-2.6)
:   

sub
:   MUST NOT be present. This prevents reuse of the statement for `private_key_jwt` client authentication.[¶](#section-12.1.1.1-2.8)
:   

jti
:   REQUIRED. JWT ID. A unique identifier for the JWT, which can be used to prevent reuse of the Request Object. A Request Object MUST only be used once, unless conditions for reuse were negotiated between the parties; any such negotiation is beyond the scope of this specification.[¶](#section-12.1.1.1-2.10)
:   

exp
:   REQUIRED. Number. Expiration time after which the JWT MUST NOT be accepted for processing. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-12.1.1.1-2.12)
:   

iat
:   OPTIONAL. Number. Time when this Request Object was issued. This is expressed as Seconds Since the Epoch, per \[[RFC7519](#RFC7519)\].[¶](#section-12.1.1.1-2.14)
:   

trust_chain

:   OPTIONAL. Array containing the sequence of Entity Statements that comprise the Trust Chain between the RP making the request and the selected Trust Anchor. When the RP and the OP are part of the same federation, the RP MUST select a Trust Anchor that it has in common with the OP; otherwise, the RP is free to select the Trust Anchor to use.[¶](#section-12.1.1.1-2.16.1)

    NOTE: The use of the `trust_chain` header parameter, as specified in [Section 4.3](#trust_chain_head_param), is RECOMMENDED over the use of this parameter; it is retained for historical reasons.[¶](#section-12.1.1.1-2.16.2)

:   

###### [12.1.1.1.1.](#section-12.1.1.1.1) [Authorization Request with a Trust Chain](#name-authorization-request-with-)

When the `trust_chain` header parameter is used in the authentication request, the Relying Party informs the OP of the sequence of Entity Statements that proves the trust relationship between it and the selected Trust Anchor.[¶](#section-12.1.1.1.1-1)

Due to the large size of a Trust Chain, it may be necessary to use the HTTP POST method, a `request_uri`, or a [Pushed Authorization Request](#RFC9126) \[[RFC9126](#RFC9126)\] for the request.[¶](#section-12.1.1.1.1-2)

The following is a non-normative example of the header parameters and JWT Claims Set in a Request Object:[¶](#section-12.1.1.1.1-3)

    {
      "typ": "oauth-authz-req+jwt",
      "alg": "RS256",
      "kid": "kid-that-points-to-a-jwk-contained-in-the-trust-chain",
      "trust_chain" : [
        "eyJhbGciOiJSUzI1NiIsImtpZCI6Ims1NEhRdERpYnlHY3M5WldWTWZ2aUhm ...",
        "eyJhbGciOiJSUzI1NiIsImtpZCI6IkJYdmZybG5oQU11SFIwN2FqVW1BY0JS ...",
        "eyJhbGciOiJSUzI1NiIsImtpZCI6IkJYdmZybG5oQU11SFIwN2FqVW1BY0JS ..."
      ]
    }
    .
    {
      "aud": "https://op.example.org",
      "client_id": "https://rp.example.com",
      "exp": 1589699162,
      "iat": 1589699102,
      "iss": "https://rp.example.com",
      "jti": "4d3ec0f81f134ee9a97e0449be6d32be",
      "nonce": "4LX0mFMxdBjkGmtx7a8WIOnB",
      "redirect_uri": "https://rp.example.com/authz_cb",
      "response_type": "code",
      "scope": "openid profile email address phone",
      "state": "YmX8PM9I7WbNoMnnieKKBiptVW0sP2OZ"
    }

[Figure 50](#figure-50): [Request Object JWT Claims Set](#name-request-object-jwt-claims-s)

The following is a non-normative example of an Authentication Request using the `request` parameter (with line wraps within values for display purposes only):[¶](#section-12.1.1.1.1-5)

    Host: server.example.com
    GET /authorize?
        redirect_uri=https%3A%2F%2Frp.example.com%2Fauthz_cb
        &scope=openid+profile+email+address+phone
        &response_type=code
        &client_id=https%3A%2F%2Frp.example.com
        &request=eyJ0eXAiOiJvYXV0aC1hdXRoei1yZXErand0IiwiYWxnIjoiUlMyNTYiLCJ
            raWQiOiJOX19EOThJdkI4TmFlLWt3QTZuck90LWlwVGhqSGtEeDM3bmljRE1IM04
            0In0.
            eyJhdWQiOiJodHRwczovL29wLmV4YW1wbGUub3JnIiwiY2xpZW50X2lkIjoiaHR0
            cHM6Ly9ycC5leGFtcGxlLmNvbSIsImV4cCI6MTU4OTY5OTE2MiwiaWF0IjoxNTg5
            Njk5MTAyLCJpc3MiOiJodHRwczovL3JwLmV4YW1wbGUuY29tIiwianRpIjoiNGQz
            ZWMwZjgxZjEzNGVlOWE5N2UwNDQ5YmU2ZDMyYmUiLCJub25jZSI6IjRMWDBtRk14
            ZEJqa0dtdHg3YThXSU9uQiIsInJlZGlyZWN0X3VyaSI6Imh0dHBzOi8vcnAuZXhh
            bXBsZS5jb20vYXV0aHpfY2IiLCJyZXNwb25zZV90eXBlIjoiY29kZSIsInNjb3Bl
            Ijoib3BlbmlkIHByb2ZpbGUgZW1haWwgYWRkcmVzcyBwaG9uZSIsInN0YXRlIjoi
            WW1YOFBNOUk3V2JOb01ubmllS0tCaXB0Vlcwc1AyT1oiLCJ0cnVzdF9jaGFpbiI6
            WyJleUpoYkdjaU9pSlNVekkxTmlJc0ltdHBaQ0k2SW1zMU5FaFJkRVJwWW5sSFkz
            TTVXbGRXVFdaMmFVaG0gLi4uIiwiZXlKaGJHY2lPaUpTVXpJMU5pSXNJbXRwWkNJ
            NklrSllkbVp5Ykc1b1FVMTFTRkl3TjJGcVZXMUJZMEpTIC4uLiIsImV5SmhiR2Np
            T2lKU1V6STFOaUlzSW10cFpDSTZJa0pZZG1aeWJHNW9RVTExU0ZJd04yRnFWVzFC
            WTBKUyAuLi4iXX0.
            Rv0isfuku0FcRFintgxgKDk7EnhFkpQRg3Tm6N6fCHAHEKFxVVdjy4
            9JboJtxKcQVZKN9TKn3lEYM1wtF1e9PQrNt4HZ21ICfnzxXuNx1F5SY1GXCU2n2y
            FVKtz3N0YkAFbTStzy-sPRTXB0stLBJH74RoPiLs2c6dDvrwEv__GA7oGkg2gWt6
            VDvnfDpnvFi3ZEUR1J8MOeW_VFsayrT9sNjyjsz62Po4LzvQKQMKxq0dNwPNYuuS
            fUmb-YvmFguxDb3weYl8WS-
            48EIkP1h4b_KGU9x9n7a1fUOHrS02ATQZmaL8jUil7yLJqx5MiCsPr4pCAXV0doA
            4pwhs_FIw HTTP/1.1

[Figure 51](#figure-51): [Authentication Request using Request Object](#name-authentication-request-usin)

When the `trust_chain` header parameter is included, the `peer_trust_chain` header parameter MAY also be included to provide a Trust Chain between the OP and the Trust Anchor the RP selected. The Peer Trust Chain contains metadata and policy values that the RP has chosen for the OP to use during the registration. The Trust Anchors selected in both Trust Chains MUST be the same. Inclusion of both Trust Chains enables achieving the Federation Integrity and Metadata Integrity properties, as defined in \[[App-Fed-Linkage](#App-Fed-Linkage)\].[¶](#section-12.1.1.1.1-7)

###### [12.1.1.1.2.](#section-12.1.1.1.2) [Processing the Authentication Request](#name-processing-the-authenticati)

When the OP receives an incoming Authentication Request, the OP supports OpenID Federation, the incoming Client ID is a valid URL, and the OP does not have the Client ID registered as a known client, then the OP SHOULD resolve the Trust Chains related to the requestor.[¶](#section-12.1.1.1.2-1)

An RP MAY include a Trust Chain from itself to the Trust Anchor that it selected in the Request Object using the `trust_chain` header parameter defined in [Section 4.3](#trust_chain_head_param). If the OP does not have a valid registration for the RP or its registration has expired, the OP MAY use the received Trust Chain as a hint for which path to take from the RP\'s Entity to the Trust Anchor. The OP MAY evaluate the statements in the provided Trust Chain to make its Federation Entity Discovery procedure more efficient, especially if the RP contains multiple authority hints in its Entity Configuration. If the OP already has a valid registration for the RP, it MAY use the received Trust Chain to update the RP\'s registration.[¶](#section-12.1.1.1.2-2)

A Trust Chain may be relied upon by the OP because it has validated all of its statements. This is true whether these statements are retrieved from their URLs or whether they are provided via the `trust_chain` request parameter in the Request Object. In both cases, the OP MUST fully verify the Trust Chain including every Entity Statement contained in it.[¶](#section-12.1.1.1.2-3)

The RP MAY likewise include a Trust Chain from the OP to the Trust Anchor that the RP selected in the Request Object using the `peer_trust_chain` header parameter defined in [Section 4.4](#peer_trust_chain_head_param). The OP SHOULD use the metadata and policy values that the RP has chosen during the registration. Inclusion of both Trust Chains enables achieving the Federation Integrity and Metadata Integrity properties, as defined in \[[App-Fed-Linkage](#App-Fed-Linkage)\].[¶](#section-12.1.1.1.2-4)

If the RP does not include the `trust_chain` header parameter in the Request Object, the OP for some reason decides not to use the provided Trust Chain, or the OP does not support this feature, it then MUST validate the possible Trust Chains, starting with the RP\'s Entity Configuration, as described in [Section 10.1](#fetching-es), and resolve the RP metadata with Entity Type `openid_relying_party`.[¶](#section-12.1.1.1.2-5)

The OP SHOULD furthermore verify that the Resolved Metadata of the RP complies with the client metadata specification [OpenID Connect Dynamic Client Registration 1.0](#OpenID.Registration) \[[OpenID.Registration](#OpenID.Registration)\].[¶](#section-12.1.1.1.2-6)

Once the OP has the RP\'s metadata, it MUST verify that the client was actually the one sending the Authentication Request by verifying the signature of the Request Object using the key material the client published in its metadata for the `openid_relying_party` Entity Type. If the signature does not verify, the OP MUST reject the request.[¶](#section-12.1.1.1.2-7)

##### [12.1.1.2.](#section-12.1.1.2) [Using Pushed Authorization](#name-using-pushed-authorization)

[Pushed Authorization Requests](#RFC9126) \[[RFC9126](#RFC9126)\] provide an interoperable way to push authentication request parameters directly to the AS in exchange for a one-time-use `request_uri`. The standard PAR metadata parameters are used in the RP and OP metadata to indicate its use.[¶](#section-12.1.1.2-1)

When using PAR with Automatic Registration, either a Request Object MUST be used as a PAR parameter, with the Request Object being as described in [Section 12.1.1.1](#UsingAuthzRequestObject), or a client authentication method for the PAR endpoint MUST be used that proves possession of one of the RP\'s private keys. Furthermore, the corresponding public key MUST be in the Entity\'s RP JWK Set \[[RFC7517](#RFC7517)\].[¶](#section-12.1.1.2-2)

The two applicable PAR client authentication methods are:[¶](#section-12.1.1.2-3)

-   JWT Client authentication, as described for `private_key_jwt` in Section 9 of [OpenID Connect Core 1.0](#OpenID.Core) \[[OpenID.Core](#OpenID.Core)\]. In this case, the audience of the client authentication JWT MUST be the Entity Identifier of the OP and MUST NOT include any other values.[¶](#section-12.1.1.2-4.1.1)

-   mTLS using self-signed certificates, as described in Section 2.2 of \[[RFC8705](#RFC8705)\]. In this case, the self-signed certificate MUST be present as the value of an `x5c` Claim for a key in the Entity\'s RP JWK Set. In this case, the server MUST omit certificate chain validation.[¶](#section-12.1.1.2-4.2.1)

A Pushed Authorization Request to the OP could look like this:[¶](#section-12.1.1.2-5)

    POST /par HTTP/1.1
    Host: op.example.org
    Content-Type: application/x-www-form-urlencoded

    redirect_uri=https%3A%2F%2Frp.example.com%2Fauthz_cb
    &scope=openid+profile+email+address+phone
    &response_type=code
    &nonce=4LX0mFMxdBjkGmtx7a8WIOnB
    &state=YmX8PM9I7WbNoMnnieKKBiptVW0sP2OZ
    &client_id=https%3A%2F%2Frp.example.com
    &client_assertion_type=urn%3Aietf%3Aparams%3Aoauth%3A
      client-assertion-type%3Ajwt-bearer
    &client_assertion=eyJhbGciOiJSUzI1NiIsImtpZCI6ImRVTjJ
      hMDF3Umtoa1NXcGxRVGh2Y1ZCSU5VSXdUVWRPVUZVMlRtVnJTbW
      hFUVhnelpYbHBUemRRTkEifQ.
      eyJzdWIiOiAiaHR0cHM6Ly9ycC5leGFtcGxlLmNvbSIsICJpc3M
      iOiAiaHR0cHM6Ly9ycC5leGFtcGxlLmNvbSIsICJpYXQiOiAxNT
      g5NzA0NzAxLCAiZXhwIjogMTU4OTcwNDc2MSwgImF1ZCI6ICJod
      HRwczovL29wLmV4YW1wbGUub3JnIiwgImp0aSI6ICIzOWQ1YWU1
      NTJkOWM0OGYwYjkxMmRjNTU2OGVkNTBkNiJ9.
      oUt9Knx_lxb4V2S0tyNFH
      CNZeP7sImBy5XDsFxv1cUpGkAojNXSy2dnU5HEzscMgNW4wguz6
      KDkC01aq5OfN04SuVItS66bsx0h4Gs7grKAp_51bClzreBVzU4g
      _-dFTgF15T9VLIgM_juFNPA_g4Lx7Eb5r37rWTUrzXdmfxeou0X
      FC2p9BIqItU3m9gmH0ojdBCUX5Up0iDsys6_npYomqitAcvaBRD
      PiuUBa5Iar9HVR-H7FMAr7aq7s-dH5gx2CHIfM3-qlc2-_Apsy0
      BrQl6VePR6j-3q6JCWvNw7l4_F2UpHeanHb31fLKQbK-1yoXDNz
      DwA7B0ZqmuSmMFQ

[Figure 52](#figure-52): [Pushed Authorization Request to the OP](#name-pushed-authorization-reques)

###### [12.1.1.2.1.](#section-12.1.1.2.1) [Processing the Pushed Authentication Request](#name-processing-the-pushed-authe)

The requirements specified in [Section 12.1.1.1.2](#AuthzRequestProcessing) also apply to [Pushed Authorization Requests](#RFC9126) \[[RFC9126](#RFC9126)\].[¶](#section-12.1.1.2.1-1)

Once the OP has the RP\'s metadata, it MUST verify the client is using the keys published for the `openid_relying_party` Entity Type Identifier. If the RP does not verify, the OP MUST reject the request.[¶](#section-12.1.1.2.1-2)

The means of verification depends on the client authentication method used:[¶](#section-12.1.1.2.1-3)

private_key_jwt
:   If this method is used, then the OP verifies the signature of the signed JWT using the key material published by the RP in its metadata. If the authentication is successful, then the registration is valid. The audience of the signed JWT MUST be the Authorization Server\'s Entity Identifier and MUST NOT include any other values.[¶](#section-12.1.1.2.1-4.2)
:   

self_signed_tls_client_auth
:   If mTLS is used with a self-signed certificate, then the certificate MUST be present as the value of an `x5c` Claim for a key in the JWK Set containing the RP\'s keys.[¶](#section-12.1.1.2.1-4.4)
:   

#### [12.1.2.](#section-12.1.2) [Successful Authentication Response](#name-successful-authentication-r)

The response to a successful authentication request when using Automatic Registration is the same as the successful authentication responses defined in \[[OpenID.Core](#OpenID.Core)\]. It is a successful OAuth 2.0 authorization response sent to the Client\'s redirection URI.[¶](#section-12.1.2-1)

#### [12.1.3.](#section-12.1.3) [Authentication Error Response](#name-authentication-error-respon)

The error response to an unsuccessful authentication request when using Automatic Registration is the same as the error authentication responses defined in \[[OpenID.Core](#OpenID.Core)\]. It is an OAuth 2.0 authorization error response sent to the Client\'s redirection URI, unless a [Pushed Authorization Request](#RFC9126) \[[RFC9126](#RFC9126)\] was used for the request.[¶](#section-12.1.3-1)

However, as in both \[[OpenID.Core](#OpenID.Core)\] and \[[RFC6749](#RFC6749)\], if the redirection URI is invalid, redirection MUST NOT be performed, and instead the Authorization Server SHOULD inform the End-User of the error in the user interface. The Authorization Server MAY also choose to do this if it has reason to believe that the redirection URI might be being used as a component of an open redirector.[¶](#section-12.1.3-2)

If the OP fails to establish trust with the RP or finds the RP metadata to be invalid or in conflict with metadata policy, it MUST treat the redirection URI as invalid and not perform redirection. This means that the error codes `invalid_trust_anchor`, `invalid_trust_chain`, and `invalid_metadata`, which are about reasons trust failed to be established, SHOULD only be returned in [Pushed Authorization Request](#RFC9126) \[[RFC9126](#RFC9126)\] error responses, and not to the Client\'s redirection URI.[¶](#section-12.1.3-3)

In addition to the error codes contained in the IANA \"OAuth Extensions Error Registry\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\], this specification also defines the error codes in [Section 8.9](#error_response), which MAY also be used.[¶](#section-12.1.3-4)

The following is a non-normative example authentication error response:[¶](#section-12.1.3-5)

    HTTP/1.1 302 Found
      Location: https://client.example.org/cb?
        error=consent_required
        &error_description=
          Consent%20by%20the%20End-User%20required
        &state=af0ifjsldkj

[Figure 53](#figure-53): [Authentication Error Response](#name-authentication-error-respons)

#### [12.1.4.](#section-12.1.4) [Automatic Registration and Client Authentication](#name-automatic-registration-and-)

Note that when using Automatic Registration, the client authentication methods that the client can use are declared to the OP using RP Metadata parameters: either the `token_endpoint_auth_methods_supported` parameter defined in \[[OpenID.RP.Choices](#OpenID.RP.Choices)\] or the `token_endpoint_auth_method` parameter. Those that the OP can use are likewise declared to the RP using OP Metadata parameters. However, if there are multiple methods supported by both the RP and the OP, the OP does not know which one the RP will pick in advance of it being used, since this is not declared at the time the Automatic Registration occurs.[¶](#section-12.1.4-1)

OPs SHOULD accept any client authentication method that is mutually supported and RPs MUST only use mutually supported methods. Because some OPs may be coded in such a way that they expect the RP to always use the same client authentication method for subsequent interactions, note that interoperability may be improved by the RP doing so.[¶](#section-12.1.4-2)

#### [12.1.5.](#section-12.1.5) [Possible Other Uses of Automatic Registration](#name-possible-other-uses-of-auto)

Automatic Registration is designed to be able to be employed for OAuth 2.0 use cases beyond OpenID Connect, as noted in [Section 12](#client_registration). For instance, ecosystems using bare [OAuth 2.0](#RFC6749) \[[RFC6749](#RFC6749)\] or [FAPI](#FAPI) \[[FAPI](#FAPI)\] can utilize Automatic Registration.[¶](#section-12.1.5-1)

Also note that Client ID values that are Entity Identifiers could be used to identify clients using Automatic Registration at endpoints other than the Authorization Endpoint and Token Endpoint in OAuth 2.0 deployments, such as the Pushed Authorization Request (PAR) Endpoint and Introspection Endpoint. Describing particular such scenarios is beyond the scope of this specification.[¶](#section-12.1.5-2)

### [12.2.](#section-12.2) [Explicit Registration](#name-explicit-registration)

Using this method, the RP establishes its client registration with the OP by means of a dedicated registration request, similar to \[[OpenID.Registration](#OpenID.Registration)\], but instead of its metadata, the RP submits its Entity Configuration or an entire Trust Chain. When the Explicit Registration is completed, the RP can proceed to make regular OpenID authentication requests to the OP.[¶](#section-12.2-1)

An OP that supports Explicit Registration MUST include the `explicit` keyword in its `client_registration_types_supported` metadata parameter and set the `federation_registration_endpoint` metadata parameter to the URL at which it receives Explicit Registration requests.[¶](#section-12.2-2)

Explicit Registration is suitable for implementation on top of the [OpenID Connect Dynamic Client Registration 1.0](#OpenID.Registration) \[[OpenID.Registration](#OpenID.Registration)\] endpoint of an OP deployment. In contrast to Automatic Registration, it enables an OP to provision a Client ID, potentially a Client Secret, and other metadata parameters.[¶](#section-12.2-3)

An example of an Explicit Registration is provided in [Appendix A.3.2](#ExplicitRegExample).[¶](#section-12.2-4)

#### [12.2.1.](#section-12.2.1) [Explicit Client Registration Request](#name-explicit-client-registratio)

The RP performs Explicit Client Registration as follows:[¶](#section-12.2.1-1)

1.  Once the RP has determined a set of Trust Anchors it has in common with the OP, it chooses the subset it wants to proceed with. This may be a single Trust Anchor, or it can also be more than one. The RP MUST perform Trust Chain and metadata resolution for the OP, as specified in [Section 10](#resolving_trust). If the resolution is not successful, the RP MUST abort the request.[¶](#section-12.2.1-2.1.1)

2.  Using this subset of Trust Anchors, the RP chooses a set of `authority_hints` from the hints that are available to it. Each hint MUST, when used as a starting point for Trust Chain collection, lead to at least one of the Trust Anchors in the subset. If the RP has more than one Trust Anchor in common with the OP it MUST select a subset of Trust Anchors to proceed with. The subset may be as small as a single Trust Anchor, or include multiple ones.[¶](#section-12.2.1-2.2.1)

3.  The RP will then construct its Entity Configuration, where the metadata statement chosen is influenced by the OP\'s metadata and where the `authority_hints` included are picked by the process described above. From its Immediate Superiors, the RP MUST select one or more `authority_hints` so that every hint, when used as the starting point for a Trust Chain collection, leads to at least one of the Trust Anchors in the subset selected above.[¶](#section-12.2.1-2.3.1)

4.  The RP MAY include its Entity Configuration in a Trust Chain regarding itself. There are two ways to do this. The first way is for the Registration Request to contain an array with the sequence of Entity Statements that compose the Trust Chain between the RP that is making the request and the selected Trust Anchor. The second way is to use the `trust_chain` header parameter, as specified in [Section 4.3](#trust_chain_head_param).[¶](#section-12.2.1-2.4.1)

    NOTE: The use of the `trust_chain` header parameter in the request JWT is RECOMMENDED over the first syntax; it is retained for historical reasons.[¶](#section-12.2.1-2.4.2)

5.  The RP MUST include its metadata in its Entity Configuration and use the `authority_hints` selected above.[¶](#section-12.2.1-2.5.1)

    The RP SHOULD select its metadata parameters to comply with the OP\'s Resolved Metadata and thus ensure a successful registration with the OP. Note that if the submitted RP metadata is not compliant with the metadata of the OP, the OP may choose to modify it to make it compliant rather than reject the request with an error response.[¶](#section-12.2.1-2.5.2)

6.  The Entity Configuration, or the entire Trust Chain, is sent, using HTTP POST, to the `federation_registration_endpoint`. The Entity Configuration or Trust Chain is the entire POST body. The RP MUST sign its Entity Configuration with a current Federation Entity Key in its possession.[¶](#section-12.2.1-2.6.1)

7.  The content type of the Registration Request MUST be `application/entity-statement+jwt` when it is the Entity Configuration of the requestor. Otherwise, when it is a Trust Chain, the content type of the Registration Request MUST be `application/trust-chain+json`. The RP MAY include its Entity Configuration in a Trust Chain that leads to the RP. In this case, the registration request will contain an array consisting of the sequence of statements that make up the Trust Chain between the RP and the Trust Anchor the RP selected.[¶](#section-12.2.1-2.7.1)

8.  The `trust_chain` header parameter MAY be included to provide a Trust Chain between the RP and the Trust Anchor the RP selected when the request is the Entity Configuration of the RP. This is equivalent to providing the Trust Chain as the request body. When the `trust_chain` header parameter is included, the `peer_trust_chain` header parameter MAY also be included to provide a Trust Chain between the OP and the Trust Anchor the RP selected. The Peer Trust Chain contains metadata and policy values that the RP has chosen for the OP to use during the registration. The `peer_trust_chain` header parameter MUST NOT be used when the request body is a Trust Chain. The Trust Anchors selected in both Trust Chains MUST be the same. Inclusion of both Trust Chains enables achieving the Federation Integrity and Metadata Integrity properties, as defined in \[[App-Fed-Linkage](#App-Fed-Linkage)\].[¶](#section-12.2.1-2.8.1)

The following Entity Configuration Claims are specified for use in Explicit Registration requests. Their full descriptions are in [Section 3](#entity-statement).[¶](#section-12.2.1-3)

iss
:   REQUIRED. Its value MUST be the Entity Identifier of the RP.[¶](#section-12.2.1-4.2)
:   

sub
:   REQUIRED. Its value MUST be the Entity Identifier of the RP.[¶](#section-12.2.1-4.4)
:   

iat
:   REQUIRED.[¶](#section-12.2.1-4.6)
:   

exp
:   REQUIRED.[¶](#section-12.2.1-4.8)
:   

jwks
:   REQUIRED.[¶](#section-12.2.1-4.10)
:   

aud
:   REQUIRED. The \"aud\" (audience) value MUST be the Entity Identifier of the OP and MUST NOT include any other values. This Claim is used in Explicit Registration requests; it is not a general Entity Statement Claim.[¶](#section-12.2.1-4.12)
:   

authority_hints
:   REQUIRED.[¶](#section-12.2.1-4.14)
:   

metadata
:   REQUIRED. It MUST contain the RP metadata under the `openid_relying_party` Entity Type Identifier.[¶](#section-12.2.1-4.16)
:   

crit
:   OPTIONAL.[¶](#section-12.2.1-4.18)
:   

trust_marks
:   OPTIONAL.[¶](#section-12.2.1-4.20)
:   

The request MUST be an HTTP request to the `federation_registration_endpoint` of the OP, using the POST method.[¶](#section-12.2.1-5)

When the RP submits an Entity Configuration the content type of the request MUST be `application/entity-statement+jwt`. When the RP submits a Trust Chain, the content type MUST be `application/trust-chain+json`.[¶](#section-12.2.1-6)

#### [12.2.2.](#section-12.2.2) [Processing Explicit Client Registration Request by OP](#name-processing-explicit-client-)

The OP processes the request as follows:[¶](#section-12.2.2-1)

1.  Upon receiving a registration request, the OP MUST inspect the content type to determine whether it contains an Entity Configuration or an entire Trust Chain.[¶](#section-12.2.2-2.1.1)

2.  The OP MUST validate the RP\'s explicit registration request JWT. All the normal Entity Statement validation rules apply. In addition, if the `aud` (audience) Claim Value is not the Entity Identifier of the OP, then the request MUST be rejected.[¶](#section-12.2.2-2.2.1)

3.  If no Trust Chain is provided from the RP to the Trust Anchor in the request, the OP MUST use the provided Entity Configuration to complete the Federation Entity Discovery by collecting and evaluating the Trust Chains that start with the `authority_hints` in the Entity Configuration of the RP. After validating at least one Trust Chain, the OP MUST verify the signature of the received Entity Configuration. If the OP finds more than one acceptable Trust Chain, it MUST choose one Trust Chain from among them as the one to proceed with.[¶](#section-12.2.2-2.3.1)

4.  If the request body is a Trust Chain, the OP MAY evaluate the statements in the Trust Chain to save the HTTP calls that are necessary to perform the Federation Entity Discovery, especially if the RP included more than one authority hint in its Entity Configuration. Otherwise, the OP MUST extract the RP\'s Entity Configuration from the Trust Chain and proceed according to Step 3, as if only an Entity Configuration was received.[¶](#section-12.2.2-2.4.1)

5.  When a Trust Chain is provided using the `trust_chain` header parameter in the request\'s Entity Configuration, the OP MAY likewise evaluate the statements in the Trust Chain to save the HTTP calls that are necessary to perform the Federation Entity Discovery. Note that in this case, the RP\'s Entity Configuration in the Trust Chain is only used to establish that there is a path from the RP to the Trust Anchor; it is the metadata, etc. in the request Entity Configuration, which may be tailored by the RP for the OP, that is used when processing the registration request. If the provided Trust Chain is not used, the OP MUST proceed according to Step 3, using the request Entity Configuration.[¶](#section-12.2.2-2.5.1)

6.  If the request uses the `peer_trust_chain` header parameter to include a Trust Chain from the OP to the Trust Anchor that the RP selected, verify that it begins at the OP. Furthermore, verify that it ends at the same Trust Anchor as any provided Trust chain from the RP to the Trust Anchor. The OP SHOULD use the metadata and policy values that the RP has chosen during the registration.[¶](#section-12.2.2-2.6.1)

7.  At this point, if the OP finds that it already has an existing client registration for the requesting RP, then that registration MUST be invalidated. The precise time of the invalidation is at the OP\'s discretion, as the OP may want to ensure the completion of concurrent OpenID authentication requests initiated by the RP while the registration request is being processed.[¶](#section-12.2.2-2.7.1)

    The OP MAY retain client credentials and key material from the invalidated registration in order to verify past RP signatures and perform other cryptographic operations on past RP data.[¶](#section-12.2.2-2.7.2)

8.  The OP uses the Resolved Metadata for the RP to create a client registration compliant with its own OP metadata and other applicable policies.[¶](#section-12.2.2-2.8.1)

    The OP MAY provision the RP with a `client_id` other than the RP\'s Entity Identifier. This enables Explicit Registration to be implemented on top of the [OpenID Connect Dynamic Client Registration 1.0](#OpenID.Registration) \[[OpenID.Registration](#OpenID.Registration)\] endpoint of an OP.[¶](#section-12.2.2-2.8.2)

    If the RP is provisioned with a `client_secret` it MUST NOT expire before the expiration of the registration Entity Statement that will be returned to the RP.[¶](#section-12.2.2-2.8.3)

    The OP SHOULD NOT provision the RP with a `registration_access_token` and a `registration_client_uri` because the expected way for the RP to update its registration is to make a new Explicit Registration request. If the RP is provisioned with a `registration_access_token` for some purpose, for example, to let it independently check its registered metadata, the token MUST NOT allow modification of the registration.[¶](#section-12.2.2-2.8.4)

    The OP MAY modify the received RP metadata, for example, by substituting an invalid or unsupported parameter, to make it compliant with its own OP metadata and other policies. If the OP does not accept the RP metadata or is unwilling to modify it to make it compliant, it MUST return a client registration error response, with an appropriate error, such as `invalid_client_metadata` or `invalid_redirect_uri`, as specified in Section 3.3 of \[[OpenID.Registration](#OpenID.Registration)\].[¶](#section-12.2.2-2.8.5)

9.  The OP MUST assign an expiration time to the created registration. This time MUST NOT exceed the expiration time of the Trust Chain that the OP selected to process the request.[¶](#section-12.2.2-2.9.1)

#### [12.2.3.](#section-12.2.3) [Successful Explicit Client Registration Response](#name-successful-explicit-client-)

If the OP created a client registration for the RP, it MUST then construct a success response in the form of an Entity Statement.[¶](#section-12.2.3-1)

The OP MUST set the `trust_anchor` Claim of the Entity Statement to the Trust Anchor it selected to process the request. The `authority_hints` Claim MUST be set to the RP\'s Immediate Superior in the selected Trust Chain.[¶](#section-12.2.3-2)

The OP MUST set the `exp` Claim to the expiration time of the created registration. The OP MAY choose to invalidate the registration before that, as explained in [Section 12.2.6](#AfterExplicitReg).[¶](#section-12.2.3-3)

The OP MUST express the client registration it created for the RP by means of the `metadata` Claim, by placing the metadata parameters under the `openid_relying_party` Entity Type Identifier. The parameters MUST include the `client_id` that was provisioned for the RP. If the RP was provisioned with credentials, for example, a `client_secret`, these MUST be included as well.[¶](#section-12.2.3-4)

The OP SHOULD include metadata parameters that have a default value, for example `token_endpoint_auth_method` which has a default value of `client_secret_basic`, to simplify the processing of the response by the RP.[¶](#section-12.2.3-5)

The OP MUST sign the registration Entity Statement with a current Federation Entity Key in its possession.[¶](#section-12.2.3-6)

The following Entity Statement Claims are used in Explicit Registration responses, as specified in [Section 3](#entity-statement):[¶](#section-12.2.3-7)

iss
:   REQUIRED. Its value MUST be the Entity Identifier of the OP.[¶](#section-12.2.3-8.2)
:   

sub
:   REQUIRED. Its value MUST be the Entity Identifier of the RP.[¶](#section-12.2.3-8.4)
:   

iat
:   REQUIRED. Time when this statement was issued.[¶](#section-12.2.3-8.6)
:   

exp
:   REQUIRED. Expiration time after which the statement MUST NOT be accepted for processing.[¶](#section-12.2.3-8.8)
:   

jwks
:   OPTIONAL. If present, it MUST be a verbatim copy of the `jwks` Entity Statement Claim from the received Entity Configuration of the RP. Note that this is distinct from the identically named RP metadata parameter.[¶](#section-12.2.3-8.10)
:   

aud
:   REQUIRED. The \"aud\" (audience) value MUST be the Entity Identifier of the RP and MUST NOT include any other values. This Claim is used in Explicit Registration responses; it is not a general Entity Statement Claim.[¶](#section-12.2.3-8.12)
:   

trust_anchor

:   REQUIRED. Its value MUST be the Entity Identifier of the Trust Anchor that the OP selected to process the Explicit Registration request. If complete Trust Chains to the Trust Anchor selected by the RP were provided in the request and/or by using the `peer_trust_chain` header parameter, this MUST match the Trust Anchors at the roots of those Trust Chains. This Claim is specific to Explicit Registration responses; it is not a general Entity Statement Claim.[¶](#trust_anchor-claim)

:   

authority_hints
:   REQUIRED. It MUST be a single-element array, whose value references the Immediate Superior of the RP in the Trust Chain that the OP selected to process the request.[¶](#section-12.2.3-8.16)
:   

metadata
:   REQUIRED. It MUST contain the registered RP metadata under the `openid_relying_party` Entity Type Identifier.[¶](#section-12.2.3-8.18)
:   

crit
:   OPTIONAL. Set of Claims that MUST be understood and processed, as specified in [Section 3.1.1](#common-claims).[¶](#section-12.2.3-8.20)
:   

A successful response MUST have an HTTP status code 200 and the content type `application/explicit-registration-response+jwt`. Furthermore, the `typ` header parameter value in the response MUST be `explicit-registration-response+jwt` (and not `entity-statement+jwt`) to prevent cross-JWT confusion between the Explicit Registration response and other kinds of Entity Statements, per Section 3.11 of \[[RFC8725](#RFC8725)\].[¶](#section-12.2.3-9)

#### [12.2.4.](#section-12.2.4) [Explicit Client Registration Error Response](#name-explicit-client-registration)

For a client registration error, the response is as defined in [Section 8.9](#error_response) and MAY use errors defined there and in Section 3.3 of \[[OpenID.Registration](#OpenID.Registration)\] and Section 3.2.2 of \[[RFC7591](#RFC7591)\].[¶](#section-12.2.4-1)

#### [12.2.5.](#section-12.2.5) [Processing Explicit Client Registration Response by RP](#name-processing-explicit-client-r)

1.  If the response indicates success, the RP MUST verify that its content is a valid Entity Statement and issued by the OP.[¶](#section-12.2.5-1.1.1)

    The RP MUST ensure the signing Federation Entity Key used by the OP is present in the `jwks` Claim of the Subordinate Statement issued by the OP\'s Immediate Superior in a Trust Chain that the RP successfully resolved for the OP when it prepared the Explicit Registration request.[¶](#section-12.2.5-1.1.2)

2.  The RP MUST verify that the `aud` (audience) Claim Value is its Entity Identifier.[¶](#section-12.2.5-1.2.1)

3.  The RP MUST verify that the `trust_anchor` represents one of its own Trust Anchors. If complete Trust Chains to the Trust Anchor selected by the RP were provided in the request and/or by using the `peer_trust_chain` header parameter, the RP MUST verify that this matches the Trust Anchors at the root of those Trust Chains.[¶](#section-12.2.5-1.3.1)

4.  The RP MUST verify that at least one of the `authority_hints` it specified in the Explicit Registration request leads to the Trust Anchor that the OP set in the `trust_anchor` Claim.[¶](#section-12.2.5-1.4.1)

5.  The RP MUST first ensure that the information it was registered with at the OP contains the same set of Entity Types as the request does. After having collected a Trust Chain using the response Claim `trust_anchor` as the Entity Identifier for the Trust Anchor and `authority_hints` as starting points for the Trust Chain collection, the RP SHOULD verify that the response metadata for each entity type is valid by applying the resolved policies to the received metadata, as specified in [Section 6.1.4.1](#metadata_policy_resolution).[¶](#section-12.2.5-1.5.1)

6.  If the received registration Entity Statement does not pass the above checks, the RP MUST reject it. The RP MAY choose to retry the Explicit Registration request to work around a transient exception, for example, due to a recent change of Entity metadata or metadata policy causing temporary misalignment of metadata.[¶](#section-12.2.5-1.6.1)

#### [12.2.6.](#section-12.2.6) [Explicit Client Registration Lifetime](#name-explicit-client-registration-)

An RP can utilize the `exp` Claim of the registration Entity Statement to devise a suitable strategy for renewing its client registration. RP implementers should note that if the RP\'s registration has expired at the OP, this can cause OpenID Connect authentication requests, token requests, and/or UserInfo requests to fail. Renewing the RP\'s registration prior to its expiration can prevent such errors from occurring and ensure the end-user experience is not disrupted.[¶](#section-12.2.6-1)

An OP MAY invalidate a client registration before the expiration that is indicated in the registration Entity Statement for the RP. An example reason could be the OP leaving the federation that was used to register the RP.[¶](#section-12.2.6-2)

### [12.3.](#section-12.3) [Registration Validity and Trust Reevaluation](#name-registration-validity-and-t)

The validity of an Automatic or Explicit Registration at an OP MUST NOT exceed the lifetime of the Trust Chain the OP used to create the registration. An OP MAY choose to expire the registration at some earlier time, or choose to perform additional periodic reevaluations of the Trust Chain for the registered RP before the Trust Chain reaches its expiration time.[¶](#section-12.3-1)

Similarly, an RP that obtained an Automatic or Explicit Registration MUST NOT use it past the expiration of the Trust Chain the RP used to establish trust in the OP. For an RP using Automatic Registration, the trust in the OP MUST be successfully reevaluated before continuing to make requests to the OP. For an RP using Explicit Registration, the RP MUST successfully renew its registration. An RP MAY choose to perform additional periodic reevaluations of the Trust Chain for the OP before the Trust Chain reaches its expiration time.[¶](#section-12.3-2)

### [12.4.](#section-12.4) [Differences between Automatic Registration and Explicit Registration](#name-differences-between-automat)

The primary differences between Automatic Registration and Explicit Registration are:[¶](#section-12.4-1)

-   With Automatic Registration, there is no registration step prior to the Authentication Request, whereas with Explicit Registration, there is. ([OpenID Connect Dynamic Client Registration 1.0](#OpenID.Registration) \[[OpenID.Registration](#OpenID.Registration)\] and [OAuth 2.0 Dynamic Client Registration](#RFC7591) \[[RFC7591](#RFC7591)\] also employ a prior registration step.)[¶](#section-12.4-2.1.1)

-   With Automatic Registration, the Client ID value is the RP\'s Entity Identifier and is supplied to the OP by the RP, whereas with Explicit Registration, a Client ID is assigned by the OP and supplied to the RP.[¶](#section-12.4-2.2.1)

-   With Automatic Registration, the Client is authenticated by means of the RP proving that it controls a private key corresponding to one of its Entity Configuration\'s public keys. Whereas with Explicit Registration, a broader set of options is available for authenticating the Client, including the use of a Client Secret.[¶](#section-12.4-2.3.1)

### [12.5.](#section-12.5) [Rationale for Trust Chains in the Request](#name-rationale-for-trust-chains-)

Both Automatic and Explicit Client Registration support the submission of the Trust Chain embedded in the Request, calculated by the requestor, and related to itself. This provides the following benefits:[¶](#section-12.5-1)

-   It solves the problem of OPs using RP metadata that has become stale. This stale data may occur when the OP uses cached RP metadata from a Trust Chain that has not reached its expiration time yet. The RP MAY notify the OP that a change has taken place by including the `trust_chain` header parameter or the `trust_chain` request parameter in the request, thus letting the OP update its Client Registration and preventing potential temporary faults due to stale metadata.[¶](#section-12.5-2.1.1)

-   It enables the RP to pass a verifiable hint for which trust path to take to build the Trust Chain. This can reduce the costs of RP Federation Entity Discovery for OPs in complex federations where the RP has multiple Trust Anchors or the Trust Chain resolution may result in dead-ends.[¶](#section-12.5-2.2.1)

-   It enables direct passing of the Entity Configuration, including any present Trust Marks, thus saving the OP from having to make an HTTP request to the RP `/.well-known/openid-federation` endpoint.[¶](#section-12.5-2.3.1)

Both also support the submission of the Peer Trust Chain, which provides a Trust Chain between the OP and the Trust Anchor the RP selected. As described in [Section 4.4](#peer_trust_chain_head_param), inclusion of both Trust Chains enables achieving the Federation Integrity and Metadata Integrity properties, as defined in \[[App-Fed-Linkage](#App-Fed-Linkage)\].[¶](#section-12.5-3)

## [13.](#section-13) [General-Purpose JWT Claims](#name-general-purpose-jwt-claims)

This section defines general-purpose JWT Claims designed to be used by many different JWT profiles. They are also used in specific kinds of JWTs defined by this specification.[¶](#section-13-1)

### [13.1.](#section-13.1) [\"jwks\" (JSON Web Key Set) Claim](#name-jwks-json-web-key-set-claim)

The `jwks` (JSON Web Key Set) Claim value is a JWK Set, as defined in \[[RFC7517](#RFC7517)\]. It is used to convey a set of cryptographic keys. Use of this Claim is OPTIONAL.[¶](#section-13.1-1)

For instance, the `jwks` (JSON Web Key Set) Claim might be used to represent a set of signing keys for an application. This Claim is used in this specification as specified in [Section 3.1.1](#common-claims) to represent the public keys used to sign the Entity Statement.[¶](#section-13.1-2)

### [13.2.](#section-13.2) [\"metadata\" Claim](#name-metadata-claim)

The `metadata` Claim is used for conveying metadata pertaining to the JWT. Its value is a JSON object. The details of the metadata contained are application-specific. Use of this Claim is OPTIONAL.[¶](#section-13.2-1)

For instance, the `metadata` Claim might be used to represent a set of endpoint URLs and algorithm identifiers in an API description. This Claim is used in this specification as specified in [Section 3.1.1](#common-claims) to represent metadata about the Entity.[¶](#section-13.2-2)

### [13.3.](#section-13.3) [\"constraints\" Claim](#name-constraints-claim)

The `constraints` Claim is used for conveying constraints pertaining to the JWT. Its value is a JSON object. The details of the constraints contained are application-specific. Use of this Claim is OPTIONAL.[¶](#section-13.3-1)

For instance, this Claim is used in this specification as specified in [Section 3.1.3](#ss-specific) to represent constraints on the Trust Chain for the Entity.[¶](#section-13.3-2)

### [13.4.](#section-13.4) [\"crit\" (Critical) Claim](#name-crit-critical-claim)

The `crit` (critical) Claim indicates that extensions to the set of Claims specified for use in this type of JWT are being used that MUST be understood and processed. It is used in the same way that the `crit` header parameter is used for extension JOSE header parameters that MUST be understood and processed. Its value is an array listing the Claim Names present in the JWT that use those extensions. If any of the listed Claims are not understood and supported by the recipient, then the JWT is invalid. Producers MUST NOT include Claim Names already specified for use in this type of JWT, duplicate names, or names that do not occur as Claim Names in the JWT in the `crit` list. Producers MUST NOT use the empty array `[]` as the `crit` value. Use of this Claim is OPTIONAL.[¶](#section-13.4-1)

Section 4 of \[[RFC7519](#RFC7519)\] states that \"Specific applications of JWTs will require implementations to understand and process some claims in particular ways. However, in the absence of such requirements, all claims that are not understood by implementations MUST be ignored.\" Thus, for the `crit` (critical) Claim to be effective, the definition for the particular kind of JWT using it MUST specify that `crit` MUST be understood and processed, as allowed in the statement above.[¶](#section-13.4-2)

This Claim is used in this specification as specified in [Section 3.1.1](#common-claims) to identify Claims not defined by this specification that MUST be understood and processed when used in Entity Statements.[¶](#section-13.4-3)

### [13.5.](#section-13.5) [\"ref\" (Reference) Claim](#name-ref-reference-claim)

The `ref` (reference) Claim is used for conveying the URI for a resource pertaining to the JWT. It plays a similar role in a JWT as the `href` property does in HTML. The nature of the content at the referenced resource is generally application specific. The `ref` value is a case-sensitive string containing a URI value. Use of this Claim is OPTIONAL.[¶](#section-13.5-1)

For instance, a JWT referring to a contract between two parties might use the `ref` (reference) Claim to refer to a resource at which the contract terms can be read. This Claim is used in this specification as specified in [Section 7.1](#trust_mark_claims) to provide a URL referring to human-readable information about the issuance of the Trust Mark.[¶](#section-13.5-2)

### [13.6.](#section-13.6) [\"delegation\" Claim](#name-delegation-claim)

The `delegation` Claim expresses that authority is being delegated to the party referenced in the Claim Value. The `delegation` value is a case-sensitive string containing a StringOrURI value. Use of this Claim is OPTIONAL.[¶](#section-13.6-1)

For instance, the `delegation` Claim might be used to express that the referenced party may sign a legal document on behalf of the subject. This Claim is used in this specification as specified in [Section 7.1](#trust_mark_claims) to represent delegation of the right to issue Trust Marks with a particular identifier.[¶](#section-13.6-2)

Note that the `delegation` Claim is both syntactically and semantically distinct from the existing `act` Claim \[[IANA.JWT.Claims](#IANA.JWT.Claims)\]. `act` is a JSON object whereas `delegation` is a StringOrURI. Semantically, the Delegation JWT defined in [Section 7.2.1](#delegation_jwt) carries a signature by an issuer cryptographically proving the right to delegate to the party. The `act` Claim, carrying no signature, cannot achieve this.[¶](#section-13.6-3)

### [13.7.](#section-13.7) [\"logo_uri\" (Logo URI) Claim](#name-logo_uri-logo-uri-claim)

The `logo_uri` Claim value is a URI that references a logo pertaining to the JWT. Use of this Claim is OPTIONAL.[¶](#section-13.7-1)

For instance, the `logo_uri` Claim might be used to represent the location from which to retrieve the logo of an organization to display in a user interface. This Claim is used in this specification as specified in [Section 7.1](#trust_mark_claims) to convey a URL that references a logo for the Entity.[¶](#section-13.7-2)

## [14.](#section-14) [Claims Languages and Scripts](#name-claims-languages-and-script)

Human-readable Claim Values and Claim Values that reference human-readable values MAY be represented in multiple languages and scripts. This specification enables such representations in the same manner as defined in Section 5.2 of [OpenID Connect Core 1.0](#OpenID.Core) \[[OpenID.Core](#OpenID.Core)\].[¶](#section-14-1)

As described in OpenID Connect Core, to specify the languages and scripts, [BCP47](#RFC5646) \[[RFC5646](#RFC5646)\] language tags are added to member names, delimited by a `#` character. For example, `family_name#ja-Kana-JP` expresses the Family Name in Katakana in Japanese, which is commonly used to index and represent the phonetics of the Kanji representation of the same name represented as `family_name#ja-Hani-JP`.[¶](#section-14-2)

Language tags can be used in any data structures containing or referencing human-readable values, including metadata parameters and Trust Mark parameters. For instance, both `organization_name` and `organization_name#de` might occur together in metadata.[¶](#section-14-3)

## [15.](#section-15) [Media Types](#name-media-types)

These media types \[[RFC2046](#RFC2046)\] are defined by this specification.[¶](#section-15-1)

### [15.1.](#section-15.1) [\"application/entity-statement+jwt\" Media Type](#name-application-entity-statemen)

The `application/entity-statement+jwt` media type is used to specify that the associated content is an Entity Statement, as defined in [Section 3](#entity-statement). No parameters are used with this media type.[¶](#section-15.1-1)

### [15.2.](#section-15.2) [\"application/trust-mark+jwt\" Media Type](#name-application-trust-markjwt-m)

The `application/trust-mark+jwt` media type is used to specify that the associated content is a Trust Mark, as defined in [Section 7](#trust_marks). No parameters are used with this media type.[¶](#section-15.2-1)

### [15.3.](#section-15.3) [\"application/resolve-response+jwt\" Media Type](#name-application-resolve-respons)

The `application/resolve-response+jwt` media type is used to specify that the associated content is a Resolve Response, as defined in [Section 8.3.2](#resolve-response). No parameters are used with this media type.[¶](#section-15.3-1)

### [15.4.](#section-15.4) [\"application/trust-chain+json\" Media Type](#name-application-trust-chainjson)

The `application/trust-chain+json` media type is used to specify that the associated content is a JSON array representing a Trust Chain, as defined in [Section 4](#trust_chain). No parameters are used with this media type.[¶](#section-15.4-1)

### [15.5.](#section-15.5) [\"application/trust-mark-delegation+jwt\" Media Type](#name-application-trust-mark-dele)

The `application/trust-mark-delegation+jwt` media type is used to specify that the associated content is a Trust Mark delegation, as defined in [Section 7.2.1](#delegation_jwt). No parameters are used with this media type.[¶](#section-15.5-1)

### [15.6.](#section-15.6) [\"application/jwk-set+jwt\" Media Type](#name-application-jwk-setjwt-medi)

The `application/jwk-set+jwt` media type is used to specify that the associated content is a signed JWK Set, as defined in [Section 8.7.2](#HistKeysResp). No parameters are used with this media type.[¶](#section-15.6-1)

### [15.7.](#section-15.7) [\"application/trust-mark-status-response+jwt\" Media Type](#name-application-trust-mark-stat)

The `application/trust-mark-status-response+jwt` media type is used to specify that the associated content is a Trust Mark Status Response, as defined in [Section 8.4.2](#tm-status-response). No parameters are used with this media type.[¶](#section-15.7-1)

### [15.8.](#section-15.8) [\"application/explicit-registration-response+jwt\" Media Type](#name-application-explicit-regist)

The `application/explicit-registration-response+jwt` media type is used to specify that the associated content is an Explicit Registration response, as defined in [Section 12.2.3](#cliregresp). No parameters are used with this media type.[¶](#section-15.8-1)

## [16.](#section-16) [String Operations](#name-string-operations)

Processing some OpenID Federation messages requires comparing values in the messages to other values. For example, the Entity Identifier in an `iss` Claim might be compared to the Entity Identifier in a `sub` Claim. Comparing Unicode \[[UNICODE](#UNICODE)\] strings, however, has significant security implications.[¶](#section-16-1)

Therefore, comparisons between JSON strings and other Unicode strings MUST be performed as specified below:[¶](#section-16-2)

1.  Remove any JSON applied escaping to produce an array of Unicode code points.[¶](#section-16-3.1.1)

2.  Unicode Normalization \[[USA15](#USA15)\] MUST NOT be applied at any point to either the JSON string or to the string it is to be compared against.[¶](#section-16-3.2.1)

3.  Comparisons between the two strings MUST be performed as a Unicode code point to code point equality comparison.[¶](#section-16-3.3.1)

Note that this is the same comparison procedure as specified in Section 14 of [OpenID Connect Core 1.0](#OpenID.Core) \[[OpenID.Core](#OpenID.Core)\].[¶](#section-16-4)

## [17.](#section-17) [Implementation Considerations](#name-implementation-consideratio)

This section provides guidance to implementers and deployers of Federations on situations and properties that they should consider for their Federations.[¶](#section-17-1)

### [17.1.](#section-17.1) [Federation Topologies](#name-federation-topologies)

It is possible to construct Federation topologies that have multiple trust paths between Entities. The specification does not disallow this, but it can create ambiguities that deployers need to be aware of.[¶](#section-17.1-1)

Consider the following Federation topology:[¶](#section-17.1-2)

                  .--------------.
                  | Trust Anchor |
                  '--.---.-----.-'
                     |   |     |
                  .--'   '--.  '---------------.
                  |         |                  |
    .-------------v--.   .--v-------------.    |
    | Intermediate 1 |   | Intermediate 2 |    |
    '-------------.--'   '--.-------------'    |
                  |         |                .-v--.
                  |         |                | OP |
               .--v---------v---.            '----'
               | Intermediate 3 |
               '-------.--------'
                       |
                       |
                     .-v--.
                     | RP |
                     '----'

[Figure 54](#figure-54): [Example Topology with Multiple Trust Paths between Entities](#name-example-topology-with-multi)

In this topology, there are multiple trust paths between the RP and the Trust Anchor, meaning that multiple different Trust Chains could be built between them. If the metadata policies of Intermediate 1 and Intermediate 2 are different, this could result in the Resolved Metadata for the RP differing, depending upon which Intermediate is used when building the Trust Chain. Some such differences will be innocuous and some can cause failures.[¶](#section-17.1-4)

It is the job of the Federation architects to deploy topologies and metadata policies that work as intended, no matter which trust path is chosen when building a Trust Chain. Of course, one way to avoid potential ambiguities is to only use topologies that are trees, without multiple paths between two Entities. Topologies that are not trees are permitted, but should be used consciously and with care.[¶](#section-17.1-5)

Even when a Federation topology contains loops, Trust Chains built from them MUST NOT contain loops, as mandated in [Section 10.1](#fetching-es).[¶](#section-17.1-6)

### [17.2.](#section-17.2) [Federation Discovery and Trust Chain Resolution Patterns](#name-federation-discovery-and-tr)

This section describes different patterns that implementations may use for discovering entities within a federation and for resolving Trust Chains. It is important to distinguish between two related but distinct concepts:[¶](#section-17.2-1)

-   **Discovery**: The process of finding entities that are part of a federation, typically to build directories or catalogs of available service providers.[¶](#section-17.2-2.1.1)

-   **Trust Chain Resolution**: The process of building and validating a Trust Chain from a known entity to a Trust Anchor, as described in [Section 10](#resolving_trust). This process is also known as Federation Entity Discovery in this specification (per the definition in [Section 1.2](#Terminology)).[¶](#section-17.2-2.2.1)

While these patterns may involve both discovery and Trust Chain resolution, they serve different purposes and may be used independently, depending on the use case. For example, a discovery service building a directory of OpenID Providers may collect entity identifiers without necessarily resolving Trust Chains for all discovered entities, since the actual Trust Chain resolution may occur later on, for example, when an RP and OP engage in an authentication transaction, and use the Trust Chain resolution process described in [Section 10](#resolving_trust).[¶](#section-17.2-3)

Implementations may support one or more of the following patterns:[¶](#section-17.2-4)

-   [Bottom-Up Trust Chain Resolution](#bottom_up_discovery) ([Section 17.2.1](#bottom_up_discovery)): Resolving Trust Chains starting from a known entity identifier, as described in [Section 10](#resolving_trust).[¶](#section-17.2-5.1.1)

-   [Top-Down Discovery](#top_down_discovery) ([Section 17.2.2](#top_down_discovery)): Finding entities within a federation by starting from a Trust Anchor and traversing down the hierarchy.[¶](#section-17.2-5.2.1)

-   [Single Point of Trust Resolution](#single_point_discovery) ([Section 17.2.3](#single_point_discovery)): Delegating Trust Chain resolution to a trusted resolver implementing the Resolve Endpoint.[¶](#section-17.2-5.3.1)

Federation operators may choose to support multiple patterns to accommodate different use cases and integration scenarios. The choice of supported patterns affects the federation\'s usability and the types of applications that can effectively integrate with it.[¶](#section-17.2-6)

#### [17.2.1.](#section-17.2.1) [Bottom-Up Trust Chain Resolution](#name-bottom-up-trust-chain-resol)

Bottom-up Trust Chain resolution is the process described in [Section 10](#resolving_trust), also known as Federation Entity Discovery (see the definition in the Terminology section). This process starts with a known Entity Identifier and builds a Trust Chain by traversing up the federation hierarchy until reaching a Trust Anchor. This pattern is not discovery in the sense of finding unknown entities, but rather trust resolution for a known entity.[¶](#section-17.2.1-1)

This pattern is used when an entity needs to verify the trustworthiness of another entity whose Entity Identifier is already known. This is typically used for:[¶](#section-17.2.1-2)

-   OpenID Providers verifying Relying Parties during authentication requests,[¶](#section-17.2.1-3.1.1)

-   Resource servers verifying Client trustworthiness,[¶](#section-17.2.1-3.2.1)

-   Any Entity validating incoming requests from known parties, and[¶](#section-17.2.1-3.3.1)

-   Dynamic trust establishment in federated environments.[¶](#section-17.2.1-3.4.1)

The bottom-up Trust Chain resolution process follows these steps, as described in [Section 10](#resolving_trust):[¶](#section-17.2.1-4)

1.  Start with the subject entity\'s Entity Configuration (provided or fetched using the process defined in [Section 9](#federation_configuration)).[¶](#section-17.2.1-5.1.1)

2.  Use `authority_hints` to identify immediate superior entities.[¶](#section-17.2.1-5.2.1)

3.  Fetch Entity Configuration for each Superior Entity.[¶](#section-17.2.1-5.3.1)

4.  Use Fetch Endpoints (as defined in [Section 8.1.1](#fetch_statement)) to obtain Subordinate Statements about the subject entity.[¶](#section-17.2.1-5.4.1)

5.  Recursively traverse up the hierarchy until reaching a Trust Anchor.[¶](#section-17.2.1-5.5.1)

6.  Build and validate the complete Trust Chain.[¶](#section-17.2.1-5.6.1)

7.  Apply federation policies to derive Resolved Metadata.[¶](#section-17.2.1-5.7.1)

#### [17.2.2.](#section-17.2.2) [Top-Down Discovery](#name-top-down-discovery)

Top-down discovery is the process of finding entities that are part of a federation by starting from a known Trust Anchor and traversing down the federation hierarchy. This pattern is used when the goal is to discover available entities, particularly entities of specific Entity Types, without necessarily knowing their Entity Identifiers in advance.[¶](#section-17.2.2-1)

This pattern is particularly useful for:[¶](#section-17.2.2-2)

-   Service discovery by Relying Parties looking for OpenID Providers,[¶](#section-17.2.2-3.1.1)

-   Resource discovery by Clients looking for specific service types,[¶](#section-17.2.2-3.2.1)

-   Federation browsing and exploration, and[¶](#section-17.2.2-3.3.1)

-   Building provider directories and catalogs (e.g., WAYF services, Seamless Access).[¶](#section-17.2.2-3.4.1)

The top-down discovery process follows these steps:[¶](#section-17.2.2-4)

1.  Start with a known Trust Anchor that the discovering entity trusts.[¶](#section-17.2.2-5.1.1)

2.  Use the list endpoint (as defined in [Section 8.2](#entity_listing)) to discover Immediate Subordinate entities.[¶](#section-17.2.2-5.2.1)

3.  Filter by `entity_type` parameter to find protocol-specific providers (e.g., `openid_provider`).[¶](#section-17.2.2-5.3.1)

4.  For Intermediate entities, recursively traverse their Subordinates.[¶](#section-17.2.2-5.4.1)

5.  Collect Entity Identifiers and optionally Entity Configurations for discovered entities.[¶](#section-17.2.2-5.5.1)

Note that top-down discovery may or may not include Trust Chain resolution, depending on the use case. For example, when building a directory of OpenID Providers for user selection at login time, the discovery service may collect entity identifiers and basic metadata without resolving Trust Chains for all discovered entities. However, if the discovery service needs to verify that entities are properly registered in the federation before including them in the directory, it may choose to perform Trust Chain resolution as part of the discovery process.[¶](#section-17.2.2-6)

#### [17.2.3.](#section-17.2.3) [Single Point of Trust Resolution](#name-single-point-of-trust-resol)

Single point of trust resolution delegates the entire Trust Chain resolution process to a trusted resolver implementing the Resolve Endpoint defined in [Section 8.3](#resolve). This pattern allows entities to offload the complexity of Trust Chain resolution to a specialized service.[¶](#section-17.2.3-1)

This pattern is useful for:[¶](#section-17.2.3-2)

-   Entities that want to offload Trust Chain resolution complexity,[¶](#section-17.2.3-3.1.1)

-   Centralized trust evaluation services,[¶](#section-17.2.3-3.2.1)

-   Performance optimization by caching Resolved Metadata, and[¶](#section-17.2.3-3.3.1)

-   Simplified integration for lightweight clients.[¶](#section-17.2.3-3.4.1)

Note that this pattern requires the Entity Identifier of the subject entity to be known in advance. It does not provide discovery functionality in the sense of finding unknown entities within a federation.[¶](#section-17.2.3-4)

The single point of trust resolution process follows these steps:[¶](#section-17.2.3-5)

1.  Identify a trusted resolver with a Resolve Endpoint.[¶](#section-17.2.3-6.1.1)

2.  Submit the subject Entity Identifier and Trust Anchor to the resolver.[¶](#section-17.2.3-6.2.1)

3.  The resolver performs complete Trust Chain resolution internally (following the bottom-up pattern).[¶](#section-17.2.3-6.3.1)

4.  The resolver returns Resolved Metadata and Trust Marks.[¶](#section-17.2.3-6.4.1)

5.  Optionally verify the resolver\'s own Trust Chain.[¶](#section-17.2.3-6.5.1)

6.  Use Resolved Metadata for protocol operations.[¶](#section-17.2.3-6.6.1)

### [17.3.](#section-17.3) [Trust Anchors and Resolvers Go Together](#name-trust-anchors-and-resolvers)

If only one resolver is present in a federation, that entity should be both Trust Anchor and Resolver. If so, users of the resolver will not have to collect and evaluate Trust Chains for the Resolver. The Trust Anchor is by definition trusted and if the entity also serves as Resolver, that service will be implicitly trusted.[¶](#section-17.3-1)

### [17.4.](#section-17.4) [One Entity, One Service](#name-one-entity-one-service)

Apart from letting an entity provide both the Trust Anchor and Resolver services, there is a good reason for having each entity only do one thing. The reason is that, later in time, it will be much easier to share specific services between federations.[¶](#section-17.4-1)

### [17.5.](#section-17.5) [Trust Mark Policies](#name-trust-mark-policies)

When validating trust marks in an Entity Statement, it can be split into three parts.[¶](#section-17.5-1)

Validating Trust Marks in the Context of Validating an Entity Statement
:   According to the text on Entity Statement Validation in [Section 3.2](#ESValidation), validating a Trust Mark is confined to validating the syntax of the Claim Value, including that the `trust_mark_type` value is consistent.[¶](#section-17.5-2.2)
:   

Validating a Specific Trust Mark
:   This is what is described in [Section 7.3](#trust-mark-validation). In order to validate a Trust Mark, the entity must find a Trust Chain for the Trust Mark Issuer to a Trust Anchor the Entity trusts. This has nothing to do with which federation that will later be used for the application protocol.[¶](#section-17.5-2.4)
:   

Deciding which Trust Marks to Use

:   A federation MAY have a policy that states that only Trust Marks that match certain criteria SHOULD be used.[¶](#section-17.5-2.6.1)

    An example of such criteria could be that the Trust Mark\'s `trust_mark_type` must be listed in the Trust Anchor\'s `trust_mark_issuers`, and if so, that the instance\'s `iss` appears in the corresponding list of Entity Identifiers. Note that the list may be an empty list, which signifies that anyone can issue a Trust Mark with the `trust_mark_type` in question. Such Trust Marks can appear for various reasons, such as the Entity Configuration including Trust Marks associated with another federation, or Trust Marks intended for specific purposes or Entity audiences.[¶](#section-17.5-2.6.2)

    An Entity MAY also choose, at its own discretion, to utilize Trust Marks presented to it that are not recognized within the federation, and where the accreditation authority is established by an out-of-band mechanism.[¶](#section-17.5-2.6.3)

:   

### [17.6.](#section-17.6) [Related Specifications](#name-related-specifications)

These related specifications refactor the functionality defined in this specification into separate protocol-independent and protocol-specific specifications. The two specifications together are equivalent to this specification; no new functionality is added and none is removed. They can therefore be used interchangeably with this specification.[¶](#section-17.6-1)

-   [OpenID Federation 1.1](#OpenID.Federation-1.1) \[[OpenID.Federation-1.1](#OpenID.Federation-1.1)\] contains the protocol-independent functionality defined in this specification. This includes Entity Statements, Trust Chains, Metadata, Policies, Trust Marks, and Federation Endpoints.[¶](#section-17.6-2.1.1)

-   [OpenID Federation for OpenID Connect 1.1](#OpenID.Federation.Connect-1.1) \[[OpenID.Federation.Connect-1.1](#OpenID.Federation.Connect-1.1)\] contains the protocol-specific functionality defined in this specification. This includes Entity Type Identifiers and Metadata for OpenID Connect and OAuth 2.0 Entities and Client Registration flows.[¶](#section-17.6-2.2.1)

## [18.](#section-18) [Security Considerations](#name-security-considerations)

### [18.1.](#section-18.1) [Denial-of-Service Attack Prevention](#name-denial-of-service-attack-pr)

Some of the interfaces defined in this specification could be used for Denial-of-Service attacks (DoS), most notably, the resolve endpoint ([Section 8.3](#resolve)), Explicit Client Registration ([Section 12.2](#explicit)), and Automatic Client Registration ([Section 12.1](#automatic)) can be exploited as vectors of HTTP propagation attacks. Below is an explanation of how such an attack can occur and the countermeasures to prevent it.[¶](#section-18.1-1)

An adversary, providing hundreds of fake `authority_hints` in its Entity Configuration, could exploit the Federation Entity Discovery mechanism to propagate many HTTP requests. Imagine an adversary controlling an RP that sends an authorization request to an OP. For each request crafted by the adversary, the OP produces one request for the adversary\'s Entity Configuration and another one for each URL found in the `authority_hints`.[¶](#section-18.1-2)

If these endpoints are provided, some adequate defense methods are required, such as those described below and in \[[RFC4732](#RFC4732)\].[¶](#section-18.1-3)

Implementations should set a limit on the number of `authority_hints` they are willing to inspect. This is to protect against attacks where an adversary might define a large count of false `authority_hints` in their Entity Configuration.[¶](#section-18.1-4)

Entities may be required to include a Trust Chain in their requests, as explained in [Section 12.1.1.1](#UsingAuthzRequestObject). The static Trust Chain gives a predefined trust path, meaning that Federation Entity Discovery need not be performed. In this case, the propagation attacks will be prevented since the Trust Chain can be statically validated with a public key of the Trust Anchor.[¶](#section-18.1-5)

A Trust Mark can be statically validated using the public key of its issuer. The static validation of the Trust Marks represents a filter against propagation attacks. If the OpenID Provider (OP) discovers at least one valid Trust Mark within an Entity Configuration, this may serve as evidence of the reliability of the Relying Party that initiated the request. Given that the Trust Mark is optional, the decision to require one is at the discretion of the federation implementation, where a federation may define and require Trust Marks according to specific needs.[¶](#section-18.1-6)

If Client authentication is not required at the resolve endpoint, then incoming requests should not automatically trigger the collection (Federation Entity Discovery process) and assessment of Trust Chains. Instead, the resolve endpoint should only respond to unauthenticated Client requests with cached information about Entities that have already been evaluated and deemed trustworthy. The initiation of the Federation Entity Discovery process should not be the default action for the resolve endpoint in this case.[¶](#section-18.1-7)

Passing request objects by reference (using the `request_uri` request parameter) may not be supported by some deployments, as described in [Section 12.1.1](#authn-request), to eliminate a mechanism by which an attacker could otherwise require OPs to retrieve arbitrary content under the control of the attacker.[¶](#section-18.1-8)

### [18.2.](#section-18.2) [Unsigned Error Messages](#name-unsigned-error-messages)

One of the fundamental design goals of this protocol is to protect messages end-to-end. This cannot be accomplished by demanding TLS since TLS, in lots of cases, is not end-to-end but ends in an HTTPS to HTTP Reverse Proxy. Allowing unsigned error messages therefore opens an attack vector for someone who wants to run a Denial-of-Service attack. This is not specific to OpenID Federation but equally valid for other protocols when HTTPS to HTTP reverse proxies are used.[¶](#section-18.2-1)

## [19.](#section-19) [Privacy Considerations](#name-privacy-considerations)

Implementers should be aware of these privacy considerations.[¶](#section-19-1)

### [19.1.](#section-19.1) [Entity Statement Privacy Considerations](#name-entity-statement-privacy-co)

Entity Statements are designed to establish trust relationships between organizational entities within a federation, rather than between individuals or for specific business applications. Trust and reputation assessments for individuals or legal entities, such as those required for Know Your Customer and Anti-Money Laundering processes, should be managed through specialized platforms tailored for those purposes. Given that Entity Statements facilitate trust relationships using a public infrastructure, they should be limited to the essential information necessary for federation operations and organizational trust establishment.[¶](#section-19.1-1)

### [19.2.](#section-19.2) [Trust Mark Status Privacy Considerations](#name-trust-mark-status-privacy-c)

The Trust Mark Status endpoint enables querying the status of Trust Marks in real time. Similar to the Fetch endpoint, in cases where the Trust Mark Status endpoint is not protected by any client authentication method, requests to validate Trust Marks may not necessarily indicate an actual interaction or relationship between Entities, as they could simply be part of routine network inspection or discovery processes. This could potentially enable Trust Mark issuers to track Entities evaluating Trust Marks about other Entities through standard network diagnostic tools like IPv4/IPv6 addresses and DNS Whois entries. To mitigate tracking risks, implementations can use short-lived Trust Marks, or use the Trust Marked Entities Listing ([Section 8.5](#tm_listing)) with only the `trust_mark_type` parameter and not the `sub` parameter, reducing the need to use the Trust Mark Status endpoint.[¶](#section-19.2-1)

### [19.3.](#section-19.3) [Fetch Endpoint Privacy Considerations](#name-fetch-endpoint-privacy-cons)

The Fetch endpoint enables querying Subordinate Statements in real time. Similar to Trust Mark Status validation, in the cases where the federation infrastructure is public and widely browsable and endpoints are not protected by any client authentication method, requests to fetch Subordinate Statements may not necessarily indicate an actual interaction or relationship between Entities, as they could simply be part of routine network inspection or discovery processes. However, this could potentially enable Trust Anchors or Intermediates to track Entities evaluating trust relationships with other Entities through standard network diagnostic tools like IPv4/IPv6 addresses and DNS Whois entries. To mitigate tracking risks around Entities inspecting and interacting with other Entities, implementers should consider using static and short-lived Trust Chains where appropriate, which can reduce the need for real-time fetching of Subordinate Statements.[¶](#section-19.3-1)

## [20.](#section-20) [IANA Considerations](#name-iana-considerations)

### [20.1.](#section-20.1) [OAuth Dynamic Client Registration Metadata Registration](#name-oauth-dynamic-client-regist)

This specification registers the following client metadata entries in the IANA \"OAuth Dynamic Client Registration Metadata\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\] established by \[[RFC7591](#RFC7591)\].[¶](#section-20.1-1)

-   Client Metadata Name: `client_registration_types`[¶](#section-20.1-2.1.1)

-   Client Metadata Description: An array of strings specifying the client registration types the RP wants to use[¶](#section-20.1-2.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.1-2.3.1)

-   Specification Document(s): [Section 5.1.2](#RP_metadata) of this specification[¶](#section-20.1-2.4.1)

&nbsp;

-   Client Metadata Name: `signed_jwks_uri`[¶](#section-20.1-3.1.1)

-   Client Metadata Description: URL referencing a signed JWT having the client\'s JWK Set document as its payload[¶](#section-20.1-3.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.1-3.3.1)

-   Specification Document(s): [Section 5.2.1](#jwks_metadata) of this specification[¶](#section-20.1-3.4.1)

&nbsp;

-   Client Metadata Name: `organization_name`[¶](#section-20.1-4.1.1)

-   Client Metadata Description: Human-readable name representing the organization owning this client[¶](#section-20.1-4.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.1-4.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.1-4.4.1)

&nbsp;

-   Client Metadata Name: `description`[¶](#section-20.1-5.1.1)

-   Client Metadata Description: Human-readable brief description of this client presentable to the End-User[¶](#section-20.1-5.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.1-5.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.1-5.4.1)

&nbsp;

-   Client Metadata Name: `keywords`[¶](#section-20.1-6.1.1)

-   Client Metadata Description: JSON array with one or more strings representing search keywords, tags, categories, or labels that apply to this client[¶](#section-20.1-6.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.1-6.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.1-6.4.1)

&nbsp;

-   Client Metadata Name: `information_uri`[¶](#section-20.1-7.1.1)

-   Client Metadata Description: URL for documentation of additional information about this client viewable by the End-User[¶](#section-20.1-7.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.1-7.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.1-7.4.1)

&nbsp;

-   Client Metadata Name: `organization_uri`[¶](#section-20.1-8.1.1)

-   Client Metadata Description: URL of a Web page for the organization owning this client[¶](#section-20.1-8.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.1-8.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.1-8.4.1)

### [20.2.](#section-20.2) [OAuth Authorization Server Metadata Registration](#name-oauth-authorization-server-)

This specification registers the following metadata entries in the IANA \"OAuth Authorization Server Metadata\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\] established by \[[RFC8414](#RFC8414)\].[¶](#section-20.2-1)

-   Metadata Name: `client_registration_types_supported`[¶](#section-20.2-2.1.1)

-   Metadata Description: Client Registration Types Supported[¶](#section-20.2-2.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-2.3.1)

-   Specification Document(s): [Section 5.1.3](#OP_metadata) of this specification[¶](#section-20.2-2.4.1)

&nbsp;

-   Metadata Name: `federation_registration_endpoint`[¶](#section-20.2-3.1.1)

-   Metadata Description: Federation Registration Endpoint[¶](#section-20.2-3.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-3.3.1)

-   Specification Document(s): [Section 5.1.3](#OP_metadata) of this specification[¶](#section-20.2-3.4.1)

&nbsp;

-   Metadata Name: `signed_jwks_uri`[¶](#section-20.2-4.1.1)

-   Metadata Description: URL referencing a signed JWT having this authorization server\'s JWK Set document as its payload[¶](#section-20.2-4.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-4.3.1)

-   Specification Document(s): [Section 5.2.1](#jwks_metadata) of this specification[¶](#section-20.2-4.4.1)

&nbsp;

-   Metadata Name: `jwks`[¶](#section-20.2-5.1.1)

-   Metadata Description: JSON Web Key Set document, passed by value[¶](#section-20.2-5.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-5.3.1)

-   Specification Document(s): [Section 5.2.1](#jwks_metadata) of this specification[¶](#section-20.2-5.4.1)

&nbsp;

-   Metadata Name: `organization_name`[¶](#section-20.2-6.1.1)

-   Metadata Description: Human-readable name representing the organization owning this authorization server[¶](#section-20.2-6.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-6.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.2-6.4.1)

&nbsp;

-   Metadata Name: `display_name`[¶](#section-20.2-7.1.1)

-   Metadata Description: Human-readable name of the authorization server to be presented to the End-User[¶](#section-20.2-7.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-7.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.2-7.4.1)

&nbsp;

-   Metadata Name: `description`[¶](#section-20.2-8.1.1)

-   Metadata Description: Human-readable brief description of this authorization server presentable to the End-User[¶](#section-20.2-8.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-8.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.2-8.4.1)

&nbsp;

-   Metadata Name: `keywords`[¶](#section-20.2-9.1.1)

-   Metadata Description: JSON array with one or more strings representing search keywords, tags, categories, or labels that apply to this authorization server[¶](#section-20.2-9.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-9.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.2-9.4.1)

&nbsp;

-   Metadata Name: `contacts`[¶](#section-20.2-10.1.1)

-   Metadata Description: Array of strings representing ways to contact people responsible for this authorization server, typically email addresses[¶](#section-20.2-10.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-10.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.2-10.4.1)

&nbsp;

-   Metadata Name: `logo_uri`[¶](#section-20.2-11.1.1)

-   Metadata Description: URL that references a logo for the organization owning this authorization server[¶](#section-20.2-11.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-11.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.2-11.4.1)

&nbsp;

-   Metadata Name: `information_uri`[¶](#section-20.2-12.1.1)

-   Metadata Description: URL for documentation of additional information about this authorization server viewable by the End-User[¶](#section-20.2-12.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-12.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.2-12.4.1)

&nbsp;

-   Metadata Name: `organization_uri`[¶](#section-20.2-13.1.1)

-   Metadata Description: URL of a Web page for the organization owning this authorization server[¶](#section-20.2-13.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.2-13.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.2-13.4.1)

### [20.3.](#section-20.3) [OAuth Protected Resource Metadata Registration](#name-oauth-protected-resource-me)

This specification registers the following protected resource metadata entries in the IANA \"OAuth Protected Resource Metadata\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\] established by \[[RFC9728](#RFC9728)\].[¶](#section-20.3-1)

-   Metadata Name: `signed_jwks_uri`[¶](#section-20.3-2.1.1)

-   Metadata Description: URL referencing a signed JWT having the protected resource\'s JWK Set document as its payload[¶](#section-20.3-2.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.3-2.3.1)

-   Specification Document(s): [Section 5.2.1](#jwks_metadata) of this specification[¶](#section-20.3-2.4.1)

&nbsp;

-   Metadata Name: `jwks`[¶](#section-20.3-3.1.1)

-   Metadata Description: JSON Web Key Set document, passed by value[¶](#section-20.3-3.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.3-3.3.1)

-   Specification Document(s): [Section 5.2.1](#jwks_metadata) of this specification[¶](#section-20.3-3.4.1)

&nbsp;

-   Metadata Name: `organization_name`[¶](#section-20.3-4.1.1)

-   Metadata Description: Human-readable name representing the organization owning this protected resource[¶](#section-20.3-4.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.3-4.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.3-4.4.1)

&nbsp;

-   Metadata Name: `description`[¶](#section-20.3-5.1.1)

-   Metadata Description: Human-readable brief description of this protected resource presentable to the End-User[¶](#section-20.3-5.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.3-5.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.3-5.4.1)

&nbsp;

-   Metadata Name: `keywords`[¶](#section-20.3-6.1.1)

-   Metadata Description: JSON array with one or more strings representing search keywords, tags, categories, or labels that apply to this protected resource[¶](#section-20.3-6.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.3-6.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.3-6.4.1)

&nbsp;

-   Metadata Name: `contacts`[¶](#section-20.3-7.1.1)

-   Metadata Description: Array of strings representing ways to contact people responsible for this protected resource, typically email addresses[¶](#section-20.3-7.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.3-7.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.3-7.4.1)

&nbsp;

-   Metadata Name: `logo_uri`[¶](#section-20.3-8.1.1)

-   Metadata Description: URL that references a logo for the organization owning this protected resource[¶](#section-20.3-8.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.3-8.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.3-8.4.1)

&nbsp;

-   Metadata Name: `organization_uri`[¶](#section-20.3-9.1.1)

-   Metadata Description: URL of a Web page for the organization owning this protected resource[¶](#section-20.3-9.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.3-9.3.1)

-   Specification Document(s): [Section 5.2.2](#informational_metadata) of this specification[¶](#section-20.3-9.4.1)

### [20.4.](#section-20.4) [OAuth Parameters Registration](#name-oauth-parameters-registrati)

This specification registers the following parameter in the IANA \"OAuth Parameters\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\] established by \[[RFC6749](#RFC6749)\].[¶](#section-20.4-1)

-   Parameter Name: `trust_chain`[¶](#section-20.4-2.1.1)

-   Parameter Usage Location: authorization request[¶](#section-20.4-2.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.4-2.3.1)

-   Specification Document(s): [Section 12.1.1.1](#trust_chain-param) of this specification[¶](#section-20.4-2.4.1)

### [20.5.](#section-20.5) [OAuth Extensions Error Registration](#name-oauth-extensions-error-regi)

This specification registers the following values in the IANA \"OAuth Extensions Error Registry\" registry \[[IANA.OAuth.Parameters](#IANA.OAuth.Parameters)\] established by \[[RFC6749](#RFC6749)\].[¶](#section-20.5-1)

-   Name: invalid_client[¶](#section-20.5-2.1.1)

-   Usage Location: authorization endpoint[¶](#section-20.5-2.2.1)

-   Protocol Extension: OpenID Federation[¶](#section-20.5-2.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.5-2.4.1)

-   Reference: [Section 8.9](#error_response) of this specification[¶](#section-20.5-2.5.1)

&nbsp;

-   Name: invalid_issuer[¶](#section-20.5-3.1.1)

-   Usage Location: authorization endpoint[¶](#section-20.5-3.2.1)

-   Protocol Extension: OpenID Federation[¶](#section-20.5-3.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.5-3.4.1)

-   Reference: [Section 8.9](#error_response) of this specification[¶](#section-20.5-3.5.1)

&nbsp;

-   Name: invalid_subject[¶](#section-20.5-4.1.1)

-   Usage Location: authorization endpoint[¶](#section-20.5-4.2.1)

-   Protocol Extension: OpenID Federation[¶](#section-20.5-4.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.5-4.4.1)

-   Reference: [Section 8.9](#error_response) of this specification[¶](#section-20.5-4.5.1)

&nbsp;

-   Name: invalid_trust_anchor[¶](#section-20.5-5.1.1)

-   Usage Location: authorization endpoint[¶](#section-20.5-5.2.1)

-   Protocol Extension: OpenID Federation[¶](#section-20.5-5.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.5-5.4.1)

-   Reference: [Section 8.9](#error_response) of this specification[¶](#section-20.5-5.5.1)

&nbsp;

-   Name: invalid_trust_chain[¶](#section-20.5-6.1.1)

-   Usage Location: authorization endpoint[¶](#section-20.5-6.2.1)

-   Protocol Extension: OpenID Federation[¶](#section-20.5-6.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.5-6.4.1)

-   Reference: [Section 8.9](#error_response) of this specification[¶](#section-20.5-6.5.1)

&nbsp;

-   Name: invalid_metadata[¶](#section-20.5-7.1.1)

-   Usage Location: authorization endpoint[¶](#section-20.5-7.2.1)

-   Protocol Extension: OpenID Federation[¶](#section-20.5-7.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.5-7.4.1)

-   Reference: [Section 8.9](#error_response) of this specification[¶](#section-20.5-7.5.1)

&nbsp;

-   Name: not_found[¶](#section-20.5-8.1.1)

-   Usage Location: authorization endpoint[¶](#section-20.5-8.2.1)

-   Protocol Extension: OpenID Federation[¶](#section-20.5-8.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.5-8.4.1)

-   Reference: [Section 8.9](#error_response) of this specification[¶](#section-20.5-8.5.1)

&nbsp;

-   Name: unsupported_parameter[¶](#section-20.5-9.1.1)

-   Usage Location: authorization endpoint[¶](#section-20.5-9.2.1)

-   Protocol Extension: OpenID Federation[¶](#section-20.5-9.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.5-9.4.1)

-   Reference: [Section 8.9](#error_response) of this specification[¶](#section-20.5-9.5.1)

### [20.6.](#section-20.6) [JSON Web Signature and Encryption Header Parameters Registration](#name-json-web-signature-and-encr)

This specification registers the following JWS header parameters in the IANA \"JSON Web Signature and Encryption Header Parameters\" registry \[[IANA.JOSE](#IANA.JOSE)\] established by \[[RFC7515](#RFC7515)\].[¶](#section-20.6-1)

-   Header Parameter Name: `trust_chain`[¶](#section-20.6-2.1.1)

-   Header Parameter Description: OpenID Federation Trust Chain[¶](#section-20.6-2.2.1)

-   Header Parameter Usage Location: JWS[¶](#section-20.6-2.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.6-2.4.1)

-   Specification Document(s): [Section 4.3](#trust_chain_head_param) of this specification[¶](#section-20.6-2.5.1)

&nbsp;

-   Header Parameter Name: `peer_trust_chain`[¶](#section-20.6-3.1.1)

-   Header Parameter Description: OpenID Federation Peer Trust Chain[¶](#section-20.6-3.2.1)

-   Header Parameter Usage Location: JWS[¶](#section-20.6-3.3.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.6-3.4.1)

-   Specification Document(s): [Section 4.4](#peer_trust_chain_head_param) of this specification[¶](#section-20.6-3.5.1)

### [20.7.](#section-20.7) [JSON Web Key Parameters Registration](#name-json-web-key-parameters-reg)

This specification registers the following parameters in the IANA \"JSON Web Key Parameters\" registry \[[IANA.JOSE](#IANA.JOSE)\] established by \[[RFC7517](#RFC7517)\].[¶](#section-20.7-1)

-   Parameter Name: `iat`[¶](#section-20.7-2.1.1)

-   Parameter Description: Issued At, as defined in RFC 7519[¶](#section-20.7-2.2.1)

-   Used with \"kty\" Value(s): \*[¶](#section-20.7-2.3.1)

-   Parameter Information Class: Public[¶](#section-20.7-2.4.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.7-2.5.1)

-   Specification Document(s): [Section 8.7.2](#HistKeysResp) of this specification[¶](#section-20.7-2.6.1)

&nbsp;

-   Parameter Name: `nbf`[¶](#section-20.7-3.1.1)

-   Parameter Description: Not Before, as defined in RFC 7519[¶](#section-20.7-3.2.1)

-   Used with \"kty\" Value(s): \*[¶](#section-20.7-3.3.1)

-   Parameter Information Class: Public[¶](#section-20.7-3.4.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.7-3.5.1)

-   Specification Document(s): [Section 8.7.2](#HistKeysResp) of this specification[¶](#section-20.7-3.6.1)

&nbsp;

-   Parameter Name: `exp`[¶](#section-20.7-4.1.1)

-   Parameter Description: Expiration Time, as defined in RFC 7519[¶](#section-20.7-4.2.1)

-   Used with \"kty\" Value(s): \*[¶](#section-20.7-4.3.1)

-   Parameter Information Class: Public[¶](#section-20.7-4.4.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.7-4.5.1)

-   Specification Document(s): [Section 8.7.2](#HistKeysResp) of this specification[¶](#section-20.7-4.6.1)

&nbsp;

-   Parameter Name: `revoked`[¶](#section-20.7-5.1.1)

-   Parameter Description: Revoked Key Properties[¶](#section-20.7-5.2.1)

-   Used with \"kty\" Value(s): \*[¶](#section-20.7-5.3.1)

-   Parameter Information Class: Public[¶](#section-20.7-5.4.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.7-5.5.1)

-   Specification Document(s): [Section 8.7.2](#HistKeysResp) of this specification[¶](#section-20.7-5.6.1)

### [20.8.](#section-20.8) [JSON Web Token Claims Registration](#name-json-web-token-claims-regis)

This specification registers the following Claims in the IANA \"JSON Web Token Claims\" registry \[[IANA.JWT.Claims](#IANA.JWT.Claims)\] established by \[[RFC7519](#RFC7519)\].[¶](#section-20.8-1)

-   Claim Name: `jwks`[¶](#section-20.8-2.1.1)

-   Claim Description: JSON Web Key Set[¶](#section-20.8-2.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-2.3.1)

-   Specification Document(s): [Section 13.1](#jwksClaim) of this specification[¶](#section-20.8-2.4.1)

&nbsp;

-   Claim Name: `metadata`[¶](#section-20.8-3.1.1)

-   Claim Description: Metadata object[¶](#section-20.8-3.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-3.3.1)

-   Specification Document(s): [Section 13.2](#metadataClaim) of this specification[¶](#section-20.8-3.4.1)

&nbsp;

-   Claim Name: `constraints`[¶](#section-20.8-4.1.1)

-   Claim Description: Constraints object[¶](#section-20.8-4.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-4.3.1)

-   Specification Document(s): [Section 13.3](#constraintsClaim) of this specification[¶](#section-20.8-4.4.1)

&nbsp;

-   Claim Name: `crit`[¶](#section-20.8-5.1.1)

-   Claim Description: List of Claims in this JWT defined by extensions to this kind of JWT that MUST be understood and processed[¶](#section-20.8-5.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-5.3.1)

-   Specification Document(s): [Section 13.4](#critClaim) of this specification[¶](#section-20.8-5.4.1)

&nbsp;

-   Claim Name: `ref`[¶](#section-20.8-6.1.1)

-   Claim Description: Reference[¶](#section-20.8-6.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-6.3.1)

-   Specification Document(s): [Section 13.5](#refClaim) of this specification[¶](#section-20.8-6.4.1)

&nbsp;

-   Claim Name: `delegation`[¶](#section-20.8-7.1.1)

-   Claim Description: Delegation[¶](#section-20.8-7.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-7.3.1)

-   Specification Document(s): [Section 13.6](#delegationClaim) of this specification[¶](#section-20.8-7.4.1)

&nbsp;

-   Claim Name: `logo_uri`[¶](#section-20.8-8.1.1)

-   Claim Description: URI referencing a logo[¶](#section-20.8-8.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-8.3.1)

-   Specification Document(s): [Section 13.7](#logo_uriClaim) of this specification[¶](#section-20.8-8.4.1)

&nbsp;

-   Claim Name: `authority_hints`[¶](#section-20.8-9.1.1)

-   Claim Description: Authority Hints[¶](#section-20.8-9.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-9.3.1)

-   Specification Document(s): [Section 3.1.2](#ec-specific) of this specification[¶](#section-20.8-9.4.1)

&nbsp;

-   Claim Name: `trust_anchor_hints`[¶](#section-20.8-10.1.1)

-   Claim Description: Trust Anchor Hints[¶](#section-20.8-10.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-10.3.1)

-   Specification Document(s): [Section 3.1.2](#ec-specific) of this specification[¶](#section-20.8-10.4.1)

&nbsp;

-   Claim Name: `trust_marks`[¶](#section-20.8-11.1.1)

-   Claim Description: Trust Marks[¶](#section-20.8-11.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-11.3.1)

-   Specification Document(s): [Section 3.1.2](#ec-specific) of this specification[¶](#section-20.8-11.4.1)

&nbsp;

-   Claim Name: `trust_mark_issuers`[¶](#section-20.8-12.1.1)

-   Claim Description: Trust Mark Issuers[¶](#section-20.8-12.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-12.3.1)

-   Specification Document(s): [Section 3.1.2](#ec-specific) of this specification[¶](#section-20.8-12.4.1)

&nbsp;

-   Claim Name: `trust_mark_owners`[¶](#section-20.8-13.1.1)

-   Claim Description: Trust Mark Owners[¶](#section-20.8-13.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-13.3.1)

-   Specification Document(s): [Section 3.1.2](#ec-specific) of this specification[¶](#section-20.8-13.4.1)

&nbsp;

-   Claim Name: `metadata_policy`[¶](#section-20.8-14.1.1)

-   Claim Description: Metadata Policy object[¶](#section-20.8-14.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-14.3.1)

-   Specification Document(s): [Section 3.1.3](#ss-specific) of this specification[¶](#section-20.8-14.4.1)

&nbsp;

-   Claim Name: `metadata_policy_crit`[¶](#section-20.8-15.1.1)

-   Claim Description: Critical Metadata Policy Operators[¶](#section-20.8-15.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-15.3.1)

-   Specification Document(s): [Section 3.1.3](#ss-specific) of this specification[¶](#section-20.8-15.4.1)

&nbsp;

-   Claim Name: `source_endpoint`[¶](#section-20.8-16.1.1)

-   Claim Description: Source Endpoint URL[¶](#section-20.8-16.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-16.3.1)

-   Specification Document(s): [Section 3.1.3](#ss-specific) of this specification[¶](#section-20.8-16.4.1)

&nbsp;

-   Claim Name: `keys`[¶](#section-20.8-17.1.1)

-   Claim Description: Array of JWK values in a JWK Set[¶](#section-20.8-17.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-17.3.1)

-   Specification Document(s): [Section 5.2.1](#jwks_metadata) of this specification[¶](#section-20.8-17.4.1)

&nbsp;

-   Claim Name: `trust_mark_type`[¶](#section-20.8-18.1.1)

-   Claim Description: Trust Mark Type Identifier[¶](#section-20.8-18.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-18.3.1)

-   Specification Document(s): [Section 7.1](#trust_mark_claims) of this specification[¶](#section-20.8-18.4.1)

&nbsp;

-   Claim Name: `trust_chain`[¶](#section-20.8-19.1.1)

-   Claim Description: Trust Chain[¶](#section-20.8-19.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-19.3.1)

-   Specification Document(s): [Section 8.3.2](#resolve-response) of this specification[¶](#section-20.8-19.4.1)

&nbsp;

-   Claim Name: `trust_anchor`[¶](#section-20.8-20.1.1)

-   Claim Description: Trust Anchor ID[¶](#section-20.8-20.2.1)

-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.8-20.3.1)

-   Specification Document(s): [Section 12.2.3](#trust_anchor-claim) of this specification[¶](#section-20.8-20.4.1)

### [20.9.](#section-20.9) [Well-Known URI Registration](#name-well-known-uri-registration)

This specification registers the following well-known URI in the IANA \"Well-Known URIs\" registry \[[IANA.well-known](#IANA.well-known)\] established by \[[RFC5785](#RFC5785)\].[¶](#section-20.9-1)

-   URI suffix: `openid-federation`[¶](#section-20.9-2.1.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.9-2.2.1)

-   Specification document: [Section 9](#federation_configuration) of this specification[¶](#section-20.9-2.3.1)

-   Related information: (none)[¶](#section-20.9-2.4.1)

### [20.10.](#section-20.10) [Media Type Registration](#name-media-type-registration)

This specification registers the following media types \[[RFC2046](#RFC2046)\] in the IANA \"Media Types\" registry \[[IANA.MediaTypes](#IANA.MediaTypes)\] in the manner described in \[[RFC6838](#RFC6838)\].[¶](#section-20.10-1)

-   Type name: application[¶](#section-20.10-2.1.1)

-   Subtype name: entity-statement+jwt[¶](#section-20.10-2.2.1)

-   Required parameters: n/a[¶](#section-20.10-2.3.1)

-   Optional parameters: n/a[¶](#section-20.10-2.4.1)

-   Encoding considerations: binary; An Entity Statement is a JWT; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#section-20.10-2.5.1)

-   Security considerations: See [Section 18](#Security) of this specification[¶](#section-20.10-2.6.1)

-   Interoperability considerations: n/a[¶](#section-20.10-2.7.1)

-   Published specification: [Section 15.1](#entity-statement_jwt) of this specification[¶](#section-20.10-2.8.1)

-   Applications that use this media type: Applications that use this specification[¶](#section-20.10-2.9.1)

-   Fragment identifier considerations: n/a[¶](#section-20.10-2.10.1)

-   Additional information:[¶](#section-20.10-2.11.1)

    -   Magic number(s): n/a[¶](#section-20.10-2.11.2.1.1)

    -   File extension(s): n/a[¶](#section-20.10-2.11.2.2.1)

    -   Macintosh file type code(s): n/a[¶](#section-20.10-2.11.2.3.1)

-   Person & email address to contact for further information:[¶](#section-20.10-2.12.1)

    Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-2.12.2)

-   Intended usage: COMMON[¶](#section-20.10-2.13.1)

-   Restrictions on usage: none[¶](#section-20.10-2.14.1)

-   Author: Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-2.15.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.10-2.16.1)

-   Provisional registration? No[¶](#section-20.10-2.17.1)

&nbsp;

-   Type name: application[¶](#section-20.10-3.1.1)

-   Subtype name: trust-mark+jwt[¶](#section-20.10-3.2.1)

-   Required parameters: n/a[¶](#section-20.10-3.3.1)

-   Optional parameters: n/a[¶](#section-20.10-3.4.1)

-   Encoding considerations: binary; A Trust Mark is a JWT; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#section-20.10-3.5.1)

-   Security considerations: See [Section 18](#Security) of this specification[¶](#section-20.10-3.6.1)

-   Interoperability considerations: n/a[¶](#section-20.10-3.7.1)

-   Published specification: [Section 15.2](#trust-mark_jwt) of this specification[¶](#section-20.10-3.8.1)

-   Applications that use this media type: Applications that use this specification[¶](#section-20.10-3.9.1)

-   Fragment identifier considerations: n/a[¶](#section-20.10-3.10.1)

-   Additional information:[¶](#section-20.10-3.11.1)

    -   Magic number(s): n/a[¶](#section-20.10-3.11.2.1.1)

    -   File extension(s): n/a[¶](#section-20.10-3.11.2.2.1)

    -   Macintosh file type code(s): n/a[¶](#section-20.10-3.11.2.3.1)

-   Person & email address to contact for further information:[¶](#section-20.10-3.12.1)

    Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-3.12.2)

-   Intended usage: COMMON[¶](#section-20.10-3.13.1)

-   Restrictions on usage: none[¶](#section-20.10-3.14.1)

-   Author: Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-3.15.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.10-3.16.1)

-   Provisional registration? No[¶](#section-20.10-3.17.1)

&nbsp;

-   Type name: application[¶](#section-20.10-4.1.1)

-   Subtype name: resolve-response+jwt[¶](#section-20.10-4.2.1)

-   Required parameters: n/a[¶](#section-20.10-4.3.1)

-   Optional parameters: n/a[¶](#section-20.10-4.4.1)

-   Encoding considerations: binary; An Entity Resolve Response is a signed JWT; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#section-20.10-4.5.1)

-   Security considerations: See [Section 18](#Security) of this specification[¶](#section-20.10-4.6.1)

-   Interoperability considerations: n/a[¶](#section-20.10-4.7.1)

-   Published specification: [Section 15.3](#resolve-response_jwt) of this specification[¶](#section-20.10-4.8.1)

-   Applications that use this media type: Applications that use this specification[¶](#section-20.10-4.9.1)

-   Fragment identifier considerations: n/a[¶](#section-20.10-4.10.1)

-   Additional information:[¶](#section-20.10-4.11.1)

    -   Magic number(s): n/a[¶](#section-20.10-4.11.2.1.1)

    -   File extension(s): n/a[¶](#section-20.10-4.11.2.2.1)

    -   Macintosh file type code(s): n/a[¶](#section-20.10-4.11.2.3.1)

-   Person & email address to contact for further information:[¶](#section-20.10-4.12.1)

    Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-4.12.2)

-   Intended usage: COMMON[¶](#section-20.10-4.13.1)

-   Restrictions on usage: none[¶](#section-20.10-4.14.1)

-   Author: Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-4.15.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.10-4.16.1)

-   Provisional registration? No[¶](#section-20.10-4.17.1)

&nbsp;

-   Type name: application[¶](#section-20.10-5.1.1)

-   Subtype name: trust-chain+json[¶](#section-20.10-5.2.1)

-   Required parameters: n/a[¶](#section-20.10-5.3.1)

-   Optional parameters: n/a[¶](#section-20.10-5.4.1)

-   Encoding considerations: binary; A Trust Chain is a JSON array of JWTs; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#section-20.10-5.5.1)

-   Security considerations: See [Section 18](#Security) of this specification[¶](#section-20.10-5.6.1)

-   Interoperability considerations: n/a[¶](#section-20.10-5.7.1)

-   Published specification: [Section 15.4](#trust-chain_json) of this specification[¶](#section-20.10-5.8.1)

-   Applications that use this media type: Applications that use this specification[¶](#section-20.10-5.9.1)

-   Fragment identifier considerations: n/a[¶](#section-20.10-5.10.1)

-   Additional information:[¶](#section-20.10-5.11.1)

    -   Magic number(s): n/a[¶](#section-20.10-5.11.2.1.1)

    -   File extension(s): n/a[¶](#section-20.10-5.11.2.2.1)

    -   Macintosh file type code(s): n/a[¶](#section-20.10-5.11.2.3.1)

-   Person & email address to contact for further information:[¶](#section-20.10-5.12.1)

    Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-5.12.2)

-   Intended usage: COMMON[¶](#section-20.10-5.13.1)

-   Restrictions on usage: none[¶](#section-20.10-5.14.1)

-   Author: Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-5.15.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.10-5.16.1)

-   Provisional registration? No[¶](#section-20.10-5.17.1)

&nbsp;

-   Type name: application[¶](#section-20.10-6.1.1)

-   Subtype name: trust-mark-delegation+jwt[¶](#section-20.10-6.2.1)

-   Required parameters: n/a[¶](#section-20.10-6.3.1)

-   Optional parameters: n/a[¶](#section-20.10-6.4.1)

-   Encoding considerations: binary; A Trust Mark delegation is a signed JWT; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#section-20.10-6.5.1)

-   Security considerations: See [Section 18](#Security) of this specification[¶](#section-20.10-6.6.1)

-   Interoperability considerations: n/a[¶](#section-20.10-6.7.1)

-   Published specification: [Section 15.5](#trust-mark-delegation_jwt) of this specification[¶](#section-20.10-6.8.1)

-   Applications that use this media type: Applications that use this specification[¶](#section-20.10-6.9.1)

-   Fragment identifier considerations: n/a[¶](#section-20.10-6.10.1)

-   Additional information:[¶](#section-20.10-6.11.1)

    -   Magic number(s): n/a[¶](#section-20.10-6.11.2.1.1)

    -   File extension(s): n/a[¶](#section-20.10-6.11.2.2.1)

    -   Macintosh file type code(s): n/a[¶](#section-20.10-6.11.2.3.1)

-   Person & email address to contact for further information:[¶](#section-20.10-6.12.1)

    Roland Hedberg, roland@catalogix.se[¶](#section-20.10-6.12.2)

-   Intended usage: COMMON[¶](#section-20.10-6.13.1)

-   Restrictions on usage: none[¶](#section-20.10-6.14.1)

-   Author: Roland Hedberg, roland@catalogix.se[¶](#section-20.10-6.15.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.10-6.16.1)

-   Provisional registration? No[¶](#section-20.10-6.17.1)

&nbsp;

-   Type name: application[¶](#section-20.10-7.1.1)

-   Subtype name: jwk-set+jwt[¶](#section-20.10-7.2.1)

-   Required parameters: n/a[¶](#section-20.10-7.3.1)

-   Optional parameters: n/a[¶](#section-20.10-7.4.1)

-   Encoding considerations: binary; A signed JWK Set is a signed JWT; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#section-20.10-7.5.1)

-   Security considerations: See [Section 18](#Security) of this specification[¶](#section-20.10-7.6.1)

-   Interoperability considerations: n/a[¶](#section-20.10-7.7.1)

-   Published specification: [Section 15.6](#jwk-set_jwt) of this specification[¶](#section-20.10-7.8.1)

-   Applications that use this media type: Applications that use this specification[¶](#section-20.10-7.9.1)

-   Fragment identifier considerations: n/a[¶](#section-20.10-7.10.1)

-   Additional information:[¶](#section-20.10-7.11.1)

    -   Magic number(s): n/a[¶](#section-20.10-7.11.2.1.1)

    -   File extension(s): n/a[¶](#section-20.10-7.11.2.2.1)

    -   Macintosh file type code(s): n/a[¶](#section-20.10-7.11.2.3.1)

-   Person & email address to contact for further information:[¶](#section-20.10-7.12.1)

    Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-7.12.2)

-   Intended usage: COMMON[¶](#section-20.10-7.13.1)

-   Restrictions on usage: none[¶](#section-20.10-7.14.1)

-   Author: Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-7.15.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.10-7.16.1)

-   Provisional registration? No[¶](#section-20.10-7.17.1)

&nbsp;

-   Type name: application[¶](#section-20.10-8.1.1)

-   Subtype name: trust-mark-status-response+jwt[¶](#section-20.10-8.2.1)

-   Required parameters: n/a[¶](#section-20.10-8.3.1)

-   Optional parameters: n/a[¶](#section-20.10-8.4.1)

-   Encoding considerations: binary; A Trust Mark Status Response is a signed JWT; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#section-20.10-8.5.1)

-   Security considerations: See [Section 18](#Security) of this specification[¶](#section-20.10-8.6.1)

-   Interoperability considerations: n/a[¶](#section-20.10-8.7.1)

-   Published specification: [Section 15.7](#trust-mark-status-response_jwt) of this specification[¶](#section-20.10-8.8.1)

-   Applications that use this media type: Applications that use this specification[¶](#section-20.10-8.9.1)

-   Fragment identifier considerations: n/a[¶](#section-20.10-8.10.1)

-   Additional information:[¶](#section-20.10-8.11.1)

    -   Magic number(s): n/a[¶](#section-20.10-8.11.2.1.1)

    -   File extension(s): n/a[¶](#section-20.10-8.11.2.2.1)

    -   Macintosh file type code(s): n/a[¶](#section-20.10-8.11.2.3.1)

-   Person & email address to contact for further information:[¶](#section-20.10-8.12.1)

    Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-8.12.2)

-   Intended usage: COMMON[¶](#section-20.10-8.13.1)

-   Restrictions on usage: none[¶](#section-20.10-8.14.1)

-   Author: Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-8.15.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.10-8.16.1)

-   Provisional registration? No[¶](#section-20.10-8.17.1)

&nbsp;

-   Type name: application[¶](#section-20.10-9.1.1)

-   Subtype name: explicit-registration-response+jwt[¶](#section-20.10-9.2.1)

-   Required parameters: n/a[¶](#section-20.10-9.3.1)

-   Optional parameters: n/a[¶](#section-20.10-9.4.1)

-   Encoding considerations: binary; An Explicit Registration response is a signed JWT; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#section-20.10-9.5.1)

-   Security considerations: See [Section 18](#Security) of this specification[¶](#section-20.10-9.6.1)

-   Interoperability considerations: n/a[¶](#section-20.10-9.7.1)

-   Published specification: [Section 15.8](#explicit-registration-response_jwt) of this specification[¶](#section-20.10-9.8.1)

-   Applications that use this media type: Applications that use this specification[¶](#section-20.10-9.9.1)

-   Fragment identifier considerations: n/a[¶](#section-20.10-9.10.1)

-   Additional information:[¶](#section-20.10-9.11.1)

    -   Magic number(s): n/a[¶](#section-20.10-9.11.2.1.1)

    -   File extension(s): n/a[¶](#section-20.10-9.11.2.2.1)

    -   Macintosh file type code(s): n/a[¶](#section-20.10-9.11.2.3.1)

-   Person & email address to contact for further information:[¶](#section-20.10-9.12.1)

    Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-9.12.2)

-   Intended usage: COMMON[¶](#section-20.10-9.13.1)

-   Restrictions on usage: none[¶](#section-20.10-9.14.1)

-   Author: Michael B. Jones, michael_b_jones@hotmail.com[¶](#section-20.10-9.15.1)

-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net[¶](#section-20.10-9.16.1)

-   Provisional registration? No[¶](#section-20.10-9.17.1)

## [21.](#section-21) [References](#name-references)

### [21.1.](#section-21.1) [Normative References](#name-normative-references)

\[OpenID.Core\]
:   Sakimura, N., Bradley, J., Jones, M.B., de Medeiros, B., and C. Mortimore, \"OpenID Connect Core 1.0\", 15 December 2023, \<<https://openid.net/specs/openid-connect-core-1_0.html>\>.
:   

\[OpenID.Discovery\]
:   Sakimura, N., Bradley, J., Jones, M.B., and E. Jay, \"OpenID Connect Discovery 1.0\", 15 December 2023, \<<https://openid.net/specs/openid-connect-discovery-1_0.html>\>.
:   

\[OpenID.Registration\]
:   Sakimura, N., Bradley, J., and M.B. Jones, \"OpenID Connect Dynamic Client Registration 1.0\", 15 December 2023, \<<https://openid.net/specs/openid-connect-registration-1_0.html>\>.
:   

\[OpenID.RP.Choices\]
:   Jones, M.B., Hedberg, R., Bradley, J., and F. Skokan, \"OpenID Connect Relying Party Metadata Choices 1.0\", 8 January 2026, \<<https://openid.net/specs/openid-connect-rp-metadata-choices-1_0.html>\>.
:   

\[RFC2119\]
:   Bradner, S., \"Key words for use in RFCs to Indicate Requirement Levels\", BCP 14, RFC 2119, DOI 10.17487/RFC2119, March 1997, \<<https://www.rfc-editor.org/info/rfc2119>\>.
:   

\[RFC4732\]
:   Handley, M., Ed., Rescorla, E., Ed., and IAB, \"Internet Denial-of-Service Considerations\", RFC 4732, DOI 10.17487/RFC4732, December 2006, \<<https://www.rfc-editor.org/info/rfc4732>\>.
:   

\[RFC5280\]
:   Cooper, D., Santesson, S., Farrell, S., Boeyen, S., Housley, R., and W. Polk, \"Internet X.509 Public Key Infrastructure Certificate and Certificate Revocation List (CRL) Profile\", RFC 5280, DOI 10.17487/RFC5280, May 2008, \<<https://www.rfc-editor.org/info/rfc5280>\>.
:   

\[RFC5646\]
:   Phillips, A., Ed. and M. Davis, Ed., \"Tags for Identifying Languages\", BCP 47, RFC 5646, DOI 10.17487/RFC5646, September 2009, \<<https://www.rfc-editor.org/info/rfc5646>\>.
:   

\[RFC6749\]
:   Hardt, D., Ed., \"The OAuth 2.0 Authorization Framework\", RFC 6749, DOI 10.17487/RFC6749, October 2012, \<<https://www.rfc-editor.org/info/rfc6749>\>.
:   

\[RFC7515\]
:   Jones, M., Bradley, J., and N. Sakimura, \"JSON Web Signature (JWS)\", RFC 7515, DOI 10.17487/RFC7515, May 2015, \<<https://www.rfc-editor.org/info/rfc7515>\>.
:   

\[RFC7516\]
:   Jones, M. and J. Hildebrand, \"JSON Web Encryption (JWE)\", RFC 7516, DOI 10.17487/RFC7516, May 2015, \<<https://www.rfc-editor.org/info/rfc7516>\>.
:   

\[RFC7517\]
:   Jones, M., \"JSON Web Key (JWK)\", RFC 7517, DOI 10.17487/RFC7517, May 2015, \<<https://www.rfc-editor.org/info/rfc7517>\>.
:   

\[RFC7519\]
:   Jones, M., Bradley, J., and N. Sakimura, \"JSON Web Token (JWT)\", RFC 7519, DOI 10.17487/RFC7519, May 2015, \<<https://www.rfc-editor.org/info/rfc7519>\>.
:   

\[RFC7591\]
:   Richer, J., Ed., Jones, M., Bradley, J., Machulak, M., and P. Hunt, \"OAuth 2.0 Dynamic Client Registration Protocol\", RFC 7591, DOI 10.17487/RFC7591, July 2015, \<<https://www.rfc-editor.org/info/rfc7591>\>.
:   

\[RFC7638\]
:   Jones, M. and N. Sakimura, \"JSON Web Key (JWK) Thumbprint\", RFC 7638, DOI 10.17487/RFC7638, September 2015, \<<https://www.rfc-editor.org/info/rfc7638>\>.
:   

\[RFC8174\]
:   Leiba, B., \"Ambiguity of Uppercase vs Lowercase in RFC 2119 Key Words\", BCP 14, RFC 8174, DOI 10.17487/RFC8174, May 2017, \<<https://www.rfc-editor.org/info/rfc8174>\>.
:   

\[RFC8259\]
:   Bray, T., Ed., \"The JavaScript Object Notation (JSON) Data Interchange Format\", STD 90, RFC 8259, DOI 10.17487/RFC8259, December 2017, \<<https://www.rfc-editor.org/info/rfc8259>\>.
:   

\[RFC8414\]
:   Jones, M., Sakimura, N., and J. Bradley, \"OAuth 2.0 Authorization Server Metadata\", RFC 8414, DOI 10.17487/RFC8414, June 2018, \<<https://www.rfc-editor.org/info/rfc8414>\>.
:   

\[RFC8705\]
:   Campbell, B., Bradley, J., Sakimura, N., and T. Lodderstedt, \"OAuth 2.0 Mutual-TLS Client Authentication and Certificate-Bound Access Tokens\", RFC 8705, DOI 10.17487/RFC8705, February 2020, \<<https://www.rfc-editor.org/info/rfc8705>\>.
:   

\[RFC9101\]
:   Sakimura, N., Bradley, J., and M. Jones, \"The OAuth 2.0 Authorization Framework: JWT-Secured Authorization Request (JAR)\", RFC 9101, DOI 10.17487/RFC9101, August 2021, \<<https://www.rfc-editor.org/info/rfc9101>\>.
:   

\[RFC9126\]
:   Lodderstedt, T., Campbell, B., Sakimura, N., Tonge, D., and F. Skokan, \"OAuth 2.0 Pushed Authorization Requests\", RFC 9126, DOI 10.17487/RFC9126, September 2021, \<<https://www.rfc-editor.org/info/rfc9126>\>.
:   

\[RFC9728\]
:   Jones, M.B., Hunt, P., and A. Parecki, \"OAuth 2.0 Protected Resource Metadata\", RFC 9728, DOI 10.17487/RFC9728, April 2025, \<<https://www.rfc-editor.org/info/rfc9728>\>.
:   

\[UNICODE\]
:   The Unicode Consortium, \"The Unicode Standard\", \<<http://www.unicode.org/versions/latest/>\>.
:   

\[USA15\]
:   Whistler, K., \"Unicode Normalization Forms\", Unicode Standard Annex 15, 30 July 2025, \<<https://www.unicode.org/reports/tr15/>\>.
:   

### [21.2.](#section-21.2) [Informative References](#name-informative-references)

\[App-Fed-Linkage\]
:   Dzhuvinov, V., \"How to link an application protocol to an OpenID Federation 1.0 trust layer\", 4 December 2024, \<<https://connect2id.com/blog/how-to-link-an-app-protocol-to-an-openid-federation-trust-layer>\>.
:   

\[FAPI\]
:   Sakimura, N., Bradley, J., and E. Jay, \"Financial-grade API Security Profile 1.0 - Part 2: Advanced\", 12 March 2021, \<<https://openid.net/specs/openid-financial-api-part-2-1_0.html>\>.
:   

\[IANA.JOSE\]
:   IANA, \"JSON Object Signing and Encryption (JOSE)\", \<<https://www.iana.org/assignments/jose>\>.
:   

\[IANA.JWT.Claims\]
:   IANA, \"JSON Web Token Claims\", \<<https://www.iana.org/assignments/jwt>\>.
:   

\[IANA.MediaTypes\]
:   IANA, \"Media Types\", \<<https://www.iana.org/assignments/media-types>\>.
:   

\[IANA.OAuth.Parameters\]
:   IANA, \"OAuth Parameters\", \<<https://www.iana.org/assignments/oauth-parameters>\>.
:   

\[IANA.well-known\]
:   IANA, \"Well-Known URIs\", \<<https://www.iana.org/assignments/well-known-uris>\>.
:   

\[OpenID.Federation-1.1\]
:   Hedberg, R., Jones, M.B., Ed., De Marco, G., Solberg, A.Å., Bradley, J., and V. Dzhuvinov, \"OpenID Federation 1.1\", 17 February 2026, \<<https://openid.net/specs/openid-federation-1_1.html>\>.
:   

\[OpenID.Federation.Connect-1.1\]
:   Hedberg, R., Jones, M.B., Ed., De Marco, G., Solberg, A.Å., Bradley, J., and V. Dzhuvinov, \"OpenID Federation for OpenID Connect 1.1\", 17 February 2026, \<<https://openid.net/specs/openid-federation-connect-1_1.html>\>.
:   

\[RFC2046\]
:   Freed, N. and N. Borenstein, \"Multipurpose Internet Mail Extensions (MIME) Part Two: Media Types\", RFC 2046, DOI 10.17487/RFC2046, November 1996, \<<https://www.rfc-editor.org/info/rfc2046>\>.
:   

\[RFC5785\]
:   Nottingham, M. and E. Hammer-Lahav, \"Defining Well-Known Uniform Resource Identifiers (URIs)\", RFC 5785, DOI 10.17487/RFC5785, April 2010, \<<https://www.rfc-editor.org/info/rfc5785>\>.
:   

\[RFC6838\]
:   Freed, N., Klensin, J., and T. Hansen, \"Media Type Specifications and Registration Procedures\", BCP 13, RFC 6838, DOI 10.17487/RFC6838, January 2013, \<<https://www.rfc-editor.org/info/rfc6838>\>.
:   

\[RFC8725\]
:   Sheffer, Y., Hardt, D., and M. Jones, \"JSON Web Token Best Current Practices\", BCP 225, RFC 8725, DOI 10.17487/RFC8725, February 2020, \<<https://www.rfc-editor.org/info/rfc8725>\>.
:   

\[RFC9525\]
:   Saint-Andre, P. and R. Salz, \"Service Identity in TLS\", RFC 9525, DOI 10.17487/RFC9525, November 2023, \<<https://www.rfc-editor.org/info/rfc9525>\>.
:   

## [Appendix A.](#appendix-A) [Examples Building and Using Trust Chains](#name-examples-building-and-using)

Let us assume the following: The project LIGO would like to offer access to its wiki to all OPs in eduGAIN. LIGO is registered with the InCommon federation.[¶](#appendix-A-1)

The following depicts a federation under the eduGAIN Trust Anchor:[¶](#appendix-A-2)

                           eduGAIN
                              |
           +------------------+------------------+
           |                                     |
        SWAMID                               InCommon
           |                                     |
         umu.se                                  |
           |                                     |
       op.umu.se                           wiki.ligo.org

[Figure 55](#figure-55): [Participants in the eduGAIN Federation](#name-participants-in-the-edugain)

Both SWAMID and InCommon are identity federations in their own right. They also have in common that they are both members of the eduGAIN federation.[¶](#appendix-A-4)

SWAMID and InCommon are different in how they register Entities. SWAMID registers organizations and lets the organizations register Entities that belong to the organization, while InCommon registers all Entities directly and not beneath any organization Entity. Hence the differences in depth in the federations.[¶](#appendix-A-5)

Let us assume a researcher from Umeå University would like to login to the LIGO Wiki. At the Wiki, the researcher will use some kind of discovery service to find the home identity provider (op.umu.se).[¶](#appendix-A-6)

Once the RP part of the Wiki knows which OP it SHOULD talk to, it has to find out a few things about the OP. All those things can be found in the metadata. But finding the metadata is not enough; the RP also has to trust the metadata.[¶](#appendix-A-7)

Let us make a detour and start with what it takes to build a federation.[¶](#appendix-A-8)

### [A.1.](#appendix-A.1) [Setting Up a Federation](#name-setting-up-a-federation)

These are the steps to set up a federation infrastructure:[¶](#appendix-A.1-1)

-   Generation of Trust Anchor signing keys. These MUST be public/private key pairs.[¶](#appendix-A.1-2.1.1)

-   Set up a signing service that can sign JWTs/Entity Statements using the Federation Entity Keys.[¶](#appendix-A.1-2.2.1)

-   Set up web services that can publish signed Entity Statements, one for the URL corresponding to the federation\'s Entity Identifier returning an Entity Configuration and the other one providing the fetch endpoint, as described in [Section 8.1.1](#fetch_statement).[¶](#appendix-A.1-2.3.1)

Once these requirements have been satisfied, a Federation Operator can add Entities to the federation. Adding an Entity comes down to:[¶](#appendix-A.1-3)

-   Providing the Entity with the federation\'s Entity Identifier and the public part of the key pairs used by the federation operator for signing Entity Statements.[¶](#appendix-A.1-4.1.1)

-   Getting the Entity\'s Entity Identifier and the JWK Set that the Entity plans to publish in its Entity Configuration.[¶](#appendix-A.1-4.2.1)

Before the federation operator starts adding Entities, there must be policies on who can be part of the federation and the layout of the federation. Is it supposed to be a one-layer federation like InCommon, a two-layer one like the SWAMID federation, or a multi-layer federation? The federation may also want to consider implementing other policies using the federation policy framework, as described in [Section 6](#federation_policy).[¶](#appendix-A.1-5)

With the federation in place, things can start happening.[¶](#appendix-A.1-6)

### [A.2.](#appendix-A.2) [The LIGO Wiki Discovers the OP\'s Metadata](#name-the-ligo-wiki-discovers-the)

Federation Entity Discovery is a sequence of steps that starts with the RP fetching the Entity Configuration of the OP\'s Entity (in this case, https://op.umu.se) using the process defined in [Section 9](#federation_configuration). What follows that is this sequence of steps:[¶](#appendix-A.2-1)

1.  Pick out the Immediate Superior Entities using the authority hints.[¶](#appendix-A.2-2.1.1)

2.  Fetch the Entity Configuration for each such Entity. This uses the process defined in [Section 9](#federation_configuration).[¶](#appendix-A.2-2.2.1)

3.  Use the fetch endpoint of each Immediate Superior to obtain Subordinate Statements about the Immediate Subordinate Entity, per [Section 8.1.1](#fetch_statement).[¶](#appendix-A.2-2.3.1)

How many times this has to be repeated depends on the depth of the federation. What follows below is the result of each step the RP has to take to find the OP\'s metadata using the federation setup described above.[¶](#appendix-A.2-3)

When building the Trust Chain, the Subordinate Statements issued by each Immediate Superior about their Immediate Subordinates are used together with the Entity Configuration of the Trust Chain subject.[¶](#appendix-A.2-4)

The Entity Configurations of Intermediates are not part of the Trust Chain.[¶](#appendix-A.2-5)

#### [A.2.1.](#appendix-A.2.1) [Entity Configuration for https://op.umu.se](#name-entity-configuration-for-ht)

The LIGO WIKI RP fetches the Entity Configuration from the OP (op.umu.se) using the process defined in [Section 9](#federation_configuration).[¶](#appendix-A.2.1-1)

The result is this Entity Configuration:[¶](#appendix-A.2.1-2)

    {
      "authority_hints": [
        "https://umu.se"
      ],
      "exp": 1568397247,
      "iat": 1568310847,
      "iss": "https://op.umu.se",
      "sub": "https://op.umu.se",
      "jwks": {
        "keys": [
          {
            "e": "AQAB",
            "kid": "dEEtRjlzY3djcENuT01wOGxrZlkxb3RIQVJlMTY0...",
            "kty": "RSA",
            "n": "x97YKqc9Cs-DNtFrQ7_vhXoH9bwkDWW6En2jJ044yH..."
          }
        ]
      },
      "metadata": {
        "openid_provider": {
          "issuer": "https://op.umu.se",
          "signed_jwks_uri": "https://op.umu.se/jwks.jose",
          "authorization_endpoint":
            "https://op.umu.se/authorization",
          "client_registration_types_supported": [
            "automatic",
            "explicit"
          ],
          "request_parameter_supported": true,
          "grant_types_supported": [
            "authorization_code",
            "implicit",
            "urn:ietf:params:oauth:grant-type:jwt-bearer"
          ],
          "id_token_signing_alg_values_supported": [
            "ES256", "RS256"
          ],
          "logo_uri":
            "https://www.umu.se/img/umu-logo-left-neg-SE.svg",
          "op_policy_uri":
            "https://www.umu.se/en/website/legal-information/",
          "response_types_supported": [
            "code",
            "code id_token",
            "token"
          ],
          "subject_types_supported": [
            "pairwise",
            "public"
          ],
          "token_endpoint": "https://op.umu.se/token",
          "federation_registration_endpoint":
            "https://op.umu.se/fedreg",
          "token_endpoint_auth_methods_supported": [
            "client_secret_post",
            "client_secret_basic",
            "client_secret_jwt",
            "private_key_jwt"
          ]
        }
      }
    }

[Figure 56](#figure-56): [Entity Configuration Issued by https://op.umu.se](#name-entity-configuration-issued)

The `authority_hints` points to the Intermediate Entity `https://umu.se`. So that is the next step.[¶](#appendix-A.2.1-4)

This Entity Configuration is the first link in the Trust Chain.[¶](#appendix-A.2.1-5)

#### [A.2.2.](#appendix-A.2.2) [Entity Configuration for https://umu.se](#name-entity-configuration-for-htt)

The LIGO RP fetches the Entity Configuration from https://umu.se using the process defined in [Section 9](#federation_configuration).[¶](#appendix-A.2.2-1)

The request will look like this:[¶](#appendix-A.2.2-2)

    GET /.well-known/openid-federation HTTP/1.1
    Host: umu.se

[Figure 57](#figure-57): [Entity Configuration Issued by https://umu.se](#name-entity-configuration-issued-)

And the GET will return:[¶](#appendix-A.2.2-4)

    {
      "authority_hints": [
        "https://swamid.se"
      ],
      "exp": 1568397247,
      "iat": 1568310847,
      "iss": "https://umu.se",
      "sub": "https://umu.se",
      "jwks": {
        "keys": [
          {
            "e": "AQAB",
            "kid": "endwNUZrNTJsX2NyQlp4bjhVcTFTTVltR2gxV2RV...",
            "kty": "RSA",
            "n": "vXdXzZwQo0hxRSmZEcDIsnpg-CMEkor50SOG-1XUlM..."
          }
        ]
      },
      "metadata": {
        "federation_entity": {
          "contacts": ["ops@umu.se"],
          "federation_fetch_endpoint": "https://umu.se/openid/fedapi",
          "organization_uri": "https://www.umu.se",
          "organization_name": "UmU"
        }
      }
    }

[Figure 58](#figure-58): [Entity Configuration JWT Claims Set](#name-entity-configuration-jwt-cl)

The only piece of information that is used from this Entity Configuration in this process is the `federation_fetch_endpoint`, which is used in the next step.[¶](#appendix-A.2.2-6)

#### [A.2.3.](#appendix-A.2.3) [Subordinate Statement Published by https://umu.se about https://op.umu.se](#name-subordinate-statement-publi)

The RP uses the fetch endpoint provided by https://umu.se, as defined in [Section 8.1.1](#fetch_statement), to fetch information about https://op.umu.se.[¶](#appendix-A.2.3-1)

The request will look like this:[¶](#appendix-A.2.3-2)

    GET /openid/fedapi?sub=https%3A%2F%2Fop.umu.se&
    iss=https%3A%2F%2Fumu.se HTTP/1.1
    Host: umu.se

[Figure 59](#figure-59): [Request Subordinate Statement from https://umu.se about https://op.umu.se](#name-request-subordinate-stateme)

And the result is this:[¶](#appendix-A.2.3-4)

    {
      "exp": 1568397247,
      "iat": 1568310847,
      "iss": "https://umu.se",
      "sub": "https://op.umu.se",
      "source_endpoint": "https://umu.se/openid/fedapi",
      "jwks": {
        "keys": [
          {
            "e": "AQAB",
            "kid": "dEEtRjlzY3djcENuT01wOGxrZlkxb3RIQVJlMTY0...",
            "kty": "RSA",
            "n": "x97YKqc9Cs-DNtFrQ7_vhXoH9bwkDWW6En2jJ044yH..."
          }
        ]
      },
      "metadata_policy": {
        "openid_provider": {
          "contacts": {
            "add": [
              "ops@swamid.se"
            ]
          },
          "organization_name": {
            "value": "University of Umeå"
          },
          "subject_types_supported": {
            "value": [
              "pairwise"
            ]
          },
          "token_endpoint_auth_methods_supported": {
            "default": [
              "private_key_jwt"
            ],
            "subset_of": [
              "private_key_jwt",
              "client_secret_jwt"
            ],
            "superset_of": [
              "private_key_jwt"
            ]
          }
        }
      }
    }

[Figure 60](#figure-60): [Subordinate Statement Issued by https://umu.se about https://op.umu.se](#name-subordinate-statement-issue)

This Subordinate Statement is the second link in the Trust Chain.[¶](#appendix-A.2.3-6)

#### [A.2.4.](#appendix-A.2.4) [Entity Configuration for https://swamid.se](#name-entity-configuration-for-http)

The LIGO Wiki RP fetches the Entity Configuration from https://swamid.se using the process defined in [Section 9](#federation_configuration).[¶](#appendix-A.2.4-1)

The request will look like this:[¶](#appendix-A.2.4-2)

    GET /.well-known/openid-federation HTTP/1.1
    Host: swamid.se

[Figure 61](#figure-61): [Request Entity Configuration from https://swamid.se](#name-request-entity-configuratio)

And the GET will return:[¶](#appendix-A.2.4-4)

    {
      "authority_hints": [
        "https://edugain.geant.org"
      ],
      "exp": 1568397247,
      "iat": 1568310847,
      "iss": "https://swamid.se",
      "sub": "https://swamid.se",
      "jwks": {
        "keys": [
          {
            "e": "AQAB",
            "kid": "N1pQTzFxUXZ1RXVsUkVuMG5uMnVDSURGRVdhUzdO...",
            "kty": "RSA",
            "n": "3EQc6cR_GSBq9km9-WCHY_lWJZWkcn0M05TGtH6D9S..."
          }
        ]
      },
      "metadata": {
        "federation_entity": {
          "contacts": ["ops@swamid.se"],
          "federation_fetch_endpoint":
            "https://swamid.se/fedapi",
          "organization_uri": "https://www.sunet.se/swamid/",
          "organization_name": "SWAMID"
        }
      }
    }

[Figure 62](#figure-62): [Entity Configuration Issued by https://swamid.se](#name-entity-configuration-issued-b)

The only piece of information that is used from this Entity Configuration in this process is the `federation_fetch_endpoint`, which is used in the next step.[¶](#appendix-A.2.4-6)

#### [A.2.5.](#appendix-A.2.5) [Subordinate Statement Published by https://swamid.se about https://umu.se](#name-subordinate-statement-publis)

The LIGO Wiki RP uses the fetch endpoint provided by https://swamid.se as defined in [Section 8.1.1](#fetch_statement) to fetch information about https://umu.se.[¶](#appendix-A.2.5-1)

The request will look like this:[¶](#appendix-A.2.5-2)

    GET /fedapi?sub=https%3A%2F%2Fumu.se&
    iss=https%3A%2F%2Fswamid.se HTTP/1.1
    Host: swamid.se

[Figure 63](#figure-63): [Request to https://swamid.se for Subordinate Statement about https://umu.se](#name-request-to-https-swamidse-f)

And the result is this:[¶](#appendix-A.2.5-4)

    {
      "exp": 1568397247,
      "iat": 1568310847,
      "iss": "https://swamid.se",
      "sub": "https://umu.se",
      "source_endpoint": "https://swamid.se/fedapi",
      "jwks": {
        "keys": [
          {
            "e": "AQAB",
            "kid": "endwNUZrNTJsX2NyQlp4bjhVcTFTTVltR2gxV2RV...",
            "kty": "RSA",
            "n": "vXdXzZwQo0hxRSmZEcDIsnpg-CMEkor50SOG-1XUlM..."
          }
        ]
      },
      "metadata_policy": {
        "openid_provider": {
          "id_token_signing_alg_values_supported": {
            "subset_of": [
              "RS256",
              "ES256",
              "ES384",
              "ES512"
            ]
          },
          "token_endpoint_auth_methods_supported": {
            "subset_of": [
              "client_secret_jwt",
              "private_key_jwt"
            ]
          },
          "userinfo_signing_alg_values_supported": {
            "subset_of": [
              "ES256",
              "ES384",
              "ES512"
            ]
          }
        }
      }
    }

[Figure 64](#figure-64): [Subordinate Statement Issued by https://swamid.se about https://umu.se](#name-subordinate-statement-issued)

This Subordinate Statement is the third link in the Trust Chain.[¶](#appendix-A.2.5-6)

If we assume that the issuer of this Subordinate Statement is not in the list of Trust Anchors the LIGO Wiki RP has access to, we have to go one step further.[¶](#appendix-A.2.5-7)

#### [A.2.6.](#appendix-A.2.6) [Entity Configuration for https://edugain.geant.org](#name-entity-configuration-for-https)

The RP fetches the Entity Configuration from https://edugain.geant.org using the process defined in [Section 9](#federation_configuration).[¶](#appendix-A.2.6-1)

The request will look like this:[¶](#appendix-A.2.6-2)

    GET /.well-known/openid-federation HTTP/1.1
    Host: edugain.geant.org

[Figure 65](#figure-65): [Entity Configuration Requested from https://edugain.geant.org](#name-entity-configuration-reques)

And the GET will return:[¶](#appendix-A.2.6-4)

    {
      "exp": 1568397247,
      "iat": 1568310847,
      "iss": "https://edugain.geant.org",
      "sub": "https://edugain.geant.org",
      "jwks": {
        "keys": [
          {
            "e": "AQAB",
            "kid": "Sl9DcjFxR3hrRGdabUNIR21KT3dvdWMyc2VUM2Fr...",
            "kty": "RSA",
            "n": "xKlwocDXUw-mrvDSO4oRrTRrVuTwotoBFpozvlq-1q..."
          }
        ]
      },
      "metadata": {
        "federation_entity": {
          "federation_fetch_endpoint": "https://geant.org/edugain/api"
        }
      }
    }

[Figure 66](#figure-66): [Entity Configuration issued by https://edugain.geant.org](#name-entity-configuration-issued-by)

Within the Trust Anchor Entity Configuration, the Relying Party looks for the `federation_fetch_endpoint` and gets the updated Federation Entity Keys of the Trust Anchor. Each Entity within a Federation may change their Federation Entity Keys, or any other attributes, at any time. See [Section 11.2](#key_rollover_anchor) for further details.[¶](#appendix-A.2.6-6)

#### [A.2.7.](#appendix-A.2.7) [Subordinate Statement Published by https://edugain.geant.org about https://swamid.se](#name-subordinate-statement-publish)

The LIGO Wiki RP uses the fetch endpoint of https://edugain.geant.org as defined in [Section 8.1.1](#fetch_statement) to fetch information about \"https://swamid.se\".[¶](#appendix-A.2.7-1)

The request will look like this:[¶](#appendix-A.2.7-2)

    GET /edugain/api?sub=https%3A%2F%2Fswamid.se&
    iss=https%3A%2F%2Fedugain.geant.org HTTP/1.1
    Host: geant.org

[Figure 67](#figure-67): [Request to https://edugain.geant.org for Subordinate Statement about https://swamid.se](#name-request-to-https-edugaingea)

And the result is this:[¶](#appendix-A.2.7-4)

    {
      "exp": 1568397247,
      "iat": 1568310847,
      "iss": "https://edugain.geant.org",
      "sub": "https://swamid.se",
      "source_endpoint": "https://edugain.geant.org/edugain/api",
      "jwks": {
        "keys": [
          {
            "e": "AQAB",
            "kid": "N1pQTzFxUXZ1RXVsUkVuMG5uMnVDSURGRVdhUzdO...",
            "kty": "RSA",
            "n": "3EQc6cR_GSBq9km9-WCHY_lWJZWkcn0M05TGtH6D9S..."
          }
        ]
      },
      "metadata_policy": {
        "openid_provider": {
          "contacts": {
            "add": ["ops@edugain.geant.org"]
          }
        },
        "openid_relying_party": {
          "contacts": {
            "add": ["ops@edugain.geant.org"]
          }
        }
      }
    }

[Figure 68](#figure-68): [Subordinate Statement issued by https://edugain.geant.org about https://swamid.se](#name-subordinate-statement-issued-)

If we assume that the issuer of this statement appears in the list of Trust Anchors the LIGO Wiki RP has access to, this Subordinate Statement would be the fourth link in the Trust Chain. The Trust Anchor\'s Entity Configuration MAY also be included in the Trust Chain; in this case, it would be the fifth and final link.[¶](#appendix-A.2.7-6)

We have now retrieved all the members of the Trust Chain. Recapping, these Entity Statements were obtained:[¶](#appendix-A.2.7-7)

-   Entity Configuration for the Leaf Entity https://op.umu.se - the first link in the Trust Chain[¶](#appendix-A.2.7-8.1.1)

-   Entity Configuration for https://umu.se - not included in the Trust Chain[¶](#appendix-A.2.7-8.2.1)

-   Subordinate Statement issued by https://umu.se about https://op.umu.se - the second link in the Trust Chain[¶](#appendix-A.2.7-8.3.1)

-   Entity Configuration for https://swamid.se - not included in the Trust Chain[¶](#appendix-A.2.7-8.4.1)

-   Subordinate Statement issued by https://swamid.se about https://umu.se - the third link in the Trust Chain[¶](#appendix-A.2.7-8.5.1)

-   Entity Configuration for https://edugain.geant.org - optionally, the fifth and last link in the Trust Chain[¶](#appendix-A.2.7-8.6.1)

-   Subordinate Statement issued by https://edugain.geant.org about https://swamid.se - the fourth link in the Trust Chain[¶](#appendix-A.2.7-8.7.1)

Using the public keys of the Trust Anchor that the LIGO Wiki RP has been provided within some secure out-of-band way, it can now verify the Trust Chain as described in [Section 10.2](#trust_chain_validation).[¶](#appendix-A.2.7-9)

#### [A.2.8.](#appendix-A.2.8) [OP Resolved Metadata for https://op.umu.se](#name-op-resolved-metadata-for-ht)

Having verified the chain, the LIGO Wiki RP can proceed with the next step.[¶](#appendix-A.2.8-1)

Combining the metadata policies from the three Subordinate Statements we have by Immediate Superiors about their Immediate Subordinates and applying the combined policy to the metadata statement that the Leaf Entity presented, we get the following Resolved Metadata for the `openid_provider` Entity Type:[¶](#appendix-A.2.8-2)

    {
      "authorization_endpoint":
        "https://op.umu.se/authorization",
      "contacts": [
        "ops@swamid.se",
        "ops@edugain.geant.org"
      ],
      "federation_registration_endpoint":
        "https://op.umu.se/fedreg",
      "client_registration_types_supported": [
        "automatic",
        "explicit"
      ],
      "grant_types_supported": [
        "authorization_code",
        "implicit",
        "urn:ietf:params:oauth:grant-type:jwt-bearer"
      ],
      "id_token_signing_alg_values_supported": [
        "RS256",
        "ES256"
      ],
      "issuer": "https://op.umu.se",
      "signed_jwks_uri": "https://op.umu.se/jwks.jose",
      "logo_uri":
        "https://www.umu.se/img/umu-logo-left-neg-SE.svg",
      "organization_name": "University of Umeå",
      "op_policy_uri":
        "https://www.umu.se/en/website/legal-information/",
      "request_parameter_supported": true,
      "response_types_supported": [
        "code",
        "code id_token",
        "token"
      ],
      "subject_types_supported": [
        "pairwise"
      ],
      "token_endpoint": "https://op.umu.se/token",
      "token_endpoint_auth_methods_supported": [
        "private_key_jwt",
        "client_secret_jwt"
      ]
    }

[Figure 69](#figure-69): [OP Resolved Metadata Derived from Trust Chain by Applying Metadata Policies](#name-op-resolved-metadata-derive)

We have now reached the end of the Provider Discovery process.[¶](#appendix-A.2.8-4)

### [A.3.](#appendix-A.3) [Client Registration Method Examples](#name-client-registration-method-)

[Section 12](#client_registration) defines two methods for performing client registration:[¶](#appendix-A.3-1)

Automatic
:   No negotiation between the RP and the OP is made regarding what features the client SHOULD use in future communication occurs. The RP\'s published metadata filtered by the chosen Trust Chain\'s metadata policies defines the metadata that is to be used.[¶](#appendix-A.3-2.2)
:   

Explicit
:   The RP will access the `federation_registration_endpoint`, which provides the RP\'s metadata. The OP MAY return a metadata policy that adds restrictions over and above what the Trust Chain already has defined.[¶](#appendix-A.3-2.4)
:   

#### [A.3.1.](#appendix-A.3.1) [RP Sends Authentication Request (Automatic Client Registration)](#name-rp-sends-authentication-req)

The LIGO Wiki RP does not do any registration but goes directly to sending an Authentication Request.[¶](#appendix-A.3.1-1)

Here is an example of such an Authentication Request:[¶](#appendix-A.3.1-2)

    GET /openid/authorization?
      request=eyJ0eXAiOiJvYXV0aC1hdXRoei1yZXErand0IiwiYWxnIjoiU
        lMyNTYiLCJraWQiOiJkVU4yYTAxd1JraGtTV3BsUVRodmNWQklOVUl3
        VFVkT1VGVTJUbVZyU21oRVFYZ3paWGxwVHpkUU5BIn0.
        eyJyZXNwb25zZV90eXBlIjogImNvZGUiLCAic2NvcGUiOiAib3Blbml
        kIHByb2ZpbGUgZW1haWwiLCAiY2xpZW50X2lkIjogImh0dHBzOi8vd2
        lraS5saWdvLm9yZyIsICJzdGF0ZSI6ICIyZmY3ZTU4OS0zODQ4LTQ2Z
        GEtYTNkMi05NDllMTIzNWU2NzEiLCAibm9uY2UiOiAiZjU4MWExODYt
        YWNhNC00NmIzLTk0ZmMtODA0ODQwODNlYjJjIiwgInJlZGlyZWN0X3V
        yaSI6ICJodHRwczovL3dpa2kubGlnby5vcmcvb3BlbmlkL2NhbGxiYW
        NrIiwgImlzcyI6ICJodHRwczovL3dpa2kubGlnby5vcmciLCAiaWF0I
        jogMTU5MzU4ODA4NSwgImF1ZCI6ICJodHRwczovL29wLnVtdS5zZSJ9
        .cRwSFNcDx6VsacAQDcIx
        5OAt_Pj30I_uUKRh04N4QJd6MZ0f50sETRv8uspSt9fMa-5yV3uzthX
        _v8OtQrV33gW1vzgOSRCdHgeCN40StbzjFk102seDwtU_Uzrcsy7KrX
        YSBp8U0dBDjuxC6h18L8ExjeR-NFjcrhy0wwua7Tnb4QqtN0QCia6DD
        8QBNVTL1Ga0YPmMdT25wS26wug23IgpbZB20VUosmMGgGtS5yCI5AwK
        Bhozv-oBH5KxxHzH1Oss-RkIGiQnjRnaWwEOTITmfZWra1eHP254wFF
        2se-EnWtz1q2XwsD9NSsOEJwWJPirPPJaKso8ng6qrrOSgw
      &response_type=code
      &client_id=https%3A%2F%2Fwiki.ligo.org
      &redirect_uri=https%3A%2F%2Fwiki.ligo.org/openid/callback
      &scope=openid+profile+email
      HTTP/1.1
    Host: op.umu.se

[Figure 70](#figure-70): [Authentication Request using Automatic Client Registration](#name-authentication-request-using)

The OP receiving this Authentication Request will, unless the RP is already registered, start to dynamically fetch and establish trust with the RP.[¶](#appendix-A.3.1-4)

##### [A.3.1.1.](#appendix-A.3.1.1) [OP Fetches Entity Statements](#name-op-fetches-entity-statement)

The OP needs to establish a Trust Chain for the RP (wiki.ligo.org). The OP in this example is configured with public keys of two federations:[¶](#appendix-A.3.1.1-1)

-   https://edugain.geant.org[¶](#appendix-A.3.1.1-2.1.1)

-   https://swamid.se[¶](#appendix-A.3.1.1-2.2.1)

The OP starts to resolve metadata for the Client Identifier https://wiki.ligo.org by fetching the Entity Configuration using the process described in [Section 9](#federation_configuration).[¶](#appendix-A.3.1.1-3)

The process is the same as described in [Appendix A.2](#op_discovery) and will result in a Trust Chain with the following Entity Statements:[¶](#appendix-A.3.1.1-4)

1.  Entity Configuration for the Leaf Entity https://wiki.ligo.org[¶](#appendix-A.3.1.1-5.1.1)

2.  Subordinate Statement issued by https://incommon.org about https://wiki.ligo.org[¶](#appendix-A.3.1.1-5.2.1)

3.  Subordinate Statement issued by https://edugain.geant.org about https://incommon.org[¶](#appendix-A.3.1.1-5.3.1)

##### [A.3.1.2.](#appendix-A.3.1.2) [OP Evaluates the RP Metadata](#name-op-evaluates-the-rp-metadat)

Using the public keys of the Trust Anchor that the LIGO Wiki RP has been provided within some secure out-of-band way, it can now verify the Trust Chain as described in [Section 10.2](#trust_chain_validation).[¶](#appendix-A.3.1.2-1)

We will not list the complete Entity Statements but only the `metadata` and `metadata_policy` parts. There are two metadata policies:[¶](#appendix-A.3.1.2-2)

edugain.geant.org:
:   
        "metadata_policy": {
          "openid_provider": {
            "contacts": {
              "add": ["ops@edugain.geant.org"]
            }
          },
          "openid_relying_party": {
            "contacts": {
              "add": ["ops@edugain.geant.org"]
            }
          }
        }

    [Figure 71](#figure-71): [Metadata Policies Related to Multiple Metadata Types](#name-metadata-policies-related-t)

    [¶](#appendix-A.3.1.2-3.2)
:   

incommon.org:
:   
        "metadata_policy": {
          "openid_relying_party": {
            "application_type": {
              "one_of": [
                "web",
                "native"
              ]
            },
            "contacts": {
              "add": ["ops@incommon.org"]
            },
            "grant_types": {
              "subset_of": [
                "authorization_code",
                "refresh_token"
              ]
            }
          }
        }

    [Figure 72](#figure-72): [Metadata Policy Related to the RP](#name-metadata-policy-related-to-)

    [¶](#appendix-A.3.1.2-4.2)
:   

Next, combine these and apply them to the metadata for wiki.ligo.org:[¶](#appendix-A.3.1.2-5)

    "metadata": {
      "openid_relying_party": {
        "application_type": "web",
        "client_name": "LIGO Wiki",
        "contacts": [
          "ops@ligo.org"
        ],
        "grant_types": [
          "authorization_code",
          "refresh_token"
        ],
        "id_token_signing_alg_values_supported":
          ["ES256", "PS256", "RS256"],
        "signed_jwks_uri": "https://wiki.ligo.org/jwks.jose",
        "redirect_uris": [
          "https://wiki.ligo.org/openid/callback"
        ],
        "response_types": [
          "code"
        ],
        "subject_type": "public",
        "token_endpoint_auth_method": "private_key_jwt"
      }
    }

[Figure 73](#figure-73): [Combined Metadata with Metadata Policy Yet to be Applied](#name-combined-metadata-with-meta)

The final result is:[¶](#appendix-A.3.1.2-7)

    "metadata": {
      "openid_relying_party": {
        "application_type": "web",
        "client_name": "LIGO Wiki",
        "contacts": [
          "ops@ligo.org",
          "ops@edugain.geant.org",
          "ops@incommon.org"
        ],
        "grant_types": [
          "refresh_token",
          "authorization_code"
        ],
        "id_token_signing_alg_values_supported":
          ["ES256", "PS256", "RS256"],
        "signed_jwks_uri": "https://wiki.ligo.org/jwks.jose",
        "redirect_uris": [
          "https://wiki.ligo.org/openid/callback"
        ],
        "response_types": [
          "code"
        ],
        "subject_type": "public",
        "token_endpoint_auth_method": "private_key_jwt"
      }
    }

[Figure 74](#figure-74): [Resolved Metadata After Metadata Policy has been Applied](#name-resolved-metadata-after-met)

Once the Trust Chain and the final Relying Party metadata have been obtained, the OpenID Provider has everything needed to validate the signature of the Request Object in the Authentication Request, using the public keys made available at the `signed_jwks_uri` endpoint.[¶](#appendix-A.3.1.2-9)

#### [A.3.2.](#appendix-A.3.2) [RP Starts with Client Registration (Explicit Client Registration)](#name-rp-starts-with-client-regis)

Here the LIGO Wiki RP sends an Explicit Registration request to the `federation_registration_endpoint` of the OP (op.umu.se). The request contains the RP\'s Entity Configuration.[¶](#appendix-A.3.2-1)

An example JWT Claims Set for the RP\'s Entity Configuration is:[¶](#appendix-A.3.2-2)

    {
      "iss": "https://wiki.ligo.org",
      "sub": "https://wiki.ligo.org",
      "iat": 1676045527,
      "exp": 1676063610,
      "aud": "https://op.umu.se",
      "metadata": {
        "openid_relying_party": {
          "application_type": "web",
          "client_name": "LIGO Wiki",
          "contacts": ["ops@ligo.org"],
          "grant_types": ["authorization_code"],
          "id_token_signed_response_alg": "RS256",
          "signed_jwks_uri": "https://wiki.ligo.org/jwks.jose",
          "redirect_uris": [
            "https://wiki.ligo.org/openid/callback"
          ],
          "response_types": ["code"],
          "subject_type": "public"
        }
      },
      "jwks": {
        "keys": [
          {
            "kty": "RSA",
            "use": "sig",
            "kid": "U2JTWHY0VFg0a2FEVVdTaHptVDJsNDNiSDk5MXRBVEtNSFVkeXZwb",
            "e": "AQAB",
            "n": "4AZjgqFwMhTVSLrpzzNcwaCyVD88C_Hb3Bmor97vH-2AzldhuVb8K..."
          }
        ]
      },
      "authority_hints": ["https://incommon.org"]
    }

[Figure 75](#figure-75): [RP\'s Entity Configuration JWT Claims Set](#name-rps-entity-configuration-jw)

The OP receives the RP\'s Entity Configuration and proceeds with the sequence of steps laid out in [Appendix A.2](#op_discovery).[¶](#appendix-A.3.2-4)

The OP successfully resolves the same RP metadata described in [Appendix A.3.1.2](#rp_metadata_eval). It then registers the RP in compliance with its own OP metadata and returns the result in a registration Entity Statement.[¶](#appendix-A.3.2-5)

Assuming the OP does not support refresh tokens it will register the RP for the `authorization_code` grant type only. This is reflected in the metadata returned to the RP.[¶](#appendix-A.3.2-6)

The returned metadata also includes the `client_id`, the `client_secret` and other parameters that the OP provisioned for the RP.[¶](#appendix-A.3.2-7)

Here is an example JWT Claims Set of the registration Entity Statement returned by the OP to the RP after successful explicit client registration:[¶](#appendix-A.3.2-8)

    {
      "iss": "https://op.umu.se",
      "sub": "https://wiki.ligo.org",
      "aud": "https://wiki.ligo.org",
      "iat": 1601457619,
      "exp": 1601544019,
      "trust_anchor": "https://edugain.geant.org",
      "metadata": {
        "openid_relying_party": {
          "client_id": "m3GyHw",
          "client_secret_expires_at": 1604049619,
          "client_secret":
            "cb44eed577f3b5edf3e08362d47a0dc44630b3dc6ea99f7a79205",
          "client_id_issued_at": 1601457619,
          "application_type": "web",
          "client_name": "LIGO Wiki",
          "contacts": [
            "ops@edugain.geant.org",
            "ops@incommon.org",
            "ops@ligo.org"
          ],
          "grant_types": [
            "authorization_code"
          ],
          "id_token_signed_response_alg": "RS256",
          "signed_jwks_uri": "https://wiki.ligo.org/jwks.jose",
          "redirect_uris": [
            "https://wiki.ligo.org/openid/callback"
          ],
          "response_types": [
            "code"
          ],
          "subject_type": "public"
        }
      },
      "authority_hints": [
        "https://incommon.org"
      ],
      "jwks": {
        "keys": [
          {
            "kty": "RSA",
            "use": "sig",
            "kid": "U2JTWHY0VFg0a2FEVVdTaHptVDJsNDNiSDk5MXRBVEtNSFVkeXZwb",
            "e": "AQAB",
            "n": "4AZjgqFwMhTVSLrpzzNcwaCyVD88C_Hb3Bmor97vH-2AzldhuVb8K..."
          },
          {
            "kty": "EC",
            "use": "sig",
            "kid": "LWtFcklLOGdrW",
            "crv": "P-256",
            "x": "X2S1dFE7zokQDST0bfHdlOWxOc8FC1l4_sG1Kwa4l4s",
            "y": "812nU6OCKxgc2ZgSPt_dkXbYldG_smHJi4wXByDHc6g"
          }
        ]
      }
    }

[Figure 76](#figure-76): [JWT Claims Set of Registration Entity Statement Returned by OP to RP after Explicit Client Registration](#name-jwt-claims-set-of-registrat)

## [Appendix B.](#appendix-B) [Notices](#name-notices)

Copyright (c) 2026 The OpenID Foundation.[¶](#appendix-B-1)

The OpenID Foundation (OIDF) grants to any Contributor, developer, implementer, or other interested party a non-exclusive, royalty free, worldwide copyright license to reproduce, prepare derivative works from, distribute, perform and display, this Implementers Draft, Final Specification, or Final Specification Incorporating Errata Corrections solely for the purposes of (i) developing specifications, and (ii) implementing Implementers Drafts, Final Specifications, and Final Specification Incorporating Errata Corrections based on such documents, provided that attribution be made to the OIDF as the source of the material, but that such attribution does not indicate an endorsement by the OIDF.[¶](#appendix-B-2)

The technology described in this specification was made available from contributions from various sources, including members of the OpenID Foundation and others. Although the OpenID Foundation has taken steps to help ensure that the technology is available for distribution, it takes no position regarding the validity or scope of any intellectual property or other rights that might be claimed to pertain to the implementation or use of the technology described in this specification or the extent to which any license under such rights might or might not be available; neither does it represent that it has made any independent effort to identify any such rights. The OpenID Foundation and the contributors to this specification make no (and hereby expressly disclaim any) warranties (express, implied, or otherwise), including implied warranties of merchantability, non-infringement, fitness for a particular purpose, or title, related to this specification, and the entire risk as to implementing this specification is assumed by the implementer. The OpenID Intellectual Property Rights policy (found at openid.net) requires contributors to offer a patent promise not to assert certain patent claims against other contributors and against implementers. OpenID invites any interested party to bring to its attention any copyrights, patents, patent applications, or other proprietary rights that may cover technology that may be required to practice this specification.[¶](#appendix-B-3)

## [Acknowledgements](#name-acknowledgements)

The authors wish to acknowledge the contributions of the following individuals and organizations to this specification: Marcus Almgren, Patrick Amrein, Pål Axelsson, Pasquale Barbaro, Ralph Bragg, Peter Brand, Brian Campbell, David Chadwick, Michele D\'Amico, Kushal Das, Andrii Deinega, Erick Domingues, Heather Flanagan, Michael Fraser, Samuel Gulliksson, Joseph Heenan, Pedram Hosseyni, Marko Ivančić, Łukasz Jaromin, Leif Johansson, Takahiko Kawasaki, Ralf Küsters, Torsten Lodderstedt, Josh Mandel, Francesco Marino, John Melati, Alexey Melnikov, Henri Mikkonen, Aaron Parecki, Eduardo Perottoni, Chris Phillips, Roberto Polli, Justin Richer, Jouke Roorda, Nat Sakimura, Mischa Sallé, Stefan Santesson, Marcos Sanz, Michael Schwartz, Giada Sciarretta, Amir Sharif, Yaron Sheffer, Sean Turner, Davide Vaghetti, Niels van Dijk, Luiky Vasconcelos, Elaine Wooton, Tim Würtele, Kristina Yasuda, Gabriel Zachmann, the JRA3T3 task force of GEANT4-2, and the SIROS Foundation.[¶](#appendix-C-1)

## [Authors\' Addresses](#name-authors-addresses)

Roland Hedberg (editor)

independent

Email: <roland@catalogix.se>

Michael B. Jones

Self-Issued Consulting

Email: <michael_b_jones@hotmail.com>

URI: [https://self-issued.info/](https://self-issued.info/)

Andreas Åkre Solberg

Sikt

Email: <Andreas.Solberg@sikt.no>

URI: [https://www.linkedin.com/in/andreassolberg/](https://www.linkedin.com/in/andreassolberg/)

John Bradley

Yubico

Email: <ve7jtb@ve7jtb.com>

URI: [https://www.linkedin.com/in/ve7jtb/](https://www.linkedin.com/in/ve7jtb/)

Giuseppe De Marco

independent

Email: <demarcog83@gmail.com>

URI: [https://www.linkedin.com/in/giuseppe-de-marco-bb054245/](https://www.linkedin.com/in/giuseppe-de-marco-bb054245/)

Vladimir Dzhuvinov

Connect2id

Email: <vladimir@connect2id.com>

URI: [https://www.linkedin.com/in/vladimirdzhuvinov/](https://www.linkedin.com/in/vladimirdzhuvinov/)
