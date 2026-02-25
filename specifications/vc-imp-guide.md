---
description: This document provides implementation guidance for Verifiable Credentials.
generator:
- HTML Tidy for HTML5 for Linux version 5.7.27
- ReSpec 24.32.2
lang: en
title: Verifiable Credentials Implementation Guidelines 1.0
viewport: width=device-width, initial-scale=1, shrink-to-fit=no
---

[![W3C](https://www.w3.org/StyleSheets/TR/2016/logos/W3C)](https://www.w3.org/)

# Verifiable Credentials Implementation Guidelines 1.0

## Implementation guidance for Verifiable Credentials

## W3C Working Group Note 24 September 2019

This version:
:   [https://www.w3.org/TR/2019/NOTE-vc-imp-guide-20190924/](https://www.w3.org/TR/2019/NOTE-vc-imp-guide-20190924/)

Latest published version:
:   <https://www.w3.org/TR/vc-imp-guide/>

Latest editor\'s draft:
:   <https://w3c.github.io/vc-imp-guide/>

Editor:
:   [Andrei Sambra](https://deiu.me/)

Authors:
:   [David Chadwick](https://www.linkedin.com/in/david-chadwick-36816395/) ([University of Kent](https://www.kent.ac.uk/))
:   [Dave Longley](https://github.com/dlongley) ([Digital Bazaar](https://digitalbazaar.com/))
:   [Manu Sporny](http://manu.sporny.org/) ([Digital Bazaar](https://digitalbazaar.com/))
:   [Oliver Terbu](mailto:oliver.terbu@consensys.net) ([Consensys](https://consensys.net/))
:   [Dmitri Zagidulin](mailto:dzagidulin@gmail.com) ([Digital Bazaar](https://digitalbazaar.com))
:   [Brent Zundel](https://www.linkedin.com/in/bzundel/) ([Evernym](https://evernym.com/))

Participate:
:   [GitHub w3c/vc-imp-guide](https://github.com/w3c/vc-imp-guide/)
:   [File a bug](https://github.com/w3c/vc-imp-guide/issues/)
:   [Commit history](https://github.com/w3c/vc-imp-guide/commits/gh-pages)
:   [Pull requests](https://github.com/w3c/vc-imp-guide/pulls/)

[Copyright](https://www.w3.org/Consortium/Legal/ipr-notice#Copyright) © 2019 [W3C](https://www.w3.org/)^®^ ([MIT](https://www.csail.mit.edu/), [ERCIM](https://www.ercim.eu/), [Keio](https://www.keio.ac.jp/), [Beihang](https://ev.buaa.edu.cn/)). W3C [liability](https://www.w3.org/Consortium/Legal/ipr-notice#Legal_Disclaimer), [trademark](https://www.w3.org/Consortium/Legal/ipr-notice#W3C_Trademarks) and [permissive document license](https://www.w3.org/Consortium/Legal/2015/copyright-software-and-document) rules apply.

------------------------------------------------------------------------

## Abstract

This document provides implementation guidance for Verifiable Credentials.

## Status of This Document

*This section describes the status of this document at the time of its publication. Other documents may supersede this document. A list of current W3C publications and the latest revision of this technical report can be found in the [W3C technical reports index](https://www.w3.org/TR/) at https://www.w3.org/TR/.*

Future versions of this document will be updated and maintained by the [Credentials Community Group](https://www.w3.org/community/credentials/). Please consult that group for the most up to date version of this document.

The work on this document was carried out under tight time constraints due to limitations of the W3C process and publishing deadlines. Under such conditions, errors are unavoidable and some of the ideas presented here are incomplete. The Working Group hopes that in the future, W3C process can be revised to better support the dynamic nature of standards work in a more consistent way across different groups.

Comments regarding this document are welcome. Please file issues directly on [GitHub](https://github.com/w3c/vc-data-model/issues/), or send them to <public-vc-comments@w3.org> ([subscribe](mailto:public-vc-comments-request@w3.org?subject=subscribe), [archives](https://lists.w3.org/Archives/Public/public-vc-comments/)).

This document was published by the [Verifiable Claims Working Group](https://www.w3.org/2017/vc/) as a Working Group Note.

[GitHub Issues](https://github.com/w3c/vc-imp-guide/issues/) are preferred for discussion of this specification. Alternatively, you can send comments to our mailing list. Please send them to <public-vc-comments@w3.org> ([archives](https://lists.w3.org/Archives/Public/public-vc-comments/)).

Publication as a Working Group Note does not imply endorsement by the W3C Membership. This is a draft document and may be updated, replaced or obsoleted by other documents at any time. It is inappropriate to cite this document as other than work in progress.

This document was produced by a group operating under the [W3C Patent Policy](https://www.w3.org/Consortium/Patent-Policy/).

This document is governed by the [1 March 2019 W3C Process Document](https://www.w3.org/2019/Process-20190301/).

## Table of Contents

1.  [1. Introduction](#introduction)
2.  [2. Identifiers](#identifiers)
3.  [3. Terminology](#terminology)
4.  [4. Verification](#verification)
    1.  [4.1 Core Data Model](#core-data-model)
    2.  [4.2 Specific Verifiable Credentials](#specific-verifiable-credentials)
    3.  [4.3 Content Integrity](#content-integrity)
        1.  [4.3.1 Hashlinks](#hashlinks)
        2.  [4.3.2 Verifiable Data Registries](#verifiable-data-registries)
5.  [5. Referencing Other Credentials](#referencing-other-credentials)
    1.  [5.1 Referencing Credentials Without Integrity Protection](#referencing-credentials-without-integrity-protection)
    2.  [5.2 Referencing Credentials With Integrity Protection](#referencing-credentials-with-integrity-protection)
    3.  [5.3 Attaching Evidence](#attaching-evidence)
6.  [6. Disputes](#disputes)
7.  [7. Presentations](#presentations)
8.  [8. Using the JWT aud claim](#using-the-jwt-aud-claim)
9.  [9. Extensions](#extensions)
    1.  [9.1 Creating New Credential Types](#creating-new-credential-types)
    2.  [9.2 Extending JWTs](#extending-jwts)
    3.  [9.3 Human Readability](#human-readability)
10. [10. Proof Formats](#proof-formats)
    1.  [10.1 Benefits of JWTs](#benefits-of-jwts)
    2.  [10.2 Benefits of JSON-LD and LD-Proofs](#benefits-of-json-ld-and-ld-proofs)
11. [11. Zero-Knowledge Proofs](#zero-knowledge-proofs)
12. [12. Progressive Trust](#progressive-trust)
    1.  [12.1 Data Minimization](#data-minimization)
    2.  [12.2 Selective Disclosure](#selective-disclosure)
    3.  [12.3 Predicates](#predicates)
    4.  [12.4 Further Techniques](#further-techniques)
13. [13. Related Specifications](#related-specifications)
    1.  [13.1 Web Authentication](#web-authentication)
14. [14. Test suite](#test-suite)
15. [A. References](#references)
    1.  [A.1 Informative references](#informative-references)

## 1. Introduction[](#introduction)

*This section is non-normative.*

This guide provides some examples and resources for implementing protocols which make use of [verifiable credentials](#dfn-verifiable-credentials), beyond those available in the core specification.

It may be useful to first familiarize yourself with the official [Use Cases document](https://www.w3.org/TR/verifiable-claims-use-cases/), which offers a concise collection of examples of [Verifiable Credentials](#dfn-verifiable-credentials) as they may appear in everyday life, and how they may be used.

The [data model specification](https://www.w3.org/TR/vc-data-model/) contains the technical details about [verifiable credentials](#dfn-verifiable-credentials). However, the data model specification does not specify any *protocols* for using [verifiable credentials](#dfn-verifiable-credentials), nor any *proof formats* or additional *identifiers* upon which such protocols may depend.

## 2. Identifiers[](#identifiers)

*This section is non-normative.*

When expressing statements about a specific entity, such as a person, product, or organization, it is often useful to have an identifier for it so that others can express statements about the same thing. The [verifiable credentials](#dfn-verifiable-credentials) [data model specification](https://www.w3.org/TR/vc-data-model//) contains numerous examples where the identifier is a [decentralized identifier](https://w3c-ccg.github.io/did-primer/), also known as a DID. An example of a DID is `did:example:123456abcdef`.

There is currently a proposed charter for a W3C [Decentralized Identifier Working Group](https://w3c-ccg.github.io/did-wg-charter/), which will put DIDs on track to become a W3C standard.

Note

As of the publication of the [verifiable credentials](#dfn-verifiable-credentials) [data model specification](https://www.w3.org/TR/vc-data-model/), DIDs are not necessary for [verifiable credentials](#dfn-verifiable-credentials) to be useful. Specifically, [verifiable credentials](#dfn-verifiable-credentials) do not depend on [DIDs](#dfn-decentralized-identifiers) and [DIDs](#dfn-decentralized-identifiers) do not depend on [verifiable credentials](#dfn-verifiable-credentials). However, it is expected that many [verifiable credentials](#dfn-verifiable-credentials) will use [DIDs](#dfn-decentralized-identifiers) and that software libraries implementing the [data model specification](https://www.w3.org/TR/vc-data-model/) will benefit from knowing how to resolve [DIDs](#dfn-decentralized-identifiers). [DID](#dfn-decentralized-identifiers)-based URLs may be used to express identifiers associated with [subjects](#dfn-subjects), [issuers](#dfn-issuers), [holders](#dfn-holders), credential status lists, cryptographic keys, and other machine-readable information associated with a [verifiable credential](#dfn-verifiable-credentials).

## 3. Terminology[](#terminology)

*This section is non-normative.*

The following terms are used to describe concepts in this specification.

claim
:   An assertion made about a [subject](#dfn-subjects).

credential
:   A set of one or more [claims](#dfn-claims) made by an [issuer](#dfn-issuers). A verifiable credential is a tamper-evident credential that has authorship that can be cryptographically verified. Verifiable credentials can be used to build [verifiable presentations](#dfn-verifiable-presentations), which can also be cryptographically verified. The [claims](#dfn-claims) in a credential can be about different [subjects](#dfn-subjects).

data minimization
:   The act of limiting the amount of shared data strictly to the minimum necessary to successfully accomplish a task or goal.

decentralized identifier
:   A portable URL-based identifier, also known as a ***DID***, associated with an [entity](#dfn-entities). These identifiers are most often used in a [verifiable credential](#dfn-verifiable-credentials) and are associated with [subjects](#dfn-subjects) such that a [verifiable credential](#dfn-verifiable-credentials) itself can be easily ported from one [repository](#dfn-credential-repository) to another without the need to reissue the [credential](#dfn-credential). An example of a DID is `did:example:123456abcdef`.

decentralized identifier document
:   Also referred to as a ***DID document***, this is a document that is accessible using a [verifiable data registry](#dfn-verifiable-data-registries) and contains information related to a specific [decentralized identifier](#dfn-decentralized-identifiers), such as the associated [repository](#dfn-credential-repository) and public key information.

derived predicate
:   A verifiable, boolean assertion about the value of another attribute in a [verifiable credential](#dfn-verifiable-credentials). These are useful in zero-knowledge-proof-style [verifiable presentations](#dfn-verifiable-presentations) because they can limit information disclosure. For example, if a [verifiable credential](#dfn-verifiable-credentials) contains an attribute for expressing a specific height in centimeters, a derived predicate might reference the height attribute in the [verifiable credential](#dfn-verifiable-credentials) demonstrating that the [issuer](#dfn-issuers) attests to a height value meeting the minimum height requirement, without actually disclosing the specific height value. For example, the [subject](#dfn-subjects) is taller than 150 centimeters.

digital signature
:   A mathematical scheme for demonstrating the authenticity of a digital message.

entity
:   A thing with distinct and independent existence, such as a person, organization, or device that performs one or more roles in the ecosystem.

graph
:   A network of information composed of [subjects](#dfn-subjects) and their relationship to other [subjects](#dfn-subjects) or data.

hashlink
:   Hashlink URLs can be used to provide content integrity for links to external resources.

holder
:   A role an [entity](#dfn-entities) might perform by possessing one or more [verifiable credentials](#dfn-verifiable-credentials) and generating [presentations](#dfn-presentations) from them. A holder is usually, but not always, a [subject](#dfn-subjects) of the [verifiable credentials](#dfn-verifiable-credentials) they are holding. Holders store their [credentials](#dfn-credential) in [credential repositories](#dfn-credential-repository).

identity
:   The means for keeping track of [entities](#dfn-entities) across contexts. Digital identities enable tracking and customization of [entity](#dfn-entities) interactions across digital contexts, typically using identifiers and attributes. Unintended distribution or use of identity information can compromise privacy. Collection and use of such information should follow the principle of [data minimization](#dfn-data-minimization).

identity provider
:   An identity provider, sometimes abbreviated as *IdP*, is a system for creating, maintaining, and managing identity information for [holders](#dfn-holders), while providing authentication services to relying party applications within a federation or distributed network. In this case the [holder](#dfn-holders) is always the [subject](#dfn-subjects). Even if the [verifiable credentials](#dfn-verifiable-credentials) are bearer [credentials](#dfn-credential), it is assumed the [verifiable credentials](#dfn-verifiable-credentials) remain with the [subject](#dfn-subjects), and if they are not, they were stolen by an attacker. This specification does not use this term unless comparing or mapping the concepts in this document to other specifications. This specification decouples the [identity provider](#dfn-identity-providers) concept into two distinct concepts: the [issuer](#dfn-issuers) and the [holder](#dfn-holders).

issuer
:   A role an [entity](#dfn-entities) can perform by asserting [claims](#dfn-claims) about one or more [subjects](#dfn-subjects), creating a [verifiable credential](#dfn-verifiable-credentials) from these [claims](#dfn-claims), and transmitting the [verifiable credential](#dfn-verifiable-credentials) to a [holder](#dfn-holders).

presentation
:   Data derived from one or more [verifiable credentials](#dfn-verifiable-credentials), issued by one or more [issuers](#dfn-issuers), that is shared with a specific [verifier](#dfn-verifier). A verifiable presentation is a tamper-evident presentation encoded in such a way that authorship of the data can be trusted after a process of cryptographic verification. Certain types of verifiable presentations might contain data that is synthesized from, but do not contain, the original [verifiable credentials](#dfn-verifiable-credentials) (for example, zero-knowledge proofs).

repository
:   A program, such as a storage vault or personal [verifiable credential](#dfn-verifiable-credentials) wallet, that stores and protects access to [holders\'](#dfn-holders) [verifiable credentials](#dfn-verifiable-credentials).

selective disclosure
:   The ability of a [holder](#dfn-holders) to make fine-grained decisions about what information to share.

subject
:   A thing about which [claims](#dfn-claims) are made.

user agent
:   A program, such as a browser or other Web client, that mediates the communication between [holders](#dfn-holders), [issuers](#dfn-issuers), and [verifiers](#dfn-verifier).

validation
:   The assurance that a [verifiable credential](#dfn-verifiable-credentials) or a [verifiable presentation](#dfn-verifiable-presentations) meets the needs of a [verifier](#dfn-verifier) and other dependent stakeholders. This specification is constrained to [verifying](#dfn-verify) [verifiable credentials](#dfn-verifiable-credentials) and [verifiable presentations](#dfn-verifiable-presentations) regardless of their usage. Validating [verifiable credentials](#dfn-verifiable-credentials) or [verifiable presentations](#dfn-verifiable-presentations) is outside the scope of this specification.

verifiable data registry
:   A role a system might perform by mediating the creation and [verification](#dfn-verify) of identifiers, keys, and other relevant data, such as [verifiable credential](#dfn-verifiable-credentials) schemas, revocation registries, issuer public keys, and so on, which might be required to use [verifiable credentials](#dfn-verifiable-credentials). Some configurations might require correlatable identifiers for [subjects](#dfn-subjects). Some registries, such as ones for UUIDs and public keys, might just act as namespaces for identifiers.

verification
:   The evaluation of whether a [verifiable credential](#dfn-verifiable-credentials) or [verifiable presentation](#dfn-verifiable-presentations) is an authentic and timely statement of the issuer or presenter, respectively. This includes checking that: the credential (or presentation) conforms to the specification; the proof method is satisfied; and, if present, the status is successfully checked.

verifier
:   The [entity](#dfn-entities) verifying a claim about a given subject.

URI
:   A Uniform Resource Identifier, as defined by \[[RFC3986](#bib-rfc3986 "Uniform Resource Identifier (URI): Generic Syntax")\].

## 4. Verification[](#verification)

*This section is non-normative.*

[Verification](#dfn-verify) is the process a [verifier](#dfn-verifier) or [holder](#dfn-holders) performs when presented with a [verifiable presentation](#dfn-verifiable-presentations) or [verifiable credential](#dfn-verifiable-credentials). [Verification](#dfn-verify) includes checking the presented item against the [core data model](https://www.w3.org/TR/vc-data-model/), and may also include validating the provided proof section and checking the item\'s status.

### 4.1 Core Data Model[](#core-data-model)

Conformant tooling that processes Verifiable Credentials will ensure that the core data model is verified when processing credentials.

### 4.2 Specific Verifiable Credentials[](#specific-verifiable-credentials)

There are many data verification languages, the following approach is one that should work for most use cases.

### 4.3 Content Integrity[](#content-integrity)

Protecting the integrity of content is an important component of verification. [Verifiers](#dfn-verifier) need to have confidence that the content they rely on to verify [credentials](#dfn-credential) doesn\'t change without their knowledge. This content may include data schemas, identifiers, public keys, etc.

There are a number of ways to provide content integrity protection. A few of these are described in greater detail below.

#### 4.3.1 Hashlinks[](#hashlinks)

[Hashlink URLs](https://tools.ietf.org/html/draft-sporny-hashlink) can be used to provide content integrity for links to external resources.

#### 4.3.2 Verifiable Data Registries[](#verifiable-data-registries)

A [verifiable data registry](#dfn-verifiable-data-registries) can also provide content integrity protection. One example of a [verifiable data registry](#dfn-verifiable-data-registries) which provides content integrity protection is a distributed ledger. This is a shared transaction record which provides mechanisms for verifying the content it stores. These mechanisms include consensus protocols, digital signatures, and verifiable data structures such as Merkle trees. These mechanisms provide cryptographic assurances that the content retrieved from the ledger has not been altered, and is complete.

## 5. Referencing Other Credentials[](#referencing-other-credentials)

Usage of [verifiable credentials](#dfn-verifiable-credentials) will often require referencing other credentials, embedding or attaching multiple credentials, or otherwise binding them together.

### 5.1 Referencing Credentials Without Integrity Protection[](#referencing-credentials-without-integrity-protection)

The simplest way for a [credential](#dfn-credential) to reference another external credential is to link to it, either directly by using its URI, or indirectly by providing a well-known ID (for example, a credential modeling an internal company Invoice may refer to its parent Purchase Order credential simply by the PO Number, relevant only within the context of this specific enterprise).

This method of linking to an external credential without using an integrity protection mechanism may be acceptable in some use cases, such as when both credentials are issued by the same entity, the verifier has a high level of trust and confidence in the issuer\'s security and auditing mechanisms, and the risk to the verifier is sufficiently low. However, implementers should keep in mind that although the credential that contains the reference may be integrity protected itself (by a cryptographic signature or a similar proof mechanism), the verifier has no way of knowing that the external credential being linked to has not been tampered with, unless the link itself has a content integrity protection mechanism built into it.

### 5.2 Referencing Credentials With Integrity Protection[](#referencing-credentials-with-integrity-protection)

The recommended way of referencing an external credential from within a [verifiable credential](#dfn-verifiable-credentials) is to use a linking mechanism that cryptographically binds the contents of the target document to the URI itself. One way to accomplish this would be to use [hashlinks](#dfn-hashlinks) or an equivalent URI scheme. Another mechanism would be to encode the full contents of the target credential in the URI itself, although this is much less commonly used, and the discussion of the practical limits of URI length are outside the scope of this document.

### 5.3 Attaching Evidence[](#attaching-evidence)

[Issuers](#dfn-issuers) wishing to attach additional supporting information to a [verifiable credential](#dfn-verifiable-credentials) are encouraged to use the [evidence](https://www.w3.org/TR/vc-data-model/#evidence) property. Note that this can be done either by embedding the relevant evidence information in the credential itself, or by referencing it (with or without an integrity protection mechanism, as previously discussed).

## 6. Disputes[](#disputes)

There are at least two different cases to consider where an [entity](#dfn-entities) wants to dispute a [credential](#dfn-credential) issued by an [issuer](#dfn-issuers):

-   A [subject](#dfn-subjects) disputes a claim made by the [issuer](#dfn-issuers). For example, the `address` property is incorrect or out of date.
-   An [entity](#dfn-entities) disputes a potentially false claim made by the [issuer](#dfn-issuers) about a different [subject](#dfn-subjects). For example, an imposter has claimed the social security number for an [entity](#dfn-entities).

The mechanism for issuing a `DisputeCredential` is the same as for a regular [credential](#dfn-credential), except that the `credentialSubject` identifier in the `DisputeCredential` property is the identifier of the disputed [credential](#dfn-credential).

For example, if a [credential](#dfn-credential) with an identifier of `https://example.org/credentials/245` is disputed, an [entity](#dfn-entities) can issue one of the [credentials](#dfn-credential) shown below. In the first example, the [subject](#dfn-subjects) might present this to the [verifier](#dfn-verifier) along with the disputed [credential](#dfn-credential). In the second example, the [entity](#dfn-entities) might publish the `DisputeCredential` in a public venue to make it known that the [credential](#dfn-credential) is disputed.

[Example 1](#example-1-a-subject-disputes-a-credential): A subject disputes a credential

``` nohighlight
{
  "@context": [
    "https://www.w3.org/2018/credentials/v1",
    "https://www.w3.org/2018/credentials/examples/v1"
  ],
  "id": "http://example.com/credentials/123",
  "type": ["VerifiableCredential", "DisputeCredential"],
  "credentialSubject": {
    "id": "http://example.com/credentials/245",
    "currentStatus": "Disputed",
    "statusReason": {
      "@value": "Address is out of date",
      "@language": "en"
    },
  },
  "issuer": "https://example.com/people#me",
  "issuanceDate": "2017-12-05T14:27:42Z",
  "proof": { ... }
}
```

[Example 2](#example-2-another-entity-disputes-a-credential): Another entity disputes a credential

``` nohighlight
{
  "@context": "https://w3id.org/credentials/v1",
  "id": "http://example.com/credentials/321",
  "type": ["VerifiableCredential", "DisputeCredential"],
  "credentialSubject": {
    "id": "http://example.com/credentials/245",
    "currentStatus": "Disputed",
    "statusReason": {
      "@value": "Credential contains disputed statements",
      "@language": "en"
    },
    "disputedClaim": {
      "id": "did:example:ebfeb1f712ebc6f1c276e12ec21",
      "address": "Is Wrong"
    }
  },
  "issuer": "https://example.com/people#me",
  "issuanceDate": "2017-12-05T14:27:42Z",
  "proof": { ... }
}
```

In the above [verifiable credential](#dfn-verifiable-credentials), the [issuer](#dfn-issuers) is claiming that the address in the disputed [verifiable credential](#dfn-verifiable-credentials) is wrong. For example, the [subject](#dfn-subjects) might wrongly be claiming to have the same address as that of the [issuer](#dfn-issuers).

Note

If a [credential](#dfn-credential) does not have an identifier, a content-addressed identifier can be used to identify the disputed [credential](#dfn-credential). Similarly, content-addressed identifiers can be used to uniquely identify individual claims.

## 7. Presentations[](#presentations)

[Verifiable credentials](#dfn-verifiable-credentials) may be presented to a [verifier](#dfn-verifier) by using a [verifiable presentation](#dfn-verifiable-presentations). A [verifiable presentation](#dfn-verifiable-presentations) can be targeted to a specific [verifier](#dfn-verifier) by using a Linked Data Proof that includes a `domain` and `challenge`. This also helps prevent a [verifier](#dfn-verifier) from reusing a [verifiable presentation](#dfn-verifiable-presentations) as their own.

The `domain` value can be any string or URI, and the `challenge` should be a randomly generated string.

The following sample [verifiable presentation](#dfn-verifiable-presentations) is for authenticating to a website, `https://example.com`.

[Example 3](#example-3-a-targeted-verifiable-presentation): A targeted verifiable presentation

``` nohighlight
{
  "@context": [
    "https://www.w3.org/2018/credentials/v1"
  ],
  "type": "VerifiablePresentation,
  "verifiableCredential": { ... },
  "proof": {
    "type": "Ed25519Signature2018",
    "created": "2019-08-13T15:09:00Z",
    "challenge": "d1b23d3...3d23d32d2",
    "domain": "https://example.com",
    "jws": "eyJhbGciOiJFZERTQSIsImI2NCI6ZmFsc2UsImNyaXQiOlsiYjY0Il19..uyW7Hv
      VOZ8QCpLJ63wHode0OdgWjsHfJ0O8d8Kfs55dMVEg3C1Z0bYUGV49s8IlTbi3eXsNvM63n
      vah79E-lAg",
    "proofPurpose": "authentication"
  }
}
```

## 8. Using the JWT aud claim[](#using-the-jwt-aud-claim)

The JWT `aud` [claim](#dfn-claims) name refers to (i.e., identifies) the intended audience of the [verifiable presentation](#dfn-verifiable-presentations) (i.e., the [verifier(s)](#dfn-verifier)). Consequently this is an alternative to the Linked Data Proof method specified above. It lets the [holder](#dfn-holders) indicate which [verifier(s)](#dfn-verifier) it allows to verify the [verifiable presentation](#dfn-verifiable-presentations). Any JWT-compliant [verifier](#dfn-verifier) that is not identified in the `aud` is required to reject the JWT (see [RFC 7519](https://tools.ietf.org/html/rfc7519)).

[RFC 7519](https://tools.ietf.org/html/rfc7519) defines `aud` as \"an array of case-sensitive strings, each containing a `StringOrURI` value\". For use in a [verifiable presentation](#dfn-verifiable-presentations), we strongly suggest that this be restricted to a single URI value, equal to the URI of the intended verifier.

The [data model specification](https://w3c.github.io/vc-data-model/) provides no guidance of how to transform this JWT [claim](#dfn-claims) into a property of the [verifiable presentation](#dfn-verifiable-presentations), nor vice versa. We strongly suggest that the `aud` JWT [claim](#dfn-claims) be mapped to the `verifier` property of the [verifiable presentation](#dfn-verifiable-presentations).

[Example 4](#example-4-an-example-of-a-targeted-verifiable-presentation-using-the-verifier-property): An example of a targeted verifiable presentation using the verifier property

``` nohighlight
{
  "@context": [
    "https://www.w3.org/2018/credentials/v1",
    "https://www.w3.org/2019/credentials/v1.1"
  ],
  "type": "VerifiablePresentation",
  "verifiableCredential": [" ... "],
  "holder": "did:example:ebfeb1f712ebc6f1c276e12ec21",
  "verifier": "https://some.verifier.com"
}
```

[Example 5](#example-5-an-example-jwt-for-a-targeted-verifiable-presentation-using-the-jwt-aud-claim): An example JWT for a targeted verifiable presentation using the JWT aud claim

``` nohighlight
{
  "iss": "did:example:ebfeb1f712ebc6f1c276e12ec21",
  "jti": "urn:uuid:3978344f-8596-4c3a-a978-8fcaba3903c5",
  "aud": "https://some.verifier.com",
  "nbf": 1541493724,
  "iat": 1541493724,
  "exp": 1573029723,
  "nonce": "343s$FSFDa-",
  "vp": {
    "@context": [
      "https://www.w3.org/2018/credentials/v1",
      "https://www.w3.org/2018/credImpGuide/v1"
    ],
    "type": "VerifiablePresentation",
    "verifiableCredential": [" ... "]
  }
}
```

## 9. Extensions[](#extensions)

*This section is non-normative.*

The Verifiable Credentials Data Model is designed around an *open world assumption*, meaning that any entity can say anything about another entity. This approach enables permissionless innovation; there is no centralized registry or authority through which an extension author must register themselves nor the specific data models and vocabularies they create.

Instead, credential data model authors are expected to use machine-readable vocabularies through the use of \[LINKED-DATA\]. This implementation guide provides examples for how to express data models using a data format that is popular with software developers and web page authors called \[JSON-LD\]. This data format provides features that enable authors to express their data models in idiomatic JSON while also ensuring that their vocabulary terms are unambigiously understood, even by software that does not implement JSON-LD processing.

The Verifiable Credentials data model also uses a graph-based data model, which allows authors to model both simple relationships that describe one or more attributes for a single entity and complex multi-entity relationships.

The rest of this section describes how to author extensions that build on the Verifiable Credentials Data Model.

### 9.1 Creating New Credential Types[](#creating-new-credential-types)

We expect the most common extensions to the Verifiable Credentials Data Model to be new credential types. Whenever someone has something to say about one or more entities and they want their authorship to be verifiable, they should use a Verifiable Credential. Sometimes there may be an existing credential type, that someone else has created, that can be reused to make the statements they want to make. However, there are often cases where new credential types are needed.

New credential types can be created by following a few steps. This guide will also walk you through creating an example new credential type. At a high level, the steps to follow are:

1.  Design the data model.
2.  Create a new JSON-LD context.
3.  Select a publishing location.
4.  Use the new JSON-LD context when issuing new credentials.

So, let\'s walk through creating a new credential type which we will call `ExampleAddressCredential`. The purpose of this credential will be to express a person\'s postal address.

#### Design the data model[](#creating-new-credential-types)

First, we must design a data model for our new credential type. We know that we will need to be able to express the basics of a postal address, things like a person\'s city, state, and zipcode. Of course, those items are quite US centric, so we should consider internationalizing those terms. But before we go further, since we\'re using \[LINKED-DATA\] vocabularies, there is a good chance that commonly known concepts may already have a vocabulary that someone else has created that we can leverage.

If we are going to use someone else\'s vocabulary, we will want to make sure it is stable and unlikely to change in any significant way. There may even be technologies that we can make use of that store immutable vocabularies that we can reference, but those are not the focus of this example. Here we will rely on the inertia that comes from a very popularly used vocabulary on the Web, schema.org. It turns out that this vocabulary has just what we need; it has already modeled a postal address and even has examples for how to express it using JSON-LD.

Please note that schema.org is developed incrementally, meaning that the definition of a term today may differ from a future definition, or even be removed. Although schema.org developers encourage using the latest release, as in the simple non-versioned schema.org URLs such as `http://schema.org/Place` in structured data applications, there are times in which more precise versioning is important. Schema.org also provides dated snapshots of each release, including both human and machine readable definitions of the schema.org core vocabulary. These are linked from the [releases page](https://schema.org/docs/releases.html). For instance, instead of the unversioned URI `http://schema.org/Place`, you might use the versioned URI `https://schema.org/version/3.9/schema-all.html#term_Place`. In addition, the `schemaVersion` property has been defined to provide a way for documents to indicate the specific intended version of schema.org\'s definitions.

Using the schema.org vocabulary and JSON-LD we can express a person\'s address like so:

[Example 6](#example-6-example-schema-org-address): Example schema.org address

``` nohighlight
{
  
  "@context": [
    "http://schema.org"
  ],
  "type": "Person",
  "address": {
    "type": "PostalAddress",
    "streetAddress": "123 Main St."
    "addressLocality": "Blacksburg",
    "addressRegion": "VA",
    "postalCode": "24060",
    "addressCountry": "US"
  }
}
```

Note the above `@context` key in the JSON. This `@context` refers to a machine-readable file (also expressed in JSON) that provides term definitions \[JSON-LD\]. A term definition maps a key or type used in the JSON, such as `address` or `PostalAddress`, to a globally unique identifier: a URL.

This ensures that when software sees the `@context` *http://schema.org*, that it will interpret the the keys and types in the JSON in a globally consistent way, without requiring developers to use full URLs in the JSON or in the code that may traverse it. As long as the software is aware of the specific `@context` used (or if it uses JSON-LD processing to transform it to some other known `@context`), then it will understand the *context* in which the JSON was written and meant to be understood. The use of `@context` also allows \[JSON-LD\] keywords such as `@type` to be aliased to the simpler `type` as is done in the above example.

Note that we could also express the JSON using full URLs, if we want to avoid using *\@context*. Here is what the example would look like if we did that:

[Example 7](#example-7-example-schema-org-address-with-full-urls): Example schema.org address with full URLs

``` nohighlight
{
  "@type": "http://schema.org/Person",
  "http://schema.org/address": {
    "@type": "http://schema.org/PostalAddress",
    "http://schema.org/streetAddress": "123 Main St."
    "http://schema.org/addressLocality": "Blacksburg",
    "http://schema.org/addressRegion": "VA",
    "http://schema.org/postalCode": "24060",
    "http://schema.org/addressCountry": "US"
  }
}
```

While this form is an acceptable way to express the information such that it is unambiguous, many software developers would prefer to use more idiomatic JSON. The use of `@context` enables idiomatic JSON without losing global consistency and without the need for a centralized registry or authority for creating extensions. Note that `@context` can also have more than one value. In this case, a JSON array is used to express multiple values, where each value references another context that defines terms. Using this mechanism we can first bring in the terms defined in the Verifiable Credentials Data Model specification and then bring in the terms defined by schema.org:

[Example 8](#example-8-example-address-credential-with-schema-org-context): Example address credential with schema.org context

``` nohighlight
{
  
  "@context": [
    "https://www.w3.org/2018/credentials/v1",
    "http://schema.org"
  ],
  ...
  "credentialSubject": {
    "type": "Person",
    "address": {
      "type": "PostalAddress",
      "streetAddress": "123 Main St."
      "addressLocality": "Blacksburg",
      "addressRegion": "VA",
      "postalCode": "24060",
      "addressCountry": "US"
    }
  },
  ...
}
```

Note, however, that each *context* might have a different definition for the same term, e.g., the JSON key `address` might map to a different URL in each *context*. By default, \[JSON-LD\] allows terms in a `@context` to be redefined using a *last term wins* order. While these changes can be safely dealt with by using JSON-LD processing, we want to lower the burden on consumers of Verifiable Credentials. We want consumer software to be able to make assumptions about the meaning of terms by only having to read and understand the string value associated with the `@context` key. We don\'t want them to have to worry about terms being redefined in unexpected ways. That way their software can inspect only the `@context` values and then be hard coded to understand the meaning of the terms.

In order to prevent term redefinition, the \[JSON-LD\] `@protected` feature must be applied to term definitions in the `@context`. All terms in the core Verifiable Credentials `@context` are already protected in this way. The only time that an existing term is allowed to be redefined is if the new definition is scoped underneath another new term that is defined in a *context*. This matches developer expectations and ensures that consumer software has strong guarantees about the semantics of the data it is processing; it can be written such that it is never confused about the definition of a term. Note that consumers must determine their own risk profile for how to handle any credentials their software processes that include terms that it does not understand.

#### Create a new JSON-LD context[](#creating-new-credential-types)

Given the above, there is at least one reason why we don\'t want to use the schema.org *context*: it is designed to be very flexible and thus does not use the `@protected` feature. There are a few additional reasons we want to create our own \[JSON-LD\] context though. First, the schema.org context does not define our new credential type: *ExampleAddressCredential*. Second, it is not served via a secure protocol (e.g., *https*); rather, it uses *http*. Note that this is less of a concern than it may seem, as it is recommended that all Verifiable Credential consumer software hard code the `@context` values it understands and not reach out to the Web to fetch them. Lastly, it is a very large context, containing many more term definitions than are necessary for our purposes.

So, we will create our own \[JSON-LD\] context that expresses just those term definitions that we need for our new credential type. Note that this does not mean that we must mint new URLs; we can still reuse the schema.org vocabulary terms. All we are doing is creating a more concise and targeted context. Here\'s what we\'ll need in our context:

[Example 9](#example-9-example-address-credential-context): Example address credential context

``` nohighlight
{
  "@version": 1.1,
  "@protected": true,

  "ExampleAddressCredential":
    "https://example.org/ExampleAddressCredential",

  "Person": {
    "@id": "http://schema.org/Person",
    "@context": {
      "@version": 1.1,
      "@protected": true,

      "address": "http://schema.org/address"
    }
  },
  "PostalAddress": {
    "@id": "http://schema.org/PostalAddress",
    "@context": {
      "@version": 1.1,
      "@protected": true,

      "streetAddress": "http://schema.org/streetAddress",
      "addressLocality": "http://schema.org/addressLocality",
      "addressRegion": "http://schema.org/addressRegion",
      "postalCode": "http://schema.org/postalCode",
      "addressCountry": "http://schema.org/addressCountry"
    }
  }
}
```

The above context defines a term for our new credential type *ExampleAddressCredential*, mapping it to the URL *https://example.org/ExampleAddressCredential*. We could have also chosen a URI like *urn:private-example:ExampleAddressCredential*, but this approach would not allow us to serve up a Web page to describe it, if we so desire. The context also defines the terms for types *Person* and *PostalAddress*, mapping them to their schema.org vocabulary URLs. Furthermore, when those types are used, it also defines protected terms for each of them via a *scoped context*, mapping terms like *address* and *streetAddress* to their schema.org vocabulary URLs. For more information on how to write a JSON-LD context or *scoped contexts*, see the \[JSON-LD\] specification.

#### Select a publishing location[](#creating-new-credential-types)

Now that we have a \[JSON-LD\] context, we must give it a URL. Technically speaking, we could just use a URI, for example, a private URN such as *urn:private-example:my-extension*. However, if we want people to be able to read and discover it on the Web, we should give it a URL like *https://example.org/example-address-credential-context/v1*.

When this URL is dereferenced, it should return *application/ld+json* by default, to allow JSON-LD processors to process the context. However, if a user agent requests *HTML*, it should return human readable text that explains, to humans, what the term definitions are and what they map to. Since we\'re reusing an existing vocabulary, schema.org, we can also simply link to the definitions of the meaning of our types and terms via their website. If we had created our own new vocabulary terms, we would describe them on our own site, ideally including machine readable Information as well.

#### Use the new JSON-LD context when issuing new credentials[](#creating-new-credential-types)

Now we\'re ready for our context to be used by anyone who wishes to issue an *ExampleAddressCredential*!

[Example 10](#example-10-example-address-credential-with-schema-org-context): Example address credential with schema.org context

``` nohighlight
{
  
  "@context": [
    "https://www.w3.org/2018/credentials/v1",
    "https://example.org/example-address-credential-context/v1"
  ],
  "id": "https://example.org/credentials/1234",
  "type": "ExampleAddressCredential",
  "issuer": "https://example.org/people#me",
  "issuanceDate": "2017-12-05T14:27:42Z",
  "credentialSubject": {
    "id": "did:example:1234",
    "type": "Person",
    "address": {
      "type": "PostalAddress",
      "streetAddress": "123 Main St."
      "addressLocality": "Blacksburg",
      "addressRegion": "VA",
      "postalCode": "24060",
      "addressCountry": "US"
    }
  },
  "proof": { ... }
}
```

Note that writing this new credential type requires permission from no one, you must only adhere to the above referenced standards.

### 9.2 Extending JWTs[](#extending-jwts)

The [Verifiable Credentials Data Model 1.0](https://www.w3.org/TR/vc-data-model/) specifies a minimal set of JWT [claim](#dfn-claims) names that are to be used to represent the properties of a [verifiable credential](#dfn-verifiable-credentials) and its `credentialSubject`. Implementers may wish to extend a [verifiable credential](#dfn-verifiable-credentials) with some properties that are new (e.g., `drivingLicenseNumber`, `mySpecialProperty` or that are already registered with IANA as JWT [claim](#dfn-claims) names (e.g., `given_name`. `phone_number_verified`.

As the [Verifiable Credentials Data Model 1.0](https://www.w3.org/TR/vc-data-model/) states, such extension properties are best placed directly in either the JWT `vc` [claim](#dfn-claims) or the `credentialSubject` property of the `vc` [claim](#dfn-claims) as appropriate, although they **MAY** be placed directly into their own JWT [claims](#dfn-claims).

If implementers wish to use JWT [claim](#dfn-claims) names for these extensions, the following steps are recommended. Note that there are three types of JWT [claim](#dfn-claims) name: public, named with a URI; private, named with a local name; and registered with IANA.

1.  First, check with IANA (https://www.iana.org/assignments/jwt/jwt.xhtml) to see if the JWT [claim](#dfn-claims) name already exists.
2.  If it does not exist, the implementer may wish to either give it a public name (i.e., a URI), give it a local name (i.e., any string), or register it with IANA.
3.  Once the JWT [claim](#dfn-claims) name exists, define encoding/decoding transformation rules to convert the [verifiable credential](#dfn-verifiable-credentials) property or `credentialSubject` property into the JWT [claim](#dfn-claims).
    -   *Encoding*: Remove the property from the [verifiable credential](#dfn-verifiable-credentials), encode it according to the defined rule, and place it in the JWT [claim](#dfn-claims)
    -   *Decoding*: Remove the value from the JWT [claim](#dfn-claims), decode it according to the defined rule, and place it in the new [verifiable credential](#dfn-verifiable-credentials) JSON object, as either a property of the [verifiable credential](#dfn-verifiable-credentials) or the `credentialSubject`, as appropriate.

### 9.3 Human Readability[](#human-readability)

The JSON-LD Context declaration mechanism is used by implementations to signal the context in which the data transmission is happening to consuming applications:

[Example 11](#example-11-use-of-context-mechanism): Use of \@context mechanism

``` nohighlight
{
  
  "@context": [
    "https://www.w3.org/2018/credentials/v1",
    "https://www.w3.org/2018/credentials/examples/v1"
  ],
  "id": "http://example.edu/credentials/1872",
  ...
```

Extension authors are urged to publish two types of information at the context URLs. The first type of information is for machines, and is the machine-readable JSON-LD Context. The second type of information is for humans, and should be an HTML document. It is suggested that the default mode of operation is to serve the machine-readable JSON-LD Context as that is the primary intended use of the URL. If content-negotiation is supported, requests for `text/html` should result in a human readable document. The human readable document should at least contain usage information for the extension, such as the expected order of URLs associated with the `@context` property, specifications that elaborate on the extension, and examples of typical usage of the extension.

## 10. Proof Formats[](#proof-formats)

*This section is non-normative.*

The [verifiable credentials](#dfn-verifiable-credentials) [data model](https://w3c.github.io/vc-data-model/) is designed to be proof format agnostic. [The specification](https://w3c.github.io/vc-data-model/) does not normatively require any particular digital proof or signature format. While the data model is the canonical representation of a [verifiable credential](#dfn-verifiable-credentials) or [verifiable presentation](#dfn-verifiable-presentations), the proving mechanisms for these are often tied to the syntax used in the transmission of the document between parties. As such, each proofing mechanism has to specify whether the validation of the proof is calculated against the state of the document as transmitted, against the transformed data model, or against another form. At the time of publication, at least two proof formats are being actively utilized by implementers, and the Working Group felt that documenting what these proof formats are and how they are being used would be beneficial to other implementers.

This guide provides tables in section [Benefits of JWTs](#pf1b) and section [Benefits of JSON-LD and LD-Proofs](#pf1b) that compare three syntax and proof format ecosystems; JSON+JWTs, JSON-LD+JWTs, and JSON-LD+LD-Proofs.

Because the Verifiable Credentials Data Model is extensible, and agnostic to any particular proof format, the specification and use of additional proof formats is supported.

### 10.1 Benefits of JWTs[](#benefits-of-jwts)

The Verifiable Credentials Data Model is designed to be compatible with a variety of existing and emerging syntaxes and digital proof formats. Each approach has benefits and drawbacks. The following table is intended to summarize a number of these native trade-offs.

The table below compares three syntax and proof format ecosystems; JSON+JWTs, JSON-LD+JWTs, and JSON-LD+LD-Proofs.

  --------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  Feature                                                                                                              JSON\             JSON‑LD\          JSON‑LD\
                                                                                                                       +\                +\                +\
                                                                                                                       JWTs              JWTs              LD‑Proofs
  -------------------------------------------------------------------------------------------------------------------- ----------------- ----------------- -----------------
  [PF1a.](#pf1a) Proof format supports Zero-Knowledge Proofs.                                                          ✓                 ✓                 ✓

  [PF2a.](#pf2a) Proof format supports arbitrary proofs such as Proof of Work, Timestamp Proofs, and Proof of Stake.   ✓                 ✓                 ✓

  [PF3a.](#pf3a) Based on existing official standards.                                                                 ✓                 ✖                 ✖

  [PF4a.](#pf4a) Designed to be small in size.                                                                         ✓                 ✖                 ✖

  [PF5a.](#pf5a) Offline support without further processing.                                                           ✓                 ✖                 ✖

  [PF6a.](#pf6a) Wide adoption in other existing standards.                                                            ✓                 ✓                 ✖

  [PF7a.](#pf7a) No type ambiguity.                                                                                    ✓                 ✖                 ✖

  [PF8a.](#pf8a) Broad library support.                                                                                ✓                 ✖                 ✖

  [PF9a.](#pf9a) Easy to understand what is signed.                                                                    ✓                 ✓                 ✖

  [PF10a.](#pf10a) Ability to be used as authn/authz token with existing systems.                                      ✓                 ✓                 ✖

  [PF11a.](#pf11a) No additional canonicalization required.                                                            ✓                 ✖                 ✖

  [PF12a.](#pf12a) No Internet PKI required.                                                                           ✓                 ✖                 ✖

  [PF13a.](#pf13a) No resolution of external documents needed.                                                         ✓                 ✖                 ✖
  --------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Note

Some of the features listed in the table above are debateable, since a feature can always be added to a particular syntax or digital proof format. The table is intended to identify native features of each combination such that no additional language design or extension is required to achieve the identified feature. Features that all languages provide, such as the ability to express numbers, have not been included for the purposes of brevity. Find more information about different proof formats in the next section.

PF1a: Proof format supports Zero-Knowledge Proofs.
:   JWTs can embed `proof` attributes for repudiable proofs such as Zero-Knowledge Proofs. In that case, the JWS will not have an signature element.

PF2a: Proof format supports arbitrary proofs such as Proof of Work, Timestamp Proofs, and Proof of Stake.
:   JWTs can embed `proof` attributes for any type of proofs such as Proof of Work, Timestamp, Proofs, and Proof Stake.

PF3a: Based on existing official standards.
:   JSON and JWT are proposed and mature IETF standards. While JSON-LD 1.0 is in REC state in W3C, JSON-LD 1.1 is still in WD state. LD-Proofs are not standardized at all.

PF4a: Designed to be small in size.
:   JSON was invented as a simple data format to be transmitted on the wire. A verifiable credential can be expressed by its attributes only, without the necessity to introduce additional meta-information such as \@context. This makes the resulting JSON+JWT credential typically also smaller in size.

PF5a: Offline support without further processing.
:   A JWT can fully describe itself without the need to retrieve or verify any external documents. JSON-LD requires the context to be queryable and requires further documents to be accessible to check the prevalent document, e.g., LD-Proof. Additional caching needs to be implemented to support offline use cases.

PF6a: Wide adoption in other existing standards.
:   JWT founds its application in many other existing standards, e.g., OAuth2, OpenID Connect. This allows for backward compatibility with existing authentication and authorization frameworks without or with only minor modifications to these legacy systems.

PF7a: No type ambiguity.
:   It is best practice that JSON data structures typically do not expect changing types of their internal attributes. JSON-LD has implicit support for compact form serialization which transforms arrays with a single element only to switch its data type. Developers writing parsers have to implement special handling of these data types, which results in more code, is more error-prone and sometimes does not allow parsers based on code generation, which rely on static types.

PF8a: Broad library support.
:   JWT and JSON due to its maturity and standardization, have a lot of open-source library support. While JSON-LD 1.0 is a standard and has support for different programming languages, it is still behind JSON which is often part of the native platform toolchain, e.g., JavaScript. For LD-Proofs, on the other hand, only a few scattered libraries exist.

PF9a: Easy to understand what is signed.
:   JWT makes it visible what is signed in contrast to LD-Proofs, e.g., LD Signatures, that are detached from the actual payload and contain links to external documents which makes it not obvious for a developer to figure out what is part of the signature.

PF10a: Ability to be used as authn/authz token with existing systems.
:   Many existing applications rely on JWT for authentication and authorization purposes. In theory, developers maintaining these applications could leverage JWT-based verifiable presentations in their current systems with minor or no modifications. LD-Proofs represents a new approach which would require more work to achieve the same result.

PF11a: No additional canonicalization required.
:   Beyond base64 URL encoding JSON and JWT don\'t require any canonicalization to be transmitted on the wire. The JWS can be calculated on any data inside of the payload. This results in less computation, less complexity, and light-weight libraries compared to JSON-LD and LD-Proofs where canonicalization is required.

PF12a: No Internet PKI required.
:   JSON-LD and LD-Proofs rely on resolving external documents, e.g., `@context`. This means that a verifiable credential system would rely on existing Internet PKI to a certain extend and cannot be fully decentralized. A JWT-based system does not need to introduce this dependency.

PF13a: No resolution of external documents needed.
:   JSON-LD and LD-Proofs require the resolution of external documents, which leads to an increased network load for the verifier of a verifiable presentation. This needs to be mitigated through caching strategies.

### 10.2 Benefits of JSON-LD and LD-Proofs[](#benefits-of-json-ld-and-ld-proofs)

The Verifiable Credentials Data Model is designed to be compatible with a variety of existing and emerging syntaxes and digital proof formats. Each approach has benefits and drawbacks. The following table is intended to summarize a number of these native trade-offs.

The table below compares three syntax and proof format ecosystems; JSON+JWTs, JSON-LD+JWTs, and JSON-LD+LD-Proofs. Readers should be aware that Zero-Knowledge Proofs are currently proposed as a sub-type of LD-Proofs and thus fall into the final column below.

  -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  Feature                                                                                                                                                                         JSON\             JSON‑LD\          JSON‑LD\
                                                                                                                                                                                  +\                +\                +\
                                                                                                                                                                                  JWTs              JWTs              LD‑Proofs
  ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- ----------------- ----------------- -----------------
  [PF1b.](#pf1b) Support for open world data modelling.                                                                                                                           ✖                 ✓                 ✓

  [PF2b.](#pf2b) Universal identifier mechanism for JSON objects via the use of URIs.                                                                                             ✖                 ✓                 ✓

  [PF3b.](#pf3b) A way to disambiguate properties shared among different JSON documents by mapping them to IRIs via a context.                                                    ✖                 ✓                 ✓

  [PF4b.](#pf4b) A mechanism to refer to data in an external document, where the data may be merged with the local document without a merge conflict in semantics or structure.   ✖                 ✓                 ✓

  [PF5b.](#pf5b) The ability to annotate strings with their language.                                                                                                             ✖                 ✓                 ✓

  [PF6b.](#pf6b) A way to associate arbitrary datatypes, such as dates and times, with arbitrary property values.                                                                 ✖                 ✓                 ✓

  [PF7b.](#pf7b) A facility to express one or more directed graphs, such as a social network, in a single document.                                                               ✖                 ✓                 ✓

  [PF8b.](#pf8b) Supports signature sets.                                                                                                                                         ✖                 ✖                 ✓

  [PF9b.](#pf9b) Embeddable in HTML such that search crawlers will index the machine-readable content.                                                                            ✖                 ✖                 ✓

  [PF10b.](#pf10b) Data on the wire is easy to debug and serialize to database systems.                                                                                           ✖                 ✖                 ✓

  [PF11b.](#pf11b) Nesting signed data does not cause data size to double for every embedding.                                                                                    ✖                 ✖                 ✓

  [PF12b.](#pf12b) Proof format supports Zero-Knowledge Proofs.                                                                                                                   ✖                 ✖                 ✓

  [PF13b.](#pf13b) Proof format supports arbitrary proofs such as Proof of Work, Timestamp Proofs, and Proof of Stake.                                                            ✖                 ✖                 ✓

  [PF14b.](#pf14b) Proofs can be expressed unmodified in other data syntaxes such as YAML, N-Quads, and CBOR.                                                                     ✖                 ✖                 ✓

  [PF15b.](#pf15b) Changing property-value ordering, or introducing whitespace does not invalidate signature.                                                                     ✖                 ✖                 ✓

  [PF16b.](#pf16b) Designed to easily support experimental signature systems.                                                                                                     ✖                 ✖                 ✓

  [PF17b.](#pf17b) Supports signature chaining.                                                                                                                                   ✖                 ✖                 ✓

  [PF18b.](#pf18b) Does not require pre-processing or post-processing.                                                                                                            ✖                 ✖                 ✓

  [PF19b.](#pf19b) Canonicalization requires only base-64 encoding.                                                                                                               ✖                 ✖                 ✓
  -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Note

Some of the features listed in the table above are debateable, since a feature can always be added to a particular syntax or digital proof format. The table is intended to identify native features of each combination such that no additional language design or extension is required to achieve the identified feature. Features that all languages provide, such as the ability to express numbers, have not been included for the purposes of brevity.

PF1b: Support for open world data modelling
:   An *open world data model* is one where any entity can make any statement about anything while simultaneously ensuring that the semantics of the statement are unambiguous. This specification is enabled by an open world data model called Linked Data. One defining characteristic of supporting an open world data model is the ability to specify the semantic context in which data is being expressed. JSON-LD provides this mechanism via the `@context` property. JSON has no such feature.

PF2b: Universal identifier mechanism for JSON objects via the use of URIs.
:   All entities in a JSON-LD document are identified either via an automatic URI, or via an explicit URI. This enables all entities in a document to be unambiguously referenced. JSON does not have a native URI type nor does it require objects to have one, making it difficult to impossible to unambiguously identify an entity expressed in JSON.

PF3b: A way to disambiguate properties shared among different JSON documents by mapping them to IRIs via a context.
:   All object properties in a JSON-LD document, such as the property \"homepage\", are either keywords or they are mapped to an IRI. This feature enables open world systems to identify the semantic meaning of the property in an unambiguous way, which enables seamless merging of data between disparate systems. JSON object properties are not mapped to IRIs, which result in ambiguities with respect to the semantic meaning of the property. For example, one JSON document might use \"title\" (meaning \"book title\") in a way that is semantically incompatible with another JSON document using \"title\" (meaning \"job title\").

PF4b: A mechanism to refer to data in an external document, where the data may be merged with the local document without a merge conflict in semantics or structure.
:   JSON-LD provides a mechanism that enables a data value to use a URL to refer to data outside of the local document. This external data may then be automatically merged with the local document without a merge conflict in semantics or structure. This feature enables a system to apply the \"follow your nose\" principle to discover a richer set of data that is associated with the local document. While a JSON document can contain pointers to external data, interpreting the pointer is often application specific and usually does not support merging the external data to construct a richer data set.

PF5b: The ability to annotate strings with their language.
:   JSON-LD enables a developer to specify the language, such as English, French, or Japanese, in which a text string is expressed via the use of language tags. JSON does not provide such a feature.

PF6b: A way to associate arbitrary datatypes, such as dates and times, with arbitrary property values.
:   JSON-LD enables a developer to specify the data type of a property value, such as Date, unsigned integer, or Temperature by specifying it in the JSON-LD Context. JSON does not provide such a feature.

PF7b: A facility to express one or more directed graphs, such as a social network, in a single document.
:   JSON-LD\'s abstract data model supports the expression of information as a directed graph of labeled nodes and edges, which enables an open world data model to be supported. JSON\'s abstract data model only supports the expression of information as a tree of unlabeled nodes and edges, which restricts the types of relationships and structures that can be natively expressed in the language.

PF8b: Supports signature sets.
:   A signature set is an unordered set of signatures over a data payload. Use cases, such as cryptographic signatures applied to a legal contract, typically require more than one signature to be associated with the contract in order to legally bind two or more parties under the terms of the contract. Linked Data Proofs, including Linked Data Signatures, natively support sets of signatures. JWTs only enable a single signature over a single payload.

PF9b: Embeddable in HTML such that search crawlers will index the machine-readable content.
:   All major search crawlers natively parse and index information expressed as JSON-LD in HTML pages. LD-Proofs enable the current data format that search engines use to be extended to support digital signatures. JWTs have no mechanism to express data in HTML pages and are currently not indexed by search crawlers.

PF10b: Data on the wire is easy to debug and serialize to database systems.
:   When developers are debugging software systems, it is beneficial for them to be able to see the data that they are operating on using common debugging tools. Similarly, it is useful to be able to serialize data from the network to a database and then from the database back out to the network using a minimal number of pre and post processing steps. LD-Proofs enable developers to use common JSON tooling without having to convert the format into a different format or structure. JWTs base-64 encode payload information, resulting in complicated pre and post processing steps to convert the data into JSON data while not destroying the digital signature. Similarly, schema-less databases, which are typically used to index JSON data, cannot index information that is expressed in an opaque base-64 encoded wrapper.

PF11b: Nesting signed data does not cause data size to double for every embedding.
:   When a JWT is encapsulated by another JWT, the entire payload must be base-64 encoded in the initial JWT, and then base-64 encoded again in the encapsulating JWT. This is often necessary when a cryptographic signature is required on a document that contains a cryptographic signature, such as when a Notary signs a document that has been signed by someone else seeking the Notary\'s services. LD-Proofs do not require base-64 encoding the signed portion of a document and instead rely on a canonicalization process that is just as secure, and that only requires the cryptographic signature to be encoded instead of the entire payload.

PF12b: Proof format supports Zero-Knowledge Proofs.
:   The LD-Proof format is capable of modifying the algorithm that generates the hash or hashes that are cryptographically signed. This cryptographic agility enables digital signature systems, such as Zero-Knowledge Proofs, to be layered on top of LD-Proofs instead of an entirely new digital signature container format to be created. JWTs are designed such that an entirely new digital signature container format will be required to support Zero-Knowledge Proofs.

PF13b: Proof format supports arbitrary proofs such as Proof of Work, Timestamp Proofs, and Proof of Stake.
:   The LD-Proof format was designed with a broader range of proof types in mind and supports cryptographic proofs beyond simple cryptographic signatures. These proof types are in common usage in systems such as decentralized ledgers and provide additional guarantees to [verifiable credentials](#dfn-verifiable-credentials), such as the ability to prove that a particular claim was made at a particular time or that a certain amount of energy was expended to generate a particular credential. The JWT format does not support arbitrary proof formats.

PF14b: Proofs can be expressed unmodified in other data syntaxes such as XML, YAML, N-Quads, and CBOR.
:   The LD-Proof format utilizes a canonicalization algorithm to generate a cryptographic hash that is used as an input to the cryptographic proof algorithm. This enables the bytes generated as the cryptographic proof to be compact and expressible in a variety of other syntaxes such as XML, YAML, N-Quads, and CBOR. Since JWTs require the use of JSON to be generated, they are inextricably tied to the JSON syntax.

PF15b: Changing property-value ordering, or introducing whitespace does not invalidate signature.
:   Since LD-Proofs utilize a canonicalization algorithm, the introduction of whitespace that does not change the meaning of the information being expressed has no effect on the final cryptographic hash over the information. This means that simple changes in whitespace formatting, such as those changes made when writing data to a schema-less database and then retrieving the same information from the same database do not cause the digital signature to fail. JWTs encode the payload using the base-64 format which is not resistant to whitespace formatting that has no effect on the information expressed. This shortcoming of JWTs make it challenging to, for example, express signed data in web pages that search crawlers index.

PF16b: Designed to easily support experimental signature systems.
:   The LD-Proof format is naturally extensible, not requiring the format to be extended in a formal international standards working group in order to prevent namespace collisions. The JWT format requires entries in a centralized registry in order to avoid naming collisions and does not support experimentation as easily as the LD-Proof format does. LD-Proof format extension is done through the decentralized publication of cryptographic suites that are guaranteed to not conflict with other LD-Proof extensions. This approach enables developers to easily experiment with new cryptographic signature mechanisms that support selective disclosure, zero-knowledge proofs, and post-quantum algorithms.

PF17b: Supports signature chaining.
:   A signature chain is an ordered set of signatures over a data payload. Use cases, such as cryptographic signatures applied to a notarized document, typically require a signature by the signing party and then an additional one by a notary to be made after the original signing party has made their signature. Linked Data Proofs, including Linked Data Signatures, natively support chains of signatures. JWTs only enable a single signature over a single payload.

PF18b: Does not require pre-processing or post-processing.
:   In order to encode a [verifiable credential](#dfn-verifiable-credentials) or a [verifiable presentation](#dfn-verifiable-presentations) in a JWT, an extra set of steps are required to convert the data to and from the JWT format. No such extra converstion step are required for [verifiable credentials](#dfn-verifiable-credentials) and [verifiable presentations](#dfn-verifiable-presentations) protected by LD-Proofs.

PF19b: Canonicalization requires only base-64 encoding.
:   The JWT format utilizes a simple base-64 encoding format to generate the cryptographic hash of the data. The encoding format for LD-Proofs requires a more complex canonicalization algorithm to generate the cryptographic hash. The benefits of the JWT approach are simplicity at the cost of encoding flexibility. The benefits of the LD-Proof approach are flexibility at the cost of implementation complexity.

## 11. Zero-Knowledge Proofs[](#zero-knowledge-proofs)

*This section is non-normative*

The Verifiable Credentials Data Model is designed to be compatible with a variety of existing and emerging digital proof formats. Each proof format has benefits and drawbacks. Many proof formats cannot selectively reveal attribute values from a verifiable credential; they can only reveal all (or none).

Zero-Knowledge Proofs (ZKPs) are a proof format that enables data-minimization features in [verifiable presentations](#dfn-verifiable-presentations), such as selective disclosure and predicate proofs.

### Full Disclosure[](#zero-knowledge-proofs)

Currently, disclosing data is an all or nothing process, whether online or off. Many digital identity systems reveal all the attributes in a digital credential. The simplest method for signing a [verifiable credential](#dfn-verifiable-credentials) signs the entire credential and when presented, fully discloses all the attributes.

Along with a full disclosure of all the attributes in a [verifiable credential](#dfn-verifiable-credentials), standard [verifiable presentations](#dfn-verifiable-presentations) reveal the actual signature. With both the data and signature in hand, a [verifier](#dfn-verifier) has a complete copy of the credential. Without care, this could enable the [verifier](#dfn-verifier) to impersonate the [holder](#dfn-holders). Also, since the signature is the same every time this credential is presented, the signature itself is a unique identifier and becomes PII (personally identifiable information).

It is also possible to fully disclose the attributes in a zero-knowledge [verifiable credential](#dfn-verifiable-credentials). Unlike non-ZKP methods, zero-knowledge methods do not reveal the actual signature; instead, they only reveal a cryptographic proof of a valid signature. Only the [holder](#dfn-holders) of the signature has the information needed to present the [credential](#dfn-credential) to a [verifier](#dfn-verifier). This means that zero-knowledge methods provide a [holder](#dfn-holders) additional protection from impersonation. Because the signature is not revealed, it also cannot be used as a unique identifier.

### Selective Disclosure[](#zero-knowledge-proofs)

Selective disclosure means that a [holder](#dfn-holders) doesn\'t have to reveal all of the attributes contained in a [verifiable credential](#dfn-verifiable-credentials). This reduces the liability of handling or holding data that it is not necessary to share or collect.

Non-ZKP methods for selective disclosure often require the credential issuer to create a unique [credential](#dfn-credential) for each individual attribute, or possible combination of attributes. This could quickly become impractical as the number of [credentials](#dfn-credential) or combinations thereof exponentially explodes. Atomic [credentials](#dfn-credential) (which only contain a single attribute) may also not guarantee that the data is properly paired when used in a [verifiable presentation](#dfn-verifiable-presentations). For example, a [holder](#dfn-holders) has two vehicle credentials, one for a 2018 Mazda with 15,000 miles and the other for a 1965 Lincoln with 350,000 miles. With atomic credentials it may be possible to claim the user has a 1965 Lincoln with 15,000 miles.

Zero-knowledge methods allow a [holder](#dfn-holders) to choose which attributes to reveal and which attributes to withhold on a case-by-case basis without involving the [issuer](#dfn-issuers). The [credential](#dfn-credential) [issuer](#dfn-issuers) only needs to provide a single [verifiable credential](#dfn-verifiable-credentials) that contains all of the attributes. Each attribute is individually incorporated into the signature. This enables two options: to reveal the attribute or to prove that you know the value of the attribute without revealing it. For example, a credential with attributes for name, birthdate, and address can be used in a presentation to reveal only your name.

Non-ZKP methods implementing selective disclosure often requires the cooperation of the [issuer](#dfn-issuers). Selective disclosure using zero-knowledge methods gives the [holder](#dfn-holders) personal control over what to reveal. A [verifiable presentation](#dfn-verifiable-presentations) based on zero-knowledge proof mechanisms only contains those attributes and associated values that are required to satisfy the presentation requirements.

### Predicate Proofs[](#zero-knowledge-proofs)

A predicate proof is a proof that answers a true-or-false question. For example, \"Are you over the age of 18?\" Using non-ZKP methods, predicate proofs must be provided by the [issuer](#dfn-issuers) as one of the attributes of a [verifiable credential](#dfn-verifiable-credentials). This means that in order for a non-ZKP [credential](#dfn-credential) to be used to prove age-over-18, it would need to contain the attribute age-over-18. This [credential](#dfn-credential) could not be used to reveal your birthdate, unless it also included a birthdate [claim](#dfn-claims). It also couldn\'t be used to prove age-over-25. To prove age-over-25, the [holder](#dfn-holders) would need to have received a [credential](#dfn-credential) with an age-over-25 [claim](#dfn-claims).

Using zero-knowledge methods, predicate proofs can be generated by the [holder](#dfn-holders) at the time of [presentation](#dfn-presentations) without [issuer](#dfn-issuers) involvement. For example, a [verifiable credential](#dfn-verifiable-credentials) with the [claim](#dfn-claims) birthdate can be used in a [verifiable presentation](#dfn-verifiable-presentations) to prove age-over-18. The same [credential](#dfn-credential) could then be used in another [presentation](#dfn-presentations) to prove age-over-25, all without revealing the [holder\'s](#dfn-holders) birthdate.

### Revocation[](#zero-knowledge-proofs)

[Verifiable credentials](#dfn-verifiable-credentials) may need to be revocable. If an [issuer](#dfn-issuers) can revoke a [credential](#dfn-credential), [verifiers](#dfn-verifier) must be able to determine a [credential\'s](#dfn-credential) revocation status.

Non-ZKP methods for checking revocation status may require the [verifier](#dfn-verifier) to directly contact the [issuer](#dfn-issuers). Less restrictive checks could be made against a list of revoked credential identifiers posted in a public registry. The [holder](#dfn-holders) is required to disclose the [credential](#dfn-credential) identifier to the [verifier](#dfn-verifier) so that it can be checked. The [verifier](#dfn-verifier) is then responsible for doing the work to check revocation.

Using zero-knowledge methods, the [credential](#dfn-credential) identifier can be checked against a list of revoked credential identifiers without revealing the identifier. This reduces the ability of network monitors to correlate a [holder\'s](#dfn-holders) credential presentations, and removes the ability of an [issuer](#dfn-issuers) to be made aware of the presentation of [verifiable credentials](#dfn-verifiable-credentials) they have issued.

### Correlation[](#zero-knowledge-proofs)

Correlation is the ability to link data from multiple interactions to a single user. Correlation can be performed by a [verifier](#dfn-verifier), by [issuers](#dfn-issuers) and [verifiers](#dfn-verifier) working together, or by a third party observing interactions on the network. Correlation is a way to collect data about a [holder](#dfn-holders) without the [holder\'s](#dfn-holders) consent or knowledge. It is also a way to deanonymize private transactions. For example, a [holder](#dfn-holders) might use a [verifiable credential](#dfn-verifiable-credentials) to prove they are authorized to vote, then submit a secret ballot. If it is possible to correlate the [holder\'s](#dfn-holders) [credential](#dfn-credential) with the secret ballot, thereby linking a specific vote to a specific voter, it would be detrimental to the democratic process and could enable retaliation.

One way to reduce correlation is through data minimization, by sharing only the information required to complete a transaction. Another way to reduce correlation is to make each interaction look unique. When interactions disclose unique identifiers, an observer can link multiple interactions to a single user. Non-ZKP methods with only a single identifier per user create correlation opportunities by embedding that identifier in multiple credentials or interactions. Zero-knowledge proofs remove this linkability between interactions.

Non-ZKP methods that reveal all attributes and use unique identifiers are completely correlatable. Zero-knowledge methods enable data minimization and allow [holders](#dfn-holders) to have trusted interactions with [verifiers](#dfn-verifier) without dependence on unique identifiers.

Although correlation can never be eliminated completely, the goal of zero-knowledge methods is to reduce the probability of correlation and to put control over the level of correlation into the hands of the [verifiable credential](#dfn-verifiable-credentials) [holder](#dfn-holders).

### Drawbacks[](#zero-knowledge-proofs)

Zero-knowledge methods are more complex than non-ZKP methods. Cryptographic engineers must understand complicated protocols and write code to create libraries that support zero-knowledge methods. System implementers can then use these libraries without being exposed to the underlying complexity, but must trust that the implementation was done correctly. They can utilize the features of selective disclosure and bring the benefits of the method to their customers without a significant increase in effort over using non-ZKP methods.

Due to the underlying complexity, zero-knowledge methods require more CPU and memory to use. This also adds to the time required to create and verify proofs. This should be considered when using less capable devices such as IOT devices or older phones.

Another drawback of zero-knowledge proofs is that they tend to be larger than simple signatures.

There is a perception that zero-knowledge methods are new and untested. Zero-knowledge methods were first introduced in 1989 as a way to guard secrets. Although they may not be well understood by the general public, they have received considerable review and scrutiny in the cryptographic community. They are considered just as secure as many common cryptographic techniques in use today.

## 12. Progressive Trust[](#progressive-trust)

*This section is non-normative.*

Entities that use [verifiable credentials](#dfn-verifiable-credentials) and [verifiable presentations](#dfn-verifiable-presentations) should follow protocols that enable progressive trust. Progressive trust refers to enabling individuals to share information about themselves only on an as needed basis, slowing building up more trust as more information is shared with another party.

Progressive trust is strongly related to the principle of data minimization, and enabled by technologies such as selective disclosure and predicate proofs. We encourage the use of progressive trust as a guiding principle for implementers as they develop protocols for [issuers](#dfn-issuers), [holders](#dfn-holders), and [verifiers](#dfn-verifier).

### 12.1 Data Minimization[](#data-minimization)

Data minimization is a principle that encourages [verifiers](#dfn-verifier) to request the minimum amount of data necessary from [holders](#dfn-holders), and for [holders](#dfn-holders) to only provide the minimum amount of data to [verifiers](#dfn-verifier). This \"minimum amount of data\" depends on the situation and may change over the course of a [holder](#dfn-holders)\'s interaction with a [verifier](#dfn-verifier).

For example, a [holder](#dfn-holders) may apply for a loan, with a bank acting as the [verifier](#dfn-verifier). There are several points at which the bank may want to determine whether the [holder](#dfn-holders) is qualified to continue in the process of applying for the loan; for instance, the bank may have a policy of only providing loans to existing account holders. A protocol that follows the principle of data minimization would allow the [holder](#dfn-holders) to reveal to the [verifier](#dfn-verifier) only that they are an existing account holder, before the bank requests any additional information, such as account balances or employment status. In this way, the applicant may progressively entrust the bank with more information, as the data needed by the bank to make its determinations is requested a piece at a time, as needed, rather than as a complete set, up front.

### 12.2 Selective Disclosure[](#selective-disclosure)

Selective disclosure is the ability of a [holder](#dfn-holders) to select some elements of a [verifiable credential](#dfn-verifiable-credentials) to share with a [verifier](#dfn-verifier), without revealing the rest. There are several different methods which support selective disclosure, we provide three examples:

-   **Atomic Credentials** - These are [verifiable credentials](#dfn-verifiable-credentials) which consist of a single claim. An [issuer](#dfn-issuers) may provide a set of atomic credentials that duplicates the claims of a standard credential. This atomicity allows a [holder](#dfn-holders) to disclose only those claims which need to be revealed to a [verifier](#dfn-verifier), rather than requiring all of the claims of a standard credential to be revealed.
-   **Selective Disclosure Signatures** - Certain signature schemes natively support selective disclosure of [verifiable credential](#dfn-verifiable-credentials) claims. One example of these is [Camenisch-Lysyanskaya signatures](https://groups.csail.mit.edu/cis/pubs/lysyanskaya/cl02b.pdf). Such Signatures allow a [holder](#dfn-holders) to disclose only those claims which need to be revealed to a [verifier](#dfn-verifier), rather than requiring all of the credential\'s claims to be revealed.
-   **Hashed Values** - With this method, the [issuer](#dfn-issuers) issues a single [verifiable credential](#dfn-verifiable-credentials) containing all the [issuer](#dfn-issuers)\'s [claims](#dfn-claims) about the subject. However, each [claim](#dfn-claims) value is created by hashing the actual value with a different nonce so that the [verifier](#dfn-verifier) cannot determine the actual value. There are several different ways of modeling this, and no standard way is currently defined. The [holder](#dfn-holders) includes the actual values of the [claims](#dfn-claims) that are to be revealed to the [verifier](#dfn-verifier) in the [verifiable presentation](#dfn-verifiable-presentations).

### 12.3 Predicates[](#predicates)

Another technique which may be used to support progressive trust is to use predicates as the values of revealed claims. Predicates allow a [holder](#dfn-holders) to provide True/False values to a [verifier](#dfn-verifier) rather than revealing claim values.

Predicate proofs may be enabled by [verifiable credential](#dfn-verifiable-credentials) [issuers](#dfn-issuers) as claims, e.g., the `credentialSubject` may include an `ageOver18` property rather than a `birthdate` property. This would allow [holders](#dfn-holders) to provide proof that they are over 18 without revealing their birthdates.

Certain signature types enable predicate proofs by allowing claims from a standard [verifiable credential](#dfn-verifiable-credentials) to be presented as predicates. For example, a [Camenisch-Lysyanskaya signed](https://groups.csail.mit.edu/cis/pubs/lysyanskaya/cl02b.pdf) [verifiable credential](#dfn-verifiable-credentials) that contains a `credentialSubject` with a `birthdate` property may be included in a [verifiable presentation](#dfn-verifiable-presentations) as a derived credential that contains an `ageOver18` property.

### 12.4 Further Techniques[](#further-techniques)

The examples provided in this section are intended to illustrate some possible mechanisms for supporting progressive trust, not provide an exhaustive or comprehensive list of all the ways progressive trust may be supported. Research in this area continues with the use of cutting-edge proof techniques such as [zk-SNARKS](https://z.cash/technology/zksnarks/) and [Bulletproofs](https://crypto.stanford.edu/bulletproofs/), as well as different signature protocols.

A draft report by the [Credentials Community Group](https://www.w3.org/community/credentials/) on [data minimization](https://w3c-ccg.github.io/data-minimization/) may also be useful reading for implementers looking to enable progressive trust.

## 13. Related Specifications[](#related-specifications)

### 13.1 Web Authentication[](#web-authentication)

The W3C [Web Authentication](https://www.w3.org/TR/webauthn/) specification extends the capabilities of in-browser web applications by enabling them to strongly authenticate users with the aid of scoped public key-based credentials. It defines the idea of [authenticators](https://www.w3.org/TR/webauthn/#authenticator), which are cryptographic entities that can generate and store public key credentials at the behest of a Relying Party, subject to user consent, mediated by the web browser to preserve user privacy.

Since the key based credentials created by [Web Authentication Level 1](https://www.w3.org/TR/webauthn/) authenticators are narrowly scoped to a particular Relying Party origin, they are unsuited (in their current form) to general purpose signature and verification operations. However, many web developers working with [Verifiable Credentials](#dfn-verifiable-credentials) have expressed interest in leveraging the Web Authentication API, since it provides a secure browser-mediated interface to crucial key management infrastructure.

The Web Authentication Working Group has agreed to address this use case in the WebAuthn Level 2 specification, and is currently working to enable the kind of [cross-origin usage](https://github.com/w3c/webauthn/issues/911) that would allow the WebAuthn API to be used for [verifiable presentations](#dfn-verifiable-presentations). For example, verifiable credential wallets could allow authentication based on verifiable presentations, by using WebAuthn authenticators to sign those presentations with challenges from verifier websites.

## 14. Test suite[](#test-suite)

*This section is non-normative.*

The W3C Verifiable Claims Working Group has produced a [test suite](https://github.com/w3c/vc-test-suite/) in order for implementers to confirm their conformance with the current specifications.

You can review the [current implementation report](https://w3c.github.io/vc-test-suite/implementations/), which contains conformance testing results for submitted implementations supporting the Verifiable Credentials Data Model specification.

## A. References[](#references)

### A.1 Informative references[](#informative-references)

\[RFC3986\]
:   [Uniform Resource Identifier (URI): Generic Syntax](https://tools.ietf.org/html/rfc3986). T. Berners-Lee; R. Fielding; L. Masinter. IETF. January 2005. Internet Standard. URL: <https://tools.ietf.org/html/rfc3986>

\[vc-data-model\]
:   [Verifiable Credentials Data Model 1.0](https://www.w3.org/TR/vc-data-model/). Manu Sporny; Grant Noble; Dave Longley; Daniel Burnett; Brent Zundel. W3C. 5 September 2019. W3C Proposed Recommendation. URL: <https://www.w3.org/TR/vc-data-model/>

[↑](#toc)
