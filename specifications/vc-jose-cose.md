---
color-scheme: light
description: This specification defines how to secure credentials and presentations conforming to the Verifiable Credential data model \[VC-DATA-MODEL-2.0\] with JSON Object Signing and Encryption (JOSE), Selective Disclosure for JWTs \[SD-JWT\], and CBOR Object Signing and Encryption (COSE) \[RFC9052\]. This enables the Verifiable Credential data model \[VC-DATA-MODEL-2.0\] to be implemented with standards for signing and encryption that are widely adopted.
generator: ReSpec 35.3.0
lang: en
title: Securing Verifiable Credentials using JOSE and COSE
viewport: width=device-width, initial-scale=1, shrink-to-fit=no
---

[![W3C](https://www.w3.org/StyleSheets/TR/2021/logos/W3C)](https://www.w3.org/)

# Securing Verifiable Credentials using JOSE and COSE

[W3C Recommendation](https://www.w3.org/standards/types#REC) 15 May 2025

More details about this document

This version:
:   [https://www.w3.org/TR/2025/REC-vc-jose-cose-20250515/](https://www.w3.org/TR/2025/REC-vc-jose-cose-20250515/)

Latest published version:
:   <https://www.w3.org/TR/vc-jose-cose/>

Latest editor\'s draft:
:   <https://w3c.github.io/vc-jose-cose/>

History:
:   <https://www.w3.org/standards/history/vc-jose-cose/>
:   [Commit history](https://github.com/w3c/vc-jose-cose/commits/)

Implementation report:
:   <https://w3c.github.io/vc-jose-cose-test-suite/>

Editors:
:   [Michael Jones](https://self-issued.info/) (Self-Issued Consulting)
:   Michael Prorock ([Mesur.io](https://mesur.io/))
:   [Gabe Cohen](https://github.com/decentralgabe) (Invited Expert)

Feedback:
:   [GitHub w3c/vc-jose-cose](https://github.com/w3c/vc-jose-cose/) ([pull requests](https://github.com/w3c/vc-jose-cose/pulls/), [new issue](https://github.com/w3c/vc-jose-cose/issues/new/choose), [open issues](https://github.com/w3c/vc-jose-cose/issues/))

Errata:
:   [Errata exists](https://w3c.github.io/vc-jose-cose/errata.html).

Related Documents
:   [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/)
:   [Controlled Identifiers 1.0](https://www.w3.org/TR/cid-1.0/)

See also [**translations**](https://www.w3.org/Translations/?technology=vc-jose-cose).

[Copyright](https://www.w3.org/policies/#copyright) © 2025 [World Wide Web Consortium](https://www.w3.org/). W3C^®^ [liability](https://www.w3.org/policies/#Legal_Disclaimer), [trademark](https://www.w3.org/policies/#W3C_Trademarks) and [permissive document license](https://www.w3.org/copyright/software-license-2023/ "W3C Software and Document Notice and License") rules apply.

------------------------------------------------------------------------

## Abstract

This specification defines how to secure credentials and presentations conforming to the Verifiable Credential data model \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\] with JSON Object Signing and Encryption ([JOSE](https://datatracker.ietf.org/wg/jose/about/)), Selective Disclosure for JWTs \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\], and CBOR Object Signing and Encryption (COSE) \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\]. This enables the Verifiable Credential data model \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\] to be implemented with standards for signing and encryption that are widely adopted.

## Status of This Document

*This section describes the status of this document at the time of its publication. A list of current W3C publications and the latest revision of this technical report can be found in the [W3C standards and drafts index](https://www.w3.org/TR/) at https://www.w3.org/TR/.*

The Working Group is actively seeking implementation feedback for this specification. In order to exit the Candidate Recommendation phase, the Working Group has set the requirement of at least two independent implementations for each mandatory feature in the specification. For details on the conformance testing process, see the test suite listed in the [implementation report](https://w3c.github.io/vc-jose-cose-test-suite/).

This document was published by the [Verifiable Credentials Working Group](https://www.w3.org/groups/wg/vc) as a Recommendation using the [Recommendation track](https://www.w3.org/policies/process/20231103/#recs-and-notes).

W3C recommends the wide deployment of this specification as a standard for the Web.

A W3C Recommendation is a specification that, after extensive consensus-building, is endorsed by W3C and its Members, and has commitments from Working Group members to [royalty-free licensing](https://www.w3.org/policies/patent-policy/#sec-Requirements) for implementations.

This document was produced by a group operating under the [W3C Patent Policy](https://www.w3.org/policies/patent-policy/). W3C maintains a [public list of any patent disclosures](https://www.w3.org/groups/wg/vc/ipr) made in connection with the deliverables of the group; that page also includes instructions for disclosing a patent. An individual who has actual knowledge of a patent which the individual believes contains [Essential Claim(s)](https://www.w3.org/policies/patent-policy/#def-essential) must disclose the information in accordance with [section 6 of the W3C Patent Policy](https://www.w3.org/policies/patent-policy/#sec-Disclosure).

This document is governed by the [03 November 2023 W3C Process Document](https://www.w3.org/policies/process/20231103/).

## Table of Contents

1.  [Abstract](#abstract)
2.  [Status of This Document](#sotd)
3.  [1. Introduction](#section-introduction)
    1.  [1.1 Conformance](#conformance)
        1.  [1.1.1 Conformance Classes](#conformance-classes)
        2.  [1.1.2 Securing Verifiable Credentials](#securing-verifiable-credentials)
4.  [2. Terminology](#terminology)
5.  [3. Securing the VC Data Model](#securing-the-vc-data-model)
    1.  [3.1 With JOSE](#with-jose)
        1.  [3.1.1 Securing JSON-LD Verifiable Credentials with JOSE](#securing-with-jose)
        2.  [3.1.2 Securing JSON-LD Verifiable Presentations with JOSE](#securing-vps-with-jose)
        3.  [3.1.3 JOSE Header Parameters and JWT Claims](#jose-header-parameters-jwt-claims)
    2.  [3.2 With SD-JWT](#with-sd-jwt)
        1.  [3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT](#securing-with-sd-jwt)
        2.  [3.2.2 Securing JSON-LD Verifiable Presentations with SD-JWT](#securing-vps-sd-jwt)
    3.  [3.3 With COSE](#securing-with-cose)
        1.  [3.3.1 Securing JSON-LD Verifiable Credentials with COSE](#securing-vcs-with-cose)
        2.  [3.3.2 Securing JSON-LD Verifiable Presentations with COSE](#securing-vps-with-cose)
        3.  [3.3.3 COSE Header Parameters and CWT Claims](#cose-header-param-cwt-claims)
6.  [4. Key Discovery](#key-discovery)
    1.  [4.1 Using Header Parameters and Claims for Key Discovery](#using-header-params-claims-key-discovery)
        1.  [4.1.1 kid](#kid)
        2.  [4.1.2 iss](#iss)
        3.  [4.1.3 cnf](#cnf)
    2.  [4.2 Using Controlled Identifier Documents](#using-controlled-identifier-documents)
7.  [5. Algorithms](#algorithms)
    1.  [5.1 Verifying a Credential or Presentation Secured with JOSE](#alg-jose)
    2.  [5.2 Verifying a Credential or Presentation Secured with SD-JWT](#alg-sd-jwt)
    3.  [5.3 Verifying a Credential or Presentation Secured with COSE](#alg-cose)
    4.  [5.4 Validation](#validation-algorithm)
8.  [6. IANA Considerations](#iana-considerations)
    1.  [6.1 Media Types](#media-types)
        1.  [6.1.1 `application/vc+jwt`](#vc-json-jwt)
        2.  [6.1.2 `application/vp+jwt`](#vp-json-jwt)
        3.  [6.1.3 `application/vc+sd-jwt`](#vc-json-sd-jwt)
        4.  [6.1.4 `application/vp+sd-jwt`](#vp-json-sd-jwt)
        5.  [6.1.5 `application/vc+cose`](#vc-json-cose)
        6.  [6.1.6 `application/vp+cose`](#vp-json-cose)
9.  [7. Other Considerations](#other-considerations)
    1.  [7.1 Privacy Considerations](#privacy-considerations)
    2.  [7.2 Security Considerations](#security-considerations)
    3.  [7.3 Accessibility](#accessibility)
10. [8. Examples](#examples)
    1.  [8.1 Controllers](#controllers)
    2.  [8.2 Credentials](#credentials)
    3.  [8.3 Presentations](#presentations)
    4.  [8.4 Data URIs](#date-uris)
    5.  [8.5 COSE Examples](#cose-examples)
11. [A. Revision History](#revision-history)
12. [B. Acknowledgements](#acknowledgements)
13. [C. References](#references)
    1.  [C.1 Normative references](#normative-references)
    2.  [C.2 Informative references](#informative-references)

## 1. Introduction

[](#section-introduction)

This specification defines how to secure media types expressing Verifiable Credentials and Verifiable Presentations as described in \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\] using approaches defined by the JOSE, OAuth, and COSE working groups at the IETF. This includes JSON Web Signature (JWS) \[[RFC7515](#bib-rfc7515 "JSON Web Signature (JWS)")\], Selective Disclosure for JWTs \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\], and CBOR Object Signing and Encryption (COSE) \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\]. It uses content types \[[RFC6838](#bib-rfc6838 "Media Type Specifications and Registration Procedures")\] to distinguish between the data types of unsecured documents conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\] and the data types of secured documents conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

JSON Web Signature (JWS) \[[RFC7515](#bib-rfc7515 "JSON Web Signature (JWS)")\] defines a standard means of digitally signing documents, including JSON documents, using JSON-based data structures. It provides a means to ensure the integrity, authenticity, and non-repudiation of the information contained in the document. Selective Disclosure for JWTs (SD-JWT) \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] builds on JWS by also providing a mechanism enabling selective disclosure of document elements. These properties make JWS and SD-JWT especially well-suited to securing documents conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

CBOR Object Signing and Encryption (COSE) \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] defines a standard means of representing digitally signed data structures using Concise Binary Object Representation (CBOR) \[[RFC8949](#bib-rfc8949 "Concise Binary Object Representation (CBOR)")\]. Like JWS, COSE provides a standardized way to secure the integrity, authenticity, and confidentiality of information. It offers a flexible and extensible set of cryptographic options, allowing for a wide range of algorithms to be used for signing and encryption.

COSE supports two main operations: signing and encryption. For signing, COSE allows the creation of digital signatures over CBOR data using various algorithms such as RSA, ECDSA, and EdDSA. These signatures provide assurance of data integrity and authenticity. COSE also supports encryption, enabling the confidentiality of CBOR data by encrypting it with symmetric or asymmetric encryption algorithms.

### 1.1 Conformance

[](#conformance)

As well as sections marked as non-normative, all authoring guidelines, diagrams, examples, and notes in this specification are non-normative. Everything else in this specification is normative.

The key words *MAY*, *MUST*, *MUST NOT*, *NOT RECOMMENDED*, *RECOMMENDED*, *SHOULD*, and *SHOULD NOT* in this document are to be interpreted as described in [BCP 14](https://www.rfc-editor.org/info/bcp14) \[[RFC2119](#bib-rfc2119 "Key words for use in RFCs to Indicate Requirement Levels")\] \[[RFC8174](#bib-rfc8174 "Ambiguity of Uppercase vs Lowercase in RFC 2119 Key Words")\] when, and only when, they appear in all capitals, as shown here.

#### 1.1.1 Conformance Classes

[](#conformance-classes)

A conforming JWS document is one that conforms to all of the \"*MUST*\" statements in Section [3.1 With JOSE](#secure-with-jose).

A conforming JWS issuer implementation produces [conforming JWS documents](#dfn-conforming-jws-document) and *MUST* secure them as described in Section [3.1 With JOSE](#secure-with-jose).

A conforming JWS verifier implementation verifies [conforming JWS documents](#dfn-conforming-jws-document) as described in Section [3.1 With JOSE](#secure-with-jose).

A conforming SD-JWT document is one that conforms to all of the \"*MUST*\" statements in Section [3.2 With SD-JWT](#secure-with-sd-jwt).

A conforming SD-JWT issuer implementation produces [conforming SD-JWT documents](#dfn-conforming-sd-jwt-document) and *MUST* secure them as described in Section [3.2 With SD-JWT](#secure-with-sd-jwt).

A conforming SD-JWT verifier implementation verifies [conforming SD-JWT documents](#dfn-conforming-sd-jwt-document) as described in Section [3.2 With SD-JWT](#secure-with-sd-jwt).

A conforming COSE document is one that conforms to all of the \"*MUST*\" statements in Section [3.3 With COSE](#secure-with-cose).

A conforming COSE issuer implementation produces [conforming COSE documents](#dfn-conforming-cose-document) and *MUST* secure them as described in Section [3.3 With COSE](#secure-with-cose).

A conforming COSE verifier implementation verifies [conforming COSE documents](#dfn-conforming-cose-document) as described in Section [3.3 With COSE](#secure-with-cose).

#### 1.1.2 Securing Verifiable Credentials

[](#securing-verifiable-credentials)

The [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#securing-mechanism-specifications) describes the approach taken by this specification to secure JSON and CBOR claims by *applying an `enveloping proof`*.

This specification defines how to secure different data structures using various `enveloping proof` mechanisms:

JSON Web Token (JWT):
:   A JWT secures a JWT Claims Set, in its entirety. A JWT Claims Set is a JSON object containing one or more claims about an entity (typically the subject of the JWT). If any part of the JWT Claims Set is to be revealed, all claims in that set must be revealed; there is no option to reveal (or conceal) *some* of the claims while concealing (or revealing) the others.

Selective Disclosure JSON Web Token (SD-JWT):
:   An SD-JWT secures a JWT Claims Set, similar to a JWT securing a JWT Claims Set, but with the added capabilities of selectively revealing or withholding parts of the JWT Claims Set. A JWT Claims Set is one or more claims about an entity (typically the subject of the SD-JWT).

CBOR Object Signing and Encryption (COSE):
:   COSE secures CBOR (Concise Binary Object Representation) data structures. CBOR is a binary data format that is more compact than JSON and is designed for constrained environments.

In the context of Verifiable Credentials:

-   When using JWTs, the Verifiable Credential or Presentation is encoded as a JWT Claims Set.
-   When using SD-JWTs, the Verifiable Credential or Presentation is encoded as a JWT Claims Set with Selective Disclosure features.
-   When using COSE, the Verifiable Credential or Presentation is encoded as a CBOR data structure.

In all cases, the underlying data model of the Verifiable Credential or Presentation remains consistent with the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], but the encoding and security mechanisms differ.

The normative statements in [Securing Mechanisms](https://www.w3.org/TR/vc-data-model-2.0/#securing-mechanisms) apply to securing `application/vc+jwt` and `application/vp+jwt`, `application/vc+sd-jwt` and `application/vp+sd-jwt`, `application/vc+cose` and `application/vp+cose`.

##### 1.1.2.1 JWT Format and Requirements

[](#jwt-format-and-requirements)

JSON Web Token implementers are advised to review [Implementation Requirements](https://www.rfc-editor.org/rfc/rfc7519#section-8).

Issuers, Holders, and Verifiers of JWTs *MUST* understand the effect of the JSON Web Token header parameter setting of `"alg": "none"` when using JSON Web Tokens to secure \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\]. When content types from the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\] are secured using JSON Web Tokens, the header parameter setting of `"alg": "none"` is used to communicate that a Verifiable Credential or Verifiable Presentation encoded as a JWT Claims Set has no integrity protection.

Issuers, Holders, and Verifiers *MUST* ignore all JWT Claims Sets that have no integrity protection.

The JWT Claim Names `vc` and `vp` *MUST NOT* be present in any JWT Claims Set that comprises a [verifiable credential](#dfn-verifiable-credentials) or a [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation).

##### 1.1.2.2 SD-JWT Format and Requirements

[](#sd-jwt-format-and-requirements)

This specification uses Selective Disclosure for JWTs (SD-JWT) as defined in the IETF draft \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\]. Implementers *SHOULD* refer to this draft for the full details of the SD-JWT format and processing requirements.

-   An SD-JWT consists of three main parts: the SD-JWT itself, optional disclosures, and an optional KB-JWT (Key Binding JWT). These parts are separated by tilde (\~) characters.
-   If the KB-JWT is not present, the SD-JWT must end with a tilde (\~) character. This is crucial for correct parsing and processing of the SD-JWT.
-   Selective disclosure is achieved through the use of disclosure objects. These are base64url-encoded JSON arrays containing the digest of the disclosed claim, the claim name, and the claim value.
-   Each disclosable claim is combined with a salt value before hashing to prevent dictionary attacks.

## 2. Terminology

[](#terminology)

This section defines the terms used in this specification. A link to these terms is included whenever they appear in this specification.

public key
:   Cryptographic material that can be used to verify digital proofs created with a corresponding [private key](#dfn-private-key).

private key
:   Cryptographic material that can be used to generate digital proofs.

verifiable credential
:   A standard data model and representation format for expressing cryptographically-verifiable digital credentials, as defined by the W3C Verifiable Credentials specification \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

controlled identifier document
:   A document that contains public cryptographic material as defined in the [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/) specification.

## 3. Securing the VC Data Model

[](#securing-the-vc-data-model)

This section outlines how to secure documents conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\] using JOSE, SD-JWT, and COSE.

Documents conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], and their associated media types, rely on JSON-LD, which is an extensible format for describing linked data; see [JSON-LD Relationship to RDF](https://www.w3.org/TR/json-ld11/#relationship-to-rdf).

A benefit to this approach is that payloads can be made to conform directly to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\] without any mappings or transformation, while at the same time supporting registered header parameters and claims that are understood in the context of JOSE, SD-JWT, and COSE.

It is *RECOMMENDED* that media types be used to distinguish [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#credentials) and [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#presentations) from other kinds of secured JSON or CBOR.

The most specific media type (or subtype) available *SHOULD* be used, instead of more generic media types (or supertypes). For example, rather than the general `application/sd-jwt`, `application/vc+sd-jwt` *SHOULD* be used, unless there is a more specific media type that would even better identify the secured envelope format.

If implementations do not know which media type to use, media types defined in this specification *MUST* be used.

### 3.1 With JOSE

[](#with-jose)

#### 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE

[](#securing-with-jose)

This section details how to use JOSE to secure [verifiable credentials](#dfn-verifiable-credentials) conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

A [conforming JWS issuer implementation](#dfn-conforming-jws-issuer-implementation) *MUST* use \[[RFC7515](#bib-rfc7515 "JSON Web Signature (JWS)")\] to secure this media type. The unsecured [verifiable credential](#dfn-verifiable-credentials) is the unencoded JWS payload.

The `typ` header parameter *SHOULD* be `vc+jwt`. When present, the `cty` header parameter *SHOULD* be `vc`. The `cty` header parameter value can be used to differentiate between secured content of different types when using `vc+jwt`. The `content type` header parameter is optional, and can be used to express a more specific media type than `application/vc` when one is available. See [Registered Header Parameter Names](https://www.rfc-editor.org/rfc/rfc7515#section-4.1) for additional details regarding usage of `typ` and `cty`.

A [conforming JWS verifier implementation](#dfn-conforming-jws-verifier-implementation) *MUST* use \[[RFC7515](#bib-rfc7515 "JSON Web Signature (JWS)")\] to verify [conforming JWS documents](#dfn-conforming-jws-document) that use this media type.

To encrypt a secured [verifiable credential](#dfn-verifiable-credentials) when transmitting over an insecure channel, implementers *MAY* use JSON Web Encryption (JWE) \[[RFC7516](#bib-rfc7516 "JSON Web Encryption (JWE)")\] by nesting the secured [verifiable credential](#dfn-verifiable-credentials) as the plaintext payload of a JWE, per the description of Nested JWTs in \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\].

[Example 1](#example-a-simple-example-of-a-verifiable-credential-secured-with-jose): A simple example of a verifiable credential secured with JOSE

-   Credential
-   jose

``` {.nohighlight .vc vc-tabs="jose"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "id": "http://university.example/credentials/3732",
  "type": ["VerifiableCredential", "ExampleDegreeCredential", "ExamplePersonCredential"],
  "issuer": "https://university.example/issuers/14",
  "validFrom": "2010-01-01T19:23:24Z",
  "credentialSubject": {
    "id": "did:example:ebfeb1f712ebc6f1c276e12ec21",
    "degree": {
      "type": "ExampleBachelorDegree",
      "name": "Bachelor of Science and Arts"
    },
    "alumniOf": {
      "name": "Example University"
    }
  },
  "credentialSchema": [{
    "id": "https://example.org/examples/degree.json",
    "type": "JsonSchema"
  },
  {
    "id": "https://example.org/examples/alumni.json",
    "type": "JsonSchema"
  }]
}
```

**Protected Headers**

    {
      "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
      "alg": "ES256"
    }

**application/vc**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "id": "http://university.example/credentials/3732",
      "type": [
        "VerifiableCredential",
        "ExampleDegreeCredential",
        "ExamplePersonCredential"
      ],
      "issuer": "https://university.example/issuers/14",
      "validFrom": "2010-01-01T19:23:24Z",
      "credentialSubject": {
        "id": "did:example:ebfeb1f712ebc6f1c276e12ec21",
        "degree": {
          "type": "ExampleBachelorDegree",
          "name": "Bachelor of Science and Arts"
        },
        "alumniOf": {
          "name": "Example University"
        }
      },
      "credentialSchema": [
        {
          "id": "https://example.org/examples/degree.json",
          "type": "JsonSchema"
        },
        {
          "id": "https://example.org/examples/alumni.json",
          "type": "JsonSchema"
        }
      ]
    }

**application/vc+jwt**

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaWQiOiJodHRwOi8vdW5pdmVyc2l0eS5leGFtcGxlL2NyZWRlbnRpYWxzLzM3MzIiLCJ0eXBlIjpbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwiRXhhbXBsZURlZ3JlZUNyZWRlbnRpYWwiLCJFeGFtcGxlUGVyc29uQ3JlZGVudGlhbCJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzE0IiwidmFsaWRGcm9tIjoiMjAxMC0wMS0wMVQxOToyMzoyNFoiLCJjcmVkZW50aWFsU3ViamVjdCI6eyJpZCI6ImRpZDpleGFtcGxlOmViZmViMWY3MTJlYmM2ZjFjMjc2ZTEyZWMyMSIsImRlZ3JlZSI6eyJ0eXBlIjoiRXhhbXBsZUJhY2hlbG9yRGVncmVlIiwibmFtZSI6IkJhY2hlbG9yIG9mIFNjaWVuY2UgYW5kIEFydHMifSwiYWx1bW5pT2YiOnsibmFtZSI6IkV4YW1wbGUgVW5pdmVyc2l0eSJ9fSwiY3JlZGVudGlhbFNjaGVtYSI6W3siaWQiOiJodHRwczovL2V4YW1wbGUub3JnL2V4YW1wbGVzL2RlZ3JlZS5qc29uIiwidHlwZSI6Ikpzb25TY2hlbWEifSx7ImlkIjoiaHR0cHM6Ly9leGFtcGxlLm9yZy9leGFtcGxlcy9hbHVtbmkuanNvbiIsInR5cGUiOiJKc29uU2NoZW1hIn1dfQ .xbpSjNX9SAAn8YM31TcXFIWgdLwNGpQguO2xoTWv_NoE1cSNW5RlWbsaO3hlYE6y9aa4q7ie5FXubvPwi1K\_\_g

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#example-using-the-credentialschema-property-to-perform-json-schema-validation) for more details regarding this example.

#### 3.1.2 Securing JSON-LD Verifiable Presentations with JOSE

[](#securing-vps-with-jose)

This section details how to use JOSE to secure [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

A [conforming JWS issuer implementation](#dfn-conforming-jws-issuer-implementation) *MUST* use \[[RFC7515](#bib-rfc7515 "JSON Web Signature (JWS)")\] to secure this media type. The unsecured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) is the unencoded JWS payload.

The `typ` header parameter *SHOULD* be `vp+jwt`. When present, the `cty` header parameter *SHOULD* be `vp`. The `cty` header parameter value can be used to differentiate between secured content of different types when using `vp+jwt`. The `content type` header parameter is optional, and can be used to express a more specific media type than `application/vc` when one is available. See [Registered Header Parameter Names](https://www.rfc-editor.org/rfc/rfc7515#section-4.1) for additional details regarding usage of `typ` and `cty`.

A [conforming JWS verifier implementation](#dfn-conforming-jws-verifier-implementation) *MUST* use \[[RFC7515](#bib-rfc7515 "JSON Web Signature (JWS)")\] to verify [conforming JWS documents](#dfn-conforming-jws-document) that use this media type.

Verifiable Credentials secured in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations) *MUST* use the [Enveloped Verifiable Credential](https://www.w3.org/TR/vc-data-model-2.0/#defn-EnvelopedVerifiableCredential) type defined by the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

Verifiable Presentations in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations) *MUST* use the [Enveloped Verifiable Presentation](https://www.w3.org/TR/vc-data-model-2.0/#defn-EnvelopedVerifiablePresentation) type defined by the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

Credentials in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) *MUST* be secured. In this case, these [credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-credential) are secured using JWS.

To encrypt a secured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) when transmitting over an insecure channel, implementers *MAY* use JSON Web Encryption (JWE) \[[RFC7516](#bib-rfc7516 "JSON Web Encryption (JWE)")\] by nesting the secured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) as the plaintext payload of a JWE, per the description of Nested JWTs in \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\].

[Example 2](#example-a-simple-example-of-a-verifiable-presentation-secured-with-jose-with-the-envelopedverifiablecredential-type): A simple example of a verifiable presentation secured with JOSE with the EnvelopedVerifiableCredential type

-   Presentation
-   jose

``` {.nohighlight .vc vc-tabs="jose"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "type": "VerifiablePresentation",
  "verifiableCredential": [{
    "@context": ["https://www.w3.org/ns/credentials/v2"],
    "type": ["EnvelopedVerifiableCredential"],
    "id": "data:application/vc+jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMzODQifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaWQiOiJodHRwOi8vdW5pdmVyc2l0eS5leGFtcGxlL2NyZWRlbnRpYWxzLzE4NzIiLCJ0eXBlIjpbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwiRXhhbXBsZUFsdW1uaUNyZWRlbnRpYWwiXSwiaXNzdWVyIjoiaHR0cHM6Ly91bml2ZXJzaXR5LmV4YW1wbGUvaXNzdWVycy81NjUwNDkiLCJ2YWxpZEZyb20iOiIyMDEwLTAxLTAxVDE5OjIzOjI0WiIsImNyZWRlbnRpYWxTY2hlbWEiOnsiaWQiOiJodHRwczovL2V4YW1wbGUub3JnL2V4YW1wbGVzL2RlZ3JlZS5qc29uIiwidHlwZSI6Ikpzb25TY2hlbWEifSwiY3JlZGVudGlhbFN1YmplY3QiOnsiaWQiOiJkaWQ6ZXhhbXBsZToxMjMiLCJkZWdyZWUiOnsidHlwZSI6IkJhY2hlbG9yRGVncmVlIiwibmFtZSI6IkJhY2hlbG9yIG9mIFNjaWVuY2UgYW5kIEFydHMifX19.d2k4O3FytQJf83kLh-HsXuPvh6yeOlhJELVo5TF71gu7elslQyOf2ZItAXrtbXF4Kz9WivNdztOayz4VUQ0Mwa8yCDZkP9B2pH-9S_tcAFxeoeJ6Z4XnFuL_DOfkR1fP"
  }]
}
```

**Protected Headers**

    {
      "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
      "alg": "ES256"
    }

**application/vp**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "type": "VerifiablePresentation",
      "verifiableCredential": [
        {
          "@context": "https://www.w3.org/ns/credentials/v2",
          "id": "data:application/vc+jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMzODQifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaWQiOiJodHRwOi8vdW5pdmVyc2l0eS5leGFtcGxlL2NyZWRlbnRpYWxzLzE4NzIiLCJ0eXBlIjpbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwiRXhhbXBsZUFsdW1uaUNyZWRlbnRpYWwiXSwiaXNzdWVyIjoiaHR0cHM6Ly91bml2ZXJzaXR5LmV4YW1wbGUvaXNzdWVycy81NjUwNDkiLCJ2YWxpZEZyb20iOiIyMDEwLTAxLTAxVDE5OjIzOjI0WiIsImNyZWRlbnRpYWxTY2hlbWEiOnsiaWQiOiJodHRwczovL2V4YW1wbGUub3JnL2V4YW1wbGVzL2RlZ3JlZS5qc29uIiwidHlwZSI6Ikpzb25TY2hlbWEifSwiY3JlZGVudGlhbFN1YmplY3QiOnsiaWQiOiJkaWQ6ZXhhbXBsZToxMjMiLCJkZWdyZWUiOnsidHlwZSI6IkJhY2hlbG9yRGVncmVlIiwibmFtZSI6IkJhY2hlbG9yIG9mIFNjaWVuY2UgYW5kIEFydHMifX19.d2k4O3FytQJf83kLh-HsXuPvh6yeOlhJELVo5TF71gu7elslQyOf2ZItAXrtbXF4Kz9WivNdztOayz4VUQ0Mwa8yCDZkP9B2pH-9S_tcAFxeoeJ6Z4XnFuL_DOfkR1fP;data:application/vc+jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMzODQifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaWQiOiJodHRwOi8vdW5pdmVyc2l0eS5leGFtcGxlL2NyZWRlbnRpYWxzLzE4NzIiLCJ0eXBlIjpbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwiRXhhbXBsZUFsdW1uaUNyZWRlbnRpYWwiXSwiaXNzdWVyIjoiaHR0cHM6Ly91bml2ZXJzaXR5LmV4YW1wbGUvaXNzdWVycy81NjUwNDkiLCJ2YWxpZEZyb20iOiIyMDEwLTAxLTAxVDE5OjIzOjI0WiIsImNyZWRlbnRpYWxTY2hlbWEiOnsiaWQiOiJodHRwczovL2V4YW1wbGUub3JnL2V4YW1wbGVzL2RlZ3JlZS5qc29uIiwidHlwZSI6Ikpzb25TY2hlbWEifSwiY3JlZGVudGlhbFN1YmplY3QiOnsiaWQiOiJkaWQ6ZXhhbXBsZToxMjMiLCJkZWdyZWUiOnsidHlwZSI6IkJhY2hlbG9yRGVncmVlIiwibmFtZSI6IkJhY2hlbG9yIG9mIFNjaWVuY2UgYW5kIEFydHMifX19.d2k4O3FytQJf83kLh-HsXuPvh6yeOlhJELVo5TF71gu7elslQyOf2ZItAXrtbXF4Kz9WivNdztOayz4VUQ0Mwa8yCDZkP9B2pH-9S_tcAFxeoeJ6Z4XnFuL_DOfkR1fP",
          "type": "EnvelopedVerifiableCredential"
        }
      ]
    }

**application/vp+jwt**

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidHlwZSI6IlZlcmlmaWFibGVQcmVzZW50YXRpb24iLCJ2ZXJpZmlhYmxlQ3JlZGVudGlhbCI6W3siQGNvbnRleHQiOiJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJpZCI6ImRhdGE6YXBwbGljYXRpb24vdmMrand0LGV5SnJhV1FpT2lKRmVFaHJRazFYT1dadFltdDJWakkyTm0xU2NIVlFNbk5WV1Y5T1gwVlhTVTR4YkdGd1ZYcFBPSEp2SWl3aVlXeG5Jam9pUlZNek9EUWlmUS5leUpBWTI5dWRHVjRkQ0k2V3lKb2RIUndjem92TDNkM2R5NTNNeTV2Y21jdmJuTXZZM0psWkdWdWRHbGhiSE12ZGpJaUxDSm9kSFJ3Y3pvdkwzZDNkeTUzTXk1dmNtY3Zibk12WTNKbFpHVnVkR2xoYkhNdlpYaGhiWEJzWlhNdmRqSWlYU3dpYVdRaU9pSm9kSFJ3T2k4dmRXNXBkbVZ5YzJsMGVTNWxlR0Z0Y0d4bEwyTnlaV1JsYm5ScFlXeHpMekU0TnpJaUxDSjBlWEJsSWpwYklsWmxjbWxtYVdGaWJHVkRjbVZrWlc1MGFXRnNJaXdpUlhoaGJYQnNaVUZzZFcxdWFVTnlaV1JsYm5ScFlXd2lYU3dpYVhOemRXVnlJam9pYUhSMGNITTZMeTkxYm1sMlpYSnphWFI1TG1WNFlXMXdiR1V2YVhOemRXVnljeTgxTmpVd05Ea2lMQ0oyWVd4cFpFWnliMjBpT2lJeU1ERXdMVEF4TFRBeFZERTVPakl6T2pJMFdpSXNJbU55WldSbGJuUnBZV3hUWTJobGJXRWlPbnNpYVdRaU9pSm9kSFJ3Y3pvdkwyVjRZVzF3YkdVdWIzSm5MMlY0WVcxd2JHVnpMMlJsWjNKbFpTNXFjMjl1SWl3aWRIbHdaU0k2SWtwemIyNVRZMmhsYldFaWZTd2lZM0psWkdWdWRHbGhiRk4xWW1wbFkzUWlPbnNpYVdRaU9pSmthV1E2WlhoaGJYQnNaVG94TWpNaUxDSmtaV2R5WldVaU9uc2lkSGx3WlNJNklrSmhZMmhsYkc5eVJHVm5jbVZsSWl3aWJtRnRaU0k2SWtKaFkyaGxiRzl5SUc5bUlGTmphV1Z1WTJVZ1lXNWtJRUZ5ZEhNaWZYMTkuZDJrNE8zRnl0UUpmODNrTGgtSHNYdVB2aDZ5ZU9saEpFTFZvNVRGNzFndTdlbHNsUXlPZjJaSXRBWHJ0YlhGNEt6OVdpdk5kenRPYXl6NFZVUTBNd2E4eUNEWmtQOUIycEgtOVNfdGNBRnhlb2VKNlo0WG5GdUxfRE9ma1IxZlA7ZGF0YTphcHBsaWNhdGlvbi92Yytqd3QsZXlKcmFXUWlPaUpGZUVoclFrMVhPV1p0WW10MlZqSTJObTFTY0hWUU1uTlZXVjlPWDBWWFNVNHhiR0Z3VlhwUE9ISnZJaXdpWVd4bklqb2lSVk16T0RRaWZRLmV5SkFZMjl1ZEdWNGRDSTZXeUpvZEhSd2N6b3ZMM2QzZHk1M015NXZjbWN2Ym5NdlkzSmxaR1Z1ZEdsaGJITXZkaklpTENKb2RIUndjem92TDNkM2R5NTNNeTV2Y21jdmJuTXZZM0psWkdWdWRHbGhiSE12WlhoaGJYQnNaWE12ZGpJaVhTd2lhV1FpT2lKb2RIUndPaTh2ZFc1cGRtVnljMmwwZVM1bGVHRnRjR3hsTDJOeVpXUmxiblJwWVd4ekx6RTROeklpTENKMGVYQmxJanBiSWxabGNtbG1hV0ZpYkdWRGNtVmtaVzUwYVdGc0lpd2lSWGhoYlhCc1pVRnNkVzF1YVVOeVpXUmxiblJwWVd3aVhTd2lhWE56ZFdWeUlqb2lhSFIwY0hNNkx5OTFibWwyWlhKemFYUjVMbVY0WVcxd2JHVXZhWE56ZFdWeWN5ODFOalV3TkRraUxDSjJZV3hwWkVaeWIyMGlPaUl5TURFd0xUQXhMVEF4VkRFNU9qSXpPakkwV2lJc0ltTnlaV1JsYm5ScFlXeFRZMmhsYldFaU9uc2lhV1FpT2lKb2RIUndjem92TDJWNFlXMXdiR1V1YjNKbkwyVjRZVzF3YkdWekwyUmxaM0psWlM1cWMyOXVJaXdpZEhsd1pTSTZJa3B6YjI1VFkyaGxiV0VpZlN3aVkzSmxaR1Z1ZEdsaGJGTjFZbXBsWTNRaU9uc2lhV1FpT2lKa2FXUTZaWGhoYlhCc1pUb3hNak1pTENKa1pXZHlaV1VpT25zaWRIbHdaU0k2SWtKaFkyaGxiRzl5UkdWbmNtVmxJaXdpYm1GdFpTSTZJa0poWTJobGJHOXlJRzltSUZOamFXVnVZMlVnWVc1a0lFRnlkSE1pZlgxOS5kMms0TzNGeXRRSmY4M2tMaC1Ic1h1UHZoNnllT2xoSkVMVm81VEY3MWd1N2Vsc2xReU9mMlpJdEFYcnRiWEY0S3o5V2l2TmR6dE9heXo0VlVRME13YTh5Q0Raa1A5QjJwSC05U190Y0FGeGVvZUo2WjRYbkZ1TF9ET2ZrUjFmUCIsInR5cGUiOiJFbnZlbG9wZWRWZXJpZmlhYmxlQ3JlZGVudGlhbCJ9XX0 .W-VCYKsmbzHlKT13jPIDNqx49jXb5VNyRLVI-cNoBy8gOoYpLJgrV1OYtG8BQd5FtU5K7DxYuwY6HxiKM5cpbQ

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#enveloped-verifiable-credentials) for more details regarding this example.

[Example 3](#example-a-simple-example-of-a-verifiable-presentation-secured-with-jose-with-the-envelopedverifiablepresentation-type): A simple example of a verifiable presentation secured with JOSE with the EnvelopedVerifiablePresentation type

-   Presentation
-   jose

``` {.nohighlight .vc vc-tabs="jose"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "type": "EnvelopedVerifiablePresentation",
  "id": "data:application/vp+jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidHlwZSI6IlZlcmlmaWFibGVQcmVzZW50YXRpb24iLCJ2ZXJpZmlhYmxlQ3JlZGVudGlhbCI6W3siQGNvbnRleHQiOiJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJpZCI6ImRhdGE6YXBwbGljYXRpb24vdmMrand0LGV5SnJhV1FpT2lKRmVFaHJRazFYT1dadFltdDJWakkyTm0xU2NIVlFNbk5WV1Y5T1gwVlhTVTR4YkdGd1ZYcFBPSEp2SWl3aVlXeG5Jam9pUlZNek9EUWlmUS5leUpBWTI5dWRHVjRkQ0k2V3lKb2RIUndjem92TDNkM2R5NTNNeTV2Y21jdmJuTXZZM0psWkdWdWRHbGhiSE12ZGpJaUxDSm9kSFJ3Y3pvdkwzZDNkeTUzTXk1dmNtY3Zibk12WTNKbFpHVnVkR2xoYkhNdlpYaGhiWEJzWlhNdmRqSWlYU3dpYVdRaU9pSm9kSFJ3T2k4dmRXNXBkbVZ5YzJsMGVTNWxlR0Z0Y0d4bEwyTnlaV1JsYm5ScFlXeHpMekU0TnpJaUxDSjBlWEJsSWpwYklsWmxjbWxtYVdGaWJHVkRjbVZrWlc1MGFXRnNJaXdpUlhoaGJYQnNaVUZzZFcxdWFVTnlaV1JsYm5ScFlXd2lYU3dpYVhOemRXVnlJam9pYUhSMGNITTZMeTkxYm1sMlpYSnphWFI1TG1WNFlXMXdiR1V2YVhOemRXVnljeTgxTmpVd05Ea2lMQ0oyWVd4cFpFWnliMjBpT2lJeU1ERXdMVEF4TFRBeFZERTVPakl6T2pJMFdpSXNJbU55WldSbGJuUnBZV3hUWTJobGJXRWlPbnNpYVdRaU9pSm9kSFJ3Y3pvdkwyVjRZVzF3YkdVdWIzSm5MMlY0WVcxd2JHVnpMMlJsWjNKbFpTNXFjMjl1SWl3aWRIbHdaU0k2SWtwemIyNVRZMmhsYldFaWZTd2lZM0psWkdWdWRHbGhiRk4xWW1wbFkzUWlPbnNpYVdRaU9pSmthV1E2WlhoaGJYQnNaVG94TWpNaUxDSmtaV2R5WldVaU9uc2lkSGx3WlNJNklrSmhZMmhsYkc5eVJHVm5jbVZsSWl3aWJtRnRaU0k2SWtKaFkyaGxiRzl5SUc5bUlGTmphV1Z1WTJVZ1lXNWtJRUZ5ZEhNaWZYMTkuZDJrNE8zRnl0UUpmODNrTGgtSHNYdVB2aDZ5ZU9saEpFTFZvNVRGNzFndTdlbHNsUXlPZjJaSXRBWHJ0YlhGNEt6OVdpdk5kenRPYXl6NFZVUTBNd2E4eUNEWmtQOUIycEgtOVNfdGNBRnhlb2VKNlo0WG5GdUxfRE9ma1IxZlA7ZGF0YTphcHBsaWNhdGlvbi92Yytqd3QsZXlKcmFXUWlPaUpGZUVoclFrMVhPV1p0WW10MlZqSTJObTFTY0hWUU1uTlZXVjlPWDBWWFNVNHhiR0Z3VlhwUE9ISnZJaXdpWVd4bklqb2lSVk16T0RRaWZRLmV5SkFZMjl1ZEdWNGRDSTZXeUpvZEhSd2N6b3ZMM2QzZHk1M015NXZjbWN2Ym5NdlkzSmxaR1Z1ZEdsaGJITXZkaklpTENKb2RIUndjem92TDNkM2R5NTNNeTV2Y21jdmJuTXZZM0psWkdWdWRHbGhiSE12WlhoaGJYQnNaWE12ZGpJaVhTd2lhV1FpT2lKb2RIUndPaTh2ZFc1cGRtVnljMmwwZVM1bGVHRnRjR3hsTDJOeVpXUmxiblJwWVd4ekx6RTROeklpTENKMGVYQmxJanBiSWxabGNtbG1hV0ZpYkdWRGNtVmtaVzUwYVdGc0lpd2lSWGhoYlhCc1pVRnNkVzF1YVVOeVpXUmxiblJwWVd3aVhTd2lhWE56ZFdWeUlqb2lhSFIwY0hNNkx5OTFibWwyWlhKemFYUjVMbVY0WVcxd2JHVXZhWE56ZFdWeWN5ODFOalV3TkRraUxDSjJZV3hwWkVaeWIyMGlPaUl5TURFd0xUQXhMVEF4VkRFNU9qSXpPakkwV2lJc0ltTnlaV1JsYm5ScFlXeFRZMmhsYldFaU9uc2lhV1FpT2lKb2RIUndjem92TDJWNFlXMXdiR1V1YjNKbkwyVjRZVzF3YkdWekwyUmxaM0psWlM1cWMyOXVJaXdpZEhsd1pTSTZJa3B6YjI1VFkyaGxiV0VpZlN3aVkzSmxaR1Z1ZEdsaGJGTjFZbXBsWTNRaU9uc2lhV1FpT2lKa2FXUTZaWGhoYlhCc1pUb3hNak1pTENKa1pXZHlaV1VpT25zaWRIbHdaU0k2SWtKaFkyaGxiRzl5UkdWbmNtVmxJaXdpYm1GdFpTSTZJa0poWTJobGJHOXlJRzltSUZOamFXVnVZMlVnWVc1a0lFRnlkSE1pZlgxOS5kMms0TzNGeXRRSmY4M2tMaC1Ic1h1UHZoNnllT2xoSkVMVm81VEY3MWd1N2Vsc2xReU9mMlpJdEFYcnRiWEY0S3o5V2l2TmR6dE9heXo0VlVRME13YTh5Q0Raa1A5QjJwSC05U190Y0FGeGVvZUo2WjRYbkZ1TF9ET2ZrUjFmUCIsInR5cGUiOiJFbnZlbG9wZWRWZXJpZmlhYmxlQ3JlZGVudGlhbCJ9XX0.DiZfXw5jTXeDBobq5ZdcL3S3o8mioZJlqo3iHDtLcEww5L_n2ZJfAJU-a-SmqvMYM--7w4CmeOfq890UGsg_aQ"
}
```

**Protected Headers**

    {
      "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
      "alg": "ES256"
    }

**application/vp**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "type": "EnvelopedVerifiablePresentation",
      "id": "data:application/vp+jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidHlwZSI6IlZlcmlmaWFibGVQcmVzZW50YXRpb24iLCJ2ZXJpZmlhYmxlQ3JlZGVudGlhbCI6W3siQGNvbnRleHQiOiJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJpZCI6ImRhdGE6YXBwbGljYXRpb24vdmMrand0LGV5SnJhV1FpT2lKRmVFaHJRazFYT1dadFltdDJWakkyTm0xU2NIVlFNbk5WV1Y5T1gwVlhTVTR4YkdGd1ZYcFBPSEp2SWl3aVlXeG5Jam9pUlZNek9EUWlmUS5leUpBWTI5dWRHVjRkQ0k2V3lKb2RIUndjem92TDNkM2R5NTNNeTV2Y21jdmJuTXZZM0psWkdWdWRHbGhiSE12ZGpJaUxDSm9kSFJ3Y3pvdkwzZDNkeTUzTXk1dmNtY3Zibk12WTNKbFpHVnVkR2xoYkhNdlpYaGhiWEJzWlhNdmRqSWlYU3dpYVdRaU9pSm9kSFJ3T2k4dmRXNXBkbVZ5YzJsMGVTNWxlR0Z0Y0d4bEwyTnlaV1JsYm5ScFlXeHpMekU0TnpJaUxDSjBlWEJsSWpwYklsWmxjbWxtYVdGaWJHVkRjbVZrWlc1MGFXRnNJaXdpUlhoaGJYQnNaVUZzZFcxdWFVTnlaV1JsYm5ScFlXd2lYU3dpYVhOemRXVnlJam9pYUhSMGNITTZMeTkxYm1sMlpYSnphWFI1TG1WNFlXMXdiR1V2YVhOemRXVnljeTgxTmpVd05Ea2lMQ0oyWVd4cFpFWnliMjBpT2lJeU1ERXdMVEF4TFRBeFZERTVPakl6T2pJMFdpSXNJbU55WldSbGJuUnBZV3hUWTJobGJXRWlPbnNpYVdRaU9pSm9kSFJ3Y3pvdkwyVjRZVzF3YkdVdWIzSm5MMlY0WVcxd2JHVnpMMlJsWjNKbFpTNXFjMjl1SWl3aWRIbHdaU0k2SWtwemIyNVRZMmhsYldFaWZTd2lZM0psWkdWdWRHbGhiRk4xWW1wbFkzUWlPbnNpYVdRaU9pSmthV1E2WlhoaGJYQnNaVG94TWpNaUxDSmtaV2R5WldVaU9uc2lkSGx3WlNJNklrSmhZMmhsYkc5eVJHVm5jbVZsSWl3aWJtRnRaU0k2SWtKaFkyaGxiRzl5SUc5bUlGTmphV1Z1WTJVZ1lXNWtJRUZ5ZEhNaWZYMTkuZDJrNE8zRnl0UUpmODNrTGgtSHNYdVB2aDZ5ZU9saEpFTFZvNVRGNzFndTdlbHNsUXlPZjJaSXRBWHJ0YlhGNEt6OVdpdk5kenRPYXl6NFZVUTBNd2E4eUNEWmtQOUIycEgtOVNfdGNBRnhlb2VKNlo0WG5GdUxfRE9ma1IxZlA7ZGF0YTphcHBsaWNhdGlvbi92Yytqd3QsZXlKcmFXUWlPaUpGZUVoclFrMVhPV1p0WW10MlZqSTJObTFTY0hWUU1uTlZXVjlPWDBWWFNVNHhiR0Z3VlhwUE9ISnZJaXdpWVd4bklqb2lSVk16T0RRaWZRLmV5SkFZMjl1ZEdWNGRDSTZXeUpvZEhSd2N6b3ZMM2QzZHk1M015NXZjbWN2Ym5NdlkzSmxaR1Z1ZEdsaGJITXZkaklpTENKb2RIUndjem92TDNkM2R5NTNNeTV2Y21jdmJuTXZZM0psWkdWdWRHbGhiSE12WlhoaGJYQnNaWE12ZGpJaVhTd2lhV1FpT2lKb2RIUndPaTh2ZFc1cGRtVnljMmwwZVM1bGVHRnRjR3hsTDJOeVpXUmxiblJwWVd4ekx6RTROeklpTENKMGVYQmxJanBiSWxabGNtbG1hV0ZpYkdWRGNtVmtaVzUwYVdGc0lpd2lSWGhoYlhCc1pVRnNkVzF1YVVOeVpXUmxiblJwWVd3aVhTd2lhWE56ZFdWeUlqb2lhSFIwY0hNNkx5OTFibWwyWlhKemFYUjVMbVY0WVcxd2JHVXZhWE56ZFdWeWN5ODFOalV3TkRraUxDSjJZV3hwWkVaeWIyMGlPaUl5TURFd0xUQXhMVEF4VkRFNU9qSXpPakkwV2lJc0ltTnlaV1JsYm5ScFlXeFRZMmhsYldFaU9uc2lhV1FpT2lKb2RIUndjem92TDJWNFlXMXdiR1V1YjNKbkwyVjRZVzF3YkdWekwyUmxaM0psWlM1cWMyOXVJaXdpZEhsd1pTSTZJa3B6YjI1VFkyaGxiV0VpZlN3aVkzSmxaR1Z1ZEdsaGJGTjFZbXBsWTNRaU9uc2lhV1FpT2lKa2FXUTZaWGhoYlhCc1pUb3hNak1pTENKa1pXZHlaV1VpT25zaWRIbHdaU0k2SWtKaFkyaGxiRzl5UkdWbmNtVmxJaXdpYm1GdFpTSTZJa0poWTJobGJHOXlJRzltSUZOamFXVnVZMlVnWVc1a0lFRnlkSE1pZlgxOS5kMms0TzNGeXRRSmY4M2tMaC1Ic1h1UHZoNnllT2xoSkVMVm81VEY3MWd1N2Vsc2xReU9mMlpJdEFYcnRiWEY0S3o5V2l2TmR6dE9heXo0VlVRME13YTh5Q0Raa1A5QjJwSC05U190Y0FGeGVvZUo2WjRYbkZ1TF9ET2ZrUjFmUCIsInR5cGUiOiJFbnZlbG9wZWRWZXJpZmlhYmxlQ3JlZGVudGlhbCJ9XX0.DiZfXw5jTXeDBobq5ZdcL3S3o8mioZJlqo3iHDtLcEww5L_n2ZJfAJU-a-SmqvMYM--7w4CmeOfq890UGsg_aQ"
    }

**application/vp+jwt**

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidHlwZSI6IkVudmVsb3BlZFZlcmlmaWFibGVQcmVzZW50YXRpb24iLCJpZCI6ImRhdGE6YXBwbGljYXRpb24vdnArand0LGV5SnJhV1FpT2lKRmVFaHJRazFYT1dadFltdDJWakkyTm0xU2NIVlFNbk5WV1Y5T1gwVlhTVTR4YkdGd1ZYcFBPSEp2SWl3aVlXeG5Jam9pUlZNeU5UWWlmUS5leUpBWTI5dWRHVjRkQ0k2V3lKb2RIUndjem92TDNkM2R5NTNNeTV2Y21jdmJuTXZZM0psWkdWdWRHbGhiSE12ZGpJaUxDSm9kSFJ3Y3pvdkwzZDNkeTUzTXk1dmNtY3Zibk12WTNKbFpHVnVkR2xoYkhNdlpYaGhiWEJzWlhNdmRqSWlYU3dpZEhsd1pTSTZJbFpsY21sbWFXRmliR1ZRY21WelpXNTBZWFJwYjI0aUxDSjJaWEpwWm1saFlteGxRM0psWkdWdWRHbGhiQ0k2VzNzaVFHTnZiblJsZUhRaU9pSm9kSFJ3Y3pvdkwzZDNkeTUzTXk1dmNtY3Zibk12WTNKbFpHVnVkR2xoYkhNdmRqSWlMQ0pwWkNJNkltUmhkR0U2WVhCd2JHbGpZWFJwYjI0dmRtTXJhbmQwTEdWNVNuSmhWMUZwVDJsS1JtVkZhSEpSYXpGWVQxZGFkRmx0ZERKV2Fra3lUbTB4VTJOSVZsRk5iazVXVjFZNVQxZ3dWbGhUVlRSNFlrZEdkMVpZY0ZCUFNFcDJTV2wzYVZsWGVHNUphbTlwVWxaTmVrOUVVV2xtVVM1bGVVcEJXVEk1ZFdSSFZqUmtRMGsyVjNsS2IyUklVbmRqZW05MlRETmtNMlI1TlROTmVUVjJZMjFqZG1KdVRYWlpNMHBzV2tkV2RXUkhiR2hpU0UxMlpHcEphVXhEU205a1NGSjNZM3B2ZGt3elpETmtlVFV6VFhrMWRtTnRZM1ppYmsxMldUTktiRnBIVm5Wa1IyeG9Za2hOZGxwWWFHaGlXRUp6V2xoTmRtUnFTV2xZVTNkcFlWZFJhVTlwU205a1NGSjNUMms0ZG1SWE5YQmtiVlo1WXpKc01HVlROV3hsUjBaMFkwZDRiRXd5VG5sYVYxSnNZbTVTY0ZsWGVIcE1la1UwVG5wSmFVeERTakJsV0VKc1NXcHdZa2xzV214amJXeHRZVmRHYVdKSFZrUmpiVlpyV2xjMU1HRlhSbk5KYVhkcFVsaG9hR0pZUW5OYVZVWnpaRmN4ZFdGVlRubGFWMUpzWW01U2NGbFhkMmxZVTNkcFlWaE9lbVJYVm5sSmFtOXBZVWhTTUdOSVRUWk1lVGt4WW0xc01scFlTbnBoV0ZJMVRHMVdORmxYTVhkaVIxVjJZVmhPZW1SWFZubGplVGd4VG1wVmQwNUVhMmxNUTBveVdWZDRjRnBGV25saU1qQnBUMmxKZVUxRVJYZE1WRUY0VEZSQmVGWkVSVFZQYWtsNlQycEpNRmRwU1hOSmJVNTVXbGRTYkdKdVVuQlpWM2hVV1RKb2JHSlhSV2xQYm5OcFlWZFJhVTlwU205a1NGSjNZM3B2ZGt3eVZqUlpWekYzWWtkVmRXSXpTbTVNTWxZMFdWY3hkMkpIVm5wTU1sSnNXak5LYkZwVE5YRmpNamwxU1dsM2FXUkliSGRhVTBrMlNXdHdlbUl5TlZSWk1taHNZbGRGYVdaVGQybFpNMHBzV2tkV2RXUkhiR2hpUms0eFdXMXdiRmt6VVdsUGJuTnBZVmRSYVU5cFNtdGhWMUUyV2xob2FHSllRbk5hVkc5NFRXcE5hVXhEU210YVYyUjVXbGRWYVU5dWMybGtTR3gzV2xOSk5rbHJTbWhaTW1oc1lrYzVlVkpIVm01amJWWnNTV2wzYVdKdFJuUmFVMGsyU1d0S2FGa3lhR3hpUnpsNVNVYzViVWxHVG1waFYxWjFXVEpWWjFsWE5XdEpSVVo1WkVoTmFXWllNVGt1WkRKck5FOHpSbmwwVVVwbU9ETnJUR2d0U0hOWWRWQjJhRFo1WlU5c2FFcEZURlp2TlZSR056Rm5kVGRsYkhOc1VYbFBaakphU1hSQldISjBZbGhHTkV0Nk9WZHBkazVrZW5SUFlYbDZORlpWVVRCTmQyRTRlVU5FV210UU9VSXljRWd0T1ZOZmRHTkJSbmhsYjJWS05sbzBXRzVHZFV4ZlJFOW1hMUl4WmxBN1pHRjBZVHBoY0hCc2FXTmhkR2x2Ymk5Mll5dHFkM1FzWlhsS2NtRlhVV2xQYVVwR1pVVm9jbEZyTVZoUFYxcDBXVzEwTWxacVNUSk9iVEZUWTBoV1VVMXVUbFpYVmpsUFdEQldXRk5WTkhoaVIwWjNWbGh3VUU5SVNuWkphWGRwV1ZkNGJrbHFiMmxTVmsxNlQwUlJhV1pSTG1WNVNrRlpNamwxWkVkV05HUkRTVFpYZVVwdlpFaFNkMk42YjNaTU0yUXpaSGsxTTAxNU5YWmpiV04yWW01TmRsa3pTbXhhUjFaMVpFZHNhR0pJVFhaa2FrbHBURU5LYjJSSVVuZGplbTkyVEROa00yUjVOVE5OZVRWMlkyMWpkbUp1VFhaWk0wcHNXa2RXZFdSSGJHaGlTRTEyV2xob2FHSllRbk5hV0UxMlpHcEphVmhUZDJsaFYxRnBUMmxLYjJSSVVuZFBhVGgyWkZjMWNHUnRWbmxqTW13d1pWTTFiR1ZIUm5SalIzaHNUREpPZVZwWFVteGlibEp3V1ZkNGVreDZSVFJPZWtscFRFTktNR1ZZUW14SmFuQmlTV3hhYkdOdGJHMWhWMFpwWWtkV1JHTnRWbXRhVnpVd1lWZEdjMGxwZDJsU1dHaG9ZbGhDYzFwVlJuTmtWekYxWVZWT2VWcFhVbXhpYmxKd1dWZDNhVmhUZDJsaFdFNTZaRmRXZVVscWIybGhTRkl3WTBoTk5reDVPVEZpYld3eVdsaEtlbUZZVWpWTWJWWTBXVmN4ZDJKSFZYWmhXRTU2WkZkV2VXTjVPREZPYWxWM1RrUnJhVXhEU2pKWlYzaHdXa1ZhZVdJeU1HbFBhVWw1VFVSRmQweFVRWGhNVkVGNFZrUkZOVTlxU1hwUGFra3dWMmxKYzBsdFRubGFWMUpzWW01U2NGbFhlRlJaTW1oc1lsZEZhVTl1YzJsaFYxRnBUMmxLYjJSSVVuZGplbTkyVERKV05GbFhNWGRpUjFWMVlqTktia3d5VmpSWlZ6RjNZa2RXZWt3eVVteGFNMHBzV2xNMWNXTXlPWFZKYVhkcFpFaHNkMXBUU1RaSmEzQjZZakkxVkZreWFHeGlWMFZwWmxOM2FWa3pTbXhhUjFaMVpFZHNhR0pHVGpGWmJYQnNXVE5SYVU5dWMybGhWMUZwVDJsS2EyRlhVVFphV0dob1lsaENjMXBVYjNoTmFrMXBURU5LYTFwWFpIbGFWMVZwVDI1emFXUkliSGRhVTBrMlNXdEthRmt5YUd4aVJ6bDVVa2RXYm1OdFZteEphWGRwWW0xR2RGcFRTVFpKYTBwb1dUSm9iR0pIT1hsSlJ6bHRTVVpPYW1GWFZuVlpNbFZuV1ZjMWEwbEZSbmxrU0UxcFpsZ3hPUzVrTW1zMFR6TkdlWFJSU21ZNE0ydE1hQzFJYzFoMVVIWm9ObmxsVDJ4b1NrVk1WbTgxVkVZM01XZDFOMlZzYzJ4UmVVOW1NbHBKZEVGWWNuUmlXRVkwUzNvNVYybDJUbVI2ZEU5aGVYbzBWbFZSTUUxM1lUaDVRMFJhYTFBNVFqSndTQzA1VTE5MFkwRkdlR1Z2WlVvMldqUllia1oxVEY5RVQyWnJVakZtVUNJc0luUjVjR1VpT2lKRmJuWmxiRzl3WldSV1pYSnBabWxoWW14bFEzSmxaR1Z1ZEdsaGJDSjlYWDAuRGlaZlh3NWpUWGVEQm9icTVaZGNMM1MzbzhtaW9aSmxxbzNpSER0TGNFd3c1TF9uMlpKZkFKVS1hLVNtcXZNWU0tLTd3NENtZU9mcTg5MFVHc2dfYVEiLCJ2ZXJpZmlhYmxlQ3JlZGVudGlhbCI6W119 .ndvXgG0tEU5qu5B9hoYgQBSExPrjgQSs8mO1Sd62hUVyfeND3Dcym5gFL4gr_rM-\_0glipfbNTZK7BxvSoibHw

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#enveloped-verifiable-presentations) for more details regarding this example.

Implementations *MUST* support the JWS compact serialization. Use of the JWS JSON serialization is *NOT RECOMMENDED*.

#### 3.1.3 JOSE Header Parameters and JWT Claims

[](#jose-header-parameters-jwt-claims)

*This section is non-normative.*

When present in the [JOSE Header](https://www.rfc-editor.org/rfc/rfc7515#section-4) or the [JWT Claims Set](https://www.rfc-editor.org/rfc/rfc7519#section-4), members registered in the IANA [JSON Web Token Claims](https://www.iana.org/assignments/jwt/jwt.xhtml) registry or the IANA [JSON Web Signature and Encryption Header Parameters](https://www.iana.org/assignments/jose/jose.xhtml) registry are to be interpreted as defined by the specifications referenced in the registries.

The normative statements in [Registered Header Parameter Names](https://www.rfc-editor.org/rfc/rfc7515#section-4.1), [JOSE Header](https://www.rfc-editor.org/rfc/rfc7519#section-5), and [Replicating Claims as Header Parameters](https://www.rfc-editor.org/rfc/rfc7519#section-5.3) apply to securing [credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-credential) and [presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-presentation).

The unencoded JOSE Header is JSON (`application/json`), not JSON-LD (`application/ld+json`).

It is *RECOMMENDED* to use the IANA [JSON Web Token Claims](https://www.iana.org/assignments/jwt/jwt.xhtml) registry and the IANA [JSON Web Signature and Encryption Header Parameters](https://www.iana.org/assignments/jose/jose.xhtml) registry to identify any claims and header parameters that might be confused with members defined by \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\]. These include but are not limited to: `iss`, `kid`, `alg`, `iat`, `exp`, and `cnf`.

When the `iat` (Issued At) and/or `exp` (Expiration Time) JWT claims are present, they represent the issuance and expiration time of the signature, respectively. Note that these are different from the `validFrom` and `validUntil` properties defined in [Validity Period](https://www.w3.org/TR/vc-data-model-2.0/#validity-period), which represent the validity of the data that is being secured. Use of the `nbf` (Not Before) claim is *NOT RECOMMENDED*, as it makes little sense to attempt to assign a future date to a signature.

The claims and security provided by this specification are independent of the data secured and semantics provided by the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\]. This means that while the security features of this specification ensure data integrity and authenticity, they do not dictate the interpretation of claim data.

Implementers *SHOULD* avoid setting JWT claims to values that conflict with the values of [verifiable credential](#dfn-verifiable-credentials) properties when a claim and property pair refer to the same conceptual entity, especially with pairs such as `iss` and `issuer`, `jti` and `id`, and `sub` and `credentialSubject.id`. For example, JWK claim `iss` *SHOULD NOT* be set to a value which conflicts with the value of [verifiable credential](#dfn-verifiable-credentials) property `issuer`.

The JWT Claim Names `vc` and `vp` *MUST NOT* be present.

Additional members may be present as header parameters and claims. If they are not understood, they *MUST* be ignored.

### 3.2 With SD-JWT

[](#with-sd-jwt)

#### 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT

[](#securing-with-sd-jwt)

This section details how to use JOSE to secure [verifiable credentials](#dfn-verifiable-credentials) conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

A [conforming SD-JWT issuer implementation](#dfn-conforming-sd-jwt-issuer-implementation) *MUST* use \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] to secure this media type. The unsecured [verifiable credential](#dfn-verifiable-credentials) is the input JWT Claims Set. The Issuer then converts the input JWT Claims Set (i.e., the unsecured [verifiable credential](#dfn-verifiable-credentials)) into an \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] payload according to [SD-JWT issuance instructions](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt#section-6.1).

The `typ` header parameter *SHOULD* be `vc+sd-jwt`. When present, the `cty` header parameter *SHOULD* be `vc`. The `cty` header parameter value can be used to differentiate between secured content of different types when using `vc+sd-jwt`. The `content type` header parameter is optional, and can be used to express a more specific media type than `application/vc` when one is available. See [Registered Header Parameter Names](https://www.rfc-editor.org/rfc/rfc7515#section-4.1) for additional details regarding usage of `typ` and `cty`.

A [conforming SD-JWT verifier implementation](#dfn-conforming-sd-jwt-verifier-implementation) *MUST* use \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] to verify [conforming JWS documents](#dfn-conforming-jws-document) that use this media type.

When securing [verifiable credentials](#dfn-verifiable-credentials) with \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\], implementers *SHOULD* ensure that properties necessary for the validation and verification of a credential are NOT selectively disclosable (i.e., such properties *SHOULD* be disclosed). These properties can include but are not limited to [`@context`](https://www.w3.org/TR/vc-data-model-2.0/#contexts), [`type`](https://www.w3.org/TR/vc-data-model-2.0/#types), [`credentialStatus`](https://www.w3.org/TR/vc-data-model-2.0/#status), [`credentialSchema`](https://www.w3.org/TR/vc-data-model-2.0/#data-schemas), and [`relatedResource`](https://www.w3.org/TR/vc-data-model-2.0/#integrity-of-related-resources).

To encrypt a secured [verifiable credential](#dfn-verifiable-credentials) when transmitting over an insecure channel, implementers *MAY* use JSON Web Encryption (JWE) \[[RFC7516](#bib-rfc7516 "JSON Web Encryption (JWE)")\] by nesting the secured [verifiable credential](#dfn-verifiable-credentials) as the plaintext payload of a JWE, per the instructions in Section 11.2 of \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\].

[Example 4](#example-a-simple-example-of-a-verifiable-credential-secured-with-sd-jwt): A simple example of a verifiable credential secured with SD-JWT

-   Credential
-   sd-jwt

``` {.nohighlight .vc vc-tabs="sd-jwt"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "id": "http://university.example/credentials/3732",
  "type": ["VerifiableCredential", "ExampleDegreeCredential", "ExamplePersonCredential"],
  "issuer": "https://university.example/issuers/14",
  "validFrom": "2010-01-01T19:23:24Z",
  "credentialSubject": {
    "id": "did:example:ebfeb1f712ebc6f1c276e12ec21",
    "degree": {
      "type": "ExampleBachelorDegree",
      "name": "Bachelor of Science and Arts"
    },
    "alumniOf": {
      "name": "Example University"
    }
  },
  "credentialSchema": [{
    "id": "https://example.org/examples/degree.json",
    "type": "JsonSchema"
  },
  {
    "id": "https://example.org/examples/alumni.json",
    "type": "JsonSchema"
  }]
}
```

-   Encoded
-   Decoded
-   Issuer Disclosures

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJpYXQiOjE3NDU1OTQ3NzIsImV4cCI6MTc0NjgwNDM3MiwiX3NkX2FsZyI6InNoYS0yNTYiLCJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaXNzdWVyIjoiaHR0cHM6Ly91bml2ZXJzaXR5LmV4YW1wbGUvaXNzdWVycy8xNCIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFN1YmplY3QiOnsiZGVncmVlIjp7Im5hbWUiOiJCYWNoZWxvciBvZiBTY2llbmNlIGFuZCBBcnRzIiwiX3NkIjpbIkdRMHZrZUVGZ2RWWkxMdEhZYTdPSkZqQmtJeVRGVGo3SlAxX1pnb3hpejgiXX0sImFsdW1uaU9mIjp7Im5hbWUiOiJFeGFtcGxlIFVuaXZlcnNpdHkifSwiX3NkIjpbIlFHOFdjdkZpcVBKcE9YVUFPWnJ5a2FYMWhTRWhKRHA2cVFNZFhiOTJPRzQiXX0sImNyZWRlbnRpYWxTY2hlbWEiOlt7Il9zZCI6WyI3S2lOSENFSEVjR3JjbExOa2t1TXZHc0lld2ZCaVVOMEJWZnA1YzU1TGlvIiwicTAyWU16dWlEX25jQ0F5S1c4Q0xMbXhmd2RqVUJvai1tbWFhNVJVTjVlVSJdfSx7Il9zZCI6WyJHZGR2b3QtZTY3eGlRd0JCcjBhZXFQOGNnMXQzQWZMcEVQdTBMLUpubFBFIiwieUNDbU5PRGhKR3dqQzJPUjRXa29XRzNia1U1X0FhYjN3cDE0QzNjSjBoZyJdfV0sIl9zZCI6WyJHYWc4bkhyVjRIbHlLQy1KWm9KTWV1TWNlSjVwNVNnaDlTZWQ3ZF9hc29nIiwicGl3RFFtME1HUjlMQ2ZHRnlQd2xIUGIwT3ZpM05aQU5xeUZCNl9iRDE0YyJdfQ .6A0qNztAmhl4HWw4pdgEaeM5hMJZie69xKJRjk2-bkwTdqlx1xDvZRtjH6kBduFRmUo_1JtyDqOPxGHe6w-nxg \~WyJGOEprZFJiT3hDbUM3UU9GbjZSX0F3IiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMzczMiJd\~WyI4d0NOVnpjcHRyZnVlQ3ZkX1ByVnpRIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVEZWdyZWVDcmVkZW50aWFsIiwgIkV4YW1wbGVQZXJzb25DcmVkZW50aWFsIl1d\~WyIwcEYzMVBUem9oRnNnZW1qXzNMb2tBIiwgImlkIiwgImRpZDpleGFtcGxlOmViZmViMWY3MTJlYmM2ZjFjMjc2ZTEyZWMyMSJd\~WyJMaU9qeGZZTU9uOTVUcEhnRnp0SGZ3IiwgInR5cGUiLCAiRXhhbXBsZUJhY2hlbG9yRGVncmVlIl0\~WyJBTlUta1NPWGoxZWg3NHlUcC0xcjNnIiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvZGVncmVlLmpzb24iXQ\~WyJhcXRSLU93Wk0xWjh0eTFIbzBwa3BRIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd\~WyJIdE91TkllQ2FaN0ZQY0lpQ3RoRS1nIiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvYWx1bW5pLmpzb24iXQ\~WyJKZWpaYy1JY0JvNjNJcUZiUWVpY19nIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd\~

``` header-value
{
  "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
  "alg": "ES256"
}
```

``` header-value
{
  "iat": 1745594772,
  "exp": 1746804372,
  "_sd_alg": "sha-256",
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "issuer": "https://university.example/issuers/14",
  "validFrom": "2010-01-01T19:23:24Z",
  "credentialSubject": {
    "degree": {
      "name": "Bachelor of Science and Arts",
      "_sd": [
        "GQ0vkeEFgdVZLLtHYa7OJFjBkIyTFTj7JP1_Zgoxiz8"
      ]
    },
    "alumniOf": {
      "name": "Example University"
    },
    "_sd": [
      "QG8WcvFiqPJpOXUAOZrykaX1hSEhJDp6qQMdXb92OG4"
    ]
  },
  "credentialSchema": [
    {
      "_sd": [
        "7KiNHCEHEcGrclLNkkuMvGsIewfBiUN0BVfp5c55Lio",
        "q02YMzuiD_ncCAyKW8CLLmxfwdjUBoj-mmaa5RUN5eU"
      ]
    },
    {
      "_sd": [
        "Gddvot-e67xiQwBBr0aeqP8cg1t3AfLpEPu0L-JnlPE",
        "yCCmNODhJGwjC2OR4WkoWG3bkU5_Aab3wp14C3cJ0hg"
      ]
    }
  ],
  "_sd": [
    "Gag8nHrV4HlyKC-JZoJMeuMceJ5p5Sgh9Sed7d_asog",
    "piwDQm0MGR9LCfGFyPwlHPb0Ovi3NZANqyFB6_bD14c"
  ]
}
```

### Claim: id

**SHA-256 Hash:** Gag8nHrV4HlyKC-JZoJMeuMceJ5p5Sgh9Sed7d_asog

**Disclosure(s):** WyJGOEprZFJiT3hDbUM3UU9GbjZSX0F3IiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMzczMiJd

**Contents:** \[\
  \"F8JkdRbOxCmC7QOFn6R_Aw\",\
  \"id\",\
  \"http://university.example/credentials/3732\"\
\]

### Claim: type

**SHA-256 Hash:** piwDQm0MGR9LCfGFyPwlHPb0Ovi3NZANqyFB6_bD14c

**Disclosure(s):** WyI4d0NOVnpjcHRyZnVlQ3ZkX1ByVnpRIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVEZWdyZWVDcmVkZW50aWFsIiwgIkV4YW1wbGVQZXJzb25DcmVkZW50aWFsIl1d

**Contents:** \[\
  \"8wCNVzcptrfueCvd_PrVzQ\",\
  \"type\",\
  \[\
    \"VerifiableCredential\",\
    \"ExampleDegreeCredential\",\
    \"ExamplePersonCredential\"\
  \]\
\]

### Claim: id

**SHA-256 Hash:** QG8WcvFiqPJpOXUAOZrykaX1hSEhJDp6qQMdXb92OG4

**Disclosure(s):** WyIwcEYzMVBUem9oRnNnZW1qXzNMb2tBIiwgImlkIiwgImRpZDpleGFtcGxlOmViZmViMWY3MTJlYmM2ZjFjMjc2ZTEyZWMyMSJd

**Contents:** \[\
  \"0pF31PTzohFsgemj_3LokA\",\
  \"id\",\
  \"did:example:ebfeb1f712ebc6f1c276e12ec21\"\
\]

### Claim: type

**SHA-256 Hash:** GQ0vkeEFgdVZLLtHYa7OJFjBkIyTFTj7JP1_Zgoxiz8

**Disclosure(s):** WyJMaU9qeGZZTU9uOTVUcEhnRnp0SGZ3IiwgInR5cGUiLCAiRXhhbXBsZUJhY2hlbG9yRGVncmVlIl0

**Contents:** \[\
  \"LiOjxfYMOn95TpHgFztHfw\",\
  \"type\",\
  \"ExampleBachelorDegree\"\
\]

### Claim: id

**SHA-256 Hash:** q02YMzuiD_ncCAyKW8CLLmxfwdjUBoj-mmaa5RUN5eU

**Disclosure(s):** WyJBTlUta1NPWGoxZWg3NHlUcC0xcjNnIiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvZGVncmVlLmpzb24iXQ

**Contents:** \[\
  \"ANU-kSOXj1eh74yTp-1r3g\",\
  \"id\",\
  \"https://example.org/examples/degree.json\"\
\]

### Claim: type

**SHA-256 Hash:** 7KiNHCEHEcGrclLNkkuMvGsIewfBiUN0BVfp5c55Lio

**Disclosure(s):** WyJhcXRSLU93Wk0xWjh0eTFIbzBwa3BRIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd

**Contents:** \[\
  \"aqtR-OwZM1Z8ty1Ho0pkpQ\",\
  \"type\",\
  \"JsonSchema\"\
\]

### Claim: id

**SHA-256 Hash:** Gddvot-e67xiQwBBr0aeqP8cg1t3AfLpEPu0L-JnlPE

**Disclosure(s):** WyJIdE91TkllQ2FaN0ZQY0lpQ3RoRS1nIiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvYWx1bW5pLmpzb24iXQ

**Contents:** \[\
  \"HtOuNIeCaZ7FPcIiCthE-g\",\
  \"id\",\
  \"https://example.org/examples/alumni.json\"\
\]

### Claim: type

**SHA-256 Hash:** yCCmNODhJGwjC2OR4WkoWG3bkU5_Aab3wp14C3cJ0hg

**Disclosure(s):** WyJKZWpaYy1JY0JvNjNJcUZiUWVpY19nIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd

**Contents:** \[\
  \"JejZc-IcBo63IqFbQeic_g\",\
  \"type\",\
  \"JsonSchema\"\
\]

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#example-using-the-credentialschema-property-to-perform-json-schema-validation) for more details regarding this example.

#### 3.2.2 Securing JSON-LD Verifiable Presentations with SD-JWT

[](#securing-vps-sd-jwt)

This section details how to use \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] to secure [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

A [conforming SD-JWT issuer implementation](#dfn-conforming-sd-jwt-issuer-implementation) *MUST* use \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] to secure this media type. The unsecured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) is the unencoded \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] payload.

The `typ` header parameter *SHOULD* be `vp+sd-jwt`. When present, the `cty` header parameter *SHOULD* be `vp`. The `cty` header parameter value can be used to differentiate between secured content of different types when using `vp+sd-jwt`. The `content type` header parameter is optional, and can be used to express a more specific media type than `application/vc` when one is available. See [Registered Header Parameter Names](https://www.rfc-editor.org/rfc/rfc7515#section-4.1) for additional details regarding usage of `typ` and `cty`.

A [conforming SD-JWT verifier implementation](#dfn-conforming-sd-jwt-verifier-implementation) *MUST* use \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] to verify [conforming JWS documents](#dfn-conforming-jws-document) that use this media type.

Verifiable Credentials secured in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations) *MUST* use the [Enveloped Verifiable Credential](https://www.w3.org/TR/vc-data-model-2.0/#defn-EnvelopedVerifiableCredential) type defined by the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

Verifiable Presentations in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations) *MUST* use the [Enveloped Verifiable Presentation](https://www.w3.org/TR/vc-data-model-2.0/#defn-EnvelopedVerifiablePresentation) type defined by the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

Credentials in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) *MUST* be secured. These [credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-credential) are secured using SD-JWT in this case.

When securing [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) with \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\] implementers *SHOULD* ensure that properties necessary for the validation and verification of a credential are NOT selectively disclosable (i.e., such properties *SHOULD* be disclosed). These properties can include but are not limited to [`@context`](https://www.w3.org/TR/vc-data-model-2.0/#contexts), [`type`](https://www.w3.org/TR/vc-data-model-2.0/#types), [`credentialStatus`](https://www.w3.org/TR/vc-data-model-2.0/#status), [`credentialSchema`](https://www.w3.org/TR/vc-data-model-2.0/#data-schemas), and [`relatedResource`](https://www.w3.org/TR/vc-data-model-2.0/#integrity-of-related-resources).

To encrypt a secured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) when transmitting over an insecure channel, implementers *MAY* use JSON Web Encryption (JWE) \[[RFC7516](#bib-rfc7516 "JSON Web Encryption (JWE)")\] by nesting the secured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) as the plaintext payload of a JWE, per the instructions in Section 11.2 of \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\].

[Example 5](#example-a-simple-example-of-a-verifiable-presentation-secured-with-sd-jwt-using-the-envelopedverifiablecredential-type): A simple example of a verifiable presentation secured with SD-JWT using the EnvelopedVerifiableCredential type

-   Presentation
-   sd-jwt

``` {.nohighlight .vc vc-tabs="sd-jwt"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "type": "VerifiablePresentation",
  "verifiableCredential": [{
    "@context": "https://www.w3.org/ns/credentials/v2",
    "type": "EnvelopedVerifiableCredential",
    "id": "data:application/vc+sd-jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ.eyJfc2RfYWxnIjoic2hhLTI1NiIsIkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFNjaGVtYSI6eyJfc2QiOlsiNjVFLVZZbmE3UE5mSGVsUDN6THFwcE5ERXhSLWhjWkhSTnlxN2U0ZVdabyIsIjhJbEwtUGx4Ukt3S0hLaTMtTXhXMjM4d0FkTmQ0NHdabC1iY3NBc2JIQjAiXX0sImNyZWRlbnRpYWxTdWJqZWN0Ijp7ImRlZ3JlZSI6eyJuYW1lIjoiQmFjaGVsb3Igb2YgU2NpZW5jZSBhbmQgQXJ0cyIsIl9zZCI6WyJMVXhqcWtsWS1hdDVSVmFoSXpxM3NJZ015dkdwVDlwdlUwdTRyU2ktMXl3Il19LCJfc2QiOlsiVmxZLW50ZklPOUI5RGRsUWp5U2REMldoVWI0bjc3Zl9HWDZ2U1dLQWpCNCJdfSwiX3NkIjpbIi1iREZ4Um94UUVlcEdjZFl6a250aTVGWXBsUTU5N0djaEdUTGVtLVJSY1UiLCJfREFVZ0xrTF9zVkVtLTBvcE8zaWhpeVFhS0ZzT08xUl9ONk1CUmprOWhFIl19.Kc083RKbBxc3Vr5qR3iEEPp3dKxTa6sPaWNsqtkIw8TvMRf9EZL2ajtgkWSBYzyzOzawOrCXryyp4rMTyI9vfA ~WyJiQ1RTaU9HNUo1VXhPY1QwUlNfd01nIiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~WyJTclNWMS01SjR6cWhOU3N3STIwaHdRIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyJKX294dDhtUGUtaDl4MkQzc29uT1N3IiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvZGVncmVlLmpzb24iXQ~WyJDMlpWektmZ185RUh1ajB2S1ExdWJnIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd~WyJ6Szd5QlFPbFhfX2Q0X0VoYUc0Y0pRIiwgImlkIiwgImRpZDpleGFtcGxlOjEyMyJd~WyJ6b1pzRzMzeXBMeVRGMm9aS3ZmMVFnIiwgInR5cGUiLCAiQmFjaGVsb3JEZWdyZWUiXQ~"
  }]
}
```

-   Encoded
-   Decoded
-   Issuer Disclosures

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJpYXQiOjE3NDU1OTQ3NzIsImV4cCI6MTc0NjgwNDM3MiwiX3NkX2FsZyI6InNoYS0yNTYiLCJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidmVyaWZpYWJsZUNyZWRlbnRpYWwiOlt7IkBjb250ZXh0IjoiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiX3NkIjpbIi1UVlJVNnlRUmtWRi01cTA4NmNYdWZ4RnU4dnA0QVpKTy1oX1U4enlGX1EiLCJhZ0wxdzlFS3hLMGw2cVZLeDc5RGt1eUdGaDJzemdIRzlSZjBrR18ybnVnIl19XSwiX3NkIjpbIlJ6VHI4ek0wcDEtMHJ3aEx3czAyX0kzMEQ4RzZrcERQRmxPRmdhZzB5YTAiXX0 .-CPblBXo8Oep4RSgE7QjlZwy2oAMlfmWUue7MHjYlqhyZSX6BvZ4hLGBKNqdqgaKDvq6M-VFXB8xE9GUvF9Iqg \~WyIwempCdDNBa0VRd0tJbllMNmhvX0lBIiwgInR5cGUiLCAiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd\~WyJHRXRVYzJuSS1Qd2xMYVhZM19Wd2p3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwiXQ\~WyJRenl4OTJ5azNZQ2tyZmdRejZQVHVnIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdmMrc2Qtand0LCBleUpyYVdRaU9pSkZlRWhyUWsxWE9XWnRZbXQyVmpJMk5tMVNjSFZRTW5OVldWOU9YMFZYU1U0eGJHRndWWHBQT0hKdklpd2lZV3huSWpvaVJWTXlOVFlpZlEuZXlKZmMyUmZZV3huSWpvaWMyaGhMVEkxTmlJc0lrQmpiMjUwWlhoMElqcGJJbWgwZEhCek9pOHZkM2QzTG5jekxtOXlaeTl1Y3k5amNtVmtaVzUwYVdGc2N5OTJNaUlzSW1oMGRIQnpPaTh2ZDNkM0xuY3pMbTl5Wnk5dWN5OWpjbVZrWlc1MGFXRnNjeTlsZUdGdGNHeGxjeTkyTWlKZExDSnBjM04xWlhJaU9pSm9kSFJ3Y3pvdkwzVnVhWFpsY25OcGRIa3VaWGhoYlhCc1pTOXBjM04xWlhKekx6VTJOVEEwT1NJc0luWmhiR2xrUm5KdmJTSTZJakl3TVRBdE1ERXRNREZVTVRrNk1qTTZNalJhSWl3aVkzSmxaR1Z1ZEdsaGJGTmphR1Z0WVNJNmV5SmZjMlFpT2xzaU5qVkZMVlpaYm1FM1VFNW1TR1ZzVURONlRIRndjRTVFUlhoU0xXaGpXa2hTVG5seE4yVTBaVmRhYnlJc0lqaEpiRXd0VUd4NFVrdDNTMGhMYVRNdFRYaFhNak00ZDBGa1RtUTBOSGRhYkMxaVkzTkJjMkpJUWpBaVhYMHNJbU55WldSbGJuUnBZV3hUZFdKcVpXTjBJanA3SW1SbFozSmxaU0k2ZXlKdVlXMWxJam9pUW1GamFHVnNiM0lnYjJZZ1UyTnBaVzVqWlNCaGJtUWdRWEowY3lJc0lsOXpaQ0k2V3lKTVZYaHFjV3RzV1MxaGREVlNWbUZvU1hweE0zTkpaMDE1ZGtkd1ZEbHdkbFV3ZFRSeVUya3RNWGwzSWwxOUxDSmZjMlFpT2xzaVZteFpMVzUwWmtsUE9VSTVSR1JzVVdwNVUyUkVNbGRvVldJMGJqYzNabDlIV0RaMlUxZExRV3BDTkNKZGZTd2lYM05rSWpwYklpMWlSRVo0VW05NFVVVmxjRWRqWkZsNmEyNTBhVFZHV1hCc1VUVTVOMGRqYUVkVVRHVnRMVkpTWTFVaUxDSmZSRUZWWjB4clRGOXpWa1Z0TFRCdmNFOHphV2hwZVZGaFMwWnpUMDh4VWw5T05rMUNVbXByT1doRklsMTkuS2MwODNSS2JCeGMzVnI1cVIzaUVFUHAzZEt4VGE2c1BhV05zcXRrSXc4VHZNUmY5RVpMMmFqdGdrV1NCWXp5ek96YXdPckNYcnl5cDRyTVR5STl2ZkEgfld5SmlRMVJUYVU5SE5VbzFWWGhQWTFRd1VsTmZkMDFuSWl3Z0ltbGtJaXdnSW1oMGRIQTZMeTkxYm1sMlpYSnphWFI1TG1WNFlXMXdiR1V2WTNKbFpHVnVkR2xoYkhNdk1UZzNNaUpkfld5SlRjbE5XTVMwMVNqUjZjV2hPVTNOM1NUSXdhSGRSSWl3Z0luUjVjR1VpTENCYklsWmxjbWxtYVdGaWJHVkRjbVZrWlc1MGFXRnNJaXdnSWtWNFlXMXdiR1ZCYkhWdGJtbERjbVZrWlc1MGFXRnNJbDFkfld5SktYMjk0ZERodFVHVXRhRGw0TWtRemMyOXVUMU4zSWl3Z0ltbGtJaXdnSW1oMGRIQnpPaTh2WlhoaGJYQnNaUzV2Y21jdlpYaGhiWEJzWlhNdlpHVm5jbVZsTG1wemIyNGlYUX5XeUpETWxwV2VrdG1aMTg1UlVoMWFqQjJTMUV4ZFdKbklpd2dJblI1Y0dVaUxDQWlTbk52YmxOamFHVnRZU0pkfld5SjZTemQ1UWxGUGJGaGZYMlEwWDBWb1lVYzBZMHBSSWl3Z0ltbGtJaXdnSW1ScFpEcGxlR0Z0Y0d4bE9qRXlNeUpkfld5SjZiMXB6UnpNemVYQk1lVlJHTW05YVMzWm1NVkZuSWl3Z0luUjVjR1VpTENBaVFtRmphR1ZzYjNKRVpXZHlaV1VpWFF-Il0\~

``` header-value
{
  "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
  "alg": "ES256"
}
```

``` header-value
{
  "iat": 1745594772,
  "exp": 1746804372,
  "_sd_alg": "sha-256",
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "verifiableCredential": [
    {
      "@context": "https://www.w3.org/ns/credentials/v2",
      "_sd": [
        "-TVRU6yQRkVF-5q086cXufxFu8vp4AZJO-h_U8zyF_Q",
        "agL1w9EKxK0l6qVKx79DkuyGFh2szgHG9Rf0kG_2nug"
      ]
    }
  ],
  "_sd": [
    "RzTr8zM0p1-0rwhLws02_I30D8G6kpDPFlOFgag0ya0"
  ]
}
```

### Claim: type

**SHA-256 Hash:** RzTr8zM0p1-0rwhLws02_I30D8G6kpDPFlOFgag0ya0

**Disclosure(s):** WyIwempCdDNBa0VRd0tJbllMNmhvX0lBIiwgInR5cGUiLCAiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd

**Contents:** \[\
  \"0zjBt3AkEQwKInYL6ho_IA\",\
  \"type\",\
  \"VerifiablePresentation\"\
\]

### Claim: type

**SHA-256 Hash:** agL1w9EKxK0l6qVKx79DkuyGFh2szgHG9Rf0kG_2nug

**Disclosure(s):** WyJHRXRVYzJuSS1Qd2xMYVhZM19Wd2p3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwiXQ

**Contents:** \[\
  \"GEtUc2nI-PwlLaXY3_Vwjw\",\
  \"type\",\
  \"EnvelopedVerifiableCredential\"\
\]

### Claim: id

**SHA-256 Hash:** -TVRU6yQRkVF-5q086cXufxFu8vp4AZJO-h_U8zyF_Q

**Disclosure(s):** WyJRenl4OTJ5azNZQ2tyZmdRejZQVHVnIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdmMrc2Qtand0LCBleUpyYVdRaU9pSkZlRWhyUWsxWE9XWnRZbXQyVmpJMk5tMVNjSFZRTW5OVldWOU9YMFZYU1U0eGJHRndWWHBQT0hKdklpd2lZV3huSWpvaVJWTXlOVFlpZlEuZXlKZmMyUmZZV3huSWpvaWMyaGhMVEkxTmlJc0lrQmpiMjUwWlhoMElqcGJJbWgwZEhCek9pOHZkM2QzTG5jekxtOXlaeTl1Y3k5amNtVmtaVzUwYVdGc2N5OTJNaUlzSW1oMGRIQnpPaTh2ZDNkM0xuY3pMbTl5Wnk5dWN5OWpjbVZrWlc1MGFXRnNjeTlsZUdGdGNHeGxjeTkyTWlKZExDSnBjM04xWlhJaU9pSm9kSFJ3Y3pvdkwzVnVhWFpsY25OcGRIa3VaWGhoYlhCc1pTOXBjM04xWlhKekx6VTJOVEEwT1NJc0luWmhiR2xrUm5KdmJTSTZJakl3TVRBdE1ERXRNREZVTVRrNk1qTTZNalJhSWl3aVkzSmxaR1Z1ZEdsaGJGTmphR1Z0WVNJNmV5SmZjMlFpT2xzaU5qVkZMVlpaYm1FM1VFNW1TR1ZzVURONlRIRndjRTVFUlhoU0xXaGpXa2hTVG5seE4yVTBaVmRhYnlJc0lqaEpiRXd0VUd4NFVrdDNTMGhMYVRNdFRYaFhNak00ZDBGa1RtUTBOSGRhYkMxaVkzTkJjMkpJUWpBaVhYMHNJbU55WldSbGJuUnBZV3hUZFdKcVpXTjBJanA3SW1SbFozSmxaU0k2ZXlKdVlXMWxJam9pUW1GamFHVnNiM0lnYjJZZ1UyTnBaVzVqWlNCaGJtUWdRWEowY3lJc0lsOXpaQ0k2V3lKTVZYaHFjV3RzV1MxaGREVlNWbUZvU1hweE0zTkpaMDE1ZGtkd1ZEbHdkbFV3ZFRSeVUya3RNWGwzSWwxOUxDSmZjMlFpT2xzaVZteFpMVzUwWmtsUE9VSTVSR1JzVVdwNVUyUkVNbGRvVldJMGJqYzNabDlIV0RaMlUxZExRV3BDTkNKZGZTd2lYM05rSWpwYklpMWlSRVo0VW05NFVVVmxjRWRqWkZsNmEyNTBhVFZHV1hCc1VUVTVOMGRqYUVkVVRHVnRMVkpTWTFVaUxDSmZSRUZWWjB4clRGOXpWa1Z0TFRCdmNFOHphV2hwZVZGaFMwWnpUMDh4VWw5T05rMUNVbXByT1doRklsMTkuS2MwODNSS2JCeGMzVnI1cVIzaUVFUHAzZEt4VGE2c1BhV05zcXRrSXc4VHZNUmY5RVpMMmFqdGdrV1NCWXp5ek96YXdPckNYcnl5cDRyTVR5STl2ZkEgfld5SmlRMVJUYVU5SE5VbzFWWGhQWTFRd1VsTmZkMDFuSWl3Z0ltbGtJaXdnSW1oMGRIQTZMeTkxYm1sMlpYSnphWFI1TG1WNFlXMXdiR1V2WTNKbFpHVnVkR2xoYkhNdk1UZzNNaUpkfld5SlRjbE5XTVMwMVNqUjZjV2hPVTNOM1NUSXdhSGRSSWl3Z0luUjVjR1VpTENCYklsWmxjbWxtYVdGaWJHVkRjbVZrWlc1MGFXRnNJaXdnSWtWNFlXMXdiR1ZCYkhWdGJtbERjbVZrWlc1MGFXRnNJbDFkfld5SktYMjk0ZERodFVHVXRhRGw0TWtRemMyOXVUMU4zSWl3Z0ltbGtJaXdnSW1oMGRIQnpPaTh2WlhoaGJYQnNaUzV2Y21jdlpYaGhiWEJzWlhNdlpHVm5jbVZsTG1wemIyNGlYUX5XeUpETWxwV2VrdG1aMTg1UlVoMWFqQjJTMUV4ZFdKbklpd2dJblI1Y0dVaUxDQWlTbk52YmxOamFHVnRZU0pkfld5SjZTemQ1UWxGUGJGaGZYMlEwWDBWb1lVYzBZMHBSSWl3Z0ltbGtJaXdnSW1ScFpEcGxlR0Z0Y0d4bE9qRXlNeUpkfld5SjZiMXB6UnpNemVYQk1lVlJHTW05YVMzWm1NVkZuSWl3Z0luUjVjR1VpTENBaVFtRmphR1ZzYjNKRVpXZHlaV1VpWFF-Il0

**Contents:** \[\
  \"Qzyx92yk3YCkrfgQz6PTug\",\
  \"id\",\
  \"data:application/vc+sd-jwt, eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ.eyJfc2RfYWxnIjoic2hhLTI1NiIsIkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFNjaGVtYSI6eyJfc2QiOlsiNjVFLVZZbmE3UE5mSGVsUDN6THFwcE5ERXhSLWhjWkhSTnlxN2U0ZVdabyIsIjhJbEwtUGx4Ukt3S0hLaTMtTXhXMjM4d0FkTmQ0NHdabC1iY3NBc2JIQjAiXX0sImNyZWRlbnRpYWxTdWJqZWN0Ijp7ImRlZ3JlZSI6eyJuYW1lIjoiQmFjaGVsb3Igb2YgU2NpZW5jZSBhbmQgQXJ0cyIsIl9zZCI6WyJMVXhqcWtsWS1hdDVSVmFoSXpxM3NJZ015dkdwVDlwdlUwdTRyU2ktMXl3Il19LCJfc2QiOlsiVmxZLW50ZklPOUI5RGRsUWp5U2REMldoVWI0bjc3Zl9HWDZ2U1dLQWpCNCJdfSwiX3NkIjpbIi1iREZ4Um94UUVlcEdjZFl6a250aTVGWXBsUTU5N0djaEdUTGVtLVJSY1UiLCJfREFVZ0xrTF9zVkVtLTBvcE8zaWhpeVFhS0ZzT08xUl9ONk1CUmprOWhFIl19.Kc083RKbBxc3Vr5qR3iEEPp3dKxTa6sPaWNsqtkIw8TvMRf9EZL2ajtgkWSBYzyzOzawOrCXryyp4rMTyI9vfA \~WyJiQ1RTaU9HNUo1VXhPY1QwUlNfd01nIiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd\~WyJTclNWMS01SjR6cWhOU3N3STIwaHdRIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d\~WyJKX294dDhtUGUtaDl4MkQzc29uT1N3IiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvZGVncmVlLmpzb24iXQ\~WyJDMlpWektmZ185RUh1ajB2S1ExdWJnIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd\~WyJ6Szd5QlFPbFhfX2Q0X0VoYUc0Y0pRIiwgImlkIiwgImRpZDpleGFtcGxlOjEyMyJd\~WyJ6b1pzRzMzeXBMeVRGMm9aS3ZmMVFnIiwgInR5cGUiLCAiQmFjaGVsb3JEZWdyZWUiXQ\~\"\
\]

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#enveloped-verifiable-credentials) for more details regarding this example.

[Example 6](#example-a-simple-example-of-a-verifiable-presentation-secured-with-sd-jwt-using-the-envelopedverifiablepresentation-type): A simple example of a verifiable presentation secured with SD-JWT using the EnvelopedVerifiablePresentation type

-   Presentation
-   sd-jwt

``` {.nohighlight .vc vc-tabs="sd-jwt"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "type": "EnvelopedVerifiablePresentation",
  "id": "data:application/vp+sd-jwt,eyJhbGciOiJFUzM4NCIsImtpZCI6IlVRTV9fblE0UzZCTzhuUTRuT05YeHB4aHRob3lOeGI1M0xZZ1l6LTJBQnMiLCJ0eXAiOiJ2cCtsZCtqc29uK3NkLWp3dCIsImN0eSI6InZwK2xkK2pzb24ifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidmVyaWZpYWJsZUNyZWRlbnRpYWwiOlt7IkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFN1YmplY3QiOnsiYWx1bW5pT2YiOnsibmFtZSI6IkV4YW1wbGUgVW5pdmVyc2l0eSIsIl9zZCI6WyJoek9LRzU2cDI5c1ByTGFDNUE4RndFdUczVU05dUlZU1p1cU9YczJlVGJBIl19LCJfc2QiOlsiWVdXVmVDRndxQmk4WDBqSF9jV0NWWU16STNhOHBjTEVYRWZicFNSQVlndyJdfSwiX3NkIjpbIjJJZjhhaUs4REZwVWJ4dEc1cGMwel9SaFJzbm1ybGFRMEhzcTk4WFNyYWsiLCJUeDZ4ZWZMVUdUZUpfYWtVUFdGeHNvbUhobGtWVnpfNzVoaVZ6eWpyYmVzIl19XSwiX3NkIjpbIjd2anl0VVN3ZEJ0MXQ5RktlOVFfS3JIRXhFWGxrTEFaTzBKM0Jpd200dlkiXSwiX3NkX2FsZyI6InNoYS0yNTYiLCJpYXQiOjE3MDY1NjI4NDksImV4cCI6MTczODE4NTI0OSwiY25mIjp7Imp3ayI6eyJrdHkiOiJFQyIsImNydiI6IlAtMzg0IiwiYWxnIjoiRVMzODQiLCJ4IjoidWtEd1U2ZzlQUVRFUWhYaEgyckRZNndMQlg3UHFlUjZBcGlhVHBEUXowcl8tdDl6UXNxem54Z0hEcE5oekZlQyIsInkiOiJMQnhVYnBVdFNGMVVKVTVpYnJIdkpINjBUSG5YMk1xa0xHZGltU1l0UGR4RlkxOEdhcldiS3FZV0djUkZHVE9BIn19fQ.kYD63YtBNYnLUTw6Szf1vs_Ug3UBXhPwCyqpNmPnPDa3rXZQhQLdB1BgaoO8zgQ-c3B41fxaXMnLHYV9-B20uboSpJP0B-2Vre917eQt1cSDswDGA_Ytvn4BSqYVBB2J~WyJFMkFsRzhsY2p0QVFrcllIbjlIbnVRIiwgInR5cGUiLCAiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd~WyI5NldYMDRneno4cVZzOVZLU2wwYTVnIiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~WyJaekU2VFVaamtHMW1DWXBKMEhnc0l3IiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyItQ3NsS25GZGFYb2JiQWsyU0JBVGR3IiwgImlkIiwgImRpZDpleGFtcGxlOmViZmViMWY3MTJlYmM2ZjFjMjc2ZTEyZWMyMSJd~WyJuRm1OWl9IczB3WWNoOFdkeTdnQUNRIiwgImlkIiwgImRpZDpleGFtcGxlOmMyNzZlMTJlYzIxZWJmZWIxZjcxMmViYzZmMSJd~"
}
```

-   Encoded
-   Decoded
-   Issuer Disclosures

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJpYXQiOjE3NDU1OTQ3NzIsImV4cCI6MTc0NjgwNDM3MiwiX3NkX2FsZyI6InNoYS0yNTYiLCJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiX3NkIjpbIjNoUmczNzNhVEhVblI0b2NrMkNBU2EzeHdpaVdlOGVVUndWNU1GVFA3dlEiLCJXb2J4dDdHZ1VtRVpKaEJJZGkyb1NyaDN4aEtqak5Xa0Rnc2t4M0RJNmtnIl19 .789HJKTur9F0FpxUR_EUk8SudozGvoayr83QyxuoiDbP7BudeJMibmU_CWB_AGSVR5XNDMlqJW4XLvj3XQ3WCw \~WyJZM3JRNVNkUFd3YW5ieTRlMGhMSDF3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd\~WyJFUWRUZEprS3Fjd3RrMkVVMUxkOTZnIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdnArc2Qtand0LCBleUpoYkdjaU9pSkZVek00TkNJc0ltdHBaQ0k2SWxWUlRWOWZibEUwVXpaQ1R6aHVVVFJ1VDA1WWVIQjRhSFJvYjNsT2VHSTFNMHhaWjFsNkxUSkJRbk1pTENKMGVYQWlPaUoyY0N0c1pDdHFjMjl1SzNOa0xXcDNkQ0lzSW1OMGVTSTZJblp3SzJ4a0sycHpiMjRpZlEuZXlKQVkyOXVkR1Y0ZENJNld5Sm9kSFJ3Y3pvdkwzZDNkeTUzTXk1dmNtY3Zibk12WTNKbFpHVnVkR2xoYkhNdmRqSWlMQ0pvZEhSd2N6b3ZMM2QzZHk1M015NXZjbWN2Ym5NdlkzSmxaR1Z1ZEdsaGJITXZaWGhoYlhCc1pYTXZkaklpWFN3aWRtVnlhV1pwWVdKc1pVTnlaV1JsYm5ScFlXd2lPbHQ3SWtCamIyNTBaWGgwSWpwYkltaDBkSEJ6T2k4dmQzZDNMbmN6TG05eVp5OXVjeTlqY21Wa1pXNTBhV0ZzY3k5Mk1pSXNJbWgwZEhCek9pOHZkM2QzTG5jekxtOXlaeTl1Y3k5amNtVmtaVzUwYVdGc2N5OWxlR0Z0Y0d4bGN5OTJNaUpkTENKcGMzTjFaWElpT2lKb2RIUndjem92TDNWdWFYWmxjbk5wZEhrdVpYaGhiWEJzWlM5cGMzTjFaWEp6THpVMk5UQTBPU0lzSW5aaGJHbGtSbkp2YlNJNklqSXdNVEF0TURFdE1ERlVNVGs2TWpNNk1qUmFJaXdpWTNKbFpHVnVkR2xoYkZOMVltcGxZM1FpT25zaVlXeDFiVzVwVDJZaU9uc2libUZ0WlNJNklrVjRZVzF3YkdVZ1ZXNXBkbVZ5YzJsMGVTSXNJbDl6WkNJNld5Sm9lazlMUnpVMmNESTVjMUJ5VEdGRE5VRTRSbmRGZFVjelZVMDVkVWxaVTFwMWNVOVljekpsVkdKQklsMTlMQ0pmYzJRaU9sc2lXVmRYVm1WRFJuZHhRbWs0V0RCcVNGOWpWME5XV1UxNlNUTmhPSEJqVEVWWVJXWmljRk5TUVZsbmR5SmRmU3dpWDNOa0lqcGJJakpKWmpoaGFVczRSRVp3VldKNGRFYzFjR013ZWw5U2FGSnpibTF5YkdGUk1FaHpjVGs0V0ZOeVlXc2lMQ0pVZURaNFpXWk1WVWRVWlVwZllXdFZVRmRHZUhOdmJVaG9iR3RXVm5wZk56Vm9hVlo2ZVdweVltVnpJbDE5WFN3aVgzTmtJanBiSWpkMmFubDBWVk4zWkVKME1YUTVSa3RsT1ZGZlMzSklSWGhGV0d4clRFRmFUekJLTTBKcGQyMDBkbGtpWFN3aVgzTmtYMkZzWnlJNkluTm9ZUzB5TlRZaUxDSnBZWFFpT2pFM01EWTFOakk0TkRrc0ltVjRjQ0k2TVRjek9ERTROVEkwT1N3aVkyNW1JanA3SW1wM2F5STZleUpyZEhraU9pSkZReUlzSW1OeWRpSTZJbEF0TXpnMElpd2lZV3huSWpvaVJWTXpPRFFpTENKNElqb2lkV3RFZDFVMlp6bFFVVlJGVVdoWWFFZ3lja1JaTm5kTVFsZzNVSEZsVWpaQmNHbGhWSEJFVVhvd2NsOHRkRGw2VVhOeGVtNTRaMGhFY0U1b2VrWmxReUlzSW5raU9pSk1RbmhWWW5CVmRGTkdNVlZLVlRWcFluSklka3BJTmpCVVNHNVlNazF4YTB4SFpHbHRVMWwwVUdSNFJsa3hPRWRoY2xkaVMzRlpWMGRqVWtaSFZFOUJJbjE5ZlEua1lENjNZdEJOWW5MVVR3NlN6ZjF2c19VZzNVQlhoUHdDeXFwTm1QblBEYTNyWFpRaFFMZEIxQmdhb084emdRLWMzQjQxZnhhWE1uTEhZVjktQjIwdWJvU3BKUDBCLTJWcmU5MTdlUXQxY1NEc3dER0FfWXR2bjRCU3FZVkJCMkp-V3lKRk1rRnNSemhzWTJwMFFWRnJjbGxJYmpsSWJuVlJJaXdnSW5SNWNHVWlMQ0FpVm1WeWFXWnBZV0pzWlZCeVpYTmxiblJoZEdsdmJpSmR-V3lJNU5sZFlNRFJuZW5vNGNWWnpPVlpMVTJ3d1lUVm5JaXdnSW1sa0lpd2dJbWgwZEhBNkx5OTFibWwyWlhKemFYUjVMbVY0WVcxd2JHVXZZM0psWkdWdWRHbGhiSE12TVRnM01pSmR-V3lKYWVrVTJWRlZhYW10SE1XMURXWEJLTUVobmMwbDNJaXdnSW5SNWNHVWlMQ0JiSWxabGNtbG1hV0ZpYkdWRGNtVmtaVzUwYVdGc0lpd2dJa1Y0WVcxd2JHVkJiSFZ0Ym1sRGNtVmtaVzUwYVdGc0lsMWR-V3lJdFEzTnNTMjVHWkdGWWIySmlRV3N5VTBKQlZHUjNJaXdnSW1sa0lpd2dJbVJwWkRwbGVHRnRjR3hsT21WaVptVmlNV1kzTVRKbFltTTJaakZqTWpjMlpURXlaV015TVNKZH5XeUp1Um0xT1dsOUljekIzV1dOb09GZGtlVGRuUVVOUklpd2dJbWxrSWl3Z0ltUnBaRHBsZUdGdGNHeGxPbU15TnpabE1USmxZekl4WldKbVpXSXhaamN4TW1WaVl6Wm1NU0pkfiJd\~

``` header-value
{
  "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
  "alg": "ES256"
}
```

``` header-value
{
  "iat": 1745594772,
  "exp": 1746804372,
  "_sd_alg": "sha-256",
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "_sd": [
    "3hRg373aTHUnR4ock2CASa3xwiiWe8eURwV5MFTP7vQ",
    "Wobxt7GgUmEZJhBIdi2oSrh3xhKjjNWkDgskx3DI6kg"
  ]
}
```

### Claim: type

**SHA-256 Hash:** 3hRg373aTHUnR4ock2CASa3xwiiWe8eURwV5MFTP7vQ

**Disclosure(s):** WyJZM3JRNVNkUFd3YW5ieTRlMGhMSDF3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd

**Contents:** \[\
  \"Y3rQ5SdPWwanby4e0hLH1w\",\
  \"type\",\
  \"EnvelopedVerifiablePresentation\"\
\]

### Claim: id

**SHA-256 Hash:** Wobxt7GgUmEZJhBIdi2oSrh3xhKjjNWkDgskx3DI6kg

**Disclosure(s):** WyJFUWRUZEprS3Fjd3RrMkVVMUxkOTZnIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdnArc2Qtand0LCBleUpoYkdjaU9pSkZVek00TkNJc0ltdHBaQ0k2SWxWUlRWOWZibEUwVXpaQ1R6aHVVVFJ1VDA1WWVIQjRhSFJvYjNsT2VHSTFNMHhaWjFsNkxUSkJRbk1pTENKMGVYQWlPaUoyY0N0c1pDdHFjMjl1SzNOa0xXcDNkQ0lzSW1OMGVTSTZJblp3SzJ4a0sycHpiMjRpZlEuZXlKQVkyOXVkR1Y0ZENJNld5Sm9kSFJ3Y3pvdkwzZDNkeTUzTXk1dmNtY3Zibk12WTNKbFpHVnVkR2xoYkhNdmRqSWlMQ0pvZEhSd2N6b3ZMM2QzZHk1M015NXZjbWN2Ym5NdlkzSmxaR1Z1ZEdsaGJITXZaWGhoYlhCc1pYTXZkaklpWFN3aWRtVnlhV1pwWVdKc1pVTnlaV1JsYm5ScFlXd2lPbHQ3SWtCamIyNTBaWGgwSWpwYkltaDBkSEJ6T2k4dmQzZDNMbmN6TG05eVp5OXVjeTlqY21Wa1pXNTBhV0ZzY3k5Mk1pSXNJbWgwZEhCek9pOHZkM2QzTG5jekxtOXlaeTl1Y3k5amNtVmtaVzUwYVdGc2N5OWxlR0Z0Y0d4bGN5OTJNaUpkTENKcGMzTjFaWElpT2lKb2RIUndjem92TDNWdWFYWmxjbk5wZEhrdVpYaGhiWEJzWlM5cGMzTjFaWEp6THpVMk5UQTBPU0lzSW5aaGJHbGtSbkp2YlNJNklqSXdNVEF0TURFdE1ERlVNVGs2TWpNNk1qUmFJaXdpWTNKbFpHVnVkR2xoYkZOMVltcGxZM1FpT25zaVlXeDFiVzVwVDJZaU9uc2libUZ0WlNJNklrVjRZVzF3YkdVZ1ZXNXBkbVZ5YzJsMGVTSXNJbDl6WkNJNld5Sm9lazlMUnpVMmNESTVjMUJ5VEdGRE5VRTRSbmRGZFVjelZVMDVkVWxaVTFwMWNVOVljekpsVkdKQklsMTlMQ0pmYzJRaU9sc2lXVmRYVm1WRFJuZHhRbWs0V0RCcVNGOWpWME5XV1UxNlNUTmhPSEJqVEVWWVJXWmljRk5TUVZsbmR5SmRmU3dpWDNOa0lqcGJJakpKWmpoaGFVczRSRVp3VldKNGRFYzFjR013ZWw5U2FGSnpibTF5YkdGUk1FaHpjVGs0V0ZOeVlXc2lMQ0pVZURaNFpXWk1WVWRVWlVwZllXdFZVRmRHZUhOdmJVaG9iR3RXVm5wZk56Vm9hVlo2ZVdweVltVnpJbDE5WFN3aVgzTmtJanBiSWpkMmFubDBWVk4zWkVKME1YUTVSa3RsT1ZGZlMzSklSWGhGV0d4clRFRmFUekJLTTBKcGQyMDBkbGtpWFN3aVgzTmtYMkZzWnlJNkluTm9ZUzB5TlRZaUxDSnBZWFFpT2pFM01EWTFOakk0TkRrc0ltVjRjQ0k2TVRjek9ERTROVEkwT1N3aVkyNW1JanA3SW1wM2F5STZleUpyZEhraU9pSkZReUlzSW1OeWRpSTZJbEF0TXpnMElpd2lZV3huSWpvaVJWTXpPRFFpTENKNElqb2lkV3RFZDFVMlp6bFFVVlJGVVdoWWFFZ3lja1JaTm5kTVFsZzNVSEZsVWpaQmNHbGhWSEJFVVhvd2NsOHRkRGw2VVhOeGVtNTRaMGhFY0U1b2VrWmxReUlzSW5raU9pSk1RbmhWWW5CVmRGTkdNVlZLVlRWcFluSklka3BJTmpCVVNHNVlNazF4YTB4SFpHbHRVMWwwVUdSNFJsa3hPRWRoY2xkaVMzRlpWMGRqVWtaSFZFOUJJbjE5ZlEua1lENjNZdEJOWW5MVVR3NlN6ZjF2c19VZzNVQlhoUHdDeXFwTm1QblBEYTNyWFpRaFFMZEIxQmdhb084emdRLWMzQjQxZnhhWE1uTEhZVjktQjIwdWJvU3BKUDBCLTJWcmU5MTdlUXQxY1NEc3dER0FfWXR2bjRCU3FZVkJCMkp-V3lKRk1rRnNSemhzWTJwMFFWRnJjbGxJYmpsSWJuVlJJaXdnSW5SNWNHVWlMQ0FpVm1WeWFXWnBZV0pzWlZCeVpYTmxiblJoZEdsdmJpSmR-V3lJNU5sZFlNRFJuZW5vNGNWWnpPVlpMVTJ3d1lUVm5JaXdnSW1sa0lpd2dJbWgwZEhBNkx5OTFibWwyWlhKemFYUjVMbVY0WVcxd2JHVXZZM0psWkdWdWRHbGhiSE12TVRnM01pSmR-V3lKYWVrVTJWRlZhYW10SE1XMURXWEJLTUVobmMwbDNJaXdnSW5SNWNHVWlMQ0JiSWxabGNtbG1hV0ZpYkdWRGNtVmtaVzUwYVdGc0lpd2dJa1Y0WVcxd2JHVkJiSFZ0Ym1sRGNtVmtaVzUwYVdGc0lsMWR-V3lJdFEzTnNTMjVHWkdGWWIySmlRV3N5VTBKQlZHUjNJaXdnSW1sa0lpd2dJbVJwWkRwbGVHRnRjR3hsT21WaVptVmlNV1kzTVRKbFltTTJaakZqTWpjMlpURXlaV015TVNKZH5XeUp1Um0xT1dsOUljekIzV1dOb09GZGtlVGRuUVVOUklpd2dJbWxrSWl3Z0ltUnBaRHBsZUdGdGNHeGxPbU15TnpabE1USmxZekl4WldKbVpXSXhaamN4TW1WaVl6Wm1NU0pkfiJd

**Contents:** \[\
  \"EQdTdJkKqcwtk2EU1Ld96g\",\
  \"id\",\
  \"data:application/vp+sd-jwt, eyJhbGciOiJFUzM4NCIsImtpZCI6IlVRTV9fblE0UzZCTzhuUTRuT05YeHB4aHRob3lOeGI1M0xZZ1l6LTJBQnMiLCJ0eXAiOiJ2cCtsZCtqc29uK3NkLWp3dCIsImN0eSI6InZwK2xkK2pzb24ifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidmVyaWZpYWJsZUNyZWRlbnRpYWwiOlt7IkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFN1YmplY3QiOnsiYWx1bW5pT2YiOnsibmFtZSI6IkV4YW1wbGUgVW5pdmVyc2l0eSIsIl9zZCI6WyJoek9LRzU2cDI5c1ByTGFDNUE4RndFdUczVU05dUlZU1p1cU9YczJlVGJBIl19LCJfc2QiOlsiWVdXVmVDRndxQmk4WDBqSF9jV0NWWU16STNhOHBjTEVYRWZicFNSQVlndyJdfSwiX3NkIjpbIjJJZjhhaUs4REZwVWJ4dEc1cGMwel9SaFJzbm1ybGFRMEhzcTk4WFNyYWsiLCJUeDZ4ZWZMVUdUZUpfYWtVUFdGeHNvbUhobGtWVnpfNzVoaVZ6eWpyYmVzIl19XSwiX3NkIjpbIjd2anl0VVN3ZEJ0MXQ5RktlOVFfS3JIRXhFWGxrTEFaTzBKM0Jpd200dlkiXSwiX3NkX2FsZyI6InNoYS0yNTYiLCJpYXQiOjE3MDY1NjI4NDksImV4cCI6MTczODE4NTI0OSwiY25mIjp7Imp3ayI6eyJrdHkiOiJFQyIsImNydiI6IlAtMzg0IiwiYWxnIjoiRVMzODQiLCJ4IjoidWtEd1U2ZzlQUVRFUWhYaEgyckRZNndMQlg3UHFlUjZBcGlhVHBEUXowcl8tdDl6UXNxem54Z0hEcE5oekZlQyIsInkiOiJMQnhVYnBVdFNGMVVKVTVpYnJIdkpINjBUSG5YMk1xa0xHZGltU1l0UGR4RlkxOEdhcldiS3FZV0djUkZHVE9BIn19fQ.kYD63YtBNYnLUTw6Szf1vs_Ug3UBXhPwCyqpNmPnPDa3rXZQhQLdB1BgaoO8zgQ-c3B41fxaXMnLHYV9-B20uboSpJP0B-2Vre917eQt1cSDswDGA_Ytvn4BSqYVBB2J\~WyJFMkFsRzhsY2p0QVFrcllIbjlIbnVRIiwgInR5cGUiLCAiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd\~WyI5NldYMDRneno4cVZzOVZLU2wwYTVnIiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd\~WyJaekU2VFVaamtHMW1DWXBKMEhnc0l3IiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d\~WyItQ3NsS25GZGFYb2JiQWsyU0JBVGR3IiwgImlkIiwgImRpZDpleGFtcGxlOmViZmViMWY3MTJlYmM2ZjFjMjc2ZTEyZWMyMSJd\~WyJuRm1OWl9IczB3WWNoOFdkeTdnQUNRIiwgImlkIiwgImRpZDpleGFtcGxlOmMyNzZlMTJlYzIxZWJmZWIxZjcxMmViYzZmMSJd\~\"\
\]

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#enveloped-verifiable-presentations) for more details regarding this example.

Implementations *MUST* support the compact serialization (`application/sd-jwt`) and *MAY* support the JSON serialization (`application/sd-jwt+json`). If the JSON serialization is used, it is *RECOMMENDED* that a profile be defined to ensure any additional JSON members are understood consistently.

### 3.3 With COSE

[](#securing-with-cose)

COSE \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] is a common approach to encoding and securing information using CBOR \[[RFC8949](#bib-rfc8949 "Concise Binary Object Representation (CBOR)")\]. Verifiable credentials *MAY* be secured using COSE \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] and *SHOULD* be identified through use of content types as outlined in this section.

#### 3.3.1 Securing JSON-LD Verifiable Credentials with COSE

[](#securing-vcs-with-cose)

This section details how to use COSE to secure [verifiable credentials](#dfn-verifiable-credentials) conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

A [conforming COSE issuer implementation](#dfn-conforming-cose-issuer-implementation) *MUST* use COSE_Sign1 as specified in \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] to secure this media type. The unsecured [verifiable credential](#dfn-verifiable-credentials) is the unencoded COSE_Sign1 payload.

The `typ (16)` header parameter, as described in [COSE \"typ\" (type) Header Parameter](https://www.rfc-editor.org/rfc/rfc9596#section-2), *SHOULD* be `application/vc+cose`. The `content type (3)` header parameter *SHOULD* be `application/vc`. The `content type (3)` header parameter is optional, and can be used to express a more specific media type than `application/vc` when one is available. See [Common COSE Header Parameters](https://www.rfc-editor.org/rfc/rfc9052#section-3.1) for additional details.

A [conforming COSE verifier implementation](#dfn-conforming-cose-verifier-implementation) *MUST* use COSE_Sign1 as specified in \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] to verify [conforming COSE documents](#dfn-conforming-cose-document) that use this media type.

When including [verifiable credentials](#dfn-verifiable-credentials) secured with COSE in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) as [Enveloped Verifiable Credentials](https://www.w3.org/TR/vc-data-model-2.0/#defn-EnvelopedVerifiableCredential), the credentials *MUST* be encoded using base64 as specified in \[[RFC2397](#bib-rfc2397 "The "data" URL scheme")\].

To encrypt a secured [verifiable credential](#dfn-verifiable-credentials) when transmitting over an insecure channel, implementers *MAY* use COSE encryption, as defined in Section 5 of \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\], by nesting the secured [verifiable credential](#dfn-verifiable-credentials) as the plaintext payload of an encrypted COSE object.

[Example 7](#example-a-simple-example-of-a-verifiable-credential-secured-with-cose): A simple example of a verifiable credential secured with COSE

-   Credential
-   cose

``` {.nohighlight .vc vc-tabs="cose"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "id": "http://university.example/credentials/3732",
  "type": ["VerifiableCredential", "ExampleDegreeCredential", "ExamplePersonCredential"],
  "issuer": "https://university.example/issuers/14",
  "validFrom": "2010-01-01T19:23:24Z",
  "credentialSubject": {
    "id": "did:example:ebfeb1f712ebc6f1c276e12ec21",
    "degree": {
      "type": "ExampleBachelorDegree",
      "name": "Bachelor of Science and Arts"
    },
    "alumniOf": {
      "name": "Example University"
    }
  },
  "credentialSchema": [{
    "id": "https://example.org/examples/degree.json",
    "type": "JsonSchema"
  },
  {
    "id": "https://example.org/examples/alumni.json",
    "type": "JsonSchema"
  }]
}
```

**application/vc**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "id": "http://university.example/credentials/3732",
      "type": [
        "VerifiableCredential",
        "ExampleDegreeCredential",
        "ExamplePersonCredential"
      ],
      "issuer": "https://university.example/issuers/14",
      "validFrom": "2010-01-01T19:23:24Z",
      "credentialSubject": {
        "id": "did:example:ebfeb1f712ebc6f1c276e12ec21",
        "degree": {
          "type": "ExampleBachelorDegree",
          "name": "Bachelor of Science and Arts"
        },
        "alumniOf": {
          "name": "Example University"
        }
      },
      "credentialSchema": [
        {
          "id": "https://example.org/examples/degree.json",
          "type": "JsonSchema"
        },
        {
          "id": "https://example.org/examples/alumni.json",
          "type": "JsonSchema"
        }
      ]
    }

**application/vc+cose**

d28444a1013822a059029e7b2240636f6e74657874223a5b2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f6578616d706c65732f7632225d2c226964223a22687474703a2f2f756e69766572736974792e6578616d706c652f63726564656e7469616c732f33373332222c2274797065223a5b2256657269666961626c6543726564656e7469616c222c224578616d706c6544656772656543726564656e7469616c222c224578616d706c65506572736f6e43726564656e7469616c225d2c22697373756572223a2268747470733a2f2f756e69766572736974792e6578616d706c652f697373756572732f3134222c2276616c696446726f6d223a22323031302d30312d30315431393a32333a32345a222c2263726564656e7469616c5375626a656374223a7b226964223a226469643a6578616d706c653a656266656231663731326562633666316332373665313265633231222c22646567726565223a7b2274797065223a224578616d706c6542616368656c6f72446567726565222c226e616d65223a2242616368656c6f72206f6620536369656e636520616e642041727473227d2c22616c756d6e694f66223a7b226e616d65223a224578616d706c6520556e6976657273697479227d7d2c2263726564656e7469616c536368656d61223a5b7b226964223a2268747470733a2f2f6578616d706c652e6f72672f6578616d706c65732f6465677265652e6a736f6e222c2274797065223a224a736f6e536368656d61227d2c7b226964223a2268747470733a2f2f6578616d706c652e6f72672f6578616d706c65732f616c756d6e692e6a736f6e222c2274797065223a224a736f6e536368656d61227d5d7d5840be50900a466b077ece4d0bfe1e96588e048e43b6f45fbc3fb3e659a1b3f8b4431678db4d352bb933ab9aeb9010aea13bf177c775d7927ff074e3f86402fe3a97

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#example-using-the-credentialschema-property-to-perform-json-schema-validation) for more details regarding this example.

#### 3.3.2 Securing JSON-LD Verifiable Presentations with COSE

[](#securing-vps-with-cose)

This section details how to use COSE to secure [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) conforming to \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

A [conforming COSE issuer implementation](#dfn-conforming-cose-issuer-implementation) *MUST* use COSE_Sign1 as specified in \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] to secure this media type. The unsecured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) is the unencoded COSE_Sign1 payload.

The `typ (16)` header parameter, as described in [COSE \"typ\" (type) Header Parameter](https://www.rfc-editor.org/rfc/rfc9596#section-2), *SHOULD* be `application/vp+cose`. The `content type (3)` header parameter *SHOULD* be `application/vp`. The `content type (3)` header parameter is optional, and can be used to express a more specific media type than `application/vp` when one is available. See [Common COSE Header Parameters](https://www.rfc-editor.org/rfc/rfc9052#section-3.1) for additional details.

A [conforming COSE verifier implementation](#dfn-conforming-cose-verifier-implementation) *MUST* use COSE_Sign1 as specified in \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] to verify [conforming COSE documents](#dfn-conforming-cose-document) that use this media type.

Verifiable Credentials secured in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations) *MUST* use the [Enveloped Verifiable Credential](https://www.w3.org/TR/vc-data-model-2.0/#defn-EnvelopedVerifiableCredential) type defined by the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

Verifiable Presentations in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations) *MUST* use the [Enveloped Verifiable Presentation](https://www.w3.org/TR/vc-data-model-2.0/#defn-EnvelopedVerifiablePresentation) type defined by the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

Credentials in [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) *MUST* be secured. These [credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-credential) are secured using COSE in this case.

To encrypt a secured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) when transmitting over an insecure channel, implementers *MAY* use COSE encryption, as defined in Section 5 of \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\], by nesting the secured [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) as the plaintext payload of an encrypted COSE object.

[Example 8](#example-a-simple-example-of-a-verifiable-presentation-secured-withcose-using-the-envelopedverifiablecredential-type): A simple example of a verifiable presentation secured withCOSE using the EnvelopedVerifiableCredential type

-   Presentation
-   cose

``` {.nohighlight .vc vc-tabs="cose"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "type": "VerifiablePresentation",
  "verifiableCredential": [{
    "@context": "https://www.w3.org/ns/credentials/v2",
    "type": "EnvelopedVerifiableCredential",
    "id": "data:application/vc+sd-jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ.eyJfc2RfYWxnIjoic2hhLTI1NiIsIkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFNjaGVtYSI6eyJfc2QiOlsiNWJBeDMteHBmQWxVS0ZJOXNuM2hWQ21wR2trcUlzWmMzLUxiMzNmWmpiayIsIlpjQXZIMDhsdEJySUpmSWh0OF9tS1BfYzNscG5YMWNHclltVG8wZ1lCeTgiXX0sImNyZWRlbnRpYWxTdWJqZWN0Ijp7ImRlZ3JlZSI6eyJuYW1lIjoiQmFjaGVsb3Igb2YgU2NpZW5jZSBhbmQgQXJ0cyIsIl9zZCI6WyJST1Q3MUl0dTNMNlVXWFVqby1oWVdJQjY3bHVPTkVEUlNCaGxEVENxVU9RIl19LCJfc2QiOlsiTUVuZXNnMlhPUk5jY3NCTWVaXzE2MDJneTQwUi00WUJ2VlIweFE4b0Y4YyJdfSwiX3NkIjpbIkVlc2Jiay1mcGZwd2ZMOXdOczFxcjZ0aU43ZnEtSXQzWVM2V3ZCbl9iWG8iLCJab1I1ZGRhckdtZk15NEhuV0xVak5URnFURjNYRjZpdFBnZnlGQkhVX3FVIl19.gw3paxbkLjpi8CTsyRpXKbC7tpVa0q2sWKSD-_dcbuZ1LpZV3oQ8Ifzcm2bE8RY3fmJgbuyA9gbPL3sQBaTzkg ~WyJSeUQxVlB4VHBvbmtPeXZpczkta293IiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~WyJfVjd1eTd3ay1RM3VZd2ZpZ0NvWUVBIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyJhazdqMTlnYVMtRDJLX2hzY3RVZGNRIiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvZGVncmVlLmpzb24iXQ~WyJUTjBXaXVZRkhXWkV2ZDZIQUJHQS1nIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd~WyJVMnBzMkxYVERVbVh3MDcxRVBmRUpnIiwgImlkIiwgImRpZDpleGFtcGxlOjEyMyJd~WyJsQ042eTNEaTNDUk9VX3JuXzRENWRnIiwgInR5cGUiLCAiQmFjaGVsb3JEZWdyZWUiXQ~"
  }]
}
```

**application/vp**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "type": "VerifiablePresentation",
      "verifiableCredential": [
        {
          "@context": "https://www.w3.org/ns/credentials/v2",
          "id": "data:application/vc+sd-jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ.eyJfc2RfYWxnIjoic2hhLTI1NiIsIkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFNjaGVtYSI6eyJfc2QiOlsiNWJBeDMteHBmQWxVS0ZJOXNuM2hWQ21wR2trcUlzWmMzLUxiMzNmWmpiayIsIlpjQXZIMDhsdEJySUpmSWh0OF9tS1BfYzNscG5YMWNHclltVG8wZ1lCeTgiXX0sImNyZWRlbnRpYWxTdWJqZWN0Ijp7ImRlZ3JlZSI6eyJuYW1lIjoiQmFjaGVsb3Igb2YgU2NpZW5jZSBhbmQgQXJ0cyIsIl9zZCI6WyJST1Q3MUl0dTNMNlVXWFVqby1oWVdJQjY3bHVPTkVEUlNCaGxEVENxVU9RIl19LCJfc2QiOlsiTUVuZXNnMlhPUk5jY3NCTWVaXzE2MDJneTQwUi00WUJ2VlIweFE4b0Y4YyJdfSwiX3NkIjpbIkVlc2Jiay1mcGZwd2ZMOXdOczFxcjZ0aU43ZnEtSXQzWVM2V3ZCbl9iWG8iLCJab1I1ZGRhckdtZk15NEhuV0xVak5URnFURjNYRjZpdFBnZnlGQkhVX3FVIl19.gw3paxbkLjpi8CTsyRpXKbC7tpVa0q2sWKSD-_dcbuZ1LpZV3oQ8Ifzcm2bE8RY3fmJgbuyA9gbPL3sQBaTzkg ~WyJSeUQxVlB4VHBvbmtPeXZpczkta293IiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~WyJfVjd1eTd3ay1RM3VZd2ZpZ0NvWUVBIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyJhazdqMTlnYVMtRDJLX2hzY3RVZGNRIiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvZGVncmVlLmpzb24iXQ~WyJUTjBXaXVZRkhXWkV2ZDZIQUJHQS1nIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd~WyJVMnBzMkxYVERVbVh3MDcxRVBmRUpnIiwgImlkIiwgImRpZDpleGFtcGxlOjEyMyJd~WyJsQ042eTNEaTNDUk9VX3JuXzRENWRnIiwgInR5cGUiLCAiQmFjaGVsb3JEZWdyZWUiXQ~;data:application/vc+sd-jwt,eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ.eyJfc2RfYWxnIjoic2hhLTI1NiIsIkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFNjaGVtYSI6eyJfc2QiOlsiNWJBeDMteHBmQWxVS0ZJOXNuM2hWQ21wR2trcUlzWmMzLUxiMzNmWmpiayIsIlpjQXZIMDhsdEJySUpmSWh0OF9tS1BfYzNscG5YMWNHclltVG8wZ1lCeTgiXX0sImNyZWRlbnRpYWxTdWJqZWN0Ijp7ImRlZ3JlZSI6eyJuYW1lIjoiQmFjaGVsb3Igb2YgU2NpZW5jZSBhbmQgQXJ0cyIsIl9zZCI6WyJST1Q3MUl0dTNMNlVXWFVqby1oWVdJQjY3bHVPTkVEUlNCaGxEVENxVU9RIl19LCJfc2QiOlsiTUVuZXNnMlhPUk5jY3NCTWVaXzE2MDJneTQwUi00WUJ2VlIweFE4b0Y4YyJdfSwiX3NkIjpbIkVlc2Jiay1mcGZwd2ZMOXdOczFxcjZ0aU43ZnEtSXQzWVM2V3ZCbl9iWG8iLCJab1I1ZGRhckdtZk15NEhuV0xVak5URnFURjNYRjZpdFBnZnlGQkhVX3FVIl19.gw3paxbkLjpi8CTsyRpXKbC7tpVa0q2sWKSD-_dcbuZ1LpZV3oQ8Ifzcm2bE8RY3fmJgbuyA9gbPL3sQBaTzkg ~WyJSeUQxVlB4VHBvbmtPeXZpczkta293IiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~WyJfVjd1eTd3ay1RM3VZd2ZpZ0NvWUVBIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyJhazdqMTlnYVMtRDJLX2hzY3RVZGNRIiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvZGVncmVlLmpzb24iXQ~WyJUTjBXaXVZRkhXWkV2ZDZIQUJHQS1nIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd~WyJVMnBzMkxYVERVbVh3MDcxRVBmRUpnIiwgImlkIiwgImRpZDpleGFtcGxlOjEyMyJd~WyJsQ042eTNEaTNDUk9VX3JuXzRENWRnIiwgInR5cGUiLCAiQmFjaGVsb3JEZWdyZWUiXQ~",
          "type": "EnvelopedVerifiableCredential"
        }
      ]
    }

**application/vp+cose**

d28444a1013822a0590d1c7b2240636f6e74657874223a5b2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f6578616d706c65732f7632225d2c2274797065223a2256657269666961626c6550726573656e746174696f6e222c2276657269666961626c6543726564656e7469616c223a5b7b2240636f6e74657874223a2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c226964223a22646174613a6170706c69636174696f6e2f76632b73642d6a77742c65794a72615751694f694a4665456872516b31584f575a74596d7432566a49324e6d3153634856514d6e4e565756394f583056585355347862474677565870504f484a76496977695957786e496a6f6952564d794e54596966512e65794a66633252665957786e496a6f69633268684c5449314e694973496b426a623235305a586830496a7062496d68306448427a4f693876643364334c6e637a4c6d39795a7939756379396a636d566b5a57353061574673637939324d694973496d68306448427a4f693876643364334c6e637a4c6d39795a7939756379396a636d566b5a573530615746736379396c654746746347786c637939324d694a644c434a7063334e315a5849694f694a6f64485277637a6f764c33567561585a6c636e4e7064486b755a586868625842735a53397063334e315a584a7a4c7a55324e5441304f534973496e5a6862476c6b526e4a7662534936496a49774d5441744d4445744d4446554d546b364d6a4d364d6a52614969776959334a6c5a47567564476c6862464e6a614756745953493665794a66633251694f6c73694e574a4265444d746548426d5157785653305a4a4f584e754d326857513231775232747263556c7a576d4d7a4c5578694d7a4e6d576d706961794973496c706a51585a494d44687364454a795355706d535768304f46397453314266597a4e73634735594d574e48636c6c74564738775a316c436554676958583073496d4e795a57526c626e52705957785464574a715a574e30496a7037496d526c5a334a6c5a53493665794a755957316c496a6f69516d466a61475673623349676232596755324e705a57356a5a534268626d516751584a3063794973496c397a5a43493657794a53543151334d556c3064544e4d4e6c5658574656716279316f5756644a516a593362485650546b5645556c4e436147784556454e7856553952496c31394c434a66633251694f6c7369545556755a584e6e4d6c6850556b356a59334e4354575661587a45324d444a6e655451775569303057554a32566c4977654645346230593459794a646653776958334e6b496a7062496b566c63324a696179316d63475a7764325a4d4f58644f637a4678636a5a30615534335a6e45745358517a57564d3256335a43626c3969574738694c434a61623149315a475268636b64745a6b31354e45687556307856616b3555526e4655526a4e59526a5a706446426e5a6e6c47516b685658334656496c31392e677733706178626b4c6a706938435473795270584b6243377470566130713273574b53442d5f646362755a314c705a56336f513849667a636d32624538525933666d4a6762757941396762504c3373514261547a6b67207e57794a5365555178566c423456484276626d745065585a70637a6b746132393349697767496d6c6b49697767496d6830644841364c793931626d6c325a584a7a615852354c6d5634595731776247557659334a6c5a47567564476c6862484d764d5467334d694a647e57794a66566a643165546433617931524d33565a64325a705a304e765755564249697767496e5235634755694c434262496c5a6c636d6c6d6157466962475644636d566b5a5735306157467349697767496b5634595731776247564262485674626d6c44636d566b5a57353061574673496c31647e57794a68617a64714d546c6e59564d7452444a4c5832687a593352565a474e5249697767496d6c6b49697767496d68306448427a4f6938765a586868625842735a533576636d63765a586868625842735a584d765a47566e636d566c4c6d707a6232346958517e57794a55546a42586158565a526b6858576b56325a445a4951554a485153316e49697767496e5235634755694c434169536e4e76626c4e6a6147567459534a647e57794a564d6e427a4d6b785956455256625668334d4463785256426d5255706e49697767496d6c6b49697767496d52705a44706c654746746347786c4f6a45794d794a647e57794a735130343265544e4561544e44556b395658334a75587a52454e57526e49697767496e5235634755694c434169516d466a6147567362334a455a5764795a57556958517e3b646174613a6170706c69636174696f6e2f76632b73642d6a77742c65794a72615751694f694a4665456872516b31584f575a74596d7432566a49324e6d3153634856514d6e4e565756394f583056585355347862474677565870504f484a76496977695957786e496a6f6952564d794e54596966512e65794a66633252665957786e496a6f69633268684c5449314e694973496b426a623235305a586830496a7062496d68306448427a4f693876643364334c6e637a4c6d39795a7939756379396a636d566b5a57353061574673637939324d694973496d68306448427a4f693876643364334c6e637a4c6d39795a7939756379396a636d566b5a573530615746736379396c654746746347786c637939324d694a644c434a7063334e315a5849694f694a6f64485277637a6f764c33567561585a6c636e4e7064486b755a586868625842735a53397063334e315a584a7a4c7a55324e5441304f534973496e5a6862476c6b526e4a7662534936496a49774d5441744d4445744d4446554d546b364d6a4d364d6a52614969776959334a6c5a47567564476c6862464e6a614756745953493665794a66633251694f6c73694e574a4265444d746548426d5157785653305a4a4f584e754d326857513231775232747263556c7a576d4d7a4c5578694d7a4e6d576d706961794973496c706a51585a494d44687364454a795355706d535768304f46397453314266597a4e73634735594d574e48636c6c74564738775a316c436554676958583073496d4e795a57526c626e52705957785464574a715a574e30496a7037496d526c5a334a6c5a53493665794a755957316c496a6f69516d466a61475673623349676232596755324e705a57356a5a534268626d516751584a3063794973496c397a5a43493657794a53543151334d556c3064544e4d4e6c5658574656716279316f5756644a516a593362485650546b5645556c4e436147784556454e7856553952496c31394c434a66633251694f6c7369545556755a584e6e4d6c6850556b356a59334e4354575661587a45324d444a6e655451775569303057554a32566c4977654645346230593459794a646653776958334e6b496a7062496b566c63324a696179316d63475a7764325a4d4f58644f637a4678636a5a30615534335a6e45745358517a57564d3256335a43626c3969574738694c434a61623149315a475268636b64745a6b31354e45687556307856616b3555526e4655526a4e59526a5a706446426e5a6e6c47516b685658334656496c31392e677733706178626b4c6a706938435473795270584b6243377470566130713273574b53442d5f646362755a314c705a56336f513849667a636d32624538525933666d4a6762757941396762504c3373514261547a6b67207e57794a5365555178566c423456484276626d745065585a70637a6b746132393349697767496d6c6b49697767496d6830644841364c793931626d6c325a584a7a615852354c6d5634595731776247557659334a6c5a47567564476c6862484d764d5467334d694a647e57794a66566a643165546433617931524d33565a64325a705a304e765755564249697767496e5235634755694c434262496c5a6c636d6c6d6157466962475644636d566b5a5735306157467349697767496b5634595731776247564262485674626d6c44636d566b5a57353061574673496c31647e57794a68617a64714d546c6e59564d7452444a4c5832687a593352565a474e5249697767496d6c6b49697767496d68306448427a4f6938765a586868625842735a533576636d63765a586868625842735a584d765a47566e636d566c4c6d707a6232346958517e57794a55546a42586158565a526b6858576b56325a445a4951554a485153316e49697767496e5235634755694c434169536e4e76626c4e6a6147567459534a647e57794a564d6e427a4d6b785956455256625668334d4463785256426d5255706e49697767496d6c6b49697767496d52705a44706c654746746347786c4f6a45794d794a647e57794a735130343265544e4561544e44556b395658334a75587a52454e57526e49697767496e5235634755694c434169516d466a6147567362334a455a5764795a57556958517e222c2274797065223a22456e76656c6f70656456657269666961626c6543726564656e7469616c227d5d7d584048aa5ace527e2bf29b91fb5e84434f101d848b930c0ff9e5ddd82c9b3e2087351b47d26ce2f4c8a0101b2586c9e33d9f830d100a77be54c15a8cb1a51ad68291

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#enveloped-verifiable-credentials) for more details regarding this example.

[Example 9](#example-a-simple-example-of-a-verifiable-presentation-secured-with-cose-using-the-envelopedverifiablepresentation-type): A simple example of a verifiable presentation secured with COSE using the EnvelopedVerifiablePresentation type

-   Presentation
-   cose

``` {.nohighlight .vc vc-tabs="cose"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "type": "EnvelopedVerifiablePresentation",
  "id": "data:application/vp+sd-jwt,eyJhbGciOiJFUzM4NCIsImtpZCI6IlVRTV9fblE0UzZCTzhuUTRuT05YeHB4aHRob3lOeGI1M0xZZ1l6LTJBQnMiLCJ0eXAiOiJ2cCtsZCtqc29uK3NkLWp3dCIsImN0eSI6InZwK2xkK2pzb24ifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidmVyaWZpYWJsZUNyZWRlbnRpYWwiOlt7IkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFN1YmplY3QiOnsiYWx1bW5pT2YiOnsibmFtZSI6IkV4YW1wbGUgVW5pdmVyc2l0eSIsIl9zZCI6WyJoek9LRzU2cDI5c1ByTGFDNUE4RndFdUczVU05dUlZU1p1cU9YczJlVGJBIl19LCJfc2QiOlsiWVdXVmVDRndxQmk4WDBqSF9jV0NWWU16STNhOHBjTEVYRWZicFNSQVlndyJdfSwiX3NkIjpbIjJJZjhhaUs4REZwVWJ4dEc1cGMwel9SaFJzbm1ybGFRMEhzcTk4WFNyYWsiLCJUeDZ4ZWZMVUdUZUpfYWtVUFdGeHNvbUhobGtWVnpfNzVoaVZ6eWpyYmVzIl19XSwiX3NkIjpbIjd2anl0VVN3ZEJ0MXQ5RktlOVFfS3JIRXhFWGxrTEFaTzBKM0Jpd200dlkiXSwiX3NkX2FsZyI6InNoYS0yNTYiLCJpYXQiOjE3MDY1NjI4NDksImV4cCI6MTczODE4NTI0OSwiY25mIjp7Imp3ayI6eyJrdHkiOiJFQyIsImNydiI6IlAtMzg0IiwiYWxnIjoiRVMzODQiLCJ4IjoidWtEd1U2ZzlQUVRFUWhYaEgyckRZNndMQlg3UHFlUjZBcGlhVHBEUXowcl8tdDl6UXNxem54Z0hEcE5oekZlQyIsInkiOiJMQnhVYnBVdFNGMVVKVTVpYnJIdkpINjBUSG5YMk1xa0xHZGltU1l0UGR4RlkxOEdhcldiS3FZV0djUkZHVE9BIn19fQ.kYD63YtBNYnLUTw6Szf1vs_Ug3UBXhPwCyqpNmPnPDa3rXZQhQLdB1BgaoO8zgQ-c3B41fxaXMnLHYV9-B20uboSpJP0B-2Vre917eQt1cSDswDGA_Ytvn4BSqYVBB2J~WyJFMkFsRzhsY2p0QVFrcllIbjlIbnVRIiwgInR5cGUiLCAiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd~WyI5NldYMDRneno4cVZzOVZLU2wwYTVnIiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~WyJaekU2VFVaamtHMW1DWXBKMEhnc0l3IiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyItQ3NsS25GZGFYb2JiQWsyU0JBVGR3IiwgImlkIiwgImRpZDpleGFtcGxlOmViZmViMWY3MTJlYmM2ZjFjMjc2ZTEyZWMyMSJd~WyJuRm1OWl9IczB3WWNoOFdkeTdnQUNRIiwgImlkIiwgImRpZDpleGFtcGxlOmMyNzZlMTJlYzIxZWJmZWIxZjcxMmViYzZmMSJd~"
}
```

**application/vp**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "type": "EnvelopedVerifiablePresentation",
      "id": "data:application/vp+sd-jwt,eyJhbGciOiJFUzM4NCIsImtpZCI6IlVRTV9fblE0UzZCTzhuUTRuT05YeHB4aHRob3lOeGI1M0xZZ1l6LTJBQnMiLCJ0eXAiOiJ2cCtsZCtqc29uK3NkLWp3dCIsImN0eSI6InZwK2xkK2pzb24ifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidmVyaWZpYWJsZUNyZWRlbnRpYWwiOlt7IkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJpc3N1ZXIiOiJodHRwczovL3VuaXZlcnNpdHkuZXhhbXBsZS9pc3N1ZXJzLzU2NTA0OSIsInZhbGlkRnJvbSI6IjIwMTAtMDEtMDFUMTk6MjM6MjRaIiwiY3JlZGVudGlhbFN1YmplY3QiOnsiYWx1bW5pT2YiOnsibmFtZSI6IkV4YW1wbGUgVW5pdmVyc2l0eSIsIl9zZCI6WyJoek9LRzU2cDI5c1ByTGFDNUE4RndFdUczVU05dUlZU1p1cU9YczJlVGJBIl19LCJfc2QiOlsiWVdXVmVDRndxQmk4WDBqSF9jV0NWWU16STNhOHBjTEVYRWZicFNSQVlndyJdfSwiX3NkIjpbIjJJZjhhaUs4REZwVWJ4dEc1cGMwel9SaFJzbm1ybGFRMEhzcTk4WFNyYWsiLCJUeDZ4ZWZMVUdUZUpfYWtVUFdGeHNvbUhobGtWVnpfNzVoaVZ6eWpyYmVzIl19XSwiX3NkIjpbIjd2anl0VVN3ZEJ0MXQ5RktlOVFfS3JIRXhFWGxrTEFaTzBKM0Jpd200dlkiXSwiX3NkX2FsZyI6InNoYS0yNTYiLCJpYXQiOjE3MDY1NjI4NDksImV4cCI6MTczODE4NTI0OSwiY25mIjp7Imp3ayI6eyJrdHkiOiJFQyIsImNydiI6IlAtMzg0IiwiYWxnIjoiRVMzODQiLCJ4IjoidWtEd1U2ZzlQUVRFUWhYaEgyckRZNndMQlg3UHFlUjZBcGlhVHBEUXowcl8tdDl6UXNxem54Z0hEcE5oekZlQyIsInkiOiJMQnhVYnBVdFNGMVVKVTVpYnJIdkpINjBUSG5YMk1xa0xHZGltU1l0UGR4RlkxOEdhcldiS3FZV0djUkZHVE9BIn19fQ.kYD63YtBNYnLUTw6Szf1vs_Ug3UBXhPwCyqpNmPnPDa3rXZQhQLdB1BgaoO8zgQ-c3B41fxaXMnLHYV9-B20uboSpJP0B-2Vre917eQt1cSDswDGA_Ytvn4BSqYVBB2J~WyJFMkFsRzhsY2p0QVFrcllIbjlIbnVRIiwgInR5cGUiLCAiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd~WyI5NldYMDRneno4cVZzOVZLU2wwYTVnIiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~WyJaekU2VFVaamtHMW1DWXBKMEhnc0l3IiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyItQ3NsS25GZGFYb2JiQWsyU0JBVGR3IiwgImlkIiwgImRpZDpleGFtcGxlOmViZmViMWY3MTJlYmM2ZjFjMjc2ZTEyZWMyMSJd~WyJuRm1OWl9IczB3WWNoOFdkeTdnQUNRIiwgImlkIiwgImRpZDpleGFtcGxlOmMyNzZlMTJlYzIxZWJmZWIxZjcxMmViYzZmMSJd~"
    }

**application/vp+cose**

d28444a1013822a05908837b2240636f6e74657874223a5b2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f6578616d706c65732f7632225d2c2274797065223a22456e76656c6f70656456657269666961626c6550726573656e746174696f6e222c226964223a22646174613a6170706c69636174696f6e2f76702b73642d6a77742c65794a68624763694f694a46557a4d344e434973496d74705a434936496c565254563966626c4530557a5a43547a68755554527554303559654842346148526f62336c4f654749314d30785a5a316c364c544a42516e4d694c434a30655841694f694a32634374735a437471633239754b334e6b4c57703364434973496d4e3065534936496e5a774b32786b4b32707a6232346966512e65794a4159323975644756346443493657794a6f64485277637a6f764c336433647935334d793576636d6376626e4d7659334a6c5a47567564476c6862484d76646a49694c434a6f64485277637a6f764c336433647935334d793576636d6376626e4d7659334a6c5a47567564476c6862484d765a586868625842735a584d76646a496958537769646d567961575a7059574a735a554e795a57526c626e5270595777694f6c7437496b426a623235305a586830496a7062496d68306448427a4f693876643364334c6e637a4c6d39795a7939756379396a636d566b5a57353061574673637939324d694973496d68306448427a4f693876643364334c6e637a4c6d39795a7939756379396a636d566b5a573530615746736379396c654746746347786c637939324d694a644c434a7063334e315a5849694f694a6f64485277637a6f764c33567561585a6c636e4e7064486b755a586868625842735a53397063334e315a584a7a4c7a55324e5441304f534973496e5a6862476c6b526e4a7662534936496a49774d5441744d4445744d4446554d546b364d6a4d364d6a52614969776959334a6c5a47567564476c6862464e31596d706c593351694f6e73695957783162573570543259694f6e7369626d46745a534936496b5634595731776247556756573570646d567963326c3065534973496c397a5a43493657794a6f656b394c527a55326344493563314279544746444e554534526e64466455637a5655303564556c5a5531703163553959637a4a6c56474a42496c31394c434a66633251694f6c736957566458566d5644526e6478516d6b34574442715346396a56304e575755313653544e684f48426a5445565952575a6963464e5351566c6e64794a646653776958334e6b496a7062496a4a4a5a6a68686155733452455a7756574a346445633163474d77656c395361464a7a626d3179624746524d45687a63546b3457464e79595773694c434a5565445a345a575a4d565564555a557066595774565546644765484e766255686f62477457566e70664e7a566f61565a3665577079596d567a496c31395853776958334e6b496a7062496a6432616e6c3056564e335a454a304d585135526b746c4f56466653334a49525868465747787254454661547a424b4d304a7064323030646c6b695853776958334e6b583246735a794936496e4e6f595330794e5459694c434a70595851694f6a45334d4459314e6a49344e446b73496d5634634349364d54637a4f4445344e5449304f5377695932356d496a7037496d70336179493665794a7264486b694f694a4651794973496d4e7964694936496c41744d7a6730496977695957786e496a6f6952564d7a4f4451694c434a34496a6f6964577445643155325a7a6c51555652465557685961456779636b525a4e6e644d516c67335548466c556a5a4263476c685648424555586f77636c387464446c3655584e78656d35345a3068456345356f656b5a6c51794973496e6b694f694a4d516e6856596e425664464e474d56564b56545670596e4a49646b70494e6a4255534735594d6b3178613078485a476c7455316c3055475234526c6b784f456468636c64695333465a5630646a556b5a4856453942496e313966512e6b594436335974424e596e4c55547736537a663176735f556733554258685077437971704e6d506e5044613372585a5168514c6442314267616f4f387a67512d6333423431667861584d6e4c485956392d42323075626f53704a5030422d325672653931376551743163534473774447415f5974766e3442537159564242324a7e57794a464d6b4673527a68735932703051564672636c6c49626a6c49626e565249697767496e5235634755694c434169566d567961575a7059574a735a5642795a584e6c626e526864476c7662694a647e577949354e6c64594d44526e656e6f3463565a7a4f565a4c553277775954566e49697767496d6c6b49697767496d6830644841364c793931626d6c325a584a7a615852354c6d5634595731776247557659334a6c5a47567564476c6862484d764d5467334d694a647e57794a61656b553256465661616d74484d5731445758424b4d45686e63306c3349697767496e5235634755694c434262496c5a6c636d6c6d6157466962475644636d566b5a5735306157467349697767496b5634595731776247564262485674626d6c44636d566b5a57353061574673496c31647e5779497451334e73533235475a47465962324a695157737955304a425647523349697767496d6c6b49697767496d52705a44706c654746746347786c4f6d56695a6d56694d5759334d544a6c596d4d325a6a466a4d6a63325a5445795a574d794d534a647e57794a75526d314f576c3949637a423357574e6f4f46646b6554646e51554e5249697767496d6c6b49697767496d52705a44706c654746746347786c4f6d4d794e7a5a6c4d544a6c597a49785a574a6d5a5749785a6a63784d6d5669597a5a6d4d534a647e222c2276657269666961626c6543726564656e7469616c223a5b5d7d58403e5b7774a6fd70c0736455e8f28c3809df399f132d5e5e66f390c7632812bd718705cb2af5acdcfb9193f274814e9fd12a872d7fbf5c44a5a0cfed3f3ef798b4

See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#enveloped-verifiable-presentations) for more details regarding this example.

#### 3.3.3 COSE Header Parameters and CWT Claims

[](#cose-header-param-cwt-claims)

When present in the [COSE Header](https://www.rfc-editor.org/rfc/rfc9052#section-3.1) or as [CWT Claims](https://www.rfc-editor.org/rfc/rfc8392#section-3), members registered in the IANA [CBOR Web Token (CWT) Claims](https://www.iana.org/assignments/cwt/cwt.xhtml) registry or the IANA [COSE Header Parameters](https://www.iana.org/assignments/cose/cose.xhtml) registry are to be interpreted as defined by the specifications referenced in those registries. CBOR Web Token (CWT) \[[RFC8392](#bib-rfc8392 "CBOR Web Token (CWT)")\] Claims *MAY* be included in a COSE header parameter, as specified in [I-D.ietf-cose-cwt-claims-in-headers](https://www.ietf.org/archive/id/draft-ietf-cose-cwt-claims-in-headers-10.html).

The normative statements in [Header Parameters](https://www.rfc-editor.org/rfc/rfc9052#section-3.1), [Claims](https://www.rfc-editor.org/rfc/rfc8392#section-3), and [CBOR Web Token (CWT) Claims in COSE Headers](https://www.ietf.org/archive/id/draft-ietf-cose-cwt-claims-in-headers-10.html) apply to securing credentials and presentations.

It is *RECOMMENDED* to use the IANA [CBOR Web Token Claims](https://www.iana.org/assignments/cwt/cwt.xhtml) registry and the IANA [COSE Header Parameters](https://www.iana.org/assignments/cose/cose.xhtml) registry to identify any claims and header parameters that might be confused with members defined by \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\]. These include but are not limited to: `iss`, `kid`, `alg`, `iat`, `exp`, and `cnf`.

When the `iat` (Issued At) and/or `exp` (Expiration Time) CWT claims are present, they represent the issuance and expiration time of the signature, respectively. Note that these are different from the `validFrom` and `validUntil` properties defined in [Validity Period](https://www.w3.org/TR/vc-data-model-2.0/#validity-period), which represent the validity of the data that is being secured. Use of the `nbf` (Not Before) claim is *NOT RECOMMENDED*, as it makes little sense to attempt to assign a future date to a signature.

Additional members may be present as header parameters and claims. If they are not understood, they *MUST* be ignored.

## 4. Key Discovery

[](#key-discovery)

To complete the [verification](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verify) process, a [verifier](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifier) needs to obtain the cryptographic keys used to secure the [credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-credential).

There are several different ways to discover the verification keys of the [issuers](https://www.w3.org/TR/vc-data-model-2.0/#dfn-issuers) and [holders](https://www.w3.org/TR/vc-data-model-2.0/#dfn-holders).

### 4.1 Using Header Parameters and Claims for Key Discovery

[](#using-header-params-claims-key-discovery)

These JOSE header parameters and JWT claims can be used by [verifiers](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifier) to discover verification keys.

#### 4.1.1 kid

[](#kid)

If `kid` is present in the [JOSE Header](https://www.rfc-editor.org/rfc/rfc7515#section-4.1) or the [COSE Header](https://www.rfc-editor.org/rfc/rfc9052#section-3), a [verifier](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifier) can use this parameter as a hint indicating which key was used to secure the [verifiable credential](#dfn-verifiable-credentials), when performing a [verification](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verify) process as defined in [RFC7515](https://www.rfc-editor.org/rfc/rfc7515#section-4.1.4).

`kid` *MUST* be present when the key of the [issuer](https://www.w3.org/TR/vc-data-model-2.0/#dfn-issuers) or [subject](https://www.w3.org/TR/vc-data-model-2.0/#dfn-subjects) is expressed as a DID URL.

#### 4.1.2 iss

[](#iss)

If `iss` is present in the [JOSE Header](https://www.rfc-editor.org/rfc/rfc7515#section-4.1), the [JWT Claims](https://www.rfc-editor.org/rfc/rfc7519#section-4.1.1), or the [COSE Header](https://www.rfc-editor.org/rfc/rfc9052#section-3), a [verifier](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifier) can use this parameter to obtain a [JSON Web Key](https://www.rfc-editor.org/rfc/rfc7517#section-4) to use in the [verification](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verify) process.

The value of the [issuer](https://www.w3.org/TR/vc-data-model-2.0/#issuer) property can be either a string or an object. When `issuer` value is a string, `iss` value, if present, *MUST* match `issuer` value. When `issuer` value is an object with an `id` value, `iss` value, if present, *MUST* match `issuer.id` value.

If `kid` is also present in the [JOSE Header](https://www.rfc-editor.org/rfc/rfc7515#section-4.1), it is used to distinguish the specific key used.

#### 4.1.3 cnf

[](#cnf)

If `cnf` is present in the [JOSE Header](https://www.rfc-editor.org/rfc/rfc7515#section-4.1), the [JWT Claims](https://www.rfc-editor.org/rfc/rfc7519#section-4.1.1), or the [COSE Header](https://www.rfc-editor.org/rfc/rfc9052#section-3), a [verifier](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifier) *MAY* use this parameter to identify a proof-of-possession key in the manner described in \[[RFC7800](#bib-rfc7800 "Proof-of-Possession Key Semantics for JSON Web Tokens (JWTs)")\] or \[[RFC8747](#bib-rfc8747 "Proof-of-Possession Key Semantics for CBOR Web Tokens (CWTs)")\] for use in the [verification](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verify) process.

Use of a proof-of-possession key provided by the [Holder](https://www.w3.org/TR/vc-data-model-2.0/#dfn-holders) to the [Issuer](https://www.w3.org/TR/vc-data-model-2.0/#dfn-issuers) to establish a cryptographic binding to the [Holder](https://www.w3.org/TR/vc-data-model-2.0/#dfn-holders) in the [Verifiable Credential](#dfn-verifiable-credentials) that is verifiable by the [Verifier](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifier) in the [Verifiable Presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) is *RECOMMENDED*.

### 4.2 Using Controlled Identifier Documents

[](#using-controlled-identifier-documents)

When using [controlled identifier documents](#dfn-controlled-identifier-document) with this specification, the following requirements apply.

The value of the `type` property of the verification method *MUST* be `JsonWebKey`.

Verification material *MUST* be expressed in the `publicKeyJwk` property of a `JsonWebKey`. This key material is retrieved based on hints in the JOSE or COSE message envelopes, such as `kid` or `iss`. At the time of writing, there is no standard way to retrieve a public key in JWK format from a DID URL or [controlled identifier documents](#dfn-controlled-identifier-document).

When [iss](#iss) is absent, and the [issuer](https://www.w3.org/TR/vc-data-model-2.0/#dfn-issuers) is identified as a \[[URL](#bib-url "URL Standard")\], the [kid](#kid) *MUST* be an absolute \[[URL](#bib-url "URL Standard")\] to a verification method listed in a [controlled identifier documents](#dfn-controlled-identifier-document) or a [DID Document](https://www.w3.org/TR/did-core/#dfn-did-documents).

When using \[[URL](#bib-url "URL Standard")\] identifiers, the `kid` is *RECOMMENDED* to be an absolute \[[URL](#bib-url "URL Standard")\] that includes a JWK Thumbprint URI as defined in \[[RFC7638](#bib-rfc7638 "JSON Web Key (JWK) Thumbprint")\]. For example: `https://vendor.example/issuers/42/keys/urn:ietf:params:oauth:jwk-thumbprint:sha-256:NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs`

[Example 10](#example-an-issuer-identified-by-a-controlled-identifier-document-identifier): An issuer identified by a controlled identifier document identifier

``` {aria-busy="false"}
{
  "issuer": {
    "id": "https://university.example/issuers/565049"
  }
  // ...
}
```

[Example 11](#example-a-kid-as-a-controlled-identifier-document-verification-method-identifier): A kid as a controlled identifier document verification method identifier

``` {aria-busy="false"}
{
  "alg": "ES384",
  "kid": "https://university.example/issuers/565049#key-123
}
```

When the [holder](https://www.w3.org/TR/vc-data-model-2.0/#dfn-holders) is identified as a \[[URL](#bib-url "URL Standard")\], and [iss](#iss) is absent, the [kid](#kid) *MUST* be an absolute \[[URL](#bib-url "URL Standard")\] to a verification method listed in a [controlled identifier document](#dfn-controlled-identifier-document).

[Example 12](#example-a-holder-identified-by-a-controlled-identifier-document-identifier): A holder identified by a controlled identifier document identifier

``` {aria-busy="false"}
{
  "holder": {
    "id": "https://university.example/issuers/565049"
  }
  // ...
}
```

[Example 13](#example-a-kid-as-a-controlled-identifier-document-verification-method-identifier-0): A kid as a controlled identifier document verification method identifier

``` {aria-busy="false"}
{
  "alg": "ES384",
  "kid": "https://university.example/issuers/565049#key-123
}
```

## 5. Algorithms

[](#algorithms)

This specification might be used with many different key discovery protocols. Therefore, discovery of verification keys is described in [4. Key Discovery](#key_discovery), and is assumed to have succeeded prior to beginning the verification process.

As a general rule, verifiers *SHOULD* strive to minimize the processing of untrusted data. This includes minimizing any processing of the protected header, unprotected header, or payload as part of the key discovery procedures.

After verification has succeeded, additional validation checks *SHOULD* be performed as described in Section [5.4 Validation](#validation-algorithm)

The outputs for the following algorithms are:

-   `status`: a boolean indicating the result of verification, `true` for success and `false` for failure.
-   `document`: a document conforming to the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\]
-   `mediaType`: `vc` or `vp`

### 5.1 Verifying a Credential or Presentation Secured with JOSE

[](#alg-jose)

The inputs for this algorithm are:

-   `inputMediaType`: `vc+jwt` or `vp+jwt`
-   `inputDocument`: the verifiable credential secured as a JWT \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\]

Upon receipt of the verifiable credential or presentation secured as a JWT \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\], the holder or verifier follows this algorithm:

1.  Follow the algorithm defined in [Validating a JWT](https://www.rfc-editor.org/rfc/rfc7519#section-7.2) \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\].
2.  If processing completes successfully:
    1.  Set `status` to `true`
    2.  Set `mediaType` to `vc` or `vp`
    3.  Set `document` to the decoded JWS payload.
    4.  Return
3.  If processing aborts for any reason or the JWT is rejected:
    1.  Set `status` to `false`
    2.  Set `document` to `null`
    3.  Set `mediaType` to `null`
    4.  Return

### 5.2 Verifying a Credential or Presentation Secured with SD-JWT

[](#alg-sd-jwt)

The inputs for this algorithm are:

-   `inputMediaType`: `vc+sd-jwt`
-   `inputDocument`: the verifiable credential secured with \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\]

Upon receipt of the verifiable credential or presentation secured with \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\], the holder or verifier follows this algorithm:

1.  Follow the algorithms defined in [SD-JWT](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt#section-8) for verification of the SD-JWT.
2.  If processing completes successfully:
    1.  Set `status` to `true`
    2.  Set `mediaType` to `vc`
    3.  Convert the SD-JWT payload back into the JWT Claims Set by reversing the process in \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\]. Set `document` to the JWT Claims Set. (For examples of the transition from JWT Claims Set to SD-JWT payload, please see [SD-JWT examples](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt#appendix-A)).
    4.  Return
3.  If processing aborts for any reason or the SD-JWT is rejected:
    1.  Set `status` to `false`
    2.  Set `document` to `null`
    3.  Set `mediaType` to `null`
    4.  Return

### 5.3 Verifying a Credential or Presentation Secured with COSE

[](#alg-cose)

The inputs for this algorithm are:

-   `inputMediaType`: `vc+cose` or `vp+cose`
-   `inputDocument`: the [verifiable credential](#dfn-verifiable-credentials) or [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation) secured with [CBOR Object Signing and Encryption (COSE): Structures and Process](https://www.rfc-editor.org/rfc/rfc9052)

Upon receipt of the verifiable credential or presentation secured with \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\], the holder or verifier follows this algorithm:

1.  Follow the algorithm defined in [CBOR Object Signing and Encryption (COSE): Structures and Process](https://www.rfc-editor.org/rfc/rfc9052) \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] under the Signing and Verification Process for COSE_Sign1.
2.  If processing completes successfully:
    1.  Set `status` to `true`
    2.  Set `mediaType` to `vc` or `vp`
    3.  Set `document` to the decoded COSE_Sign1 payload.
    4.  Return
3.  If processing aborts for any reason:
    1.  Set `status` to `false`
    2.  Set `document` to `null`
    3.  Set `mediaType` to `null`
    4.  Return

### 5.4 Validation

[](#validation-algorithm)

All claims expected for the `typ` *MUST* be present. All claims that are understood *MUST* be evaluated according the verifier\'s validation policies. All claims that are not understood *MUST* be ignored.

The verified `document` returned from verification *MUST* be a well-formed compact JSON-LD document, as described in [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#conformance).

Schema extension mechanisms such as `credentialSchema` *SHOULD* be checked. If the extension mechanism `type` is not understood, this property *MUST* be ignored.

Status extension mechanisms such as `credentialStatus` *SHOULD* be checked. If the extension mechanism `type` is not understood, this property *MUST* be ignored.

Based on the validation policy of the verifier, the type of credentials, and the type of securing mechanism, additional validation checks *MAY* be applied. For example, dependencies between multiple credentials, ordering or timing information associated with multiple credentials, and/or multiple presentations could cause an otherwise valid credential or presentation to be considered invalid.

## 6. IANA Considerations

[](#iana-considerations)

*This section is non-normative.*

### 6.1 Media Types

[](#media-types)

#### 6.1.1 `application/vc+jwt`

[](#vc-json-jwt)

This specification registers the `application/vc+jwt` Media Type specifically for identifying a [JSON Web Token (JWT)](https://www.rfc-editor.org/rfc/rfc7519) with a payload conforming to the [Verifiable Credential Data Model](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-credentials).

+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Type name:                             | `application`                                                                                                                                                                                                                                                                                                                                                            |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Subtype name:                          | `vc+jwt`                                                                                                                                                                                                                                                                                                                                                                 |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Required parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Optional parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Encoding considerations:               | binary; `application/jwt` values are a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\').                                                                                                                                                                                                                           |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Security considerations:               | As defined in [this specification](#security-considerations). See also the security considerations in \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\].                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Interoperability considerations:       | As defined in [this specification](#conformance).                                                                                                                                                                                                                                                                                                                        |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Published specification:               | <https://www.w3.org/TR/vc-jose-cose>                                                                                                                                                                                                                                                                                                                                     |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Applications that will use this media: | W3C Verifiable Credential issuer, holder, and verifier software, conforming to the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], are among the applications that will use the media types. Conforming application types are described [here](#conformance) and [here](https://www.w3.org/TR/vc-data-model-2.0/#conformance). |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Restrictions on usage:                 | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Additional information:                | 1.  Deprecated alias names for this type: N/A                                                                                                                                                                                                                                                                                                                            |
|                                        | 2.  Magic number(s): N/A                                                                                                                                                                                                                                                                                                                                                 |
|                                        | 3.  File extension(s): N/A                                                                                                                                                                                                                                                                                                                                               |
|                                        | 4.  Macintosh file type code: N/A                                                                                                                                                                                                                                                                                                                                        |
|                                        | 5.  Object Identifiers: N/A                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Author:                                | Ivan Herman <ivan@w3.org>                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Intended usage:                        | COMMON                                                                                                                                                                                                                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Change controller:                     | W3C Verifiable Credentials Working Group <public-vc-wg@w3.org>                                                                                                                                                                                                                                                                                                           |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+

#### 6.1.2 `application/vp+jwt`

[](#vp-json-jwt)

This specification registers the `application/vp+jwt` Media Type specifically for identifying a [JSON Web Token (JWT)](https://www.rfc-editor.org/rfc/rfc7519) with a payload conforming to the [Verifiable Presentations definition in the Verifiable Credential Data Model](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations).

+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Type name:                             | application                                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Subtype name:                          | vp+jwt                                                                                                                                                                                                                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Required parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Optional parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Encoding considerations:               | binary; `application/jwt` values are a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\').                                                                                                                                                                                                                           |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Security considerations:               | As defined in [this specification](#security-considerations). See also the security considerations in \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\].                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Interoperability considerations:       | As defined in [this specification](#conformance).                                                                                                                                                                                                                                                                                                                        |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Published specification:               | <https://www.w3.org/TR/vc-jose-cose>                                                                                                                                                                                                                                                                                                                                     |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Applications that will use this media: | W3C Verifiable Credential issuer, holder, and verifier software, conforming to the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], are among the applications that will use the media types. Conforming application types are described [here](#conformance) and [here](https://www.w3.org/TR/vc-data-model-2.0/#conformance). |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Restrictions on usage:                 | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Additional information:                | 1.  Deprecated alias names for this type: N/A                                                                                                                                                                                                                                                                                                                            |
|                                        | 2.  Magic number(s): N/A                                                                                                                                                                                                                                                                                                                                                 |
|                                        | 3.  File extension(s): N/A                                                                                                                                                                                                                                                                                                                                               |
|                                        | 4.  Macintosh file type code: N/A                                                                                                                                                                                                                                                                                                                                        |
|                                        | 5.  Object Identifiers: N/A                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Author:                                | Ivan Herman <ivan@w3.org>                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Intended usage:                        | COMMON                                                                                                                                                                                                                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Change controller:                     | W3C Verifiable Credentials Working Group <public-vc-wg@w3.org>                                                                                                                                                                                                                                                                                                           |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+

#### 6.1.3 `application/vc+sd-jwt`

[](#vc-json-sd-jwt)

This specification registers the `application/vc+sd-jwt` Media Type specifically for identifying a [Selective Disclosure for JWTs (SD-JWT)](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt) with a payload conforming to the [Verifiable Credential Data Model](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-credentials).

+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Type name:                             | `application`                                                                                                                                                                                                                                                                                                                                                            |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Subtype name:                          | `vc+sd-jwt`                                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Required parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Optional parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Encoding considerations:               | binary; `application/sd-jwt` values are a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') and tilde (\'\~\') characters.                                                                                                                                                                                          |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Security considerations:               | As defined in [this specification](#security-considerations). See also the security considerations in [Selective Disclosure for JWTs (SD-JWT)](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt).                                                                                                                                         |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Interoperability considerations:       | As defined in [this specification](#conformance).                                                                                                                                                                                                                                                                                                                        |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Published specification:               | <https://www.w3.org/TR/vc-jose-cose>                                                                                                                                                                                                                                                                                                                                     |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Applications that will use this media: | W3C Verifiable Credential issuer, holder, and verifier software, conforming to the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], are among the applications that will use the media types. Conforming application types are described [here](#conformance) and [here](https://www.w3.org/TR/vc-data-model-2.0/#conformance). |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Restrictions on usage:                 | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Additional information:                | 1.  Deprecated alias names for this type: N/A                                                                                                                                                                                                                                                                                                                            |
|                                        | 2.  Magic number(s): N/A                                                                                                                                                                                                                                                                                                                                                 |
|                                        | 3.  File extension(s): N/A                                                                                                                                                                                                                                                                                                                                               |
|                                        | 4.  Macintosh file type code: N/A                                                                                                                                                                                                                                                                                                                                        |
|                                        | 5.  Object Identifiers: N/A                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Author:                                | Ivan Herman <ivan@w3.org>                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Intended usage:                        | COMMON                                                                                                                                                                                                                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Change controller:                     | W3C Verifiable Credentials Working Group <public-vc-wg@w3.org>                                                                                                                                                                                                                                                                                                           |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+

#### 6.1.4 `application/vp+sd-jwt`

[](#vp-json-sd-jwt)

This specification registers the `application/vp+sd-jwt` Media Type specifically for identifying a [Selective Disclosure for JWTs (SD-JWT)](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt) with a payload conforming to the [Verifiable Presentations definition in the Verifiable Credential Data Model](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations).

+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Type name:                             | application                                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Subtype name:                          | vp+sd-jwt                                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Required parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Optional parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Encoding considerations:               | binary; `application/sd-jwt` values are a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') and tilde (\'\~\') characters.                                                                                                                                                                                          |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Security considerations:               | As defined in [this specification](#security-considerations). See also the security considerations in [Selective Disclosure for JWTs (SD-JWT)](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt).                                                                                                                                         |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Interoperability considerations:       | As defined in [this specification](#conformance).                                                                                                                                                                                                                                                                                                                        |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Published specification:               | <https://www.w3.org/TR/vc-jose-cose>                                                                                                                                                                                                                                                                                                                                     |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Applications that will use this media: | W3C Verifiable Credential issuer, holder, and verifier software, conforming to the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], are among the applications that will use the media types. Conforming application types are described [here](#conformance) and [here](https://www.w3.org/TR/vc-data-model-2.0/#conformance). |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Restrictions on usage:                 | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Additional information:                | 1.  Deprecated alias names for this type: N/A                                                                                                                                                                                                                                                                                                                            |
|                                        | 2.  Magic number(s): N/A                                                                                                                                                                                                                                                                                                                                                 |
|                                        | 3.  File extension(s): N/A                                                                                                                                                                                                                                                                                                                                               |
|                                        | 4.  Macintosh file type code: N/A                                                                                                                                                                                                                                                                                                                                        |
|                                        | 5.  Object Identifiers: N/A                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Author:                                | Ivan Herman <ivan@w3.org>                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Intended usage:                        | COMMON                                                                                                                                                                                                                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Change controller:                     | W3C Verifiable Credentials Working Group <public-vc-wg@w3.org>                                                                                                                                                                                                                                                                                                           |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+

#### 6.1.5 `application/vc+cose`

[](#vc-json-cose)

This specification registers the `application/vc+cose` Media Type specifically for identifying a COSE object \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] with a payload conforming to the [Verifiable Credential Data Model](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-credentials).

+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Type name:                             | `application`                                                                                                                                                                                                                                                                                                                                                            |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Subtype name:                          | `vc+cose`                                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Required parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Optional parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Encoding considerations:               | binary (CBOR)                                                                                                                                                                                                                                                                                                                                                            |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Security considerations:               | As defined in [this specification](#security-considerations). See also the security considerations in \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\].                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Interoperability considerations:       | As defined in [this specification](#conformance).                                                                                                                                                                                                                                                                                                                        |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Published specification:               | <https://www.w3.org/TR/vc-jose-cose>                                                                                                                                                                                                                                                                                                                                     |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Applications that will use this media: | W3C Verifiable Credential issuer, holder, and verifier software, conforming to the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], are among the applications that will use the media types. Conforming application types are described [here](#conformance) and [here](https://www.w3.org/TR/vc-data-model-2.0/#conformance). |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Restrictions on usage:                 | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Additional information:                | 1.  Deprecated alias names for this type: N/A                                                                                                                                                                                                                                                                                                                            |
|                                        | 2.  Magic number(s): N/A                                                                                                                                                                                                                                                                                                                                                 |
|                                        | 3.  File extension(s): N/A                                                                                                                                                                                                                                                                                                                                               |
|                                        | 4.  Macintosh file type code: N/A                                                                                                                                                                                                                                                                                                                                        |
|                                        | 5.  Object Identifiers: N/A                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Author:                                | Ivan Herman <ivan@w3.org>                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Intended usage:                        | COMMON                                                                                                                                                                                                                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Change controller:                     | W3C Verifiable Credentials Working Group <public-vc-wg@w3.org>                                                                                                                                                                                                                                                                                                           |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+

#### 6.1.6 `application/vp+cose`

[](#vp-json-cose)

This specification registers the `application/vp+cose` Media Type specifically for identifying a COSE object \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\] with a payload conforming to the [Verifiable Presentations definition in the Verifiable Credential Data Model](https://www.w3.org/TR/vc-data-model-2.0/#verifiable-presentations).

+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Type name:                             | `application`                                                                                                                                                                                                                                                                                                                                                            |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Subtype name:                          | `vp+cose`                                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Required parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Optional parameters:                   | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Encoding considerations:               | binary (CBOR)                                                                                                                                                                                                                                                                                                                                                            |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Security considerations:               | As defined in [this specification](#security-considerations). See also the security considerations in \[[RFC9052](#bib-rfc9052 "CBOR Object Signing and Encryption (COSE): Structures and Process")\].                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Interoperability considerations:       | As defined in [this specification](#conformance).                                                                                                                                                                                                                                                                                                                        |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Published specification:               | <https://www.w3.org/TR/vc-jose-cose>                                                                                                                                                                                                                                                                                                                                     |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Applications that will use this media: | W3C Verifiable Credential issuer, holder, and verifier software, conforming to the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], are among the applications that will use the media types. Conforming application types are described [here](#conformance) and [here](https://www.w3.org/TR/vc-data-model-2.0/#conformance). |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Restrictions on usage:                 | N/A                                                                                                                                                                                                                                                                                                                                                                      |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Additional information:                | 1.  Deprecated alias names for this type: N/A                                                                                                                                                                                                                                                                                                                            |
|                                        | 2.  Magic number(s): N/A                                                                                                                                                                                                                                                                                                                                                 |
|                                        | 3.  File extension(s): N/A                                                                                                                                                                                                                                                                                                                                               |
|                                        | 4.  Macintosh file type code: N/A                                                                                                                                                                                                                                                                                                                                        |
|                                        | 5.  Object Identifiers: N/A                                                                                                                                                                                                                                                                                                                                              |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Author:                                | Ivan Herman <ivan@w3.org>                                                                                                                                                                                                                                                                                                                                                |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Intended usage:                        | COMMON                                                                                                                                                                                                                                                                                                                                                                   |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Change controller:                     | W3C Verifiable Credentials Working Group <public-vc-wg@w3.org>                                                                                                                                                                                                                                                                                                           |
+----------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+

## 7. Other Considerations

[](#other-considerations)

*This section is non-normative.*

### 7.1 Privacy Considerations

[](#privacy-considerations)

Verifiable Credentials often contain sensitive information that needs to be protected to ensure the privacy and security of organizations and individuals. This section outlines some privacy considerations relevant to implementers and users.

Implementers are advised to note and abide by all privacy considerations called out in \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

Implementers are additionally advised to reference the [Privacy Consideration](https://www.rfc-editor.org/rfc/rfc7519#section-12) section of the JWT specification and NIST Special Publication 800-122 \[\[SP-800-122\] \"Guide to Protecting the Confidentiality of Personally Identifiable Information (PII)\" for privacy guidance.

In addition to the privacy recommendations in the \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], the following considerations are given:

-   Minimization of data: It is considered best practice for Verifiable Credentials to only contain the minimum amount of data necessary to achieve their intended purpose. This helps to limit the amount of sensitive information that is shared or stored unnecessarily.

-   Informed consent: It is considered best practice that individuals be fully informed about how their data will be used and provide the ability to consent to or decline the use of their data. This helps to ensure that individuals maintain control over their own personal information.

-   Data protection: It is considered best practice to protect Verifiable Credentials using strong encryption and other security measures to prevent unauthorized access, modification, or disclosure.

These considerations are not exhaustive, and implementers and users are advised to consult additional privacy resources and best practices to ensure the privacy and security of Verifiable Credentials implemented using this specification.

### 7.2 Security Considerations

[](#security-considerations)

This section outlines security considerations for implementers and users of this specification. It is important to carefully consider these factors to ensure the security and integrity of Verifiable Credentials when implemented using JOSE or COSE.

When implementing this specification, it is essential to address all security issues relevant to broad cryptographic applications. This especially includes protecting the user\'s asymmetric private and symmetric secret keys, as well as employing countermeasures against various attacks. Failure to adequately address these issues could compromise the security and integrity of Verifiable Credentials, potentially leading to unauthorized access, modification, or disclosure of sensitive information.

Implementers are advised to follow best practices and established cryptographic standards to ensure the secure handling of keys and other sensitive data. Additionally, conduct regular security assessments and audits to identify and address any vulnerabilities or threats.

Follow all security considerations outlined in \[[RFC7515](#bib-rfc7515 "JSON Web Signature (JWS)")\] and \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\].

When utilizing JSON-LD, take special care around remote retrieval of contexts and follow the additional security considerations noted in \[[JSON-LD11](#bib-json-ld11 "JSON-LD 1.1")\].

As noted in \[[RFC7515](#bib-rfc7515 "JSON Web Signature (JWS)")\] when utilizing JSON \[[RFC7159](#bib-rfc7159 "The JavaScript Object Notation (JSON) Data Interchange Format")\], strict validation is a security requirement. If malformed JSON is received, it may be impossible to reliably interpret the producer\'s intent, potentially leading to ambiguous or exploitable situations. To prevent these risks, it is essential to use a JSON parser that strictly validates the syntax of all input data. It is essential that any JSON inputs that do not conform to the JSON-text syntax defined in \[[RFC7159](#bib-rfc7159 "The JavaScript Object Notation (JSON) Data Interchange Format")\] be rejected in their entirety by JSON parsers. Failure to reject invalid input could compromise the security and integrity of Verifiable Credentials.

### 7.3 Accessibility

[](#accessibility)

When implementing this specification, it is crucial for technical implementers to consider various accessibility factors. Ignoring accessibility concerns renders the information unusable for a significant portion of the population. To ensure equal access for all individuals, regardless of their abilities, it is vital to adhere to accessibility guidelines and standards, such as the Web Content Accessibility Guidelines (WCAG 2.1) \[[WCAG21](#bib-wcag21 "Web Content Accessibility Guidelines (WCAG) 2.1")\]. This becomes even more critical when establishing systems that involve cryptography, as they have historically posed challenges for assistive technologies.

Implementers are advised to note and abide by all accessibility considerations called out in \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\].

## 8. Examples

[](#examples)

*This section is non-normative.*

### 8.1 Controllers

[](#controllers)

[Example 14](#example-a-minimal-controlled-identifier-document): A minimal controlled identifier document

``` {aria-busy="false"}
{
  "id": "https://vendor.example",
}
```

[Example 15](#example-a-controlled-identifier-document-with-verification-method): A controlled identifier document with verification method

``` {aria-busy="false"}
{
  "id": "https://university.example/issuers/565049",
  "verificationMethod": [{
    "id": "https://university.example/issuers/565049#key-123",
    "type": "JsonWebKey",
    "controller": "https://university.example/issuers/565049",
    "publicKeyJwk": {
      "kty": "EC",
      "crv": "P-384",
      "alg": "ES384",
      "x": "PxgAmVYOQvSNcMYL2tOzoLwSWn4Ta3tIMPEUKR8pxeb-gmR11-DyKHBoIiY-2LhM",
      "y": "BZEBTkImVdpwvxR9THIRw16eblnj5-tZa7m-ww5uVd4kyPJNRoWUn2aT9ZuarAe-"
    }
  }]
}
```

[Example 16](#example-a-controlled-identifier-document-with-verification-relationships): A controlled identifier document with verification relationships

``` {aria-busy="false"}
{
  "id": "https://university.example/issuers/565049",
  "verificationMethod": [{
    "id": "https://university.example/issuers/565049#key-123",
    "type": "JsonWebKey",
    "controller": "https://university.example/issuers/565049",
    "publicKeyJwk": {
      "kty": "EC",
      "crv": "P-384",
      "alg": "ES384",
      "x": "PxgAmVYOQvSNcMYL2tOzoLwSWn4Ta3tIMPEUKR8pxeb-gmR11-DyKHBoIiY-2LhM",
      "y": "BZEBTkImVdpwvxR9THIRw16eblnj5-tZa7m-ww5uVd4kyPJNRoWUn2aT9ZuarAe-"
    }
  }],
  "authentication": ["https://university.example/issuers/565049#key-123"],
  "assertionMethod": ["https://university.example/issuers/565049#key-123"]
}
```

[Example 17](#example-a-verifiable-credential-controlled-identifier-document): A verifiable credential controlled identifier document

``` {aria-busy="false"}
{
  "@context": [
    "https://www.w3.org/ns/did/v1",
    "https://w3id.org/security/jwk/v1",
    {
        "@vocab": "https://vendor.example#"
    }
  ],
  "id": "did:web:vendor.example",
  "alsoKnownAs": ["https://vendor.example",
    "did:jwk:eyJraWQiOiJ1cm46aWV0ZjpwYXJhbXM6b2F1dGg6andrLXRodW1icHJpbnQ6c2hhLTI1NjpGZk1iek9qTW1RNGVmVDZrdndUSUpqZWxUcWpsMHhqRUlXUTJxb2JzUk1NIiwia3R5IjoiT0tQIiwiY3J2IjoiRWQyNTUxOSIsImFsZyI6IkVkRFNBIiwieCI6IkFOUmpIX3p4Y0tCeHNqUlBVdHpSYnA3RlNWTEtKWFE5QVBYOU1QMWo3azQifQ"
  ],
  "verificationMethod": [{
    "id": "#urn:ietf:params:oauth:jwk-thumbprint:sha-256:NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs",
    "type": "JsonWebKey",
    "controller": "did:web:vendor.example",
    "publicKeyJwk": {
      "kty": "EC",
      "crv": "P-521",
      "alg": "ES512",
      "x": "AFTyMw-fIYJNg6fBVJvOPOsLxmnNj8HgqMChyRL0swLaefVAc7wrWZ8okQJqMmvv03JRUp277meQZM3JcvXFkH1v",
      "y": "ALn96CrD88b4TClmkl1sk0xk2FgAIda97ZF8TUOjbeWSzbKnN2KB6pqlpbuJ2xIRXvsn5BWQVlAT2JGpGwDNMyV1"
    }
  }, {
    "id": "#z6MkhEdpG12jyQegrr62ACRmNY8gc531W2j9Xo39cHphuCEH",
    "type": "JsonWebKey2020",
    "controller": "https://vendor.example",
    "publicKeyJwk": {
      "kid": "urn:ietf:params:oauth:jwk-thumbprint:sha-256:FfMbzOjMmQ4efT6kvwTIJjelTqjl0xjEIWQ2qobsRMM",
      "kty": "OKP",
      "crv": "Ed25519",
      "alg": "EdDSA",
      "x": "ANRjH_zxcKBxsjRPUtzRbp7FSVLKJXQ9APX9MP1j7k4"
    }
  }, {
    "id": "#subject-authentication",
    "type": "JsonWebKey",
    "controller": "did:web:vendor.example",
    "publicKeyJwk": {
      "kty": "EC",
      "crv": "P-384",
      "alg": "ES384",
      "x": "PxgAmVYOQvSNcMYL2tOzoLwSWn4Ta3tIMPEUKR8pxeb-gmR11-DyKHBoIiY-2LhM",
      "y": "BZEBTkImVdpwvxR9THIRw16eblnj5-tZa7m-ww5uVd4kyPJNRoWUn2aT9ZuarAe-"
    }
  }, {
    "id": "#credential-issuance",
    "type": "JsonWebKey",
    "controller": "did:web:vendor.example",
    "publicKeyJwk": {
      "kty": "EC",
      "crv": "P-256",
      "alg": "ES256",
      "x": "MYvnaI87pfrn3FpTqW-yNiFcF1K7fedJiqapm20_q7c",
      "y": "9YEbT6Tyuc7xp9yRvhOUVKK_NIHkn5HpK9ZMgvK5pVw"
    }
  }, {
    "id": "#key-agreement",
    "type": "JsonWebKey",
    "controller": "did:web:vendor.example",
    "publicKeyJwk": {
      "kty": "OKP",
      "crv": "X25519",
      "alg": "ECDH-ES+A128KW",
      "x": "qLZkSTbstvMWPTivmiQglEFWG2Ff7gNDVoVisdZTr1I"
    }
  }],
  "authentication": ["#subject-authentication"],
  "assertionMethod": ["#credential-issuance"]
}
```

### 8.2 Credentials

[](#credentials)

[Example 18](#example-a-revocable-credential-with-multiple-subjects): A revocable credential with multiple subjects

-   Credential
-   jose
-   cose
-   sd-jwt

``` {.nohighlight .vc vc-tabs="jose sd-jwt cose"}
{
  "@context": ["https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "id": "https://contoso.example/credentials/23894672394",
  "type": ["VerifiableCredential", "K9UnitCredential"],
  "issuer": {
    "id": "https://contoso.example"
  },
  "validFrom": "2015-04-16T05:11:32.432Z",
  "credentialStatus": {
    "id": "https://contoso.example/credentials/status/4#273762",
    "type": "StatusList2021Entry",
    "statusPurpose": "revocation",
    "statusListIndex": "273762",
    "statusListCredential": "https://contoso.example/credentials/status/4"
  },
  "credentialSubject": [{
    "id": "did:example:1312387641",
    "type": "Person"
  }, {
    "id": "did:example:63888231",
    "type": "Dog"
  }]
}
```

**Protected Headers**

    {
      "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
      "alg": "ES256"
    }

**application/vc**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "id": "https://contoso.example/credentials/23894672394",
      "type": [
        "VerifiableCredential",
        "K9UnitCredential"
      ],
      "issuer": {
        "id": "https://contoso.example"
      },
      "validFrom": "2015-04-16T05:11:32.432Z",
      "credentialStatus": {
        "id": "https://contoso.example/credentials/status/4#273762",
        "type": "StatusList2021Entry",
        "statusPurpose": "revocation",
        "statusListIndex": "273762",
        "statusListCredential": "https://contoso.example/credentials/status/4"
      },
      "credentialSubject": [
        {
          "id": "did:example:1312387641",
          "type": "Person"
        },
        {
          "id": "did:example:63888231",
          "type": "Dog"
        }
      ]
    }

**application/vc+jwt**

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaWQiOiJodHRwczovL2NvbnRvc28uZXhhbXBsZS9jcmVkZW50aWFscy8yMzg5NDY3MjM5NCIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJLOVVuaXRDcmVkZW50aWFsIl0sImlzc3VlciI6eyJpZCI6Imh0dHBzOi8vY29udG9zby5leGFtcGxlIn0sInZhbGlkRnJvbSI6IjIwMTUtMDQtMTZUMDU6MTE6MzIuNDMyWiIsImNyZWRlbnRpYWxTdGF0dXMiOnsiaWQiOiJodHRwczovL2NvbnRvc28uZXhhbXBsZS9jcmVkZW50aWFscy9zdGF0dXMvNCMyNzM3NjIiLCJ0eXBlIjoiU3RhdHVzTGlzdDIwMjFFbnRyeSIsInN0YXR1c1B1cnBvc2UiOiJyZXZvY2F0aW9uIiwic3RhdHVzTGlzdEluZGV4IjoiMjczNzYyIiwic3RhdHVzTGlzdENyZWRlbnRpYWwiOiJodHRwczovL2NvbnRvc28uZXhhbXBsZS9jcmVkZW50aWFscy9zdGF0dXMvNCJ9LCJjcmVkZW50aWFsU3ViamVjdCI6W3siaWQiOiJkaWQ6ZXhhbXBsZToxMzEyMzg3NjQxIiwidHlwZSI6IlBlcnNvbiJ9LHsiaWQiOiJkaWQ6ZXhhbXBsZTo2Mzg4ODIzMSIsInR5cGUiOiJEb2cifV19 .yQi8SfQIk9NoQJfJGJnBjFXe9kXZMMS7GvX1o_BztgC4jMMQoQiLTo2nPH_o6OP1IszRuW_M3ubRZs3WEoiZVw

**application/vc**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "id": "https://contoso.example/credentials/23894672394",
      "type": [
        "VerifiableCredential",
        "K9UnitCredential"
      ],
      "issuer": {
        "id": "https://contoso.example"
      },
      "validFrom": "2015-04-16T05:11:32.432Z",
      "credentialStatus": {
        "id": "https://contoso.example/credentials/status/4#273762",
        "type": "StatusList2021Entry",
        "statusPurpose": "revocation",
        "statusListIndex": "273762",
        "statusListCredential": "https://contoso.example/credentials/status/4"
      },
      "credentialSubject": [
        {
          "id": "did:example:1312387641",
          "type": "Person"
        },
        {
          "id": "did:example:63888231",
          "type": "Dog"
        }
      ]
    }

**application/vc+cose**

d28444a1013822a059027c7b2240636f6e74657874223a5b2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f6578616d706c65732f7632225d2c226964223a2268747470733a2f2f636f6e746f736f2e6578616d706c652f63726564656e7469616c732f3233383934363732333934222c2274797065223a5b2256657269666961626c6543726564656e7469616c222c224b39556e697443726564656e7469616c225d2c22697373756572223a7b226964223a2268747470733a2f2f636f6e746f736f2e6578616d706c65227d2c2276616c696446726f6d223a22323031352d30342d31365430353a31313a33322e3433325a222c2263726564656e7469616c537461747573223a7b226964223a2268747470733a2f2f636f6e746f736f2e6578616d706c652f63726564656e7469616c732f7374617475732f3423323733373632222c2274797065223a225374617475734c69737432303231456e747279222c22737461747573507572706f7365223a227265766f636174696f6e222c227374617475734c697374496e646578223a22323733373632222c227374617475734c69737443726564656e7469616c223a2268747470733a2f2f636f6e746f736f2e6578616d706c652f63726564656e7469616c732f7374617475732f34227d2c2263726564656e7469616c5375626a656374223a5b7b226964223a226469643a6578616d706c653a31333132333837363431222c2274797065223a22506572736f6e227d2c7b226964223a226469643a6578616d706c653a3633383838323331222c2274797065223a22446f67227d5d7d58400a6163b3d6e0157f54f5075c112326aa0bfadaeae67c9a49e5028c893529adb474569b9c17eaf9d507f2932e73182867de46a7808be1650ef1ac1f75d685f075

-   Encoded
-   Decoded
-   Issuer Disclosures

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJpYXQiOjE3NDU1OTQ3NzIsImV4cCI6MTc0NjgwNDM3MiwiX3NkX2FsZyI6InNoYS0yNTYiLCJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaXNzdWVyIjp7Il9zZCI6WyJtU2cwa1pDNzdYNmpVVkhxMklJWU55Z1NsWEtLVnpUU2RoRldvTll5eE9BIl19LCJ2YWxpZEZyb20iOiIyMDE1LTA0LTE2VDA1OjExOjMyLjQzMloiLCJjcmVkZW50aWFsU3RhdHVzIjp7InN0YXR1c1B1cnBvc2UiOiJyZXZvY2F0aW9uIiwic3RhdHVzTGlzdEluZGV4IjoiMjczNzYyIiwic3RhdHVzTGlzdENyZWRlbnRpYWwiOiJodHRwczovL2NvbnRvc28uZXhhbXBsZS9jcmVkZW50aWFscy9zdGF0dXMvNCIsIl9zZCI6WyJtdjZDZXh1LWZ3UGRMVm81WllIN3ljUzhtRkdUUVZNeFRocDJud0VDQlQ0IiwidFFhbnRYLWhoU3BPQU5PVi0xeEtGX0dSb2Y1TG5tNkpiUEtxYm9MdDdSQSJdfSwiY3JlZGVudGlhbFN1YmplY3QiOlt7Il9zZCI6WyJTTXhGcmxPY3N1UHlWTjVMUkJiVlRMWjZ4TVFxYVVCTkJTNjdrTF9YZF93IiwiVkdONDR1NXNWUWduY0VJZmdFTEplWlI5YnBlVEdjOFpBY01tVjZNUXR0ZyJdfSx7Il9zZCI6WyJNQmVfN0hLajAtUDJaZnR0dzV2WWRKMHhVZTN4dHBpMndpZGJTbndfWk9nIiwiZWMxeVVZMEM3NUo3cGd5TXpPNUR3OTBwRFVyM2ZYN1o4R3Q4d1VBSVl6SSJdfV0sIl9zZCI6WyJGSVdneTM5VXp6WjVfYU9LVjNwbzZFeWpYbUJOOWM4b0lRMmpLZV9GdGIwIiwiRm1EY2RlbUlzcGRtUU1NZXVTLTRONEo1d0NGb2JCQnVpdVVlbEdzV012VSJdfQ .iQnJDPmi4pn3fMLecrbwAqfUc0UXJWzUd4J20mUX3rRJSp74r2gRjrd1OATFkVjLsSyBAOBk3xbP5tc9nq9D7A \~WyI0SWQ0aFVoUzMxQThXelBRTlhPUWJBIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlL2NyZWRlbnRpYWxzLzIzODk0NjcyMzk0Il0\~WyJJSFNyZnVZR0YtcWk5OXcySWJkYUJBIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIks5VW5pdENyZWRlbnRpYWwiXV0\~WyJDeEpTQmlKeDVtdVgzZkhSRmZDYlBRIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlIl0\~WyJuQVhZQ29yTUNGaGRJMDhZNzh5ckNBIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlL2NyZWRlbnRpYWxzL3N0YXR1cy80IzI3Mzc2MiJd\~WyI1aUJCSmdual9pMkpQZHdRTTVzRlh3IiwgInR5cGUiLCAiU3RhdHVzTGlzdDIwMjFFbnRyeSJd\~WyJBNzFORTByODBmN3ZCVkNrcmU0N01BIiwgImlkIiwgImRpZDpleGFtcGxlOjEzMTIzODc2NDEiXQ\~WyJHaFg4N1lJMzg0cjV5b0hXOENTOEJ3IiwgInR5cGUiLCAiUGVyc29uIl0\~WyJhQWxrLVhrQ0RCaFItN1Z5NnBXZmNnIiwgImlkIiwgImRpZDpleGFtcGxlOjYzODg4MjMxIl0\~WyJzLTFSWGdUWVVQWGJNZ2JLMHVaX0NRIiwgInR5cGUiLCAiRG9nIl0\~

``` header-value
{
  "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
  "alg": "ES256"
}
```

``` header-value
{
  "iat": 1745594772,
  "exp": 1746804372,
  "_sd_alg": "sha-256",
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "issuer": {
    "_sd": [
      "mSg0kZC77X6jUVHq2IIYNygSlXKKVzTSdhFWoNYyxOA"
    ]
  },
  "validFrom": "2015-04-16T05:11:32.432Z",
  "credentialStatus": {
    "statusPurpose": "revocation",
    "statusListIndex": "273762",
    "statusListCredential": "https://contoso.example/credentials/status/4",
    "_sd": [
      "mv6Cexu-fwPdLVo5ZYH7ycS8mFGTQVMxThp2nwECBT4",
      "tQantX-hhSpOANOV-1xKF_GRof5Lnm6JbPKqboLt7RA"
    ]
  },
  "credentialSubject": [
    {
      "_sd": [
        "SMxFrlOcsuPyVN5LRBbVTLZ6xMQqaUBNBS67kL_Xd_w",
        "VGN44u5sVQgncEIfgELJeZR9bpeTGc8ZAcMmV6MQttg"
      ]
    },
    {
      "_sd": [
        "MBe_7HKj0-P2Zfttw5vYdJ0xUe3xtpi2widbSnw_ZOg",
        "ec1yUY0C75J7pgyMzO5Dw90pDUr3fX7Z8Gt8wUAIYzI"
      ]
    }
  ],
  "_sd": [
    "FIWgy39UzzZ5_aOKV3po6EyjXmBN9c8oIQ2jKe_Ftb0",
    "FmDcdemIspdmQMMeuS-4N4J5wCFobBBuiuUelGsWMvU"
  ]
}
```

### Claim: id

**SHA-256 Hash:** FIWgy39UzzZ5_aOKV3po6EyjXmBN9c8oIQ2jKe_Ftb0

**Disclosure(s):** WyI0SWQ0aFVoUzMxQThXelBRTlhPUWJBIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlL2NyZWRlbnRpYWxzLzIzODk0NjcyMzk0Il0

**Contents:** \[\
  \"4Id4hUhS31A8WzPQNXOQbA\",\
  \"id\",\
  \"https://contoso.example/credentials/23894672394\"\
\]

### Claim: type

**SHA-256 Hash:** FmDcdemIspdmQMMeuS-4N4J5wCFobBBuiuUelGsWMvU

**Disclosure(s):** WyJJSFNyZnVZR0YtcWk5OXcySWJkYUJBIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIks5VW5pdENyZWRlbnRpYWwiXV0

**Contents:** \[\
  \"IHSrfuYGF-qi99w2IbdaBA\",\
  \"type\",\
  \[\
    \"VerifiableCredential\",\
    \"K9UnitCredential\"\
  \]\
\]

### Claim: id

**SHA-256 Hash:** mSg0kZC77X6jUVHq2IIYNygSlXKKVzTSdhFWoNYyxOA

**Disclosure(s):** WyJDeEpTQmlKeDVtdVgzZkhSRmZDYlBRIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlIl0

**Contents:** \[\
  \"CxJSBiJx5muX3fHRFfCbPQ\",\
  \"id\",\
  \"https://contoso.example\"\
\]

### Claim: id

**SHA-256 Hash:** mv6Cexu-fwPdLVo5ZYH7ycS8mFGTQVMxThp2nwECBT4

**Disclosure(s):** WyJuQVhZQ29yTUNGaGRJMDhZNzh5ckNBIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlL2NyZWRlbnRpYWxzL3N0YXR1cy80IzI3Mzc2MiJd

**Contents:** \[\
  \"nAXYCorMCFhdI08Y78yrCA\",\
  \"id\",\
  \"https://contoso.example/credentials/status/4#273762\"\
\]

### Claim: type

**SHA-256 Hash:** tQantX-hhSpOANOV-1xKF_GRof5Lnm6JbPKqboLt7RA

**Disclosure(s):** WyI1aUJCSmdual9pMkpQZHdRTTVzRlh3IiwgInR5cGUiLCAiU3RhdHVzTGlzdDIwMjFFbnRyeSJd

**Contents:** \[\
  \"5iBBJgnj_i2JPdwQM5sFXw\",\
  \"type\",\
  \"StatusList2021Entry\"\
\]

### Claim: id

**SHA-256 Hash:** SMxFrlOcsuPyVN5LRBbVTLZ6xMQqaUBNBS67kL_Xd_w

**Disclosure(s):** WyJBNzFORTByODBmN3ZCVkNrcmU0N01BIiwgImlkIiwgImRpZDpleGFtcGxlOjEzMTIzODc2NDEiXQ

**Contents:** \[\
  \"A71NE0r80f7vBVCkre47MA\",\
  \"id\",\
  \"did:example:1312387641\"\
\]

### Claim: type

**SHA-256 Hash:** VGN44u5sVQgncEIfgELJeZR9bpeTGc8ZAcMmV6MQttg

**Disclosure(s):** WyJHaFg4N1lJMzg0cjV5b0hXOENTOEJ3IiwgInR5cGUiLCAiUGVyc29uIl0

**Contents:** \[\
  \"GhX87YI384r5yoHW8CS8Bw\",\
  \"type\",\
  \"Person\"\
\]

### Claim: id

**SHA-256 Hash:** MBe_7HKj0-P2Zfttw5vYdJ0xUe3xtpi2widbSnw_ZOg

**Disclosure(s):** WyJhQWxrLVhrQ0RCaFItN1Z5NnBXZmNnIiwgImlkIiwgImRpZDpleGFtcGxlOjYzODg4MjMxIl0

**Contents:** \[\
  \"aAlk-XkCDBhR-7Vy6pWfcg\",\
  \"id\",\
  \"did:example:63888231\"\
\]

### Claim: type

**SHA-256 Hash:** ec1yUY0C75J7pgyMzO5Dw90pDUr3fX7Z8Gt8wUAIYzI

**Disclosure(s):** WyJzLTFSWGdUWVVQWGJNZ2JLMHVaX0NRIiwgInR5cGUiLCAiRG9nIl0

**Contents:** \[\
  \"s-1RXgTYUPXbMgbK0uZ_CQ\",\
  \"type\",\
  \"Dog\"\
\]

[Example 19](#example-a-credential-with-a-schema): A credential with a schema

-   Credential
-   jose
-   cose
-   sd-jwt

``` {.nohighlight .vc vc-tabs="jose sd-jwt cose"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "id": "https://contoso.example/credentials/35327255",
  "type": ["VerifiableCredential", "KYCExample"],
  "issuer": "did:web:contoso.example",
  "validFrom": "2019-05-25T03:10:16.992Z",
  "validUntil": "2027-05-25T03:10:16.992Z",
  "credentialSchema": {
    "id": "https://contoso.example/bafybeigdyr...lqabf3oclgtqy55fbzdi",
    "type": "JsonSchema"
  },
  "credentialSubject": {
    "id": "did:example:1231588",
    "type": "Person"
  }
}
```

**Protected Headers**

    {
      "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
      "alg": "ES256"
    }

**application/vc**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "id": "https://contoso.example/credentials/35327255",
      "type": [
        "VerifiableCredential",
        "KYCExample"
      ],
      "issuer": "did:web:contoso.example",
      "validFrom": "2019-05-25T03:10:16.992Z",
      "validUntil": "2027-05-25T03:10:16.992Z",
      "credentialSchema": {
        "id": "https://contoso.example/bafybeigdyr...lqabf3oclgtqy55fbzdi",
        "type": "JsonSchema"
      },
      "credentialSubject": {
        "id": "did:example:1231588",
        "type": "Person"
      }
    }

**application/vc+jwt**

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaWQiOiJodHRwczovL2NvbnRvc28uZXhhbXBsZS9jcmVkZW50aWFscy8zNTMyNzI1NSIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJLWUNFeGFtcGxlIl0sImlzc3VlciI6ImRpZDp3ZWI6Y29udG9zby5leGFtcGxlIiwidmFsaWRGcm9tIjoiMjAxOS0wNS0yNVQwMzoxMDoxNi45OTJaIiwidmFsaWRVbnRpbCI6IjIwMjctMDUtMjVUMDM6MTA6MTYuOTkyWiIsImNyZWRlbnRpYWxTY2hlbWEiOnsiaWQiOiJodHRwczovL2NvbnRvc28uZXhhbXBsZS9iYWZ5YmVpZ2R5ci4uLmxxYWJmM29jbGd0cXk1NWZiemRpIiwidHlwZSI6Ikpzb25TY2hlbWEifSwiY3JlZGVudGlhbFN1YmplY3QiOnsiaWQiOiJkaWQ6ZXhhbXBsZToxMjMxNTg4IiwidHlwZSI6IlBlcnNvbiJ9fQ .L7mcUXK-zs1mpGF1iuelE0rr_2RYE5_BorKyYhvv4F5pezJgzH0mv6z-IC-ZXp9ZG1R1Y5k02BvHFX7_Ef5e3A

**application/vc**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "id": "https://contoso.example/credentials/35327255",
      "type": [
        "VerifiableCredential",
        "KYCExample"
      ],
      "issuer": "did:web:contoso.example",
      "validFrom": "2019-05-25T03:10:16.992Z",
      "validUntil": "2027-05-25T03:10:16.992Z",
      "credentialSchema": {
        "id": "https://contoso.example/bafybeigdyr...lqabf3oclgtqy55fbzdi",
        "type": "JsonSchema"
      },
      "credentialSubject": {
        "id": "did:example:1231588",
        "type": "Person"
      }
    }

**application/vc+cose**

d28444a1013822a05901e47b2240636f6e74657874223a5b2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f6578616d706c65732f7632225d2c226964223a2268747470733a2f2f636f6e746f736f2e6578616d706c652f63726564656e7469616c732f3335333237323535222c2274797065223a5b2256657269666961626c6543726564656e7469616c222c224b59434578616d706c65225d2c22697373756572223a226469643a7765623a636f6e746f736f2e6578616d706c65222c2276616c696446726f6d223a22323031392d30352d32355430333a31303a31362e3939325a222c2276616c6964556e74696c223a22323032372d30352d32355430333a31303a31362e3939325a222c2263726564656e7469616c536368656d61223a7b226964223a2268747470733a2f2f636f6e746f736f2e6578616d706c652f62616679626569676479722e2e2e6c71616266336f636c67747179353566627a6469222c2274797065223a224a736f6e536368656d61227d2c2263726564656e7469616c5375626a656374223a7b226964223a226469643a6578616d706c653a31323331353838222c2274797065223a22506572736f6e227d7d5840d89478bd15d530c2cb8af5ad917d257590979cd5f51552effc65552df35326cd08cce2b16f8f6a14166e2b7d62e64c9ef5ab7d4fd6304629c974949299049081

-   Encoded
-   Decoded
-   Issuer Disclosures

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJpYXQiOjE3NDU1OTQ3NzIsImV4cCI6MTc0NjgwNDM3MiwiX3NkX2FsZyI6InNoYS0yNTYiLCJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaXNzdWVyIjoiZGlkOndlYjpjb250b3NvLmV4YW1wbGUiLCJ2YWxpZEZyb20iOiIyMDE5LTA1LTI1VDAzOjEwOjE2Ljk5MloiLCJ2YWxpZFVudGlsIjoiMjAyNy0wNS0yNVQwMzoxMDoxNi45OTJaIiwiY3JlZGVudGlhbFNjaGVtYSI6eyJfc2QiOlsiSGlhclpVRzdOVkhlVHhNSmZMRkdRTi0zdm1mZDdqVUN6d213NFhid2FCNCIsIktTeTlKVGljY1BQcHBvN2tuVTRaU3FxalpubzM1UnotOVJOTjBZNUVwbmciXX0sImNyZWRlbnRpYWxTdWJqZWN0Ijp7Il9zZCI6WyJBdmplbXo3U3JsQklPcFhUb2tJU1lONGlWNkVIQ21LTDRHSk1fOU9qUHJnIiwiYWVTMGtVVEZBanZvSVBQSVRnQl9CcDg4dm9lM2ZuSHFyQ3Z4LUNKckgxOCJdfSwiX3NkIjpbIjFqaV8zOWllbnBoWEVzck9LeHB1ZGc0MEFBQi1pUTlHQzhCRHNQcTJWMWciLCJVRTlSNmt0cG5XVTBya2Y3cXBGaE9WYTE0Z2M3SDc4d2U5d1FfbEUxSVdzIl19 .y-E31PUTem9cL6n-E6nVIx5h9a8OpnrSBZ3N2Ggn5PncgPqnMROBPzp-tIhS3yfwZnQQfQnEOAuAWhz-M392xA \~WyIxZ3Mxd3lXd002RC1tVWl3U1pnYjJBIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlL2NyZWRlbnRpYWxzLzM1MzI3MjU1Il0\~WyJNLWlDZmZjZGdod1BFSXV2Wk9NODh3IiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIktZQ0V4YW1wbGUiXV0\~WyJ6TV9PQXNIMV9WMXBpZV9fU09QX3BBIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlL2JhZnliZWlnZHlyLi4ubHFhYmYzb2NsZ3RxeTU1ZmJ6ZGkiXQ\~WyJnNzFUcFFIbHNoQUZjX2FhNDlTaWZ3IiwgInR5cGUiLCAiSnNvblNjaGVtYSJd\~WyJ1cUZTLWZobTBkaWlTa1BjYVlFMXhRIiwgImlkIiwgImRpZDpleGFtcGxlOjEyMzE1ODgiXQ\~WyJsRU5IX0ZBc253eVJkYnFSSHBkT2l3IiwgInR5cGUiLCAiUGVyc29uIl0\~

``` header-value
{
  "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
  "alg": "ES256"
}
```

``` header-value
{
  "iat": 1745594772,
  "exp": 1746804372,
  "_sd_alg": "sha-256",
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "issuer": "did:web:contoso.example",
  "validFrom": "2019-05-25T03:10:16.992Z",
  "validUntil": "2027-05-25T03:10:16.992Z",
  "credentialSchema": {
    "_sd": [
      "HiarZUG7NVHeTxMJfLFGQN-3vmfd7jUCzwmw4XbwaB4",
      "KSy9JTiccPPppo7knU4ZSqqjZno35Rz-9RNN0Y5Epng"
    ]
  },
  "credentialSubject": {
    "_sd": [
      "Avjemz7SrlBIOpXTokISYN4iV6EHCmKL4GJM_9OjPrg",
      "aeS0kUTFAjvoIPPITgB_Bp88voe3fnHqrCvx-CJrH18"
    ]
  },
  "_sd": [
    "1ji_39ienphXEsrOKxpudg40AAB-iQ9GC8BDsPq2V1g",
    "UE9R6ktpnWU0rkf7qpFhOVa14gc7H78we9wQ_lE1IWs"
  ]
}
```

### Claim: id

**SHA-256 Hash:** 1ji_39ienphXEsrOKxpudg40AAB-iQ9GC8BDsPq2V1g

**Disclosure(s):** WyIxZ3Mxd3lXd002RC1tVWl3U1pnYjJBIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlL2NyZWRlbnRpYWxzLzM1MzI3MjU1Il0

**Contents:** \[\
  \"1gs1wyWwM6D-mUiwSZgb2A\",\
  \"id\",\
  \"https://contoso.example/credentials/35327255\"\
\]

### Claim: type

**SHA-256 Hash:** UE9R6ktpnWU0rkf7qpFhOVa14gc7H78we9wQ_lE1IWs

**Disclosure(s):** WyJNLWlDZmZjZGdod1BFSXV2Wk9NODh3IiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIktZQ0V4YW1wbGUiXV0

**Contents:** \[\
  \"M-iCffcdghwPEIuvZOM88w\",\
  \"type\",\
  \[\
    \"VerifiableCredential\",\
    \"KYCExample\"\
  \]\
\]

### Claim: id

**SHA-256 Hash:** HiarZUG7NVHeTxMJfLFGQN-3vmfd7jUCzwmw4XbwaB4

**Disclosure(s):** WyJ6TV9PQXNIMV9WMXBpZV9fU09QX3BBIiwgImlkIiwgImh0dHBzOi8vY29udG9zby5leGFtcGxlL2JhZnliZWlnZHlyLi4ubHFhYmYzb2NsZ3RxeTU1ZmJ6ZGkiXQ

**Contents:** \[\
  \"zM_OAsH1_V1pie\_\_SOP_pA\",\
  \"id\",\
  \"https://contoso.example/bafybeigdyr\...lqabf3oclgtqy55fbzdi\"\
\]

### Claim: type

**SHA-256 Hash:** KSy9JTiccPPppo7knU4ZSqqjZno35Rz-9RNN0Y5Epng

**Disclosure(s):** WyJnNzFUcFFIbHNoQUZjX2FhNDlTaWZ3IiwgInR5cGUiLCAiSnNvblNjaGVtYSJd

**Contents:** \[\
  \"g71TpQHlshAFc_aa49Sifw\",\
  \"type\",\
  \"JsonSchema\"\
\]

### Claim: id

**SHA-256 Hash:** aeS0kUTFAjvoIPPITgB_Bp88voe3fnHqrCvx-CJrH18

**Disclosure(s):** WyJ1cUZTLWZobTBkaWlTa1BjYVlFMXhRIiwgImlkIiwgImRpZDpleGFtcGxlOjEyMzE1ODgiXQ

**Contents:** \[\
  \"uqFS-fhm0diiSkPcaYE1xQ\",\
  \"id\",\
  \"did:example:1231588\"\
\]

### Claim: type

**SHA-256 Hash:** Avjemz7SrlBIOpXTokISYN4iV6EHCmKL4GJM_9OjPrg

**Disclosure(s):** WyJsRU5IX0ZBc253eVJkYnFSSHBkT2l3IiwgInR5cGUiLCAiUGVyc29uIl0

**Contents:** \[\
  \"lENH_FAsnwyRdbqRHpdOiw\",\
  \"type\",\
  \"Person\"\
\]

### 8.3 Presentations

[](#presentations)

[Example 20](#example-presentation): Presentation

-   Presentation
-   jose
-   cose
-   sd-jwt

``` {.nohighlight .vc vc-tabs="jose sd-jwt cose"}
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "type": "VerifiablePresentation",
  "verifiableCredential": [
    {
      "@context": "https://www.w3.org/ns/credentials/v2",
      "id": "data:application/vc+cose;base64,0oREo...+Q==",
      "type": "EnvelopedVerifiableCredential"
    },
    {
      "@context": "https://www.w3.org/ns/credentials/v2",
      "id": "data:application/vc+jwt,eyVjV...RMjU",
      "type": "EnvelopedVerifiableCredential"
    },
    {
      "@context": "https://www.w3.org/ns/credentials/v2",
      "id": "data:application/vc+sd-jwt,eyVjV...RMjU~",
      "type": "EnvelopedVerifiableCredential"
    }
  ]
}
```

**Protected Headers**

    {
      "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
      "alg": "ES256"
    }

**application/vp**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "type": "VerifiablePresentation",
      "verifiableCredential": [
        {
          "@context": "https://www.w3.org/ns/credentials/v2",
          "id": "data:application/vc+cose;base64url,YmFzZTY0LDBvUkVvLi4uK1E9PQ",
          "type": "EnvelopedVerifiableCredential"
        },
        {
          "@context": "https://www.w3.org/ns/credentials/v2",
          "id": "data:application/vc+jwt,eyVjV...RMjU;data:application/vc+jwt,eyVjV...RMjU",
          "type": "EnvelopedVerifiableCredential"
        },
        {
          "@context": "https://www.w3.org/ns/credentials/v2",
          "id": "data:application/vc+sd-jwt,eyVjV...RMjU~;data:application/vc+sd-jwt,eyVjV...RMjU~",
          "type": "EnvelopedVerifiableCredential"
        }
      ]
    }

**application/vp+jwt**

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidHlwZSI6IlZlcmlmaWFibGVQcmVzZW50YXRpb24iLCJ2ZXJpZmlhYmxlQ3JlZGVudGlhbCI6W3siQGNvbnRleHQiOiJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJpZCI6ImRhdGE6YXBwbGljYXRpb24vdmMrY29zZTtiYXNlNjR1cmwsWW1GelpUWTBMREJ2VWtWdkxpNHVLMUU5UFEiLCJ0eXBlIjoiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwifSx7IkBjb250ZXh0IjoiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaWQiOiJkYXRhOmFwcGxpY2F0aW9uL3ZjK2p3dCxleVZqVi4uLlJNalU7ZGF0YTphcHBsaWNhdGlvbi92Yytqd3QsZXlWalYuLi5STWpVIiwidHlwZSI6IkVudmVsb3BlZFZlcmlmaWFibGVDcmVkZW50aWFsIn0seyJAY29udGV4dCI6Imh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImlkIjoiZGF0YTphcHBsaWNhdGlvbi92YytzZC1qd3QsZXlWalYuLi5STWpVfjtkYXRhOmFwcGxpY2F0aW9uL3ZjK3NkLWp3dCxleVZqVi4uLlJNalV-IiwidHlwZSI6IkVudmVsb3BlZFZlcmlmaWFibGVDcmVkZW50aWFsIn1dfQ .\_D2fLzqkl79rrfiNjLKc7yQOb-wa1eu4L5quq82DqDlyWJsGju5rkc6RWWfKT_vv27fth8uh7oEWwPDr9RAhTQ

**application/vp**

    {
      "@context": [
        "https://www.w3.org/ns/credentials/v2",
        "https://www.w3.org/ns/credentials/examples/v2"
      ],
      "type": "VerifiablePresentation",
      "verifiableCredential": [
        {
          "@context": "https://www.w3.org/ns/credentials/v2",
          "id": "data:application/vc+cose;base64url,WW1GelpUWTBMREJ2VWtWdkxpNHVLMUU5UFE",
          "type": "EnvelopedVerifiableCredential"
        },
        {
          "@context": "https://www.w3.org/ns/credentials/v2",
          "id": "data:application/vc+jwt,eyVjV...RMjU;data:application/vc+jwt,eyVjV...RMjU",
          "type": "EnvelopedVerifiableCredential"
        },
        {
          "@context": "https://www.w3.org/ns/credentials/v2",
          "id": "data:application/vc+sd-jwt,eyVjV...RMjU~;data:application/vc+sd-jwt,eyVjV...RMjU~",
          "type": "EnvelopedVerifiableCredential"
        }
      ]
    }

**application/vp+cose**

d28444a1013822a05902a77b2240636f6e74657874223a5b2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f6578616d706c65732f7632225d2c2274797065223a2256657269666961626c6550726573656e746174696f6e222c2276657269666961626c6543726564656e7469616c223a5b7b2240636f6e74657874223a2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c226964223a22646174613a6170706c69636174696f6e2f76632b636f73653b62617365363475726c2c57573147656c70555754424d52454a3256577457646b78704e48564c4d555535554645222c2274797065223a22456e76656c6f70656456657269666961626c6543726564656e7469616c227d2c7b2240636f6e74657874223a2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c226964223a22646174613a6170706c69636174696f6e2f76632b6a77742c6579566a562e2e2e524d6a553b646174613a6170706c69636174696f6e2f76632b6a77742c6579566a562e2e2e524d6a55222c2274797065223a22456e76656c6f70656456657269666961626c6543726564656e7469616c227d2c7b2240636f6e74657874223a2268747470733a2f2f7777772e77332e6f72672f6e732f63726564656e7469616c732f7632222c226964223a22646174613a6170706c69636174696f6e2f76632b73642d6a77742c6579566a562e2e2e524d6a557e3b646174613a6170706c69636174696f6e2f76632b73642d6a77742c6579566a562e2e2e524d6a557e222c2274797065223a22456e76656c6f70656456657269666961626c6543726564656e7469616c227d5d7d58406b63ff0e996d534bb83c906ddd9a8d1d0a286cc9e9336b3da94ddfceb9af09ff91fefd01807e8f5c3835f5131f7b7f97b300cc28cf3826369f5d290e34270712

-   Encoded
-   Decoded
-   Issuer Disclosures

eyJraWQiOiJFeEhrQk1XOWZtYmt2VjI2Nm1ScHVQMnNVWV9OX0VXSU4xbGFwVXpPOHJvIiwiYWxnIjoiRVMyNTYifQ .eyJpYXQiOjE3NDU1OTQ3NzIsImV4cCI6MTc0NjgwNDM3MiwiX3NkX2FsZyI6InNoYS0yNTYiLCJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwidmVyaWZpYWJsZUNyZWRlbnRpYWwiOlt7IkBjb250ZXh0IjoiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiX3NkIjpbIkNXMnNYREtVdTJZQ2U0bG83clN6WlNGa1hhNG8xT19TQ2FlUnBlTGZQVWciLCJmaFpSeDdlZXZGNW9YV0l1OHhwUWo1dUN4N21VWnZfbXAtTkt6WnI3M0Z3Il19LHsiQGNvbnRleHQiOiJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJfc2QiOlsiMGhfM2Myb3NlZ2x1UGdMUFBuMFR5T2c0TlJWRWFQdnQyMzRBQWU1c242ayIsIk9IbHhSNTYzTENhMThyd0xuNXY5RWJMTGZBd3M3dEZfb29aM3ZlZlRwenciXX0seyJAY29udGV4dCI6Imh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsIl9zZCI6WyJiZXppN0lySTlEOGc2N0Vpa3dtMkJoNjVpSS1MZW9CX1M5NXRuNmdEWnlNIiwiaFpnNlpxQlg4dFVqMzRpZUxnU0M3SjBiNUpFN3NYaG5TMm94OXpDTUZMYyJdfV0sIl9zZCI6WyJ4Nlk2cDFwWkduVzF6aHZ6RjliV1RsWXlFSk9yTDd5NWsySGVSSmFYR0lvIl19 .-BhSlghbRw896hU-9XHB6wf6C3PrZ-pW0IZr8QW7CabHrxytjSQe78NsvMadUZw0afYfDJogqFfjzNapl1evOg \~WyJVRExLMVB5MXRhbi1fT2NSV2VxWkFRIiwgInR5cGUiLCAiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd\~WyJKazJ5OTZlcTB6QXlDbGttd0luQ3lBIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdmMrY29zZTtiYXNlNjR1cmwsIFdXMUdlbHBVV1RCTVJFSjJWV3RXZGt4cE5IVkxNVVU1VUZFIl0\~WyJCSDVfaGZ0SHFXR1pNd2puNFJWc0J3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwiXQ\~WyJWVFVsV01mbWV3Wlg5NDVyc1J5Uk9BIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdmMrand0LCBleVZqVi4uLlJNalU7ZGF0YTphcHBsaWNhdGlvbi92Yytqd3QsIGV5VmpWLi4uUk1qVSJd\~WyJ6QVA2UUJDdVN2ZVhuSWg3RUVQeml3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwiXQ\~WyJ0eF85eXpwQzlrRE82dkZsLVFyT0lBIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdmMrc2Qtand0LCBleVZqVi4uLlJNalV-O2RhdGE6YXBwbGljYXRpb24vdmMrc2Qtand0LCBleVZqVi4uLlJNalV-Il0\~WyJMWVdtZW5EVWQ2WDduSjNtMnY4d0R3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwiXQ\~

``` header-value
{
  "kid": "ExHkBMW9fmbkvV266mRpuP2sUY_N_EWIN1lapUzO8ro",
  "alg": "ES256"
}
```

``` header-value
{
  "iat": 1745594772,
  "exp": 1746804372,
  "_sd_alg": "sha-256",
  "@context": [
    "https://www.w3.org/ns/credentials/v2",
    "https://www.w3.org/ns/credentials/examples/v2"
  ],
  "verifiableCredential": [
    {
      "@context": "https://www.w3.org/ns/credentials/v2",
      "_sd": [
        "CW2sXDKUu2YCe4lo7rSzZSFkXa4o1O_SCaeRpeLfPUg",
        "fhZRx7eevF5oXWIu8xpQj5uCx7mUZv_mp-NKzZr73Fw"
      ]
    },
    {
      "@context": "https://www.w3.org/ns/credentials/v2",
      "_sd": [
        "0h_3c2osegluPgLPPn0TyOg4NRVEaPvt234AAe5sn6k",
        "OHlxR563LCa18rwLn5v9EbLLfAws7tF_ooZ3vefTpzw"
      ]
    },
    {
      "@context": "https://www.w3.org/ns/credentials/v2",
      "_sd": [
        "bezi7IrI9D8g67Eikwm2Bh65iI-LeoB_S95tn6gDZyM",
        "hZg6ZqBX8tUj34ieLgSC7J0b5JE7sXhnS2ox9zCMFLc"
      ]
    }
  ],
  "_sd": [
    "x6Y6p1pZGnW1zhvzF9bWTlYyEJOrL7y5k2HeRJaXGIo"
  ]
}
```

### Claim: type

**SHA-256 Hash:** x6Y6p1pZGnW1zhvzF9bWTlYyEJOrL7y5k2HeRJaXGIo

**Disclosure(s):** WyJVRExLMVB5MXRhbi1fT2NSV2VxWkFRIiwgInR5cGUiLCAiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiJd

**Contents:** \[\
  \"UDLK1Py1tan-\_OcRWeqZAQ\",\
  \"type\",\
  \"VerifiablePresentation\"\
\]

### Claim: id

**SHA-256 Hash:** fhZRx7eevF5oXWIu8xpQj5uCx7mUZv_mp-NKzZr73Fw

**Disclosure(s):** WyJKazJ5OTZlcTB6QXlDbGttd0luQ3lBIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdmMrY29zZTtiYXNlNjR1cmwsIFdXMUdlbHBVV1RCTVJFSjJWV3RXZGt4cE5IVkxNVVU1VUZFIl0

**Contents:** \[\
  \"Jk2y96eq0zAyClkmwInCyA\",\
  \"id\",\
  \"data:application/vc+cose;base64url, WW1GelpUWTBMREJ2VWtWdkxpNHVLMUU5UFE\"\
\]

### Claim: type

**SHA-256 Hash:** CW2sXDKUu2YCe4lo7rSzZSFkXa4o1O_SCaeRpeLfPUg

**Disclosure(s):** WyJCSDVfaGZ0SHFXR1pNd2puNFJWc0J3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwiXQ

**Contents:** \[\
  \"BH5_hftHqWGZMwjn4RVsBw\",\
  \"type\",\
  \"EnvelopedVerifiableCredential\"\
\]

### Claim: id

**SHA-256 Hash:** OHlxR563LCa18rwLn5v9EbLLfAws7tF_ooZ3vefTpzw

**Disclosure(s):** WyJWVFVsV01mbWV3Wlg5NDVyc1J5Uk9BIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdmMrand0LCBleVZqVi4uLlJNalU7ZGF0YTphcHBsaWNhdGlvbi92Yytqd3QsIGV5VmpWLi4uUk1qVSJd

**Contents:** \[\
  \"VTUlWMfmewZX945rsRyROA\",\
  \"id\",\
  \"data:application/vc+jwt, eyVjV\...RMjU;data:application/vc+jwt, eyVjV\...RMjU\"\
\]

### Claim: type

**SHA-256 Hash:** 0h_3c2osegluPgLPPn0TyOg4NRVEaPvt234AAe5sn6k

**Disclosure(s):** WyJ6QVA2UUJDdVN2ZVhuSWg3RUVQeml3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwiXQ

**Contents:** \[\
  \"zAP6QBCuSveXnIh7EEPziw\",\
  \"type\",\
  \"EnvelopedVerifiableCredential\"\
\]

### Claim: id

**SHA-256 Hash:** bezi7IrI9D8g67Eikwm2Bh65iI-LeoB_S95tn6gDZyM

**Disclosure(s):** WyJ0eF85eXpwQzlrRE82dkZsLVFyT0lBIiwgImlkIiwgImRhdGE6YXBwbGljYXRpb24vdmMrc2Qtand0LCBleVZqVi4uLlJNalV-O2RhdGE6YXBwbGljYXRpb24vdmMrc2Qtand0LCBleVZqVi4uLlJNalV-Il0

**Contents:** \[\
  \"tx_9yzpC9kDO6vFl-QrOIA\",\
  \"id\",\
  \"data:application/vc+sd-jwt, eyVjV\...RMjU\~;data:application/vc+sd-jwt, eyVjV\...RMjU\~\"\
\]

### Claim: type

**SHA-256 Hash:** hZg6ZqBX8tUj34ieLgSC7J0b5JE7sXhnS2ox9zCMFLc

**Disclosure(s):** WyJMWVdtZW5EVWQ2WDduSjNtMnY4d0R3IiwgInR5cGUiLCAiRW52ZWxvcGVkVmVyaWZpYWJsZUNyZWRlbnRpYWwiXQ

**Contents:** \[\
  \"LYWmenDUd6X7nJ3m2v8wDw\",\
  \"type\",\
  \"EnvelopedVerifiableCredential\"\
\]

### 8.4 Data URIs

[](#date-uris)

[Example 21](#example-a-simple-uri-encoded-sd-jwt-verifiable-credential): A simple URI-encoded SD-JWT Verifiable Credential

``` {aria-busy="false"}
data:application/vc+sd-jwt,eyJhbGciOiJFUzM4NCIsImtpZCI6IlNJM1JITm91aDhvODFOT09OUFFVQUw3RWdaLWtJNl94ajlvUkV2WDF4T3ciLCJ0eXAiOiJ2YytsZCtqc29uK3NkLWp3dCIsImN0eSI6InZjK2xkK2pzb24ifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaXNzdWVyIjoiaHR0cHM6Ly91bml2ZXJzaXR5LmV4YW1wbGUvaXNzdWVycy81NjUwNDkiLCJ2YWxpZEZyb20iOiIyMDEwLTAxLTAxVDE5OjIzOjI0WiIsImNyZWRlbnRpYWxTY2hlbWEiOnsiX3NkIjpbIkU3dU1sSWFyS29iYXJTdEZGRjctZm5qaV9sQVdnM3BGMkV5dVc4dWFYakUiLCJYelRaSVgyNGdDSWxSQVFHclFoNU5FRm1XWkQtZ3Z3dkIybzB5Y0FwNFZzIl19LCJjcmVkZW50aWFsU3ViamVjdCI6eyJkZWdyZWUiOnsibmFtZSI6IkJhY2hlbG9yIG9mIFNjaWVuY2UgYW5kIEFydHMiLCJfc2QiOlsiT3oxUEZIMG0tWk9TdEhwUVZyeGlmVlpKRzhvNmlQQmNnLVZ2SXQwd2plcyJdfSwiX3NkIjpbIkVZQ1daMTZZMHB5X1VNNzRHU3NVYU9zT19mdDExTlVSaFFUTS1TT1lFTVEiXX0sIl9zZCI6WyJqT055NnZUbGNvVlAzM25oSTdERGN3ekVka3d2R3VVRXlLUjdrWEVLd3VVIiwid21BdHpwc0dRbDJveS1PY2JrSEVZcE8xb3BoX3VYcWVWVTRKekF0aFFibyJdLCJfc2RfYWxnIjoic2hhLTI1NiIsImlzcyI6Imh0dHBzOi8vdW5pdmVyc2l0eS5leGFtcGxlL2lzc3VlcnMvNTY1MDQ5IiwiaWF0IjoxNjk3Mjg5OTk2LCJleHAiOjE3Mjg5MTIzOTYsImNuZiI6eyJqd2siOnsia3R5IjoiRUMiLCJjcnYiOiJQLTM4NCIsImFsZyI6IkVTMzg0IiwieCI6InZFdV84WGxZT0ZFU2hTcVRpZ2JSYWduZ0ZGM1p5U0xrclNHekh3azFBT1loanhlazVhV21HY2UwZU05S0pWOEIiLCJ5IjoiRUpNY2czWXBzUTB3M2RLNHlVa25QczE1Z0lsY2Yyay03dzFKLTNlYlBiOERENmQtUkhBeGUwMDkzSWpfdTRCOSJ9fX0.rYzbxb6j1dwop8_s491iArVVJNm6A6C3b742gOm_qYO3zdkyQU4_VxxOSJ8ECcmWj2r5KyiCNC1ojfO4Yms-zBsjt7PoMYpYWBplsqXpiIvnehmM7D0eOLi40uHXki0X~WyJSWTg1YTZNMmEwX3VDWlFTVGZmTFdRIiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~WyJMeG5GYTBXVm8wRUluVy1QdS1fd1dRIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyJUQVdrakpCaVpxdC1rVU54X1EweUJBIiwgImlkIiwgImh0dHBzOi8vZXhhbXBsZS5vcmcvZXhhbXBsZXMvZGVncmVlLmpzb24iXQ~WyJTd2xuZFpPZzZEZ1ZERFp5X0RvYVFBIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd~WyJuSnJlU3E1Nzg3RGZMSDJCbU03cXFRIiwgImlkIiwgImRpZDpleGFtcGxlOjEyMyJd~WyIxMjNNd3hNcHRiek02YUk2aW03ME1RIiwgInR5cGUiLCAiQmFjaGVsb3JEZWdyZWUiXQ~
```

[Example 22](#example-a-simple-uri-encoded-sd-jwt-verifiable-presentation): A simple URI-encoded SD-JWT Verifiable Presentation

``` {aria-busy="false"}
data:application/vp+sd-jwt,eyJhbGciOiJFUzM4NCIsImtpZCI6IlNJM1JITm91aDhvODFOT09OUFFVQUw3RWdaLWtJNl94ajlvUkV2WDF4T3ciLCJ0eXAiOiJ2YytsZCtqc29uK3NkLWp3dCIsImN0eSI6InZjK2xkK2pzb24ifQ.eyJAY29udGV4dCI6WyJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvdjIiLCJodHRwczovL3d3dy53My5vcmcvbnMvY3JlZGVudGlhbHMvZXhhbXBsZXMvdjIiXSwiaXNzdWVyIjoiaHR0cHM6Ly91bml2ZXJzaXR5LmV4YW1wbGUvaXNzdWVycy81NjUwNDkiLCJ2YWxpZEZyb20iOiIyMDEwLTAxLTAxVDE5OjIzOjI0WiIsImNyZWRlbnRpYWxTY2hlbWEiOnsiX3NkIjpbIkU3dU1sSWFyS29iYXJTdEZGRjctZm5qaV9sQVdnM3BGMkV5dVc4dWFYakUiLCJYelRaSVgyNGdDSWxSQVFHclFoNU5FRm1XWkQtZ3Z3dkIybzB5Y0FwNFZzIl19LCJjcmVkZW50aWFsU3ViamVjdCI6eyJkZWdyZWUiOnsibmFtZSI6IkJhY2hlbG9yIG9mIFNjaWVuY2UgYW5kIEFydHMiLCJfc2QiOlsiT3oxUEZIMG0tWk9TdEhwUVZyeGlmVlpKRzhvNmlQQmNnLVZ2SXQwd2plcyJdfSwiX3NkIjpbIkVZQ1daMTZZMHB5X1VNNzRHU3NVYU9zT19mdDExTlVSaFFUTS1TT1lFTVEiXX0sIl9zZCI6WyJqT055NnZUbGNvVlAzM25oSTdERGN3ekVka3d2R3VVRXlLUjdrWEVLd3VVIiwid21BdHpwc0dRbDJveS1PY2JrSEVZcE8xb3BoX3VYcWVWVTRKekF0aFFibyJdLCJfc2RfYWxnIjoic2hhLTI1NiIsImlzcyI6Imh0dHBzOi8vdW5pdmVyc2l0eS5leGFtcGxlL2lzc3VlcnMvNTY1MDQ5IiwiaWF0IjoxNjk3Mjg5OTk2LCJleHAiOjE3Mjg5MTIzOTYsImNuZiI6eyJqd2siOnsia3R5IjoiRUMiLCJjcnYiOiJQLTM4NCIsImFsZyI6IkVTMzg0IiwieCI6InZFdV84WGxZT0ZFU2hTcVRpZ2JSYWduZ0ZGM1p5U0xrclNHekh3azFBT1loanhlazVhV21HY2UwZU05S0pWOEIiLCJ5IjoiRUpNY2czWXBzUTB3M2RLNHlVa25QczE1Z0lsY2Yyay03dzFKLTNlYlBiOERENmQtUkhBeGUwMDkzSWpfdTRCOSJ9fX0.rYzbxb6j1dwop8_s491iArVVJNm6A6C3b742gOm_qYO3zdkyQU4_VxxOSJ8ECcmWj2r5KyiCNC1ojfO4Yms-zBsjt7PoMYpYWBplsqXpiIvnehmM7D0eOLi40uHXki0X~WyJTd2xuZFpPZzZEZ1ZERFp5X0RvYVFBIiwgInR5cGUiLCAiSnNvblNjaGVtYSJd~WyIxMjNNd3hNcHRiek02YUk2aW03ME1RIiwgInR5cGUiLCAiQmFjaGVsb3JEZWdyZWUiXQ~WyJMeG5GYTBXVm8wRUluVy1QdS1fd1dRIiwgInR5cGUiLCBbIlZlcmlmaWFibGVDcmVkZW50aWFsIiwgIkV4YW1wbGVBbHVtbmlDcmVkZW50aWFsIl1d~WyJSWTg1YTZNMmEwX3VDWlFTVGZmTFdRIiwgImlkIiwgImh0dHA6Ly91bml2ZXJzaXR5LmV4YW1wbGUvY3JlZGVudGlhbHMvMTg3MiJd~eyJhbGciOiJFUzM4NCIsInR5cCI6ImtiK2p3dCJ9.eyJub25jZSI6IkVmeTROTFJPX3ZvSkszdDIzcUNfQlEiLCJhdWQiOiJodHRwczovL3ZlcmlmaWVyLmV4YW1wbGUiLCJpYXQiOjE2OTcyODk5OTZ9.6G-1nVcrDKFzR6BdbcFHcbtassEb8NZ7ZavTYz3SJ-e4pXleXs0tNcCkUCwMI70gsuOY0AXzeDPbHjp5GKyLDVuNWgWCt3Wo2VSaCwUkyfLyvhkCsmkF9kvFhMIOhp1i~
```

[Example 23](#example-a-simple-uri-encoded-cose-verifiable-presentation): A simple URI-encoded COSE Verifiable Presentation

``` {aria-busy="false"}
data:application/vp+cose;base64,0oREoQE4IqBZDSJ7IkBjb250ZXh0IjpbImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy9leGFtcGxlcy92MiJdLCJ0eXBlIjoiVmVyaWZpYWJsZVByZXNlbnRhdGlvbiIsInZlcmlmaWFibGVDcmVkZW50aWFsIjpbeyJAY29udGV4dCI6Imh0dHBzOi8vd3d3LnczLm9yZy9ucy9jcmVkZW50aWFscy92MiIsImlkIjoiZGF0YTphcHBsaWNhdGlvbi92Yy1sZCtzZC1qd3QsZXlKcmFXUWlPaUpGZUVoclFrMVhPV1p0WW10MlZqSTJObTFTY0hWUU1uTlZXVjlPWDBWWFNVNHhiR0Z3VlhwUE9ISnZJaXdpWVd4bklqb2lSVk15TlRZaWZRLmV5SmZjMlJmWVd4bklqb2ljMmhoTFRJMU5pSXNJa0JqYjI1MFpYaDBJanBiSW1oMGRIQnpPaTh2ZDNkM0xuY3pMbTl5Wnk5dWN5OWpjbVZrWlc1MGFXRnNjeTkyTWlJc0ltaDBkSEJ6T2k4dmQzZDNMbmN6TG05eVp5OXVjeTlqY21Wa1pXNTBhV0ZzY3k5bGVHRnRjR3hsY3k5Mk1pSmRMQ0pwYzNOMVpYSWlPaUpvZEhSd2N6b3ZMM1Z1YVhabGNuTnBkSGt1WlhoaGJYQnNaUzlwYzNOMVpYSnpMelUyTlRBME9TSXNJblpoYkdsa1JuSnZiU0k2SWpJd01UQXRNREV0TURGVU1UazZNak02TWpSYUlpd2lZM0psWkdWdWRHbGhiRk5qYUdWdFlTSTZleUpmYzJRaU9sc2lOV0pCZURNdGVIQm1RV3hWUzBaSk9YTnVNMmhXUTIxd1IydHJjVWx6V21NekxVeGlNek5tV21waWF5SXNJbHBqUVhaSU1EaHNkRUp5U1VwbVNXaDBPRjl0UzFCZll6TnNjRzVZTVdOSGNsbHRWRzh3WjFsQ2VUZ2lYWDBzSW1OeVpXUmxiblJwWVd4VGRXSnFaV04wSWpwN0ltUmxaM0psWlNJNmV5SnVZVzFsSWpvaVFtRmphR1ZzYjNJZ2IyWWdVMk5wWlc1alpTQmhibVFnUVhKMGN5SXNJbDl6WkNJNld5SlNUMVEzTVVsMGRUTk1ObFZYV0ZWcWJ5MW9XVmRKUWpZM2JIVlBUa1ZFVWxOQ2FHeEVWRU54VlU5UklsMTlMQ0pmYzJRaU9sc2lUVVZ1WlhObk1saFBVazVqWTNOQ1RXVmFYekUyTURKbmVUUXdVaTAwV1VKMlZsSXdlRkU0YjBZNFl5SmRmU3dpWDNOa0lqcGJJa1ZsYzJKaWF5MW1jR1p3ZDJaTU9YZE9jekZ4Y2paMGFVNDNabkV0U1hReldWTTJWM1pDYmw5aVdHOGlMQ0phYjFJMVpHUmhja2R0WmsxNU5FaHVWMHhWYWs1VVJuRlVSak5ZUmpacGRGQm5abmxHUWtoVlgzRlZJbDE5Lmd3M3BheGJrTGpwaThDVHN5UnBYS2JDN3RwVmEwcTJzV0tTRC1fZGNidVoxTHBaVjNvUThJZnpjbTJiRThSWTNmbUpnYnV5QTlnYlBMM3NRQmFUemtnIH5XeUpTZVVReFZsQjRWSEJ2Ym10UGVYWnBjemt0YTI5M0lpd2dJbWxrSWl3Z0ltaDBkSEE2THk5MWJtbDJaWEp6YVhSNUxtVjRZVzF3YkdVdlkzSmxaR1Z1ZEdsaGJITXZNVGczTWlKZH5XeUpmVmpkMWVUZDNheTFSTTNWWmQyWnBaME52V1VWQklpd2dJblI1Y0dVaUxDQmJJbFpsY21sbWFXRmliR1ZEY21Wa1pXNTBhV0ZzSWl3Z0lrVjRZVzF3YkdWQmJIVnRibWxEY21Wa1pXNTBhV0ZzSWwxZH5XeUpoYXpkcU1UbG5ZVk10UkRKTFgyaHpZM1JWWkdOUklpd2dJbWxrSWl3Z0ltaDBkSEJ6T2k4dlpYaGhiWEJzWlM1dmNtY3ZaWGhoYlhCc1pYTXZaR1ZuY21WbExtcHpiMjRpWFF+V3lKVVRqQlhhWFZaUmtoWFdrVjJaRFpJUVVKSFFTMW5JaXdnSW5SNWNHVWlMQ0FpU25OdmJsTmphR1Z0WVNKZH5XeUpWTW5Cek1reFlWRVJWYlZoM01EY3hSVkJtUlVwbklpd2dJbWxrSWl3Z0ltUnBaRHBsZUdGdGNHeGxPakV5TXlKZH5XeUpzUTA0MmVUTkVhVE5EVWs5VlgzSnVYelJFTldSbklpd2dJblI1Y0dVaUxDQWlRbUZqYUdWc2IzSkVaV2R5WldVaVhRfjtkYXRhOmFwcGxpY2F0aW9uL3ZjLWxkK3NkLWp3dCxleUpyYVdRaU9pSkZlRWhyUWsxWE9XWnRZbXQyVmpJMk5tMVNjSFZRTW5OVldWOU9YMFZYU1U0eGJHRndWWHBQT0hKdklpd2lZV3huSWpvaVJWTXlOVFlpZlEuZXlKZmMyUmZZV3huSWpvaWMyaGhMVEkxTmlJc0lrQmpiMjUwWlhoMElqcGJJbWgwZEhCek9pOHZkM2QzTG5jekxtOXlaeTl1Y3k5amNtVmtaVzUwYVdGc2N5OTJNaUlzSW1oMGRIQnpPaTh2ZDNkM0xuY3pMbTl5Wnk5dWN5OWpjbVZrWlc1MGFXRnNjeTlsZUdGdGNHeGxjeTkyTWlKZExDSnBjM04xWlhJaU9pSm9kSFJ3Y3pvdkwzVnVhWFpsY25OcGRIa3VaWGhoYlhCc1pTOXBjM04xWlhKekx6VTJOVEEwT1NJc0luWmhiR2xrUm5KdmJTSTZJakl3TVRBdE1ERXRNREZVTVRrNk1qTTZNalJhSWl3aVkzSmxaR1Z1ZEdsaGJGTmphR1Z0WVNJNmV5SmZjMlFpT2xzaU5XSkJlRE10ZUhCbVFXeFZTMFpKT1hOdU0yaFdRMjF3UjJ0cmNVbHpXbU16TFV4aU16Tm1XbXBpYXlJc0lscGpRWFpJTURoc2RFSnlTVXBtU1doME9GOXRTMUJmWXpOc2NHNVlNV05IY2xsdFZHOHdaMWxDZVRnaVhYMHNJbU55WldSbGJuUnBZV3hUZFdKcVpXTjBJanA3SW1SbFozSmxaU0k2ZXlKdVlXMWxJam9pUW1GamFHVnNiM0lnYjJZZ1UyTnBaVzVqWlNCaGJtUWdRWEowY3lJc0lsOXpaQ0k2V3lKU1QxUTNNVWwwZFROTU5sVlhXRlZxYnkxb1dWZEpRalkzYkhWUFRrVkVVbE5DYUd4RVZFTnhWVTlSSWwxOUxDSmZjMlFpT2xzaVRVVnVaWE5uTWxoUFVrNWpZM05DVFdWYVh6RTJNREpuZVRRd1VpMDBXVUoyVmxJd2VGRTRiMFk0WXlKZGZTd2lYM05rSWpwYklrVmxjMkppYXkxbWNHWndkMlpNT1hkT2N6RnhjalowYVU0M1puRXRTWFF6V1ZNMlYzWkNibDlpV0c4aUxDSmFiMUkxWkdSaGNrZHRaazE1TkVodVYweFZhazVVUm5GVVJqTllSalpwZEZCblpubEdRa2hWWDNGVklsMTkuZ3czcGF4YmtManBpOENUc3lScFhLYkM3dHBWYTBxMnNXS1NELV9kY2J1WjFMcFpWM29ROElmemNtMmJFOFJZM2ZtSmdidXlBOWdiUEwzc1FCYVR6a2cgfld5SlNlVVF4VmxCNFZIQnZibXRQZVhacGN6a3RhMjkzSWl3Z0ltbGtJaXdnSW1oMGRIQTZMeTkxYm1sMlpYSnphWFI1TG1WNFlXMXdiR1V2WTNKbFpHVnVkR2xoYkhNdk1UZzNNaUpkfld5SmZWamQxZVRkM2F5MVJNM1ZaZDJacFowTnZXVVZCSWl3Z0luUjVjR1VpTENCYklsWmxjbWxtYVdGaWJHVkRjbVZrWlc1MGFXRnNJaXdnSWtWNFlXMXdiR1ZCYkhWdGJtbERjbVZrWlc1MGFXRnNJbDFkfld5SmhhemRxTVRsbllWTXRSREpMWDJoelkzUlZaR05SSWl3Z0ltbGtJaXdnSW1oMGRIQnpPaTh2WlhoaGJYQnNaUzV2Y21jdlpYaGhiWEJzWlhNdlpHVm5jbVZsTG1wemIyNGlYUX5XeUpVVGpCWGFYVlpSa2hYV2tWMlpEWklRVUpIUVMxbklpd2dJblI1Y0dVaUxDQWlTbk52YmxOamFHVnRZU0pkfld5SlZNbkJ6TWt4WVZFUlZiVmgzTURjeFJWQm1SVXBuSWl3Z0ltbGtJaXdnSW1ScFpEcGxlR0Z0Y0d4bE9qRXlNeUpkfld5SnNRMDQyZVRORWFUTkRVazlWWDNKdVh6UkVOV1JuSWl3Z0luUjVjR1VpTENBaVFtRmphR1ZzYjNKRVpXZHlaV1VpWFF+IiwidHlwZSI6IkVudmVsb3BlZFZlcmlmaWFibGVDcmVkZW50aWFsIn1dfVhA4c9H+cu0VfS8NsItpzbB1mpvjP5y2DCxTCW+bY6/4SNPCaeP+uR+JRpJ+GzVNz7/W7ZlHoXguhgBBjWhlnhh+Q==
```

### 8.5 COSE Examples

[](#cose-examples)

These examples rely on [CBOR Diagnostic Notation](https://www.rfc-editor.org/rfc/rfc7049#section-6). Remember that all actual interchange always happens in the binary format.

[Example 24](#example-a-cose-sign-1-protected-header-for-a-verifiable-credential): A COSE Sign 1 Protected Header for a Verifiable Credential

``` {aria-busy="false"}
{                                   / Protected                     /
  1: -35,                           / Algorithm                     /
  3: application/vc,                / Content type                  /
  4: h'177f12cb...1933d554',        / Key identifier                /
  15: {                             / CWT Claims                    /
    1: urn:example:123,             / Issuer                        /
    2: urn:example:456,             / Subject                       /
  },
}
```

[Example 25](#example-a-cose-sign-1-protected-header-for-a-verifiable-presentation): A COSE Sign 1 Protected Header for a Verifiable Presentation

``` {aria-busy="false"}
{                                   / Protected                     /
  1: -35,                           / Algorithm                     /
  3: application/vp,                / Content type                  /
  4: h'177f12cb...1933d554',        / Key identifier                /
  15: {                             / CWT Claims                    /
    1: urn:example:123,             / Issuer                        /
    2: urn:example:456,             / Subject                       /
  },
}
```

[Example 26](#example-a-cose-sign-1-with-an-attached-payload): A COSE Sign 1 with an attached payload

``` {aria-busy="false"}
18(                                 / COSE Sign 1                   /
    [
      h'a4013822...3a343536',       / Protected Header              /
      {}                            / Unprotected Header            /
      h'0fbe22a0...3a009118',       / Attached payload              /
      h'09772c7f...5c4e736f'        / Signature                     /
    ]
)
```

The payload can be either a credential or presentation as described in [Securing Mechanisms](https://www.w3.org/TR/vc-data-model-2.0/#securing-mechanisms).

## A. Revision History

[](#revision-history)

*This section is non-normative.*

This section describes substantive changes that have been made to this specification.

Changes since the [First Candidate Recommendation](https://www.w3.org/TR/2024/CRD-vc-jose-cose-20240425/):

-   Updated to use the [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/) specification.
-   Changed media types from application/{vc,vp}+ld+json+{jwt,sd-jwt,cose} to application/{vc,vp}+{jwt,sd-jwt,cose}.
-   Use [COSE \"typ\" (type) Header Parameter](https://www.rfc-editor.org/rfc/rfc9596#section-2).
-   Described differentiating between secured content of different types using the `cty` header parameter.
-   Added considerations about what which parameters may be selectively disclosed.
-   Described encrypting secured credentials and presentations.
-   Said that use of the `nbf` (Not Before) claim is *NOT RECOMMENDED*.
-   Updated many examples.

## B. Acknowledgements

[](#acknowledgements)

*This section is non-normative.*

The Working Group thanks Orie Steele for his substantive intellectual and content contributions to this specification. It wouldn\'t be the same without them.

## C. References

[](#references)

### C.1 Normative references

[](#normative-references)

\[CID-1.0\]
:   [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/). Michael Jones; Manu Sporny. W3C. 15 May 2025. W3C Recommendation. URL: <https://www.w3.org/TR/cid-1.0/>

\[DID-CORE\]
:   [Decentralized Identifiers (DIDs) v1.0](https://www.w3.org/TR/did-core/). Manu Sporny; Amy Guy; Markus Sabadello; Drummond Reed. W3C. 19 July 2022. W3C Recommendation. URL: <https://www.w3.org/TR/did-core/>

\[JSON-LD11\]
:   [JSON-LD 1.1](https://www.w3.org/TR/json-ld11/). Gregg Kellogg; Pierre-Antoine Champin; Dave Longley. W3C. 16 July 2020. W3C Recommendation. URL: <https://www.w3.org/TR/json-ld11/>

\[RFC2119\]
:   [Key words for use in RFCs to Indicate Requirement Levels](https://www.rfc-editor.org/rfc/rfc2119). S. Bradner. IETF. March 1997. Best Current Practice. URL: <https://www.rfc-editor.org/rfc/rfc2119>

\[RFC2397\]
:   [The \"data\" URL scheme](https://www.rfc-editor.org/rfc/rfc2397). L. Masinter. IETF. August 1998. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc2397>

\[RFC6838\]
:   [Media Type Specifications and Registration Procedures](https://www.rfc-editor.org/rfc/rfc6838). N. Freed; J. Klensin; T. Hansen. IETF. January 2013. Best Current Practice. URL: <https://www.rfc-editor.org/rfc/rfc6838>

\[RFC7515\]
:   [JSON Web Signature (JWS)](https://www.rfc-editor.org/rfc/rfc7515). M. Jones; J. Bradley; N. Sakimura. IETF. May 2015. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7515>

\[RFC7516\]
:   [JSON Web Encryption (JWE)](https://www.rfc-editor.org/rfc/rfc7516). M. Jones; J. Hildebrand. IETF. May 2015. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7516>

\[RFC7517\]
:   [JSON Web Key (JWK)](https://www.rfc-editor.org/rfc/rfc7517). M. Jones. IETF. May 2015. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7517>

\[RFC7519\]
:   [JSON Web Token (JWT)](https://www.rfc-editor.org/rfc/rfc7519). M. Jones; J. Bradley; N. Sakimura. IETF. May 2015. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7519>

\[RFC7638\]
:   [JSON Web Key (JWK) Thumbprint](https://www.rfc-editor.org/rfc/rfc7638). M. Jones; N. Sakimura. IETF. September 2015. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7638>

\[RFC7800\]
:   [Proof-of-Possession Key Semantics for JSON Web Tokens (JWTs)](https://www.rfc-editor.org/rfc/rfc7800). M. Jones; J. Bradley; H. Tschofenig. IETF. April 2016. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7800>

\[RFC8174\]
:   [Ambiguity of Uppercase vs Lowercase in RFC 2119 Key Words](https://www.rfc-editor.org/rfc/rfc8174). B. Leiba. IETF. May 2017. Best Current Practice. URL: <https://www.rfc-editor.org/rfc/rfc8174>

\[RFC8392\]
:   [CBOR Web Token (CWT)](https://www.rfc-editor.org/rfc/rfc8392). M. Jones; E. Wahlstroem; S. Erdtman; H. Tschofenig. IETF. May 2018. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc8392>

\[RFC8747\]
:   [Proof-of-Possession Key Semantics for CBOR Web Tokens (CWTs)](https://www.rfc-editor.org/rfc/rfc8747). M. Jones; L. Seitz; G. Selander; S. Erdtman; H. Tschofenig. IETF. March 2020. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc8747>

\[RFC8949\]
:   [Concise Binary Object Representation (CBOR)](https://www.rfc-editor.org/rfc/rfc8949). C. Bormann; P. Hoffman. IETF. December 2020. Internet Standard. URL: <https://www.rfc-editor.org/rfc/rfc8949>

\[RFC9052\]
:   [CBOR Object Signing and Encryption (COSE): Structures and Process](https://www.rfc-editor.org/rfc/rfc9052). J. Schaad. IETF. August 2022. Internet Standard. URL: <https://www.rfc-editor.org/rfc/rfc9052>

\[RFC9596\]
:   [CBOR Object Signing and Encryption (COSE) \"typ\" (type) Header Parameter](https://www.rfc-editor.org/rfc/rfc9596). M.B. Jones; O. Steele. IETF. June 2024. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc9596>

\[SD-JWT\]
:   [Selective Disclosure for JWTs (SD-JWT)](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt). Daniel Fett; Kristina Yasuda; Brian Campbell. IETF. Internet-Draft. URL: <https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt>

\[URL\]
:   [URL Standard](https://url.spec.whatwg.org/). Anne van Kesteren. WHATWG. Living Standard. URL: <https://url.spec.whatwg.org/>

\[VC-DATA-MODEL-2.0\]
:   [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/). Ivan Herman; Michael Jones; Manu Sporny; Ted Thibodeau Jr; Gabe Cohen. W3C. 15 May 2025. W3C Recommendation. URL: <https://www.w3.org/TR/vc-data-model-2.0/>

### C.2 Informative references

[](#informative-references)

\[JWT\]
:   [JSON Web Token (JWT)](https://www.rfc-editor.org/rfc/rfc7519). M. Jones; J. Bradley; N. Sakimura. IETF. May 2015. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7519>

\[RFC7049\]
:   [Concise Binary Object Representation (CBOR)](https://www.rfc-editor.org/rfc/rfc7049). C. Bormann; P. Hoffman. IETF. October 2013. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7049>

\[RFC7159\]
:   [The JavaScript Object Notation (JSON) Data Interchange Format](https://www.rfc-editor.org/rfc/rfc7159). T. Bray, Ed. IETF. March 2014. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7159>

\[WCAG21\]
:   [Web Content Accessibility Guidelines (WCAG) 2.1](https://www.w3.org/TR/WCAG21/). Michael Cooper; Andrew Kirkpatrick; Joshue O\'Connor; Alastair Campbell. W3C. 12 December 2024. W3C Recommendation. URL: <https://www.w3.org/TR/WCAG21/>

[↑](#title)

[Permalink](#dfn-conforming-jws-document)

**Referenced in:**

-   [§ 1.1.1 Conformance Classes](#ref-for-dfn-conforming-jws-document-1 "§ 1.1.1 Conformance Classes") [(2)](#ref-for-dfn-conforming-jws-document-2 "Reference 2")
-   [§ 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE](#ref-for-dfn-conforming-jws-document-3 "§ 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE")
-   [§ 3.1.2 Securing JSON-LD Verifiable Presentations with JOSE](#ref-for-dfn-conforming-jws-document-4 "§ 3.1.2 Securing JSON-LD Verifiable Presentations with JOSE")
-   [§ 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT](#ref-for-dfn-conforming-jws-document-5 "§ 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT")
-   [§ 3.2.2 Securing JSON-LD Verifiable Presentations with SD-JWT](#ref-for-dfn-conforming-jws-document-6 "§ 3.2.2 Securing JSON-LD Verifiable Presentations with SD-JWT")

[Permalink](#dfn-conforming-jws-issuer-implementation)

**Referenced in:**

-   [§ 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE](#ref-for-dfn-conforming-jws-issuer-implementation-1 "§ 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE")
-   [§ 3.1.2 Securing JSON-LD Verifiable Presentations with JOSE](#ref-for-dfn-conforming-jws-issuer-implementation-2 "§ 3.1.2 Securing JSON-LD Verifiable Presentations with JOSE")

[Permalink](#dfn-conforming-jws-verifier-implementation)

**Referenced in:**

-   [§ 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE](#ref-for-dfn-conforming-jws-verifier-implementation-1 "§ 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE")
-   [§ 3.1.2 Securing JSON-LD Verifiable Presentations with JOSE](#ref-for-dfn-conforming-jws-verifier-implementation-2 "§ 3.1.2 Securing JSON-LD Verifiable Presentations with JOSE")

[Permalink](#dfn-conforming-sd-jwt-document)

**Referenced in:**

-   [§ 1.1.1 Conformance Classes](#ref-for-dfn-conforming-sd-jwt-document-1 "§ 1.1.1 Conformance Classes") [(2)](#ref-for-dfn-conforming-sd-jwt-document-2 "Reference 2")

[Permalink](#dfn-conforming-sd-jwt-issuer-implementation)

**Referenced in:**

-   [§ 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT](#ref-for-dfn-conforming-sd-jwt-issuer-implementation-1 "§ 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT")
-   [§ 3.2.2 Securing JSON-LD Verifiable Presentations with SD-JWT](#ref-for-dfn-conforming-sd-jwt-issuer-implementation-2 "§ 3.2.2 Securing JSON-LD Verifiable Presentations with SD-JWT")

[Permalink](#dfn-conforming-sd-jwt-verifier-implementation)

**Referenced in:**

-   [§ 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT](#ref-for-dfn-conforming-sd-jwt-verifier-implementation-1 "§ 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT")
-   [§ 3.2.2 Securing JSON-LD Verifiable Presentations with SD-JWT](#ref-for-dfn-conforming-sd-jwt-verifier-implementation-2 "§ 3.2.2 Securing JSON-LD Verifiable Presentations with SD-JWT")

[Permalink](#dfn-conforming-cose-document)

**Referenced in:**

-   [§ 1.1.1 Conformance Classes](#ref-for-dfn-conforming-cose-document-1 "§ 1.1.1 Conformance Classes") [(2)](#ref-for-dfn-conforming-cose-document-2 "Reference 2")
-   [§ 3.3.1 Securing JSON-LD Verifiable Credentials with COSE](#ref-for-dfn-conforming-cose-document-3 "§ 3.3.1 Securing JSON-LD Verifiable Credentials with COSE")
-   [§ 3.3.2 Securing JSON-LD Verifiable Presentations with COSE](#ref-for-dfn-conforming-cose-document-4 "§ 3.3.2 Securing JSON-LD Verifiable Presentations with COSE")

[Permalink](#dfn-conforming-cose-issuer-implementation)

**Referenced in:**

-   [§ 3.3.1 Securing JSON-LD Verifiable Credentials with COSE](#ref-for-dfn-conforming-cose-issuer-implementation-1 "§ 3.3.1 Securing JSON-LD Verifiable Credentials with COSE")
-   [§ 3.3.2 Securing JSON-LD Verifiable Presentations with COSE](#ref-for-dfn-conforming-cose-issuer-implementation-2 "§ 3.3.2 Securing JSON-LD Verifiable Presentations with COSE")

[Permalink](#dfn-conforming-cose-verifier-implementation)

**Referenced in:**

-   [§ 3.3.1 Securing JSON-LD Verifiable Credentials with COSE](#ref-for-dfn-conforming-cose-verifier-implementation-1 "§ 3.3.1 Securing JSON-LD Verifiable Credentials with COSE")
-   [§ 3.3.2 Securing JSON-LD Verifiable Presentations with COSE](#ref-for-dfn-conforming-cose-verifier-implementation-2 "§ 3.3.2 Securing JSON-LD Verifiable Presentations with COSE")

[Permalink](#dfn-public-key)

**Referenced in:**

-   Not referenced in this document.

[Permalink](#dfn-private-key)

**Referenced in:**

-   [§ 2. Terminology](#ref-for-dfn-private-key-1 "§ 2. Terminology")

[Permalink](#dfn-verifiable-credentials)

**Referenced in:**

-   [§ 1.1.2.1 JWT Format and Requirements](#ref-for-dfn-verifiable-credentials-1 "§ 1.1.2.1 JWT Format and Requirements")
-   [§ 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE](#ref-for-dfn-verifiable-credentials-2 "§ 3.1.1 Securing JSON-LD Verifiable Credentials with JOSE") [(2)](#ref-for-dfn-verifiable-credentials-3 "Reference 2") [(3)](#ref-for-dfn-verifiable-credentials-4 "Reference 3") [(4)](#ref-for-dfn-verifiable-credentials-5 "Reference 4")
-   [§ 3.1.3 JOSE Header Parameters and JWT Claims](#ref-for-dfn-verifiable-credentials-6 "§ 3.1.3 JOSE Header Parameters and JWT Claims") [(2)](#ref-for-dfn-verifiable-credentials-7 "Reference 2")
-   [§ 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT](#ref-for-dfn-verifiable-credentials-8 "§ 3.2.1 Securing JSON-LD Verifiable Credentials with SD-JWT") [(2)](#ref-for-dfn-verifiable-credentials-9 "Reference 2") [(3)](#ref-for-dfn-verifiable-credentials-10 "Reference 3") [(4)](#ref-for-dfn-verifiable-credentials-11 "Reference 4") [(5)](#ref-for-dfn-verifiable-credentials-12 "Reference 5") [(6)](#ref-for-dfn-verifiable-credentials-13 "Reference 6")
-   [§ 3.3.1 Securing JSON-LD Verifiable Credentials with COSE](#ref-for-dfn-verifiable-credentials-14 "§ 3.3.1 Securing JSON-LD Verifiable Credentials with COSE") [(2)](#ref-for-dfn-verifiable-credentials-15 "Reference 2") [(3)](#ref-for-dfn-verifiable-credentials-16 "Reference 3") [(4)](#ref-for-dfn-verifiable-credentials-17 "Reference 4") [(5)](#ref-for-dfn-verifiable-credentials-18 "Reference 5")
-   [§ 4.1.1 kid](#ref-for-dfn-verifiable-credentials-19 "§ 4.1.1 kid")
-   [§ 4.1.3 cnf](#ref-for-dfn-verifiable-credentials-20 "§ 4.1.3 cnf")
-   [§ 5.3 Verifying a Credential or Presentation Secured with COSE](#ref-for-dfn-verifiable-credentials-21 "§ 5.3 Verifying a Credential or Presentation Secured with COSE")

[Permalink](#dfn-controlled-identifier-document)

**Referenced in:**

-   [§ 4.2 Using Controlled Identifier Documents](#ref-for-dfn-controlled-identifier-document-1 "§ 4.2 Using Controlled Identifier Documents") [(2)](#ref-for-dfn-controlled-identifier-document-2 "Reference 2") [(3)](#ref-for-dfn-controlled-identifier-document-3 "Reference 3") [(4)](#ref-for-dfn-controlled-identifier-document-4 "Reference 4")
