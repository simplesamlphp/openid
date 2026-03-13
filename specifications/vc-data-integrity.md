---
color-scheme: light
description: This specification describes mechanisms for ensuring the authenticity and integrity of verifiable credentials and similar types of constrained digital documents using cryptography, especially through the use of digital signatures and related mathematical proofs.
generator: ReSpec 35.3.1
lang: en
title: Verifiable Credential Data Integrity 1.0
viewport: width=device-width, initial-scale=1, shrink-to-fit=no
---

[![W3C](https://www.w3.org/StyleSheets/TR/2021/logos/W3C)](https://www.w3.org/)

# Verifiable Credential Data Integrity 1.0

## Securing the Integrity of Verifiable Credential Data

[W3C Recommendation](https://www.w3.org/standards/types#REC) 15 May 2025

More details about this document

This version:
:   [https://www.w3.org/TR/2025/REC-vc-data-integrity-20250515/](https://www.w3.org/TR/2025/REC-vc-data-integrity-20250515/)

Latest published version:
:   <https://www.w3.org/TR/vc-data-integrity/>

Latest editor\'s draft:
:   <https://w3c.github.io/vc-data-integrity/>

History:
:   <https://www.w3.org/standards/history/vc-data-integrity/>
:   [Commit history](https://github.com/w3c/vc-data-integrity/commits/)

Implementation report:
:   <https://w3c.github.io/vc-data-integrity/implementations/>

Editors:
:   [Manu Sporny](https://www.linkedin.com/in/manusporny/) ([Digital Bazaar](https://digitalbazaar.com/))
:   [Ted Thibodeau Jr](https://github.com/TallTed) ([OpenLink Software](https://www.openlinksw.com/))
:   [Ivan Herman](https://www.w3.org/People/Ivan/)[![](data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld2JveD0iMCAwIDI1NiAyNTYiPgogICAgPHN0eWxlPgoKICAgICAgLnN0MSB7CiAgICAgICAgZmlsbDogI2ZmZjsKICAgICAgfQogICAgCjwvc3R5bGU+CiAgICA8cGF0aCBkPSJNMjU2IDEyOGMwIDcwLjctNTcuMyAxMjgtMTI4IDEyOFMwIDE5OC43IDAgMTI4IDU3LjMgMCAxMjggMHMxMjggNTcuMyAxMjggMTI4eiIgZmlsbD0iI2E2Y2UzOSI+PC9wYXRoPgogICAgPHBhdGggY2xhc3M9InN0MSIgZD0iTTg2LjMgMTg2LjJINzAuOVY3OS4xaDE1LjR2MTA3LjF6TTEwOC45IDc5LjFoNDEuNmMzOS42IDAgNTcgMjguMyA1NyA1My42IDAgMjcuNS0yMS41IDUzLjYtNTYuOCA1My42aC00MS44Vjc5LjF6bTE1LjQgOTMuM2gyNC41YzM0LjkgMCA0Mi45LTI2LjUgNDIuOS0zOS43QzE5MS43IDExMS4yIDE3OCA5MyAxNDggOTNoLTIzLjd2NzkuNHpNODguNyA1Ni44YzAgNS41LTQuNSAxMC4xLTEwLjEgMTAuMXMtMTAuMS00LjYtMTAuMS0xMC4xYzAtNS42IDQuNS0xMC4xIDEwLjEtMTAuMXMxMC4xIDQuNiAxMC4xIDEwLjF6Ij48L3BhdGg+CiAgPC9zdmc+)](https://orcid.org/0000-0003-0782-2704) ([W3C](https://www.w3.org))
:   [Dave Longley](https://digitalbazaar.com/) ([Digital Bazaar](http://digitalbazaar.com/))
:   [Greg Bernstein](https://www.grotto-networking.com/) (Invited Expert)

Authors:
:   [Dave Longley](https://digitalbazaar.com/) ([Digital Bazaar](http://digitalbazaar.com/))
:   [Manu Sporny](https://www.linkedin.com/in/manusporny/) ([Digital Bazaar](https://digitalbazaar.com/))
:   [Ivan Herman](https://www.w3.org/People/Ivan/)[![](data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld2JveD0iMCAwIDI1NiAyNTYiPgogICAgPHN0eWxlPgoKICAgICAgLnN0MSB7CiAgICAgICAgZmlsbDogI2ZmZjsKICAgICAgfQogICAgCjwvc3R5bGU+CiAgICA8cGF0aCBkPSJNMjU2IDEyOGMwIDcwLjctNTcuMyAxMjgtMTI4IDEyOFMwIDE5OC43IDAgMTI4IDU3LjMgMCAxMjggMHMxMjggNTcuMyAxMjggMTI4eiIgZmlsbD0iI2E2Y2UzOSI+PC9wYXRoPgogICAgPHBhdGggY2xhc3M9InN0MSIgZD0iTTg2LjMgMTg2LjJINzAuOVY3OS4xaDE1LjR2MTA3LjF6TTEwOC45IDc5LjFoNDEuNmMzOS42IDAgNTcgMjguMyA1NyA1My42IDAgMjcuNS0yMS41IDUzLjYtNTYuOCA1My42aC00MS44Vjc5LjF6bTE1LjQgOTMuM2gyNC41YzM0LjkgMCA0Mi45LTI2LjUgNDIuOS0zOS43QzE5MS43IDExMS4yIDE3OCA5MyAxNDggOTNoLTIzLjd2NzkuNHpNODguNyA1Ni44YzAgNS41LTQuNSAxMC4xLTEwLjEgMTAuMXMtMTAuMS00LjYtMTAuMS0xMC4xYzAtNS42IDQuNS0xMC4xIDEwLjEtMTAuMXMxMC4xIDQuNiAxMC4xIDEwLjF6Ij48L3BhdGg+CiAgPC9zdmc+)](https://orcid.org/0000-0003-0782-2704) ([W3C](https://www.w3.org))

Feedback:
:   [GitHub w3c/vc-data-integrity](https://github.com/w3c/vc-data-integrity/) ([pull requests](https://github.com/w3c/vc-data-integrity/pulls/), [new issue](https://github.com/w3c/vc-data-integrity/issues/new/choose), [open issues](https://github.com/w3c/vc-data-integrity/issues/))
:   [public-vc-wg@w3.org](mailto:public-vc-wg@w3.org?subject=%5Bvc-data-integrity%5D%20YOUR%20TOPIC%20HERE) with subject line \[vc-data-integrity\] *... message topic ...* ([archives](https://lists.w3.org/Archives/Public/public-vc-wg))

Errata:
:   [Errata exists](https://w3c.github.io/vc-data-integrity/errata.html).

Related Specifications
:   [The Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/VC-DATA-MODEL-2.0/)
:   [The Edwards Digital Signature Algorithm Cryptosuites v1.0](https://www.w3.org/TR/vc-di-eddsa/)
:   [The Elliptic Curve Digital Signature Algorithm Cryptosuites v1.0](https://www.w3.org/TR/vc-di-ecdsa/)
:   [The BBS Digital Signature Algorithm Cryptosuites v1.0](https://www.w3.org/TR/vc-di-bbs/)

See also [**translations**](https://www.w3.org/Translations/?technology=vc-data-integrity).

[Copyright](https://www.w3.org/policies/#copyright) © 2025 [World Wide Web Consortium](https://www.w3.org/). W3C^®^ [liability](https://www.w3.org/policies/#Legal_Disclaimer), [trademark](https://www.w3.org/policies/#W3C_Trademarks) and [permissive document license](https://www.w3.org/copyright/software-license-2023/ "W3C Software and Document Notice and License") rules apply.

------------------------------------------------------------------------

## Abstract

This specification describes mechanisms for ensuring the authenticity and integrity of [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) and similar types of constrained digital documents using cryptography, especially through the use of digital signatures and related mathematical proofs.

## Status of This Document

*This section describes the status of this document at the time of its publication. A list of current W3C publications and the latest revision of this technical report can be found in the [W3C standards and drafts index](https://www.w3.org/TR/) at https://www.w3.org/TR/.*

Comments regarding this specification are welcome at any time. Please file issues directly on [GitHub](https://github.com/w3c/vc-data-integrity/issues/), or send them to <public-vc-comments@w3.org> if that is not possible. ([subscribe](mailto:public-vc-comments-request@w3.org?subject=subscribe), [archives](https://lists.w3.org/Archives/Public/public-vc-comments/)).

This document was published by the [Verifiable Credentials Working Group](https://www.w3.org/groups/wg/vc) as a Recommendation using the [Recommendation track](https://www.w3.org/policies/process/20231103/#recs-and-notes).

W3C recommends the wide deployment of this specification as a standard for the Web.

A W3C Recommendation is a specification that, after extensive consensus-building, is endorsed by W3C and its Members, and has commitments from Working Group members to [royalty-free licensing](https://www.w3.org/policies/patent-policy/#sec-Requirements) for implementations.

This document was produced by a group operating under the [W3C Patent Policy](https://www.w3.org/policies/patent-policy/). W3C maintains a [public list of any patent disclosures](https://www.w3.org/groups/wg/vc/ipr) made in connection with the deliverables of the group; that page also includes instructions for disclosing a patent. An individual who has actual knowledge of a patent which the individual believes contains [Essential Claim(s)](https://www.w3.org/policies/patent-policy/#def-essential) must disclose the information in accordance with [section 6 of the W3C Patent Policy](https://www.w3.org/policies/patent-policy/#sec-Disclosure).

This document is governed by the [03 November 2023 W3C Process Document](https://www.w3.org/policies/process/20231103/).

## Table of Contents

1.  [Abstract](#abstract)
2.  [Status of This Document](#sotd)
3.  [1. Introduction](#introduction)
    1.  [1.1 How it Works](#how-it-works)
    2.  [1.2 Design Goals and Rationale](#design-goals-and-rationale)
    3.  [1.3 Conformance](#conformance)
    4.  [1.4 Terminology](#terminology)
4.  [2. Data Model](#data-model)
    1.  [2.1 Proofs](#proofs)
        1.  [2.1.1 Proof Sets](#proof-sets)
        2.  [2.1.2 Proof Chains](#proof-chains)
        3.  [2.1.3 Proof Graphs](#proof-graphs)
    2.  [2.2 Proof Purposes](#proof-purposes)
    3.  [2.3 Resource Integrity](#resource-integrity)
    4.  [2.4 Contexts and Vocabularies](#contexts-and-vocabularies)
        1.  [2.4.1 Validating Contexts](#validating-contexts)
        2.  [2.4.2 Context Injection](#context-injection)
        3.  [2.4.3 Securing Data Losslessly](#securing-data-losslessly)
        4.  [2.4.4 Datatypes](#datatypes)
    5.  [2.5 Relationship to Linked Data](#relationship-to-linked-data)
    6.  [2.6 Relationship to Verifiable Credentials](#relationship-to-verifiable-credentials)
5.  [3. Cryptographic Suites](#cryptographic-suites)
    1.  [3.1 DataIntegrityProof](#dataintegrityproof)
6.  [4. Algorithms](#algorithms)
    1.  [4.1 Processing Model](#processing-model)
    2.  [4.2 Add Proof](#add-proof)
    3.  [4.3 Add Proof Set/Chain](#add-proof-set-chain)
    4.  [4.4 Verify Proof](#verify-proof)
    5.  [4.5 Verify Proof Sets and Chains](#verify-proof-sets-and-chains)
    6.  [4.6 Context Validation](#context-validation)
    7.  [4.7 Processing Errors](#processing-errors)
7.  [5. Security Considerations](#security-considerations)
    1.  [5.1 Versioning Cryptography Suites](#versioning-cryptography-suites)
    2.  [5.2 Protecting Application Developers](#protecting-application-developers)
    3.  [5.3 Conventions for Naming Cryptography Suites](#conventions-for-naming-cryptography-suites)
    4.  [5.4 Agility and Layering](#agility-and-layering)
    5.  [5.5 Safer Abstractions](#safer-abstractions)
    6.  [5.6 Transformations](#transformations)
    7.  [5.7 Protected Information](#protected-information)
    8.  [5.8 Data Opacity](#data-opacity)
    9.  [5.9 Verification Method Binding](#verification-method-binding)
    10. [5.10 Verification Relationship Validation](#verification-relationship-validation)
    11. [5.11 Proof Purpose Validation](#proof-purpose-validation)
    12. [5.12 Canonicalization Method Security](#canonicalization-method-security)
    13. [5.13 Canonicalization Method Correctness](#canonicalization-method-correctness)
    14. [5.14 Network Requests](#network-requests)
    15. [5.15 Other Security Considerations](#other-security-considerations)
8.  [6. Privacy Considerations](#privacy-considerations)
    1.  [6.1 Unlinkability](#unlinkability)
    2.  [6.2 Selective Disclosure](#selective-disclosure)
    3.  [6.3 Previous Proofs](#previous-proofs)
    4.  [6.4 Fingerprinting Network Requests](#fingerprinting-network-requests)
    5.  [6.5 Canonicalization Method Privacy](#canonicalization-method-privacy)
    6.  [6.6 Other Privacy Considerations](#other-privacy-considerations)
9.  [7. Accessibility Considerations](#accessibility-considerations)
    1.  [7.1 Presenting Time Values](#presenting-time-values)
10. [A. Understanding Proof Sets and Proof Chains](#understanding-proof-sets-and-proof-chains)
11. [B. Revision History](#revision-history)
12. [C. Acknowledgements](#acknowledgements)
13. [D. References](#references)
    1.  [D.1 Normative references](#normative-references)
    2.  [D.2 Informative references](#informative-references)

## 1. Introduction

[](#introduction)

*This section is non-normative.*

This specification describes mechanisms for ensuring the authenticity and integrity of [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) and similar types of constrained digital documents using cryptography, especially through the use of digital signatures and related mathematical proofs. Cryptographic proofs enable functionality that is useful to implementors of distributed systems. For example, proofs can be used to:

-   Make statements that can be shared without loss of trust, because their authorship can be verified by a third party, for example as part of a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\], [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation), or social media post.
-   Authenticate as an entity identified by a particular identifier, for example, as the subject identified by a Decentralized Identifier (DID) \[[DID](#bib-did "Decentralized Identifiers (DIDs) v1.0")\].
-   Delegate authorization for actions in a remote execution environment, via mechanisms such as Authorization Capabilities \[[ZCAP](#bib-zcap "Authorization Capabilities for Linked Data")\].
-   Agree to contracts where the agreement can be verified by another party.
-   Additionally, many proofs that are based on cryptographic digital signatures provide the benefit of integrity protection, making documents and data tamper-evident.

### 1.1 How it Works

[](#how-it-works)

*This section is non-normative.*

The operation of Data Integrity is conceptually simple. To create a cryptographic proof, the following steps are performed: 1) Transformation, 2) Hashing, and 3) Proof Generation.

![ Diagram showing the three steps involved in the creation of a cryptographic proof. The diagram is laid out left to right with a blue box labeled \'Data\' on the far left. The blue box travels, left to right, through three subsequent yellow arrows labeled \'Transform Data\', \'Hash Data\', and \'Generate Proof\'. The resulting blue box at the far right is labeled \'Data with Proof\'. ](diagrams/hiw-creation.svg)

[Figure 1](#hiw-creation) To create a cryptographic proof, data is transformed, hashed, and cryptographically protected.

Transformation is a process described by a transformation algorithm that takes input data and prepares it for the hashing process. One example of a possible transformation is to take a record of people\'s names that attended a meeting, sort the list alphabetically by the individual\'s family name, and rewrite the names on a piece of paper, one per line, in sorted order. Examples of transformations include [canonicalization](https://en.wikipedia.org/wiki/Canonicalization) and [binary-to-text](https://en.wikipedia.org/wiki/Binary-to-text_encoding) encoding.

Hashing is a process described by a hashing algorithm that calculates an identifier for the transformed data using a [cryptographic hash function](https://en.wikipedia.org/wiki/Cryptographic_hash_function). This process is conceptually similar to how a phone address book functions, where one takes a person\'s name (the input data) and maps that name to that individual\'s phone number (the hash). Examples of cryptographic hash functions include [SHA-3](https://en.wikipedia.org/wiki/Cryptographic_hash_function#SHA-3) and [BLAKE-3](https://en.wikipedia.org/wiki/Cryptographic_hash_function#BLAKE3).

Proof Generation is a process described by a proof serialization algorithm that calculates a value that protects the integrity of the input data from modification or otherwise proves a certain desired threshold of trust. This process is conceptually similar to the way a wax seal can be used on an envelope containing a letter to establish trust in the sender and show that the letter has not been tampered with in transit. Examples of proof serialization functions include [digital signatures](https://en.wikipedia.org/wiki/Digital_signature), [proofs of stake](https://en.wikipedia.org/wiki/Proof_of_stake), and [proofs of knowledge](https://en.wikipedia.org/wiki/Proof_of_knowledge), in general.

To verify a cryptographic proof, the following steps are performed: 1) Transformation, 2) Hashing, and 3) Proof Verification.

![ Diagram showing the three steps involved in the verification of a cryptographic proof. The diagram is laid out left to right with a blue box labeled \'Data with Proof\' on the far left. The blue box travels, left to right, through three subsequent yellow arrows labeled \'Transform Data\', \'Hash Data\', and \'Verify Proof\'. The resulting blue box at the far right is labeled \'Data with Proof\'. ](diagrams/hiw-verification.svg)

[Figure 2](#hiw-verification) To verify a cryptographic proof, data is transformed, hashed, and checked for correctness.

During verification, the [transformation](#dfn-transformation) and [hashing](#dfn-hashing) steps are conceptually the same as described above.

Proof Verification is a process that is described by a proof verification algorithm that applies a cryptographic proof verification function to see if the input data can be trusted. Possible proof verification functions include [digital signatures](https://en.wikipedia.org/wiki/Digital_signature), [proofs of stake](https://en.wikipedia.org/wiki/Proof_of_stake), and [proofs of knowledge](https://en.wikipedia.org/wiki/Proof_of_knowledge), in general.

This specification details how cryptographic software architects and implementers can package these processes together into things called [cryptographic suites](#dfn-cryptosuite) and provide them to application developers for the purposes of protecting the integrity of application data in transit and at rest.

### 1.2 Design Goals and Rationale

[](#design-goals-and-rationale)

*This section is non-normative.*

This specification optimizes for the following design goals:

Simplicity
:   The technology is designed to be easy to use for application developers, without requiring significant training in cryptography. It optimizes for the following priority of constituencies: application developers over cryptographic suite implementers, over cryptographic suite designers, over cryptographic algorithm specification authors. The solution focuses on sensible defaults to prevent the selection of ineffective protection mechanisms. See section [5.2 Protecting Application Developers](#protecting-application-developers) and [5.1 Versioning Cryptography Suites](#versioning-cryptography-suites) for further details.

Composability
:   A number of historical digital signature mechanisms have had monolithic designs which limited use cases by combining data transformation, syntax, digital signature, and serialization into a single specification. This specification layers each component such that a broader range of use cases are enabled, including generalized selective disclosure and serialization-agnostic signatures. See section [5.6 Transformations](#transformations), section [5.8 Data Opacity](#data-opacity), and [5.1 Versioning Cryptography Suites](#versioning-cryptography-suites) for further rationale.

Resilience
:   Since digital proof mechanisms might be compromised without warning due to technological advancements, it is important that [cryptographic suites](#dfn-cryptosuite) provide multiple layers of protection and can be rapidly upgraded. This specification provides for both algorithmic agility and cryptographic layering, while still keeping the digital proof format easy for developers to understand and use. See section [5.4 Agility and Layering](#agility-and-layering) to understand the particulars.

Progressive Extensibility
:   Creating and deploying new cryptographic protection mechanisms is designed to be a deliberate, iterative, and careful process that acknowledges that extension happens in phases from experimentation, to implementation, to standardization. This specification strives to balance the need for an increase in the rate of innovation in cryptography with the need for stable production-grade cryptography suites. See section [3. Cryptographic Suites](#cryptographic-suites) for instructions on establishing new types of cryptographic proofs.

Serialization Flexibility
:   Cryptographic proofs can be serialized in many different but equivalent ways and have often been tightly bound to the original document syntax. This specification enables one to create cryptographic proofs that are not bound to the original document syntax, which enables more advanced use cases such as being able to use a single digital signature across a variety of serialization syntaxes such as JSON and CBOR without the need to regenerate the cryptographic proof. See section [5.6 Transformations](#transformations) for an explanation of the benefits of such an approach.

Note: Application of technology to broader use cases

While this specification primarily focuses on [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential), the design of this technology is generalized, such that it can be used for other use cases. In these instances, implementers are expected to perform their own due diligence and expert review as to the applicability of the technology to their use case.

### 1.3 Conformance

[](#conformance)

As well as sections marked as non-normative, all authoring guidelines, diagrams, examples, and notes in this specification are non-normative. Everything else in this specification is normative.

The key words *MAY*, *MUST*, *MUST NOT*, *OPTIONAL*, and *SHOULD* in this document are to be interpreted as described in [BCP 14](https://www.rfc-editor.org/info/bcp14) \[[RFC2119](#bib-rfc2119 "Key words for use in RFCs to Indicate Requirement Levels")\] \[[RFC8174](#bib-rfc8174 "Ambiguity of Uppercase vs Lowercase in RFC 2119 Key Words")\] when, and only when, they appear in all capitals, as shown here.

A conforming secured document is any [byte sequence](https://infra.spec.whatwg.org/#byte-sequence) that can be converted to a [JSON document](https://infra.spec.whatwg.org/#parse-json-bytes-to-a-javascript-value) that follows the relevant normative requirements in Sections [2.1 Proofs](#proofs), [2.2 Proof Purposes](#proof-purposes), [2.3 Resource Integrity](#resource-integrity), [2.4 Contexts and Vocabularies](#contexts-and-vocabularies), and [3.1 DataIntegrityProof](#dataintegrityproof).

A conforming cryptographic suite specification is any specification that follows the relevant normative requirements in Section [3. Cryptographic Suites](#cryptographic-suites).

A conforming processor is any algorithm realized as software and/or hardware that generates and/or consumes a [conforming secured document](#dfn-conforming-secured-document) according to the relevant normative statements in Section [4. Algorithms](#algorithms). Conforming processors *MUST* produce errors when non-conforming documents are consumed.

### 1.4 Terminology

[](#terminology)

This section defines the terms used in this specification. A link to these terms is included whenever they appear in this specification.

controlled identifier document
:   A document that contains public cryptographic material as defined in the [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/) specification.

cryptographic suite
:   A specification defining the usage of specific cryptographic primitives in order to achieve a particular security goal. These documents are often used to specify [verification methods](#dfn-verification-method), digital signature types, their identifiers, and other related properties. See Section [3. Cryptographic Suites](#cryptographic-suites) for further detail.

data integrity proof
:   A set of attributes that represent a digital proof and the parameters required to verify it. A **digital signature** is a type of data integrity proof.

proof purpose
:   The specific intent for the proof; the reason why an entity created it. The protected declaration acts as a safeguard to prevent the proof from being misused for a purpose other than the one it was intended for.

public key
:   Cryptographic material that can be used to verify digital proofs created with a corresponding [secret key](#dfn-secret-key).

secret key
:   Cryptographic material, sometimes referred to as a **private key**, that is not to be shared with anyone, and is used to generate digital proofs and/or digital signatures.

verification method

:   A set of parameters that can be used together with a process to independently verify a proof. For example, a cryptographic [public key](#dfn-public-key) can be used as a verification method with respect to a digital signature; in such usage, it verifies that the signer possessed the associated cryptographic [secret key](#dfn-secret-key).

    \"Verification\" and \"proof\" in this definition are intended to apply broadly. For example, a cryptographic public key might be used during Diffie-Hellman key exchange to negotiate a shared symmetric key for encryption. This guarantees the integrity of the key agreement process. It is thus another type of verification method, even though descriptions of the process might not use the words \"verification\" or \"proof.\"

verifier
:   A role an entity performs by receiving data containing one or more [data integrity proofs](#dfn-data-integrity-proof) and then determining whether or not the proof is legitimate.

## 2. Data Model

[](#data-model)

This section specifies the data model that is used for expressing [data integrity proofs](#dfn-data-integrity-proof) and the integrity of related resources.

All of the data model properties and types in this specification map to URLs. The vocabulary where these URLs are defined is the [The Security Vocabulary](https://w3id.org/security). The explicit mechanism that is used to perform this mapping in a secured document is the `@context` property.

The mapping mechanism is defined by [JSON-LD 1.1](https://www.w3.org/TR/json-ld11/). To ensure a document can be interoperably consumed without the use of a JSON-LD library, document authors are advised to ensure that domain experts have 1) specified the expected order for all values associated with a `@context` property, 2) published cryptographic hashes for each `@context` file, and 3) deemed that the contents of each `@context` file are appropriate for the intended use case.

When a document is processed by a processor that does not utilize JSON-LD libraries, and there is a requirement to use the same semantics as those used in a JSON-LD environment, implementers are advised to 1) enforce the expected order and values in the `@context` property, and 2) ensure that each `@context` file matches the known cryptographic hashes for each `@context` file.

Using static, versioned, `@context` files with published cryptographic hashes in conjunction with JSON Schema is one acceptable approach to implementing the mechanisms described above, which ensures proper term identification, typing, and order, when a processor that does not utilize a JSON-LD library is used. See the section on [Type-Specific Processing](https://www.w3.org/TR/vc-data-model-2.0/#type-specific-credential-processing) in [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/) for more details.

### 2.1 Proofs

[](#proofs)

A [data integrity proof](#dfn-data-integrity-proof) provides information about the proof mechanism, parameters required to verify that proof, and the proof value itself. All of this information is provided using Linked Data vocabularies such as [The Security Vocabulary](https://w3id.org/security).

When expressing a [data integrity proof](#dfn-data-integrity-proof) on an object, a `proof` property *MUST* be used. The `proof` property within a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) is a [named graph](#dfn-named-graph). If present, its value *MUST* be either a single object, or an unordered set of objects, expressed using the properties below:

id
:   An optional identifier for the proof, which *MUST* be a URL \[[URL](#bib-url "URL Standard")\], such as a UUID as a URN (`urn:uuid:6a1676b8-b51f-11ed-937b-d76685a20ff5`). The usage of this property is further explained in Section [2.1.2 Proof Chains](#proof-chains).

type
:   The specific type of proof *MUST* be specified as a [string](https://infra.spec.whatwg.org/#string) that maps to a URL \[[URL](#bib-url "URL Standard")\]. Examples of proof types include `DataIntegrityProof` and `Ed25519Signature2020`. Proof types determine what other fields are required to secure and verify the proof.

proofPurpose
:   The reason the proof was created *MUST* be specified as a [string](https://infra.spec.whatwg.org/#string) that maps to a URL \[[URL](#bib-url "URL Standard")\]. The proof purpose acts as a safeguard to prevent the proof from being misused by being applied to a purpose other than the one that was intended. For example, without this value the creator of a proof could be tricked into using cryptographic material typically used to create a Verifiable Credential (`assertionMethod`) during a login process (`authentication`) which would then result in the creation of a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) they never meant to create instead of the intended action, which was to merely log in to a website.

verificationMethod
:   A verification method is the means and information needed to verify the proof. If included, the value *MUST* be a [string](https://infra.spec.whatwg.org/#string) that maps to a \[[URL](#bib-url "URL Standard")\]. Inclusion of `verificationMethod` is *OPTIONAL*, but if it is not included, other properties such as `cryptosuite` might provide a mechanism by which to obtain the information necessary to verify the proof. Note that when `verificationMethod` is expressed in a [data integrity proof](#dfn-data-integrity-proof), the value points to the actual location of the data; that is, the `verificationMethod` references, via a URL, the location of the [public key](#dfn-public-key) that can be used to verify the proof. This [public key](#dfn-public-key) data is stored in a [controlled identifier document](#dfn-controlled-identifier-document), which contains a full description of the verification method.

cryptosuite
:   An identifier for the cryptographic suite that can be used to verify the proof. See [3. Cryptographic Suites](#cryptographic-suites) for more information. If the proof `type` is `DataIntegrityProof`, `cryptosuite` *MUST* be specified; otherwise, `cryptosuite` *MAY* be specified. If specified, its value *MUST* be a string.

created
:   The date and time the proof was created is *OPTIONAL* and, if included, *MUST* be specified as an \[[XMLSCHEMA11-2](#bib-xmlschema11-2 "W3C XML Schema Definition Language (XSD) 1.1 Part 2: Datatypes")\] `dateTimeStamp` string, either in Universal Coordinated Time (UTC), denoted by a Z at the end of the value, or with a time zone offset relative to UTC. A [conforming processor](#dfn-conforming-processor) *MAY* chose to consume time values that were incorrectly serialized without an offset. Incorrectly serialized time values without an offset are to be interpreted as UTC.

expires
:   The `expires` property is *OPTIONAL* and, if present, specifies when the proof expires. If present, it *MUST* be an \[[XMLSCHEMA11-2](#bib-xmlschema11-2 "W3C XML Schema Definition Language (XSD) 1.1 Part 2: Datatypes")\] `dateTimeStamp` string, either in Universal Coordinated Time (UTC), denoted by a Z at the end of the value, or with a time zone offset relative to UTC. A [conforming processor](#dfn-conforming-processor) *MAY* chose to consume time values that were incorrectly serialized without an offset. Incorrectly serialized time values without an offset are to be interpreted as UTC.

domain
:   The `domain` property is *OPTIONAL*. It conveys one or more security domains in which the proof is meant to be used. If specified, the associated value *MUST* be either a string, or an unordered set of strings. A verifier *SHOULD* use the value to ensure that the proof was intended to be used in the security domain in which the verifier is operating. The specification of the `domain` parameter is useful in challenge-response protocols where the verifier is operating from within a security domain known to the creator of the proof. Example domain values include: `domain.example` (DNS domain), `https://domain.example:8443` (Web origin), `mycorp-intranet` (bespoke text string), and `b31d37d4-dd59-47d3-9dd8-c973da43b63a` (UUID).

challenge
:   A [string](https://infra.spec.whatwg.org/#string) value that *SHOULD* be included in a proof if a `domain` is specified. The value is used once for a particular [domain](https://url.spec.whatwg.org/#concept-domain) and window of time. This value is used to mitigate replay attacks. Examples of a challenge value include: `1235abcd6789`, `79d34551-ae81-44ae-823b-6dadbab9ebd4`, and `ruby`.

proofValue
:   A [string](https://infra.spec.whatwg.org/#string) value that expresses base-encoded binary data necessary to verify the digital proof using the `verificationMethod` specified. The value *MUST* use a header and encoding as described in Section [2.4 Multibase](https://www.w3.org/TR/cid-1.0/#multibase-0) of the [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/) specification to express the binary data. The contents of this value are determined by a specific cryptosuite and set to the *proof value* generated by the [Add Proof Algorithm](#add-proof) for that cryptosuite. Alternative properties with different encodings specified by the cryptosuite *MAY* be used, instead of this property, to encode the data necessary to verify the digital proof.

previousProof
:   The `previousProof` property is *OPTIONAL*. If present, it *MUST* be a string value or an unordered list of string values. Each value identifies another [data integrity proof](#dfn-data-integrity-proof), all of which *MUST* also verify for the current proof to be considered verified. This property is used in Section [2.1.2 Proof Chains](#proof-chains).

nonce
:   An *OPTIONAL* [string](https://infra.spec.whatwg.org/#string) value supplied by the proof creator. One use of this field is to increase privacy by decreasing linkability that is the result of deterministically generated signatures.

A proof can be added to a JSON document like the following:

[Example 1](#example-a-simple-json-data-document): A simple JSON data document

``` nohighlight
{
  "myWebsite": "https://hello.world.example/"
};
```

The following proof secures the document above using the `eddsa-jcs-2022` cryptography suite \[[DI-EDDSA](#bib-di-eddsa "The Edwards Digital Signature Algorithm Cryptosuites v1.0")\], which produces a verifiable digital proof by transforming the input data using the JSON Canonicalization Scheme (JCS) \[[RFC8785](#bib-rfc8785 "JSON Canonicalization Scheme (JCS)")\] and then digitally signing it using an Edwards Digital Signature Algorithm (EdDSA).

[Example 2](#example-a-simple-signed-json-data-document): A simple signed JSON data document

``` nohighlight
{
  "myWebsite": "https://hello.world.example/",
  "proof": {
    "type": "DataIntegrityProof",
    "cryptosuite": "eddsa-jcs-2022",
    "created": "2023-03-05T19:23:24Z",
    "verificationMethod": "https://di.example/issuer#z6MkjLrk3gKS2nnkeWcmcxiZPGskmesDpuwRBorgHxUXfxnG",
    "proofPurpose": "assertionMethod",
    "proofValue": "zQeVbY4oey5q2M3XKaxup3tmzN4DRFTLVqpLMweBrSxMY2xHX5XTYV8nQApmEcqaqA3Q1gVHMrXFkXJeV6doDwLWx"
  }
}
```

Similarly, a proof can be added to a JSON-LD data document like the following:

[Example 3](#example-a-simple-json-ld-data-document): A simple JSON-LD data document

``` nohighlight
{
  "@context": {"myWebsite": "https://vocabulary.example/myWebsite"},
  "myWebsite": "https://hello.world.example/"
};
```

The following proof secures the document above by using the `ecdsa-rdfc-2019` cryptography suite \[[DI-ECDSA](#bib-di-ecdsa "The Elliptic Curve Digital Signature Algorithm Cryptosuites v1.0")\], which produces a verifiable digital proof by transforming the input data using the RDF Dataset Canonicalization Scheme \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\] and then digitally signing it using the Elliptic Curve Digital Signature Algorithm (ECDSA).

[Example 4](#example-a-simple-signed-json-ld-data-document): A simple signed JSON-LD data document

``` nohighlight
{
  "@context": [
    {"myWebsite": "https://vocabulary.example/myWebsite"},
    "https://w3id.org/security/data-integrity/v2"
  ],
  "myWebsite": "https://hello.world.example/",
  "proof": {
    "type": "DataIntegrityProof",
    "cryptosuite": "ecdsa-rdfc-2019",
    "created": "2020-06-11T19:14:04Z",
    "verificationMethod": "https://ldi.example/issuer#zDnaepBuvsQ8cpsWrVKw8fbpGpvPeNSjVPTWoq6cRqaYzBKVP",
    "proofPurpose": "assertionMethod",
    "proofValue": "zXb23ZkdakfJNUhiTEdwyE598X7RLrkjnXEADLQZ7vZyUGXX8cyJZRBkNw813SGsJHWrcpo4Y8hRJ7adYn35Eetq"
  }
}
```

Note: Representing time values to individuals

This specification enables the expression of dates and times, such as through the `created` and `expires` properties. This information might be indirectly exposed to an individual if a proof is processed and is detected to be outside an allowable time range. When displaying date and time values related to the validity of cryptographic proofs, implementers are advised to respect the [locale](https://www.w3.org/TR/ltli/#locale) and local calendar preferences of the individual \[[LTLI](#bib-ltli "Language Tags and Locale Identifiers for the World Wide Web")\]. Conversion of timestamps to local time values are expected to consider the time zone expectations of the individual. See [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/#representing-time) for more details about representing time values to individuals.

[Example 5](#example-a-data-document-with-an-attached-proof-that-uses-the-expires-property): A data document with an attached proof that uses the \'expires\' property

``` nohighlight
{
  "@context": [
    {"myWebsite": "https://vocabulary.example/myWebsite"},
    "https://w3id.org/security/data-integrity/v2"
  ],
  "myWebsite": "https://hello.world.example/",
  "proof": {
    "type": "DataIntegrityProof",
    "cryptosuite": "ecdsa-rdfc-2019",
    "created": "2020-06-11T19:14:04Z",
    // the proof expires a month after it was created
    "expires": "2020-07-11T19:14:04Z",
    "verificationMethod": "https://ldi.example/issuer#zDnaepBuvsQ8cpsWrVKw8fbpGpvPeNSjVPTWoq6cRqaYzBKVP",
    "proofPurpose": "assertionMethod",
    "proofValue": "z98X7RLrkjnXEADJNUhiTEdwyE5GXX8cyJZRLQZ7vZyUXb23ZkdakfRJ7adYY8hn35EetqBkNw813SGsJHWrcpo4"
  }
}
```

The Data Integrity specification supports the concept of multiple proofs in a single document. There are two types of multi-proof approaches that are identified: Proof Sets (un-ordered) and Proof Chains (ordered).

#### 2.1.1 Proof Sets

[](#proof-sets)

A proof set is useful when the same data needs to be secured by multiple entities, but where the order of proofs does not matter, such as in the case of a set of signatures on a contract. A proof set, which has no order, is represented by associating a set of proofs with the `proof` key in a document.

[Example 6](#example-a-proof-set-in-a-data-document): A proof set in a data document

``` nohighlight
{
  "@context": [
    {"myWebsite": "https://vocabulary.example/myWebsite"},
    "https://w3id.org/security/data-integrity/v2"
  ],
  "myWebsite": "https://hello.world.example/",
  "proof": [{
    // This is one of the proofs in the set
    "type": "DataIntegrityProof",
    "cryptosuite": "eddsa-rdfc-2022",
    "created": "2020-11-05T19:23:24Z",
    "verificationMethod": "https://ldi.example/issuer/1#z6MkjLrk3gKS2nnkeWcmcxiZPGskmesDpuwRBorgHxUXfxnG",
    "proofPurpose": "assertionMethod",
    "proofValue": "z4oey5q2M3XKaxup3tmzN4DRFTLVqpLMweBrSxMY2xHX5XTYVQeVbY8nQAVHMrXFkXJpmEcqdoDwLWxaqA3Q1geV6"
  }, {
    // This is the other proof in the set
    "type": "DataIntegrityProof",
    "cryptosuite": "eddsa-rdfc-2022",
    "created": "2020-11-05T13:08:49Z",
    "verificationMethod": "https://pfps.example/issuer/2#z6MkGskxnGjLrk3gKS2mesDpuwRBokeWcmrgHxUXfnncxiZP",
    "proofPurpose": "assertionMethod",
    "proofValue": "z5QLBrp19KiWXerb8ByPnAZ9wujVFN8PDsxxXeMoyvDqhZ6Qnzr5CG9876zNht8BpStWi8H2Mi7XCY3inbLrZrm95"
  }]
}
```

#### 2.1.2 Proof Chains

[](#proof-chains)

A proof chain is useful when the same data needs to be signed by multiple entities and the order of when the proofs occurred matters, such as in the case of a notary counter-signing a proof that had been created on a document. A proof chain, where proof order needs to be preserved, is expressed by providing at least one proof with an `id`, such as a UUID \[[RFC9562](#bib-rfc9562 "Universally Unique IDentifiers (UUIDs)")\], and another proof with a `previousProof` value that identifies the previous proof.

[Example 7](#example-a-proof-chain-in-a-data-document): A proof chain in a data document

``` nohighlight
{
  "@context": [
    {"myWebsite": "https://vocabulary.example/myWebsite"},
    "https://w3id.org/security/data-integrity/v2"
],
  "myWebsite": "https://hello.world.example/",
  "proof": [{
    // The 'id' value identifies this specific proof
    "id": "urn:uuid:60102d04-b51e-11ed-acfe-2fcd717666a7",
    "type": "DataIntegrityProof",
    "cryptosuite": "eddsa-rdfc-2022",
    "created": "2020-11-05T19:23:42Z",
    "verificationMethod": "https://ldi.example/issuer/1#z6MkjLrk3gKS2nnkeWcmcxiZPGskmesDpuwRBorgHxUXfxnG",
    "proofPurpose": "assertionMethod",
    "proofValue": "zVbY8nQAVHMrXFkXJpmEcqdoDwLWxaqA3Q1geV64oey5q2M3XKaxup3tmzN4DRFTLVqpLMweBrSxMY2xHX5XTYVQe"
  }, {
    "type": "DataIntegrityProof",
    "cryptosuite": "eddsa-rdfc-2022",
    "created": "2020-11-05T21:28:14Z",
    "verificationMethod": "https://pfps.example/issuer/2#z6MkGskxnGjLrk3gKS2mesDpuwRBokeWcmrgHxUXfnncxiZP",
    "proofPurpose": "assertionMethod",
    "proofValue": "z6Qnzr5CG9876zNht8BpStWi8H2Mi7XCY3inbLrZrm955QLBrp19KiWXerb8ByPnAZ9wujVFN8PDsxxXeMoyvDqhZ",
    // The 'previousProof' value identifies which proof is verified before this one
    "previousProof": "urn:uuid:60102d04-b51e-11ed-acfe-2fcd717666a7"
  }]
}
```

#### 2.1.3 Proof Graphs

[](#proof-graphs)

When securing data in a document, it is important to clearly delineate the data being protected, which is every graph expressed in the document except the one containing the data associated with a securing mechanism, which is called a proof graph. Creating this separation enables the processing algorithms to deterministically protect and verify a secured document.

The information contained in an input document before a [data integrity proof](#dfn-data-integrity-proof) is added to the document is expressed in one or more graphs. To ensure that information from different [data integrity proofs](#dfn-data-integrity-proof) is not accidentally co-mingled, the concept of a [proof graph](#dfn-proof-graph) is used to encapsulate each [data integrity proof](#dfn-data-integrity-proof). Each value associated with the `proof` property of the document identifies a separate graph, which is sometimes referred to as a named graph, of type ProofGraph, which contains a single [data integrity proof](#dfn-data-integrity-proof).

Using these graphs has a concrete effect when performing JSON-LD processing, as this properly separates statements expressed in one graph from those in another graph. Implementers that limit their processing to other media types, such as JSON, YAML, or CBOR, will need to keep this in mind if they merge data from one document with data from another, such as when an `id` value string is the same in both documents. It is important to not merge objects that seem to have similar properties, when those objects do not have an `id` property and/or use a global identifier type such as a URL, as without these, is not possible to tell whether two such objects are expressing information about the same entity.

### 2.2 Proof Purposes

[](#proof-purposes)

A proof that describes its purpose helps prevent it from being misused for some other purpose. [Proof purposes](#dfn-proof-purpose) enable [verifiers](#dfn-verifier) to know the intent of the creator of a proof so a message cannot be accidentally abused for another purpose. For example, a message signed for the purpose of merely making an assertion (perhaps intended to be widely shared) being abused as a message to authenticate to a service or take some action (such as invoking a capability to do something).

It is important to note that [proof purposes](#dfn-proof-purpose) are a different mechanism from the `key_ops` restrictions in [JSON Web Key (JWK)](https://www.rfc-editor.org/rfc/rfc7517), the `KeyUsage` restriction in the [Web Cryptography API](https://www.w3.org/TR/WebCryptoAPI/) and the [Internet X.509 Public Key Infrastructure Certificate and Certificate Revocation List (CRL) Profile](https://www.rfc-editor.org/rfc/rfc5280). [Proof purposes](#dfn-proof-purpose) are expressions on why a [proof](#dfn-proof) was created and its intended domain of usage whereas the other mechanisms mentioned are intended to limit what a private key can be used to do. A [proof purpose](#dfn-proof-purpose) \"travels\" with the [proof](#dfn-proof) while a key restriction does not.

The following is a list of commonly used [proof purpose](#dfn-proof-purpose) values.

authentication
:   Indicates that a given proof is only to be used for the purposes of an authentication protocol.

assertionMethod
:   Indicates that a proof can only be used for making assertions, for example signing a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential).

keyAgreement
:   Indicates that a proof is used for for key agreement protocols, such as Elliptic Curve Diffie Hellman key agreement used by popular encryption libraries.

capabilityDelegation
:   Indicates that the proof can only be used for delegating capabilities. See the Authorization Capabilities \[[ZCAP](#bib-zcap "Authorization Capabilities for Linked Data")\] specification for more detail.

capabilityInvocation
:   Indicates that the proof can only be used for invoking capabilities. See the Authorization Capabilities \[[ZCAP](#bib-zcap "Authorization Capabilities for Linked Data")\] specification for more detail.

### 2.3 Resource Integrity

[](#resource-integrity)

When a link to an external resource is included in a [conforming secured document](#dfn-conforming-secured-document), it is desirable to know whether the resource that is identified has changed since the proof was created. This applies to cases where there is an external resource that is remotely retrieved as well as to cases where the [verifier](#dfn-verifier) might have a locally cached copy of the resource.

To enable confirmation that a resource referenced by a [conforming secured document](#dfn-conforming-secured-document) has not changed since the document was secured, an implementer *MAY* include a property named `digestMultibase` in any object that includes an `id` property. If present, the `digestMultibase` value *MUST* be a single [string](https://infra.spec.whatwg.org/#string) value, or an [list](https://infra.spec.whatwg.org/#list) of [string](https://infra.spec.whatwg.org/#string) values, each of which is a [Multibase](https://www.w3.org/TR/cid-1.0/#multibase)-encoded [Multihash](https://www.w3.org/TR/cid-1.0/#multihash) value.

JSON-LD context authors are expected to add `digestMultibase` to contexts that will be used in documents that refer to other resources and to include an associated cryptographic digest. For example, the [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/) base context (`https://www.w3.org/ns/credentials/v2`) includes the `digestMultibase` property.

An example of a resource integrity protected object is shown below:

[Example 8](#example-an-integrity-protected-image-that-is-associated-with-an-object): An integrity-protected image that is associated with an object

``` nohighlight
{
  ...
  "image": {
    "id": "https://university.example.org/images/58473",
    "digestMultibase": "zQmdfTbBqBPQ7VNxZEYEj14VmRuZBkqFbiwReogJgS1zR1n"
  },
  ...
}
```

Implementers are urged to consult appropriate sources, such as the [FIPS 180-4 Secure Hash Standard](https://nvlpubs.nist.gov/nistpubs/FIPS/NIST.FIPS.180-4.pdf) and the [Commercial National Security Algorithm Suite 2.0](https://en.wikipedia.org/wiki/Commercial_National_Security_Algorithm_Suite) to ensure that they are choosing a hash algorithm that is appropriate for their use case.

### 2.4 Contexts and Vocabularies

[](#contexts-and-vocabularies)

Implementations that perform JSON-LD processing *MUST* treat the following JSON-LD context URLs as already resolved, where the resolved document matches the corresponding hash values below:

  -----------------------------------------------------------------------------------------
  Context URL and Hash
  -----------------------------------------------------------------------------------------
  **URL:** https://w3id.org/security/data-integrity/v2\
  **SHA2-256 Digest:** `67f21e6e33a6c14e5ccfd2fc7865f7474fb71a04af7e94136cb399dfac8ae8f4`

  **URL:** https://w3id.org/security/multikey/v1\
  **SHA2-256 Digest:** `ba2c182de2d92f7e47184bcca8fcf0beaee6d3986c527bf664c195bbc7c58597`

  **URL:** https://w3id.org/security/jwk/v1\
  **SHA2-256 Digest:** `0f14b62f6071aafe00df265770ea0c7508e118247d79b7d861a406d2aa00bece`
  -----------------------------------------------------------------------------------------

It is possible to confirm the cryptographic digests listed above by running a command like the following (replacing `<DOCUMENT_URL>` with the appropriate value) through a modern UNIX-like OS command line interface: `curl -sL -H "Accept: application/ld+json" <DOCUMENT_URL> | openssl dgst -sha256`

The security vocabulary terms that the JSON-LD contexts listed above resolve to are in the [https://w3id.org/security#](https://w3id.org/security) namespace. That is, all security terms in this vocabulary are of the form `https://w3id.org/security#TERM`, where `TERM` is the name of a term.

Implementations that perform RDF processing *MUST* treat the JSON-LD serialization of the vocabulary URL as already dereferenced, where the dereferenced document matches the corresponding hash value below.

Note

Beyond the security terms defined by this specification, the [https://w3id.org/security#](https://w3id.org/security) namespace also includes the terms defined in the [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/) \[[CID](#bib-cid "Controlled Identifiers v1.0")\] specification, with the corresponding mappings in the context files listed above.

When dereferencing the [https://w3id.org/security#](https://w3id.org/security) URL, the media type of the data that is returned depends on HTTP content negotiation. These are as follows:

  -------------------------------------------------------------------------------------------------------------------------------------------
  Media Type                          Description and Hash
  ----------------------------------- -------------------------------------------------------------------------------------------------------
  application/ld+json                 The vocabulary in JSON-LD format \[[JSON-LD11](#bib-json-ld11 "JSON-LD 1.1")\].\
                                      \
                                      **SHA2-256 Digest:** `082434d5b742418753bbc5d593f93b66d4f1ccf1c627417c0caa2dea40728245`

  text/turtle                         The vocabulary in Turtle format \[[TURTLE](#bib-turtle "RDF 1.1 Turtle")\].\
                                      \
                                      **SHA2-256 Digest:** `9a5ba1e23f54b9adc9c39429f5eb3bd672cb392495cc4159561462921e012b37`

  text/html                           The vocabulary in HTML+RDFa Format \[[HTML-RDFA](#bib-html-rdfa "HTML+RDFa 1.1 - Second Edition")\].\
                                      \
                                      **SHA2-256 Digest:** `dadc810b5bb2c01a3caf276dc03a7c826311cddfefe86160a8c7d34704f50422`
  -------------------------------------------------------------------------------------------------------------------------------------------

It is possible to confirm the cryptographic digests listed above by running a command like the following (replacing `<MEDIA_TYPE>` and `<DOCUMENT_URL>` with the appropriate values) through a modern UNIX-like OS command line interface: `curl -sL -H "Accept: <MEDIA_TYPE>" <DOCUMENT_URL> | openssl dgst -sha256`

Authors of application-specific vocabularies and specifications *SHOULD* ensure that their JSON-LD context and vocabulary files are permanently cacheable using the approaches to caching described above or a functionally equivalent mechanism.

Implementations *MAY* load application-specific JSON-LD context files from the network during development, but *SHOULD* permanently cache JSON-LD context files used by [conforming secured documents](#dfn-conforming-secured-document) in production settings, to increase their security and privacy characteristics. Goals of processing speed *MAY* be achieved through caching approaches such as those described above or functionally equivalent mechanisms.

Some applications, such as digital wallets, that are capable of holding arbitrary [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) or other data-integrity-protected documents, from any issuer and using any contexts, might need to be able to load externally linked resources, such as JSON-LD context files, in production settings. This is expected to increase user choice, scalability, and decentralized upgrades in the ecosystem over time. Authors of such applications are advised to read the security and privacy sections of this document for further considerations.

For further information regarding processing of JSON-LD contexts and vocabularies, see [Verifiable Credentials v2.0: Base Context](https://www.w3.org/TR/vc-data-model-2.0/#base-context) and [Verifiable Credentials v2.0: Vocabularies](https://www.w3.org/TR/vc-data-model-2.0/#vocabularies).

#### 2.4.1 Validating Contexts

[](#validating-contexts)

It is necessary to ensure that a consuming application has explicitly approved of the types, and therefore the semantics, of input documents that it will process. Not checking JSON-LD context values against known good values can lead to security vulnerabilities, due to variance in the semantics that they convey. Applications *MUST* use the algorithm in Section [4.6 Context Validation](#context-validation), or one that achieves equivalent protections, to validate contexts in a [conforming secured document](#dfn-conforming-secured-document). Context validation *MUST* be run *after* running the applicable algorithm in either Section [4.4 Verify Proof](#verify-proof) or Section [4.5 Verify Proof Sets and Chains](#verify-proof-sets-and-chains).

While the algorithm described in Section [4.6 Context Validation](#context-validation) provides one way of checking context values, and one optional way of safely processing unknown context values, implementers *MAY* use alternative approaches, or a different ordering of the steps, that provide the same protections.

For example, if no JSON-LD processing is to occur, then, rather than performing this check, an application could follow the guidance in whatever trusted documentation is provided out of band for properly understanding the semantics of that type of document.

Another approach would be to configure an application to use a JSON-LD Context loader, sometimes referred to as a document loader, to use only local copies of approved context files. This would guarantee that neither the context files nor their cryptographic hashes would ever change, effectively resulting in the same result as the algorithm in Section [4.6 Context Validation](#context-validation).

Another alternative approach, also effectively equivalent to the algorithm in Section [4.6 Context Validation](#context-validation), would be for an application to keep a list of well known context URLs and their associated approved cryptographic hashes, without storing every context file locally. This would allow these contexts to be safely loaded from the network without compromising the security expectations of the application.

Yet another valid approach would be for a transmitting application to [compact](https://www.w3.org/TR/json-ld11-api/#compaction-algorithm) a document to exactly what a receiving application requests, via a protocol such as one requesting a [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation), omitting additional sender-specific context values that were used when securing the original document. As long as the cryptography suite\'s verification algorithm provides a successful verification result, such transformations are valid and would result in full URLs for terms that were previously compacted by the omitted context. That is, a term that was previously compacted to `foo` based on a sender-supplied context that is unknown to a receiver (e.g., \``https://ontology.example/v1`) would instead be \"expanded\" to a URL like `https://ontology.example#foo`, which would then be \"compacted\" to the same URL, once the unknown context is omitted and the JSON-LD compaction algorithm is applied by the receiving application.

#### 2.4.2 Context Injection

[](#context-injection)

The `@context` property is used to ensure that implementations are using the same semantics when terms in this specification are processed. For example, this can be important when properties like `type` are processed and its value, such as `DataIntegrityProof`, are used.

When an application is securing a document, if an `@context` property is not provided in the document or the Data Integrity terms used in the document are not mapped by existing values in the `@context` property, implementations *SHOULD* inject or append an `@context` property with a value of `https://w3id.org/security/data-integrity/v2` or one or more contexts with at least the same declarations, such as the Verifiable Credential Data Model v2.0 context (`https://www.w3.org/ns/credentials/v2`).

Implementations that do not intend to use JSON-LD processing *MAY* choose to not include an `@context` declaration at the top-level of the document. However, if an `@context` declaration is not included, extensions (such as the addition of new properties) related to this specification or corresponding cryptosuites *MUST NOT* be made.

#### 2.4.3 Securing Data Losslessly

[](#securing-data-losslessly)

HTML processors are designed to continue processing if recoverable errors are detected. JSON-LD processors operate in a similar manner. This design philosophy was meant to ensure that developers could use only the parts of the JSON-LD language that they find useful, without causing the processor to throw errors on things that might not be important to the developer. Among other effects, this philosophy led to JSON-LD processors being designed to not throw errors, but rather warn developers, when encountering things such as undefined terms.

When converting from JSON-LD to an RDF Dataset, such as when canonicalizing a document \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\], undefined terms and relative URLs can be dropped silently. When values are dropped, they are not protected by a digital proof. This creates a mismatch of expectations, where a developer, who is unaware of how a JSON-LD processor works, might think that certain data was being secured, and then be surprised to find that it was not, when no error was thrown. This specification requires that any recoverable loss of data when performing JSON-LD transformations result in an error, to avoid a mismatch in the security expectations of developers.

Implementations that use JSON-LD processing, such as RDF Dataset Canonicalization \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\], *MUST* throw an error, which *SHOULD* be `DATA_LOSS_DETECTION_ERROR`, when data is dropped by a JSON-LD processor, such as when an undefined term is detected in an input document.

Similarly, since [conforming secured documents](#dfn-conforming-secured-document) can be transferred from one security domain to another, [conforming processors](#dfn-conforming-processor) that process the [conforming secured document](#dfn-conforming-secured-document) cannot assume any particular base URL for the document. When deserializing to RDF, implementations *MUST* ensure that the base URL is set to null.

#### 2.4.4 Datatypes

[](#datatypes)

This section defines datatypes that are used by this specification.

##### 2.4.4.1 The `cryptosuiteString` Datatype

[](#cryptosuiteString)

This specification encodes cryptographic suite identifiers as enumerable strings, which is useful in processes that need to efficiently encode such strings, such as compression algorithms. In environments that support data types for [string](https://infra.spec.whatwg.org/#string) values, such as RDF \[[RDF-CONCEPTS](#bib-rdf-concepts "Resource Description Framework (RDF): Concepts and Abstract Syntax")\], cryptographic identifier content is indicated using a literal value whose datatype is set to `https://w3id.org/security#cryptosuiteString`.

The `cryptosuiteString` datatype is defined as follows:

The URL denoting this datatype
:   `https://w3id.org/security#cryptosuiteString`

The lexical space
:   The union of all cryptosuite strings, expressed using American Standard Code for Information Interchange \[[ASCII](#bib-ascii "ISO/IEC 646:1991, Information technology -- ISO 7-bit coded character set for information interchange")\] strings, that are defined by the collection of all Data Integrity cryptosuite specifications.

The value space
:   The union of all cryptosuite types that are expressed using the `cryptosuite` property, as defined in Section [3.1 DataIntegrityProof](#dataintegrityproof).

The lexical-to-value mapping
:   Any element of the lexical space is mapped to the result of parsing it into an internal representation that uniquely identifies the cryptosuite type from all other possible cryptosuite types.

The canonical mapping
:   Any element of the value space is mapped to the corresponding string in the lexical space.

### 2.5 Relationship to Linked Data

[](#relationship-to-linked-data)

The term Linked Data is used to describe a recommended best practice for exposing, sharing, and connecting information on the Web using standards, such as URLs, to identify things and their properties. When information is presented as Linked Data, other related information can be easily discovered and new information can be easily linked to it. Linked Data is extensible in a decentralized way, greatly reducing barriers to large scale integration.

With the increase in usage of Linked Data for a variety of applications, there is a need to be able to verify the authenticity and integrity of Linked Data documents. This specification adds authentication and integrity protection to data documents through the use of mathematical proofs without sacrificing Linked Data features such as extensibility and composability.

Note: Use of Linked Data is an optional feature

While this specification provides mechanisms to digitally sign Linked Data, the use of Linked Data is not necessary to gain some of the advantages provided by this specification.

### 2.6 Relationship to Verifiable Credentials

[](#relationship-to-verifiable-credentials)

Cryptographic suites that implement this specification can be used to secure [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) and [verifiable presentations](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation). Implementers that are addressing those use cases are cautioned that additional checks might be appropriate when processing those types of documents.

There are some use cases where it is important to ensure that the [verification method](#dfn-verification-method) used in a proof is associated with the [`issuer`](https://www.w3.org/TR/vc-data-model-2.0/#issuer) in a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential), or the [`holder`](https://www.w3.org/TR/vc-data-model-2.0/#dfn-holders) in a [verifiable presentation](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-presentation), during the process of [validation](https://www.w3.org/TR/vc-data-model-2.0/#issuer-0). One way to check for such an association is to ensure that the value of the `controller` property of a proof\'s [verification method](#dfn-verification-method) matches the URL value used to identify the [`issuer`](https://www.w3.org/TR/vc-data-model-2.0/#issuer) or [`holder`](https://www.w3.org/TR/vc-data-model-2.0/#dfn-holders), respectively, and that the verification method is expressed under a verification relationship that is acceptable given the proof\'s purpose. This particular association indicates that the [`issuer`](https://www.w3.org/TR/vc-data-model-2.0/#issuer) or [`holder`](https://www.w3.org/TR/vc-data-model-2.0/#dfn-holders), respectively, is the controller of the [verification method](#dfn-verification-method) used to verify the proof.

Document authors and implementers are advised to understand the difference between the validity period of a [proof](#proofs), which is expressed using the [`created`](#dfn-created) and [`expires`](#defn-proof-expires) properties, and the validity period of a [credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-credential), which is expressed using the [`validFrom`](https://www.w3.org/TR/vc-data-model-2.0/#defn-validFrom) and [`validUntil`](https://www.w3.org/TR/vc-data-model-2.0/#defn-validUntil) properties. While these properties might sometimes express the same validity periods, at other times they might not be aligned. When verifying a [proof](#proofs), it is important to ensure that the time of interest (which might be the current time or any other time) is within the validity period for the proof (that is, between [`created`](#dfn-created) and [`expires`](#defn-proof-expires) ). When [validating](https://www.w3.org/TR/vc-data-model-2.0/#validation) a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential), it is important to ensure that the time of interest is within the validity period for the [credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-credential) (that is, betweeen [`validFrom`](https://www.w3.org/TR/vc-data-model-2.0/#defn-validFrom) and [`validUntil`](https://www.w3.org/TR/vc-data-model-2.0/#defn-validUntil)). Note that a failure to validate either the validity period for the [proof](#proofs), or the validity period for the [credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-credential), might result in accepting data that ought to have been rejected.

Finally, implementers are also urged to understand that there is a difference between the revocation information associated with a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential), and the [revocation](https://www.w3.org/TR/cid-1.0/#dfn-revoked) and [expiration](https://www.w3.org/TR/cid-1.0/#defn-vm-expires) times for a [verification method](#dfn-verification-method). The [revocation](https://www.w3.org/TR/cid-1.0/#dfn-revoked) and [expiration](https://www.w3.org/TR/cid-1.0/#defn-vm-expires) times for a [verification method](#dfn-verification-method) are expressed using the `revocation` and `expires` properties, respectively; are related to events such as a [secret key](#dfn-secret-key) being compromised or expiring; and can provide timing information which might reveal details about a controller, such as their security practices or when they might have been compromised. The revocation information for a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) is expressed using the `credentialStatus` property; is related to events such as an individual losing the privilege that is granted by the [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential); and does not provide timing information, which enhances privacy.

## 3. Cryptographic Suites

[](#cryptographic-suites)

A [data integrity proof](#dfn-data-integrity-proof) is designed to be easy to use by developers and therefore strives to minimize the amount of information one has to remember to generate a proof. Often, just the [cryptographic suite](#dfn-cryptosuite) name (such as `eddsa-rdfc-2022`) is required from developers to initiate the creation of a proof. These [cryptographic suite](#dfn-cryptosuite)s are often created and reviewed by people that have the requisite cryptographic training to ensure that safe combinations of cryptographic primitives are used. This section specifies the requirements for authoring cryptographic suite specifications.

The requirements for all data integrity cryptographic suite specifications are as follows:

-   The specification *MUST* be published as a human-readable document at a URL.
-   The specification *MUST* identify a cryptographic suite `type` and any parameters that can be used with the suite.
-   The specification *MUST* detail the [transformation algorithms](#dfn-transformation-algorithm) (if any), parameters, and other necessary details, used to modify input data into the data to be protected.
-   The specification *MUST* detail the [hashing algorithms](#dfn-hashing-algorithm) parameters, and other necessary details used to perform cryptographic hashing to the data to be protected.
-   The specification *MUST* detail the [proof serialization algorithms](#dfn-proof-serialization-algorithm), parameters, and other necessary details used to perform cryptographic protection of the data.
-   The specification *MUST* detail the [proof verification algorithms](#dfn-proof-verification-algorithm), parameters, and other necessary details used to perform cryptographic verification of the data.
-   The specification *MUST* define a data integrity cryptographic suite instantiation algorithm that accepts a set of options ([map](https://infra.spec.whatwg.org/#ordered-map) `options`) and returns a [cryptosuite instance](#dfn-data-integrity-cryptographic-suite-instance) ([struct](https://infra.spec.whatwg.org/#struct) `cryptosuite`). This algorithm *SHOULD* be listed in the [Verifiable Credential Extensions](https://w3c.github.io/vc-extensions/) document. A data integrity cryptographic suite instance [struct](https://infra.spec.whatwg.org/#struct) has the following [items](https://infra.spec.whatwg.org/#struct-item).

    createProof
    :   An algorithm that takes an [input document](#dfn-input-document) ([map](https://infra.spec.whatwg.org/#ordered-map) `inputDocument`) and proof options ([map](https://infra.spec.whatwg.org/#ordered-map) `options`) as input, and produces a [data integrity proof](#dfn-data-integrity-proof) ([map](https://infra.spec.whatwg.org/#ordered-map)) or an error.

    verifyProof
    :   An algorithm that takes a [secured data document](#dfn-secured-data-document) ([map](https://infra.spec.whatwg.org/#ordered-map) `securedDocument`) as input, and produces a cryptosuite verification result or an error. The [cryptosuite verification result](#dfn-cryptosuite-verification-result) is a [struct](https://infra.spec.whatwg.org/#struct) that contains the following [items](https://infra.spec.whatwg.org/#struct-item):

        verified
        :   A [boolean](https://infra.spec.whatwg.org/#boolean) that is `true` if the verification succeeded, or `false` otherwise.

        verifiedDocument
        :   A [map](https://infra.spec.whatwg.org/#ordered-map) that represents the [secured data document](#dfn-secured-data-document) with the verified proofs removed if [verified](#dfn-verified) is `true`, or [null](https://infra.spec.whatwg.org/#nulls) otherwise.

        The structure *MAY* contain other implementation-specific information that is useful for developers, such as debugging information. If an error is produced, the verification process failed to complete. An error, such as a network error, does not mean that a future attempt at verification would fail.
-   The specification *MUST* detail any known resource starvation attack that can occur in an algorithm and provide testable mitigations against each attack.
-   The specification *MUST* contain a Security Considerations section detailing security considerations specific to the cryptographic suite.
-   The specification *MUST* contain a Privacy Considerations section detailing privacy considerations specific to the cryptographic suite.
-   The JSON-LD context associated with the cryptographic suite *MUST* have its terms protected from unsafe redefinition, by use of the `@protected` keyword.

A [cryptosuite instance](#dfn-data-integrity-cryptographic-suite-instance) is instantiated using a [cryptosuite instantiation algorithm](#dfn-cryptosuite-instantiation-algorithm) and is made available to algorithms in an implementation-specific manner. Implementations *MAY* use the [Verifiable Credential Extensions](https://w3c.github.io/vc-extensions/) document to discover known [cryptosuite instantiation algorithms](#dfn-cryptosuite-instantiation-algorithm).

### 3.1 DataIntegrityProof

[](#dataintegrityproof)

A number of [cryptographic suites](#dfn-cryptosuite) follow the same basic pattern when expressing a [data integrity proof](#dfn-data-integrity-proof). This section specifies that general design pattern, a [cryptographic suite](#dfn-cryptosuite) type called a `DataIntegrityProof`, which reduces the burden of writing and implementing [cryptographic suites](#dfn-cryptosuite) through the reuse of design primitives and source code.

When specifing a [cryptographic suite](#dfn-cryptosuite) that utilizes this design pattern, the `proof` value takes the following form:

type
:   The `type` property *MUST* contain the [string](https://infra.spec.whatwg.org/#string) `DataIntegrityProof`.

cryptosuite
:   The value of the `cryptosuite` property *MUST* be a [string](https://infra.spec.whatwg.org/#string) that identifies the [cryptographic suite](#dfn-cryptosuite). If the processing environment supports [string](https://infra.spec.whatwg.org/#string) subtypes, the subtype of the `cryptosuite` value *MUST* be the `https://w3id.org/security#cryptosuiteString` subtype.

proofValue
:   The `proofValue` property *MUST* be used, as specified in Section [2.1 Proofs](#proofs).

[Cryptographic suite](#dfn-cryptosuite) designers *MUST* use mandatory `proof` value properties defined in Section [2.1 Proofs](#proofs), and *MAY* define other properties specific to their cryptographic suite.

Note: Design Patterns of Legacy Cryptographic Suites

One of the design patterns seen in Data Integrity cryptosuites from 2012 to 2020 was use of the `type` property to establish a specific type for a cryptographic suite; the [Ed25519Signature2020 cryptographic suite](https://www.w3.org/TR/vc-di-eddsa/#the-ed25519signature2020-suite) was one such specification. This led to a greater burden on cryptographic suite implementations, where every new cryptographic suite required specification of a new JSON-LD Context, resulting in a sub-optimal developer experience. A streamlined version of this design pattern emerged in 2020, such that a developer would only need to include a single JSON-LD Context to support all modern cryptographic suites. This encouraged more modern cryptosuites --- such as the EdDSA Cryptosuites \[[DI-EDDSA](#bib-di-eddsa "The Edwards Digital Signature Algorithm Cryptosuites v1.0")\] and the ECDSA Cryptosuites \[[DI-ECDSA](#bib-di-ecdsa "The Elliptic Curve Digital Signature Algorithm Cryptosuites v1.0")\] --- to be built based on the streamlined pattern described in this section.\
\
To improve the developer experience, authors creating new Data Integrity cryptographic suite specifications *SHOULD* use the modern pattern --- where the `type` is set to `DataIntegrityProof`; the `cryptosuite` property carries the identifier for the cryptosuite; and any cryptosuite-specific cryptographic data is encapsulated (i.e., not directly exposed as application layer data) within `proofValue`. A list of cryptographic suite specifications that are known to follow this pattern is provided in the [Securing Mechanisms section of the Verifiable Credentials Extensions](https://w3c.github.io/vc-extensions/#securing-mechanisms) document.

## 4. Algorithms

[](#algorithms)

The algorithms defined below operate on documents represented as [JSON objects](https://www.rfc-editor.org/rfc/rfc8259#section-4). This specification follows the [JSON-LD 1.1 Processing Algorithms and API](https://www.w3.org/TR/json-ld11-api/) specification in representing a JSON object as a [map](https://infra.spec.whatwg.org/#ordered-map). An unsecured data document is a [map](https://infra.spec.whatwg.org/#ordered-map) that contains no proof values. An input document is an [map](https://infra.spec.whatwg.org/#ordered-map) that has not yet had the current proof added to it, but it *MAY* contain a proof value that was added to it by a previous process. A secured data document is a [map](https://infra.spec.whatwg.org/#ordered-map) that contains one or more proof values.

Implementers *MAY* implement reasonable defaults and safeguards in addition to the algorithms below, to help mitigate developer error, excessive resource consumption, newly discovered attack models against which there is a particular protection, and other improvements. The algorithms provided below are the minimum requirements for an interoperable implementation, and developers are urged to include additional measures that could contribute to a safer and more efficient ecosystem.

### 4.1 Processing Model

[](#processing-model)

The processing model used by a [conforming processor](#dfn-conforming-processor) and its application-specific software is described in this section. When software is to ensure information is tamper-evident, it performs the following steps:

1.  The software arranges the information into a document, such as a JSON or JSON-LD document.
2.  If the document is a JSON-LD document, the software selects one or more JSON-LD Contexts and expresses them using the `@context` property.
3.  The software selects one or more cryptography suites that meet the needs of the use case, such as one that provides full, selective, or unlinkable disclosure, using acceptable cryptographic key material.
4.  The software uses the applicable algorithm(s) provided in Section [4.2 Add Proof](#add-proof) or Section [4.3 Add Proof Set/Chain](#add-proof-set-chain) to add one or more proofs.

When software needs to use information that was transmitted to it using a mechanism described by this specification, it performs the following steps:

1.  The software transforms the incoming data into a document that can be understood by the applicable algorithm provided in Section [4.4 Verify Proof](#verify-proof) or Section [4.5 Verify Proof Sets and Chains](#verify-proof-sets-and-chains).
2.  The software uses JSON Schema or an equivalent mechanism to validate that the incoming document follows an expected schema used by the application.
3.  The software uses the applicable algorithm(s) provided in Section [4.4 Verify Proof](#verify-proof) or Section [4.5 Verify Proof Sets and Chains](#verify-proof-sets-and-chains) to verify the integrity of the incoming document.
4.  If the document is a JSON-LD document, the software uses the algorithm provided in Section [4.6 Context Validation](#context-validation), or one providing equivalent protections, to validate all JSON-LD Context values used in the document.

### 4.2 Add Proof

[](#add-proof)

The following algorithm specifies how a digital proof can be added to an [input document](#dfn-input-document), and can then be used to verify the output document\'s authenticity and integrity. Required inputs are an [input document](#dfn-input-document) ([map](https://infra.spec.whatwg.org/#ordered-map) `inputDocument`), a [cryptosuite instance](#dfn-data-integrity-cryptographic-suite-instance) ([struct](https://infra.spec.whatwg.org/#struct) `cryptosuite`), and a set of options ([map](https://infra.spec.whatwg.org/#ordered-map) `options`). Output is a [secured data document](#dfn-secured-data-document) ([map](https://infra.spec.whatwg.org/#ordered-map)) or an error. Whenever this algorithm encodes strings, it *MUST* use UTF-8 encoding.

1.  Let `proof` be the result of calling the [createProof](#dfn-createproof) algorithm specified in `cryptosuite`.`createProof` with `inputDocument` and `options` passed as a parameters. If the algorithm produces an error, the error *MUST* be propagated and *SHOULD* convey the error type.
2.  If one or more of the `proof`.`type`, `proof`.`verificationMethod`, and `proof`.`proofPurpose` values is not set, an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_GENERATION_ERROR](#PROOF_GENERATION_ERROR).
3.  If `options` has a non-null `domain` [item](https://infra.spec.whatwg.org/#struct-item), it *MUST* be equal to `proof`.`domain` or an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_GENERATION_ERROR](#PROOF_GENERATION_ERROR).
4.  If `options` has a non-null `challenge` [item](https://infra.spec.whatwg.org/#struct-item), it *MUST* be equal to `proof`.`challenge` or an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_GENERATION_ERROR](#PROOF_GENERATION_ERROR).
5.  Let `securedDataDocument` be a copy of `inputDocument`.
6.  Set `securedDataDocument`.`proof` to the value of `proof`.
7.  Return `securedDataDocument` as the [secured data document](#dfn-secured-data-document).

### 4.3 Add Proof Set/Chain

[](#add-proof-set-chain)

The following algorithm specifies how to incrementally add a proof to a proof set or proof chain starting with a secured document containing either a proof or proof set/chain. Required inputs are a [secured data document](#dfn-secured-data-document) ([map](https://infra.spec.whatwg.org/#ordered-map) `securedDocument`), a [cryptographic suite](#dfn-cryptosuite) ([cryptosuite instance](#dfn-data-integrity-cryptographic-suite-instance) `suite`), and a set of options ([map](https://infra.spec.whatwg.org/#ordered-map) `options`). Output is a new [secured data document](#dfn-secured-data-document) ([map](https://infra.spec.whatwg.org/#ordered-map)). Whenever this algorithm encodes strings, it *MUST* use UTF-8 encoding.

1.  Let `proof` be set to `securedDocument`.`proof`. Let `allProofs` be an empty list. If `proof` is a list, copy all the elements of `proof` to `allProofs`. If `proof` is an object add a copy of that object to `allProofs`.
2.  Let the `inputDocument` be a copy of the `securedDocument` with the `proof` attribute removed. Let `output` be a copy of the `inputDocument`.
3.  Let `matchingProofs` be an empty list.
4.  If `options` has a `previousProof` [item](https://infra.spec.whatwg.org/#struct-item) that is a string, add the element from `allProofs` with an `id` attribute matching `previousProof` to `matchingProofs`. If a proof with `id` equal to `previousProof` does not exist in `allProofs`, an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_GENERATION_ERROR](#PROOF_GENERATION_ERROR).
5.  If `options` has a `previousProof` [item](https://infra.spec.whatwg.org/#struct-item) that is an array, add each element from `allProofs` with an `id` attribute that matches an element of that array. If any element of `previousProof` [list](https://infra.spec.whatwg.org/#list) has an `id` attribute that does not match the `id` attribute of any element of `allProofs`, an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_GENERATION_ERROR](#PROOF_GENERATION_ERROR).
6.  Set `inputDocument`.`proof` to `matchingProofs`.
    Note: This step protects the document and existing proofs

    This step adds references to the [named graphs](#dfn-named-graph), as well as adding a copy of *all* the claims contained in the [proof graphs](#dfn-proof-graph). The step is critical, as it "binds" any matching proofs to the document prior to applying the current proof. The `proof` value for the document will be updated in a later step of this algorithm.
7.  Run steps 1 through 6 of the algorithm in section [4.2 Add Proof](#add-proof), passing `inputDocument`, `suite`, and `options`. If no exceptions are raised, append the generated `proof` value to the `allProofs`; otherwise, raise the exception.
8.  Set `output`.`proof` to the value of `allProofs`.
9.  Return `output` as the new [secured data document](#dfn-secured-data-document).

### 4.4 Verify Proof

[](#verify-proof)

The following algorithm specifies how to check the authenticity and integrity of a [secured data document](#dfn-secured-data-document) by verifying its digital proof. The algorithm takes as input:

`mediaType`
:   A [media type](https://mimesniff.spec.whatwg.org/#mime-type) as defined in \[[MIMESNIFF](#bib-mimesniff "MIME Sniffing Standard")\]

`documentBytes`
:   A [byte sequence](https://infra.spec.whatwg.org/#byte-sequence) whose media type is `mediaType`

`cryptosuite`
:   A [cryptosuite instance](#dfn-data-integrity-cryptographic-suite-instance)

`expectedProofPurpose`
:   An optional [string](https://infra.spec.whatwg.org/#string), used to ensure that the `proof` was generated by the proof creator for the expected reason by the verifier. See [2.2 Proof Purposes](#proof-purposes) for common values

`domain`
:   An optional [set](https://infra.spec.whatwg.org/#ordered-set) of [strings](https://infra.spec.whatwg.org/#string), used by the proof creator to lock a proof to a particular security domain, and used by the verifier to ensure that a proof is not being used across different security domains

`challenge`
:   An optional [string](https://infra.spec.whatwg.org/#string) [challenge](#dfn-challenge), used by the verifier to ensure that an attacker is not replaying previously created proofs

This algorithm returns a verification result, a [struct](https://infra.spec.whatwg.org/#struct) whose [items](https://infra.spec.whatwg.org/#struct-item) are:

verified
:   `true` or `false`

verifiedDocument
:   [Null](https://infra.spec.whatwg.org/#nulls), if [verified](#dfn-verified-0) is `false`; otherwise, an [input document](#dfn-input-document)

mediaType
:   [Null](https://infra.spec.whatwg.org/#nulls), if [verified](#dfn-verified-0) is `false`; otherwise, a [media type](https://mimesniff.spec.whatwg.org/#mime-type), which *MAY* include [parameters](https://mimesniff.spec.whatwg.org/#parameters)

warnings
:   a [list](https://infra.spec.whatwg.org/#list) of [ProblemDetails](#dfn-problemdetails), which defaults to an empty [list](https://infra.spec.whatwg.org/#list)

errors
:   a [list](https://infra.spec.whatwg.org/#list) of [ProblemDetails](#dfn-problemdetails), which defaults to an empty [list](https://infra.spec.whatwg.org/#list)

When a step says \"an error *MUST* be raised\", it means that a [verification result](#dfn-verification-result) *MUST* be returned with a [verified](#dfn-verified-0) value of `false` and a non-empty [errors](#dfn-errors) list.

1.  Let `securedDocument` be the result of running [parse JSON bytes to an Infra value](https://infra.spec.whatwg.org/#parse-json-bytes-to-an-infra-value) on `documentBytes`.
2.  If either `securedDocument` is not a [map](https://infra.spec.whatwg.org/#ordered-map) or `securedDocument`.`proof` is not a [map](https://infra.spec.whatwg.org/#ordered-map), an error *MUST* be raised and *SHOULD* convey an error type of [PARSING_ERROR](https://www.w3.org/TR/VC-DATA-MODEL-2.0#PARSING_ERROR).
3.  Let `proof` be `securedDocument`.`proof`.
4.  If one or more of `proof`.`type`, `proof`.`verificationMethod`, and `proof`.`proofPurpose` does not [exist](https://infra.spec.whatwg.org/#map-exists), an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_VERIFICATION_ERROR](#PROOF_VERIFICATION_ERROR).
5.  If `expectedProofPurpose` was given, and it does not match `proof`.`proofPurpose`, an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_VERIFICATION_ERROR](#PROOF_VERIFICATION_ERROR).
6.  If `domain` was given, and it does not contain the same [strings](https://infra.spec.whatwg.org/#string) as `proof`.`domain` (treating a single [string](https://infra.spec.whatwg.org/#string) as a [set](https://infra.spec.whatwg.org/#ordered-set) containing just that [string](https://infra.spec.whatwg.org/#string)), an error *MUST* be raised and *SHOULD* convey an error type of [INVALID_DOMAIN_ERROR](#INVALID_DOMAIN_ERROR).
7.  If `challenge` was given, and it does not match `proof`.`challenge`, an error *MUST* be raised and *SHOULD* convey an error type of [INVALID_CHALLENGE_ERROR](#INVALID_CHALLENGE_ERROR).
8.  Let `cryptosuiteVerificationResult` be the result of running the `cryptosuite`.[verifyProof](#dfn-verifyproof) algorithm with `securedDocument` provided as input.
9.  Return a [verification result](#dfn-verification-result) with [items](https://infra.spec.whatwg.org/#struct-item):

    [verified](#dfn-verified-0)
    :   `cryptosuiteVerificationResult`.`verified`

    [verifiedDocument](#dfn-verifieddocument-0)
    :   `cryptosuiteVerificationResult`.`verifiedDocument`

    [mediaType](#dfn-mediatype)
    :   `mediaType`

### 4.5 Verify Proof Sets and Chains

[](#verify-proof-sets-and-chains)

In a [proof set](#dfn-proof-set) or [proof chain](#dfn-proof-chain), a [secured data document](#dfn-secured-data-document) has a `proof` attribute which contains a list of [proofs](#dfn-proof) (`allProofs`). The following algorithm provides one method of checking the authenticity and integrity of a [secured data document](#dfn-secured-data-document), achieved by verifying every proof in `allProofs`. Other approaches are possible, particularly if it is only desired to verify a subset of the proofs contained in `allProofs`. If another approach is taken to verify only a subset of the proofs, then it is important to note that any proof in that subset with a `previousProof` can only be considered verified if the proofs it references are also considered verified.

Required input is a [secured data document](#dfn-secured-data-document) (`securedDocument`). A list of [verification results](#dfn-verification-result) corresponding to each proof in `allProofs` is generated, and a single combined [verification result](#dfn-verification-result) is returned as output. Implementations *MAY* return any of the other [verification result](#dfn-verification-result)s and/or any other metadata alongside the combined [verification result](#dfn-verification-result).

1.  Set `allProofs` to `securedDocument`.`proof`.
2.  Set `verificationResults` to an empty list.
3.  For each `proof` in `allProofs`, do the following steps:
    1.  Let `matchingProofs` be an empty list.
    2.  If `proof` contains a `previousProof` attribute and the value of that attribute is a [string](https://infra.spec.whatwg.org/#string), add the element from `allProofs` with an `id` attribute value matching the value of `previousProof` to `matchingProofs`. If a proof with `id` value equal to the value of `previousProof` does not exist in `allProofs`, an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_VERIFICATION_ERROR](#PROOF_VERIFICATION_ERROR). If the `previousProof` attribute is a [list](https://infra.spec.whatwg.org/#list), add each element from `allProofs` with an `id` attribute value that matches the value of an element of that [list](https://infra.spec.whatwg.org/#list). If any element of `previousProof` [list](https://infra.spec.whatwg.org/#list) has an `id` attribute value that does not match the `id` attribute value of any element of `allProofs`, an error *MUST* be raised and *SHOULD* convey an error type of [PROOF_VERIFICATION_ERROR](#PROOF_VERIFICATION_ERROR).
    3.  Let `inputDocument` be a copy of `securedDocument` with the proof value removed and then set `inputDocument`.`proof` to `matchingProofs`.
        Note: Secure document and previous proofs

        See the note in Step 6 of Section [4.3 Add Proof Set/Chain](#add-proof-set-chain) to learn about what document properties and previous proofs this step secures.
    4.  Run steps 4 through 8 of the algorithm in section [4.4 Verify Proof](#verify-proof) on `inputDocument`; if no exceptions are raised, append `cryptosuiteVerificationResult` to `verificationResults`.
4.  Set `successfulVerificationResults` to an empty list.
5.  Let `combinedVerificationResult` be an empty struct. Set `combinedVerificationResult`.`status` to `true`, `combinedVerificationResult`.`document` to `null`, and `combinedVerificationResult`.`mediaType` to `null`.
6.  For each `cryptosuiteVerificationResult` in `verificationResults`:
    1.  If `cryptosuiteVerificationResult`.`verified` is `false`, set `combinedVerificationResult`.`verified` to `false`.
    2.  Otherwise, set `combinedVerificationResult`.`document` to `cryptosuiteVerificationResult`.`verifiedDocument`, set `combinedVerificationResult`.`mediaType` to `cryptosuiteVerificationResult`.`mediaType`, and append `cryptosuiteVerificationResult` to `successfulVerificationResults`.
7.  If `combinedVerificationResult`.`status` is `false`, set `combinedVerificationResult`.`document` to `null` and `combinedVerificationResult`.`mediaType` to `null`.
8.  Return `combinedVerificationResult`, `successfulVerificationResults`.

### 4.6 Context Validation

[](#context-validation)

The following algorithm provides one mechanism that can be used to ensure that an application understands the contexts associated with a document before it executed business rules specific to the input in the document. For more rationale related to this algorithm, see Section [2.4.1 Validating Contexts](#validating-contexts). This algorithm takes inputs of a document ([map](https://infra.spec.whatwg.org/#ordered-map) `inputDocument`), a set of known JSON-LD Contexts ([list](https://infra.spec.whatwg.org/#list) `knownContext`), and a boolean to recompact when unknown contexts are detected ([boolean](https://infra.spec.whatwg.org/#boolean) `recompact`).

This algorithm returns a context validation result, a [struct](https://infra.spec.whatwg.org/#struct) whose [items](https://infra.spec.whatwg.org/#struct-item) are:

validated
:   `true` or `false`

validatedDocument
:   [Null](https://infra.spec.whatwg.org/#nulls), if [validated](#dfn-validated) is `false`; otherwise, an [input document](#dfn-input-document)

warnings
:   a [list](https://infra.spec.whatwg.org/#list) of [ProblemDetails](#dfn-problemdetails), which defaults to an empty [list](https://infra.spec.whatwg.org/#list)

errors
:   a [list](https://infra.spec.whatwg.org/#list) of [ProblemDetails](#dfn-problemdetails), which defaults to an empty [list](https://infra.spec.whatwg.org/#list)

The context validation algorithm is as follows:

1.  Set `result`.`validated` to `false`, `result`.`warnings` to an empty list, `result`.`errors` to an empty list, `compactionContext` to an empty list; and clone `inputDocument` to `result`.`validatedDocument`.
2.  Let `contextValue` be the value of the `@context` property of `result`.`validatedDocument`, which might be undefined.
3.  If `contextValue` does not deeply equal `knownContext`, any subtree in `result`.`validatedDocument` contains an `@context` property, or any URI in `contextValue` dereferences to a JSON-LD Context file that does not match a known good value or cryptographic hash, then perform the applicable action:
    1.  If `recompact` is `true`, set `result`.`validatedDocument` to the result of running the [JSON-LD Compaction Algorithm](https://www.w3.org/TR/json-ld11-api/#compaction-algorithm) with the `inputDocument` and `knownContext` as inputs. If the compaction fails, add at least one error to `result`.`errors`.
    2.  If `recompact` is not `true`, add at least one error to `result`.`errors`.
4.  If `result`.`errors` is empty, set `result`.`validated` to `true`; otherwise, set `result`.`validated` to `false`, and remove the `document` property from `result`.
5.  Return the value of `result`.

Implementations *MAY* include additional warnings or errors that enforce further validation rules that are specific to the implementation or a particular use case.

### 4.7 Processing Errors

[](#processing-errors)

The algorithms described in this specification, as well as in various cryptographic suite specifications, throw specific types of errors. Implementers might find it useful to convey these errors to other libraries or software systems. This section provides specific URLs and descriptions for the errors, such that an ecosystem implementing technologies described by this specification might interoperate more effectively when errors occur.

When exposing these errors through an HTTP interface, implementers *SHOULD* use \[[RFC9457](#bib-rfc9457 "Problem Details for HTTP APIs")\] to encode the error data structure as a ProblemDetails [map](https://infra.spec.whatwg.org/#ordered-map). If \[[RFC9457](#bib-rfc9457 "Problem Details for HTTP APIs")\] is used:

-   The `type` value of the error object *MUST* be a URL that starts with the value `https://w3id.org/security#` and ends with the value in the section listed below.
-   The `title` value *SHOULD* provide a short but specific human-readable [string](https://infra.spec.whatwg.org/#string) for the error.
-   The `detail` value *SHOULD* provide a longer human-readable [string](https://infra.spec.whatwg.org/#string) for the error.

PROOF_GENERATION_ERROR
:   A request to generate a proof failed. See Section [4.2 Add Proof](#add-proof), and Section [4.3 Add Proof Set/Chain](#add-proof-set-chain).

PROOF_VERIFICATION_ERROR
:   An error was encountered during proof verification. See Section [4.4 Verify Proof](#verify-proof).

PROOF_TRANSFORMATION_ERROR
:   An error was encountered during the transformation process.

INVALID_DOMAIN_ERROR
:   The `domain` value in a proof did not match the expected value. See Section [4.4 Verify Proof](#verify-proof).

INVALID_CHALLENGE_ERROR
:   The `challenge` value in a proof did not match the expected value. See Section [4.4 Verify Proof](#verify-proof).

## 5. Security Considerations

[](#security-considerations)

The following section describes security considerations that developers implementing this specification should be aware of in order to create secure software.

### 5.1 Versioning Cryptography Suites

[](#versioning-cryptography-suites)

Cryptography secures information through the use of secrets. Knowledge of the necessary secret makes it computationally easy to access certain information. The same information can be accessed if a computationally-difficult, brute-force effort successfully guesses the secret. All modern cryptography requires the computationally difficult approach to remain difficult throughout time, which does not always hold due to breakthroughs in science and mathematics. That is to say that *Cryptography has a shelf life*.

This specification plans for the obsolescence of all cryptographic approaches by asserting that whatever cryptography is in use today is highly likely to be broken over time. Software systems have to be able to change the cryptography in use over time in order to continue to secure information. Such changes might involve increasing required secret sizes or modifications to the cryptographic primitives used. However, some combinations of cryptographic parameters might actually reduce security. Given these assumptions, systems need to be able to distinguish different combinations of safe cryptographic parameters, also known as cryptographic suites, from one another. When identifying or versioning cryptographic suites, there are several approaches that can be taken which include: parameters, numbers, and dates.

Parametric versioning specifies the particular cryptographic parameters that are employed in a cryptographic suite. For example, one could use an identifier such as `RSASSA-PKCS1-v1_5-SHA1`. The benefit to this scheme is that a well-trained cryptographer will be able to determine all of the parameters in play by the identifier. The drawback to this scheme is that most of the population that uses these sorts of identifiers are not well trained and thus will not understand that the previously mentioned identifier is a cryptographic suite that is no longer safe to use. Additionally, this lack of knowledge might lead software developers to generalize the parsing of cryptographic suite identifiers such that any combination of cryptographic primitives becomes acceptable, resulting in reduced security. Ideally, cryptographic suites are implemented in software as specific, acceptable profiles of cryptographic parameters instead.

Numbered versioning might specify a major and minor version number such as `1.0` or `2.1`. Numbered versioning conveys a specific order and suggests that higher version numbers are more capable than lower version numbers. The benefit of this approach is that it removes complex parameters that less expert developers might not understand with a simpler model that conveys that an upgrade might be appropriate. The drawback of this approach is that its not clear if an upgrade is necessary, as software version number increases often don\'t require an upgrade for the software to continue functioning. This can lead to developers thinking their usage of a particular version is safe, when it is not. Ideally, additional signals would be given to developers that use cryptographic suites in their software that periodic reviews of those suites for continued security are required.

Date-based versioning specifies a particular release date for a specific cryptographic suite. The benefit of a date, such as a year, is that it is immediately clear to a developer if the date is relatively old or new. Seeing an old date might prompt the developer to go searching for a newer cryptographic suite, where as a parametric or number-based versioning scheme might not. The downside of a date-based version is that some cryptographic suites might not expire for 5-10 years, prompting the developer to go searching for a newer cryptographic suite only to not find one that is newer. While this might be an inconvenience, it is one that results in safer ecosystem behavior.

### 5.2 Protecting Application Developers

[](#protecting-application-developers)

Modern cryptographic algorithms provide a number of tunable parameters and options to ensure that the algorithms can meet the varied requirements of different use cases. For example, embedded systems have limited processing and memory environments and might not have the resources to generate the strongest digital signatures for a given algorithm. Other environments, like financial trading systems, might only need to protect data for a day while the trade is occurring, while other environments might need to protect data for multiple decades. To meet these needs, cryptographic algorithm designers often provide multiple ways to configure a cryptographic algorithm.

Cryptographic library implementers often take the specifications created by cryptographic algorithm designers and specification authors and implement them such that all options are available to the application developers that use their libraries. This can be due to not knowing which combination of features a particular application developer might need for a given cryptographic deployment. All options are often exposed to application developers.

Application developers that use cryptographic libraries often do not have the requisite cryptographic expertise and knowledge necessary to appropriately select cryptographic parameters and options for a given application. This lack of expertise can lead to an inappropriate selection of cryptographic parameters and options for a particular application.

This specification sets the priority of constituencies to protect application developers over cryptographic library implementers over cryptographic specification authors over cryptographic algorithm designers. Given these priorities, the following recommendations are made:

-   Cryptographic algorithm designers are advised \[[RFC7696](#bib-rfc7696 "Guidelines for Cryptographic Algorithm Agility and Selecting Mandatory-to-Implement Algorithms")\] to minimize the number of options and parameters to as few as possible to ensure that cryptographic library implementers have a more easily auditable security attack surface for their software libraries.
-   Cryptographic specification authors are advised to, if possible, further minimize the number of options and parameters to as few as possible to ensure cryptographic agility while also keeping the auditable security attack surface for downstream software libraries to a minimum.
-   Cryptographic library implementers are advised to, if possible, provide known good combinations of options and parameters to application developers. There would ideally be two pre-set default configurations for any algorithmic class, such as Elliptic Curve Digital Signatures, with no ability to fine tune parameters and options when using these pre-sets. Library options can be provided to experts to fine tune their use of the library, use of those options by the general application developer population is to be discouraged.
-   Cryptographic library implementers can provide deprecated and experimental cryptographic functionality, but are advised to only do so when explicitly requested by the developer, such as through a library option, and such that the library produces warnings that deprecated or experimental cryptography has been enabled for the application.
-   Application developers are advised to choose from a number of pre-set cryptography library configurations and to avoid modifying cryptographic options and parameters, or using experimental or deprecated cryptography.

The guidance above is meant to ensure that useful cryptographic options and parameters are provided at the lower layers of the architecture while not exposing those options and parameters to application developers who may not fully understand the balancing benefits and drawbacks of each option.

### 5.3 Conventions for Naming Cryptography Suites

[](#conventions-for-naming-cryptography-suites)

Section [5.1 Versioning Cryptography Suites](#versioning-cryptography-suites) emphasized the importance of providing relatively easy to understand information concerning the timeliness of particular cryptographic suite, while section [5.2 Protecting Application Developers](#protecting-application-developers) further emphasized minimizing the number of options to be specified. Indeed, section [3. Cryptographic Suites](#cryptographic-suites) lists requirements for cryptographic suites which include detailed specification of algorithm, transformation, hashing, and serialization. Hence, the name of the cryptographic suite does not need to include all this detail, which implies the *parametric versioning* mentioned in section [5.1 Versioning Cryptography Suites](#versioning-cryptography-suites) is neither necessary nor desirable.

The recommended naming convention for cryptographic suites is a string composed of a signature algorithm identifier, separated by a hyphen from an option identifier (if the cryptosuite supports incompatible implementation options), followed by a hyphen and designation of the approximate year that the suite was proposed.

For example, the \[[DI-EDDSA](#bib-di-eddsa "The Edwards Digital Signature Algorithm Cryptosuites v1.0")\] is based on EdDSA digital signatures, supports two incompatible options based on canonicalization approaches, and was proposed in roughly the year 2022, so it would have two different cryptosuite names: "eddsa-rdfc-2022" and "eddsa-jcs-2022".

Although the \[[DI-ECDSA](#bib-di-ecdsa "The Elliptic Curve Digital Signature Algorithm Cryptosuites v1.0")\] is based on ECDSA digital signatures, supports the same two incompatible canonicalization approaches as \[[DI-EDDSA](#bib-di-eddsa "The Edwards Digital Signature Algorithm Cryptosuites v1.0")\], and supports two different levels of security (128 bit and 192 bit) via two alternative sets of elliptic curves and hashes, it has only two cryptosuite names: "ecdsa-rdfc-2019" and "ecdsa-jcs-2019". The security level and corresponding curves and hashes are determined from the multi-key format of the public key used in validation.

### 5.4 Agility and Layering

[](#agility-and-layering)

Cryptographic agility is a practice by which one designs *frequently connected* information security systems to support *switching between multiple cryptographic primitives and/or algorithms*. The primary goal of cryptographic agility is to enable systems to rapidly adapt to new cryptographic primitives and algorithms without making disruptive changes to the systems\' infrastructure. Thus, when a particular cryptographic primitive, such as the SHA-1 algorithm, is determined to be no longer safe to use, systems can be reconfigured to use a newer primitive via a simple configuration file change.

Cryptographic agility is most effective when the client and the server in the information security system are in regular contact. However, when the messages protected by a particular cryptographic algorithm are long-lived, as with [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential), and/or when the client (holder) might not be able to easily recontact the server (issuer), then cryptographic agility does not provide the desired protections.

Cryptographic layering is a practice where one designs *rarely connected* information security systems to *employ multiple primitives and/or algorithms at the same time*. The primary goal of cryptographic layering is to enable systems to survive the failure or one or more cryptographic algorithms or primitives without losing cryptographic protection on the payload. For example, digitally signing a single piece of information using RSA, ECDSA, and Falcon algorithms in parallel would provide a mechanism that could survive the failure of two of these three digital signature algorithms. When a particular cryptographic protection is compromised, such as an RSA digital signature using 768-bit keys, systems can still utilize the non-compromised cryptographic protections to continue to protect the information. Developers are urged to take advantage of this feature for all signed content that might need to be protected for a year or longer.

This specification provides for both forms of agility. It provides for cryptographic agility, which allows one to easily switch from one algorithm to another. It also provides for cryptographic layering, which allows one to simultaneously use multiple cryptographic algorithms, typically in parallel, such that any of those used to protect information can be used without reliance on or requirement of the others, while still keeping the digital proof format easy to use for developers.

### 5.5 Safer Abstractions

[](#safer-abstractions)

A [proof](#dfn-proof) contains a `proofValue`, in which a number of parameters related to a cryptographic proof can be embedded. For example, the selective disclosure algorithms in the [Data Integrity ECDSA Cryptosuites v1.0](https://www.w3.org/TR/vc-di-ecdsa/) specification include multiple cryptographic signatures in the `proofValue`, one for every item that is selectively disclosable. This has been done to make the technology easier and safer to use by application developers.

This specification urges specification authors to use a single value to abstract information that is rarely needed by the application layer. Much like a `data:` URL expressing an image encapsulates many of the image rendering parameters into a single value, the `proofValue` property (and other similar properties) abstracts information that is not useful to an application developer in order to simplify identification of fields that *are* important to the application. Abstracting information in this way leads to a data structure that is easier for developers to work with while being less susceptible to, for instance, accidental corruption due to a programming error that adds or removes a critical property, negatively impacting the cryptographic layer.

The design of data integrity in this specification abstracts information that is generally only useful to the cryptographic layer into a single property to ease the burden on application developers and to enhance the security of the system.

### 5.6 Transformations

[](#transformations)

At times, it is beneficial to transform the data being protected during the cryptographic protection process. Such \"in-line\" transformation can enable a particular type of cryptographic protection to be agnostic to the data format it is carried in. For example, some Data Integrity cryptographic suites utilize RDF Dataset Canonicalization \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\] which transforms the initial representation into a canonical form \[[N-QUADS](#bib-n-quads "RDF 1.1 N-Quads")\] that is then serialized, hashed, and digitally signed. As long as any syntax expressing the protected data can be transformed into this canonical form, the digital signature can be verified. This enables the same digital signature over the information to be expressed in JSON, CBOR, YAML, and other compatible syntaxes without having to create a cryptographic proof for every syntax.

Being able to express the same digital signature across a variety of syntaxes is beneficial because systems often have native data formats with which they operate. For example, some systems are written against JSON data, while others are written against CBOR data. Without transformation, systems that process their data internally as CBOR are required to store the digitally signed data structures as JSON (or vice-versa). This leads to double-storing data and can lead to increased security attack surface if the unsigned representation stored in databases accidentally deviates from the signed representation. By using transformations, the digital proof can live in the native data format to help prevent otherwise undetectable database drift over time.

This specification is designed to avoid requiring the duplication of signed information by utilizing \"in-line\" data transformations. Application developers are urged to work with cryptographically protected data in the native data format for their application and not separate storage of cryptographic proofs from the data being protected. Developers are also urged to regularly confirm that the cryptographically protected data has not been tampered with as it is written to and read from application storage.

Some transformations, such as RDF Dataset Canonicalization \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\], have mitigations for input data sets that can be used by attackers to consume excessive processing cycles. This class of attack is called [dataset poisoning](https://www.w3.org/TR/rdf-canon/#dataset-poisoning), and all modern RDF Dataset canonicalizers are required to detect these sorts of bad inputs and halt processing. The test suites for RDF Dataset Canonicalization includes such poisoned datasets to ensure that such mitigations exist in all conforming implementations. Generally speaking, cryptographic suite specifications that use transformations are required to mitigate these sorts of attacks, and implementers are urged to ensure that the software libraries that they use enforce these mitigations. These attacks are in the same general category as any resource starvation attack, such as HTTP clients that deliberately slow connections, thus starving connections on the server. Implementers are advised to consider these sorts of attacks when implementing defensive security strategies.

### 5.7 Protected Information

[](#protected-information)

The data that is protected by any [data integrity proof](#dfn-data-integrity-proof) is the [transformed data](#dfn-transformation). [Transformed data](#dfn-transformation) is generated by a [transformation algorithm](#dfn-transformation-algorithm) that is specified by a particular [cryptosuite](#dfn-cryptosuite). This protection mechanism differs from some more traditional digital signature mechanisms that do not perform any sort of [transformation](#dfn-transformation) on the input data. The benefits of [transformation](#dfn-transformation) are detailed in Section [5.6 Transformations](#transformations).

For example, [cryptosuites](#dfn-cryptosuite) such as [ecdsa-jcs-2019](https://www.w3.org/TR/vc-di-ecdsa/#ecdsa-jcs-2019) and [eddsa-jcs-2022](https://www.w3.org/TR/vc-di-eddsa/#eddsa-jcs-2022) use the [JSON Canonicalization Scheme (JCS)](https://www.rfc-editor.org/rfc/rfc8785) to [transform](#dfn-transformation) the data to canonicalized JSON, which is then cryptographically hashed and digitally signed. One benefit of this approach is that adding or removing formatting characters that do not impact the meaning of the information being signed, such as spaces, tabs, and newlines, does not invalidate the digital signature. More traditional digital signature mechanisms do not have this capability.

Other [cryptosuites](#dfn-cryptosuite) such as [ecdsa-rdfc-2019](https://www.w3.org/TR/vc-di-ecdsa/#ecdsa-rdfc-2019) and [eddsa-rdfc-2022](https://www.w3.org/TR/vc-di-eddsa/#eddsa-rdfc-2022) use [RDF Dataset Canonicalization](https://www.w3.org/TR/rdf-canon/) to [transform](#dfn-transformation) the data to canonicalized N-Quads \[[N-QUADS](#bib-n-quads "RDF 1.1 N-Quads")\], which is then cryptographically hashed and digitally signed. One benefit of this approach is that the cryptographic signature is portable to a variety of different syntaxes, such as JSON, YAML, and CBOR, without invalidating the signature. More traditional cryptographic signature mechanisms do not have this capability.

Implementers and developers are urged to not trust information that contains a [data integrity proof](#dfn-data-integrity-proof) unless the proof has been [verified](#dfn-verified) and the verified data is provided in a return value from a software library that has confirmed that all data returned has been successfully protected.

### 5.8 Data Opacity

[](#data-opacity)

The inspectability of application data has effects on system efficiency and developer productivity. When cryptographically protected application data, such as base-encoded binary data, is not easily processed by application subsystems, such as databases, it increases the effort of working with the cryptographically protected information. For example, a cryptographically protected payload that can be natively stored and indexed by a database will result in a simpler system that:

-   benefits from utilizing existing industry-standard database features with no changes to the protected information,
-   avoids the complexity of duplicating data where one copy of the data preserves the message and digital signature, while the other copy only stores and indexes the message and is what drives system behaviour,
-   avoids the complexity of bespoke solutions that have to structurally modify the protected information, such as serializing and deserializing nested digitally signed data that has multiple nested base-encoded payloads.

Similarly, a cryptographically protected payload that can be processed by multiple upstream networked systems increases the ability to properly layer security architectures. For example, if upstream systems do not have to repeatedly decode the incoming payload, it increases the ability for a system to distribute processing load by specializing upstream subsystems to actively combat attacks. While a digital signature needs to always be checked before taking substantive action, other upstream checks can be performed on transparent payloads --- such as identifier-based rate limiting, signature expiration checking, or nonce/challenge checking --- to reject obviously bad requests.

Additionally, if a developer is not able to easily view data in a system, the ability to easily audit or debug system correctness is hampered. For example, requiring application developers to cut-and-paste base-encoded application data makes development more challenging and increases the chances that obvious bugs will be missed because every message needs to go through a manually operated base-decoding tool.

There are times, however, where the correct design decision is to make data opaque. Data that does not need to be processed by other application subsystems, as well as data that does not need to be modified or accessed by an application developer, can be serialized into opaque formats. Examples include digital signature values, cryptographic key parameters, and other data fields that only need to be accessed by a cryptographic library and need not be modified by the application developer. There are also examples where data opacity is appropriate when the underlying subsystem does not expose the application developer to the underlying complexity of the opaque data, such as databases that perform encryption at rest. In these cases, the application developer continues to develop against transparent application data formats while the database manages the complexity of encrypting and decrypting the application data to and from long-term storage.

This specification strives to provide an architecture where application data remains in its native format and is not made opaque, while other cryptographic data, such as digital signatures, are kept in their opaque binary encoded form. Cryptographic suite implementers are urged to consider appropriate use of data opacity when designing their suites, and to weigh the design trade-offs when making application data opaque versus providing access to cryptographic data at the application layer.

### 5.9 Verification Method Binding

[](#verification-method-binding)

Implementers ensure that a [verification method](#dfn-verification-method) is bound to a particular controller by going from the definition of the [verification method](#dfn-verification-method) to the [controlled identifier document](#dfn-controlled-identifier-document), and then ensuring that the [controlled identifier document](#dfn-controlled-identifier-document) also contains a reference to the [verification method](#dfn-verification-method). This process is described in the algorithm for [retrieving a verification method](https://www.w3.org/TR/cid-1.0/#retrieve-verification-method).

### 5.10 Verification Relationship Validation

[](#verification-relationship-validation)

When an implementation is [verifying a proof](#verify-proof), it is imperative that it verify not only that the [verification method](#dfn-verification-method) used to generate the proof is listed in the [controlled identifier document](#dfn-controlled-identifier-document), but also that it was intended to be used to generate the proof that is being verified. This process is known as \"verification relationship validation\".

The process of validating a verification relationship is outlined in Section [3.3 Retrieve Verification Method](https://www.w3.org/TR/cid-1.0/#retrieve-verification-method) of the [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/) specification.

This process is used to ensure that cryptographic material, such as a private cryptographic key, is not misused by application to an unintended purpose. An example of cryptographic material misuse would be if a private cryptographic key meant to be used to issue a [verifiable credential](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) was instead used to log into a website (that is, for authentication). Not checking a verification relationship is dangerous because the restriction and protection profile for some cryptographic material could be determined by its intended use. For example, some applications could be trusted to use cryptographic material for only one purpose, or some cryptographic material could be more protected, such as through storage in a hardware security module in a data center versus as an unencrypted file on a laptop.

### 5.11 Proof Purpose Validation

[](#proof-purpose-validation)

When an implementation is [verifying a proof](#verify-proof), it is imperative that it verify that the [proof purpose](#dfn-proof-purpose) match the intended use.

This process is used to ensure that proofs are not misused by an application for an unintended purpose, as this is dangerous for the proof creator. An example of misuse would be if a proof that stated its purpose was for securing assertions in [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) was instead used for [authentication](#dfn-authentication) to log into a website. In this case, the proof creator attached proofs to any number of [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) that they expected to be distributed to an unbounded number of other parties. Any one of these parties could log into a website as the proof creator if the website erroneously accepted such a proof as [authentication](#dfn-authentication) instead of its intended purpose.

### 5.12 Canonicalization Method Security

[](#canonicalization-method-security)

The way in which a transformation, such as canonicalization, is performed can affect the security characteristics of a system. Selecting the best canonicalization mechanisms depends on the use case. Often, the simplest mechanism that satisfies the desired security requirements is the best choice. This section attempts to provide simple guidance to help implementers choose between the two main canonicalization mechanisms referred to in this specification, namely JSON Canonicalization Scheme \[[RFC8785](#bib-rfc8785 "JSON Canonicalization Scheme (JCS)")\] and RDF Dataset Canonicalization \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\].

If an application only uses JSON and does not depend on any form of RDF semantics, then using a cryptography suite that uses JSON Canonicalization Scheme \[[RFC8785](#bib-rfc8785 "JSON Canonicalization Scheme (JCS)")\] is an attractive approach.

If an application uses JSON-LD and needs to secure the semantics of the document, then using a cryptography suite that uses RDF Dataset Canonicalization \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\] is an attractive approach.

Implementers are also advised that other mechanisms that perform no transformations are available, that secure the data by wrapping it in a cryptographic envelope instead of embedding the proof in the data, such as JWTs \[[RFC7519](#bib-rfc7519 "JSON Web Token (JWT)")\] and CWTs \[[RFC8392](#bib-rfc8392 "CBOR Web Token (CWT)")\]. These approaches have simplicity advantages in some use cases, at the expense of some of the benefits provided by the approach detailed in this specification.

### 5.13 Canonicalization Method Correctness

[](#canonicalization-method-correctness)

One of the algorithmic processes used by this specification is canonicalization, which is a type of [transformation](#dfn-transformation). Canonicalization is the process of taking information that might be expressed in a variety of semantically equivalent ways as input, and expressing all output in a single way, called a \"canonical form\".

The security of a resulting [data integrity proof](#dfn-data-integrity-proof) that utilizes canonicalization is highly dependent on the correctness of the algorithm. For example, if a canonicalization algorithm converts two inputs that have different meanings into the same output, then the author\'s intentions can be misrepresented to a [verifier](#dfn-verifier). This can be used as an attack vector by adversaries.

Additionally, if semantically relevant information in an input is not present in the output, then an attacker could insert such information into a message without causing proof verification to fail. This is similar to another transformation that is commonly used when cryptographically signing messages: cryptographic hashing. If an attacker is able to produce the same cryptographic hash from a different input, then the cryptographic hash algorithm is not considered secure.

Implementers are strongly urged to ensure proper vetting of any canonicalization algorithms to be used for [transformation](#dfn-transformation) of input to a [hashing](#dfn-hashing) process. Proper vetting includes, at a minimum, association with a peer reviewed mathematical proof of algorithm correctness; multiple implementations and vetting by experts in a standards setting organization is preferred. Implementers are strongly urged not to invent or use new mechanisms unless they have formal training in information canonicalization and/or access to experts in the field who are capable of producing a peer reviewed mathematical proof of algorithm correctness.

### 5.14 Network Requests

[](#network-requests)

This specification is designed in such a way that no network requests are required when verifying a proof on a [conforming secured document](#dfn-conforming-secured-document). Readers might note, however, that [JSON-LD contexts](#data-model) and [verification methods](#dfn-verification-method) can contain URLs that might be retrieved over a network connection. This concern exists for any URL that might be loaded from the network during or after verification.

To the extent possible, implementers are urged to permanently or aggressively cache such information to reduce the attack surface on an implementation that might need to fetch such URLs over the network. For example, caching techniques for [JSON-LD contexts](#data-model) are described in Section [2.4 Contexts and Vocabularies](#contexts-and-vocabularies), and some [verification methods](#dfn-verification-method), such as `did:key` \[[DID-KEY](#bib-did-key "The did:key Method")\], do not need to be fetched from the network at all.

When it is not possible to use cached information, such as when a specific HTTP URL-based instance of a [verification method](#dfn-verification-method) is encountered for the first time, implementers are cautioned to use defensive measures to mitigate [denial-of-service attacks](https://en.wikipedia.org/wiki/Denial-of-service_attack) during any process that might fetch a resource from the network.

### 5.15 Other Security Considerations

[](#other-security-considerations)

Since the technology to secure documents described by this specification is generalized in nature, the security implications of its use might not be immediately apparent to readers. To understand the sort of security concerns one might need to consider in a complete software system, implementers are urged to read about how this technology is used in the [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) ecosystem \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\]; see the section on [Verifiable Credential Security Considerations](https://www.w3.org/TR/vc-data-model-2.0/#security-considerations) for more information.

## 6. Privacy Considerations

[](#privacy-considerations)

The following section describes privacy considerations that developers implementing this specification should be aware of in order to create privacy enhancing software.

### 6.1 Unlinkability

[](#unlinkability)

When a digitally-signed payload contains data that is seen by multiple verifiers, it becomes a point of correlation. An example of such data is a shopping loyalty card number. Correlatable data can be used for tracking purposes by verifiers, which can sometimes violate privacy expectations. The fact that some data can be used for tracking might not be immediately apparent. Examples of such correlatable data include, but are not limited to, a static digital signature or a cryptographic hash of an image.

It is possible to create a digitally-signed payload that does not have any correlatable tracking data while also providing some level of assurance that the payload is trustworthy for a given interaction. This characteristic is called unlinkability which ensures that no correlatable data are used in a digitally-signed payload while still providing some level of trust, the sufficiency of which has to be determined by each verifier.

It is important to understand that not all use cases require or even permit unlinkability. There are use cases where linkability and correlation are required due to regulatory or safety reasons, such as correlating organizations and individuals that are shipping and storing hazardous materials. Unlinkability is useful when there is an expectation of privacy for a particular interaction.

There are at least two mechanisms that can provide some level of unlinkability. The first method is to ensure that no data value used in the message is ever repeated in a future message. The second is to ensure that any repeated data value provides adequate herd privacy such that it becomes practically impossible to correlate the entity that expects some level of privacy in the interaction.

A variety of methods can be used to achieve unlinkability. These methods include ensuring that a message is a single use bearer token with no information that can be used for the purposes of correlation, using attributes that ensure an adequate level of herd privacy, and the use of cryptosuites that enable the entity presenting a message to regenerate new signatures while not compromising the trust in the message being presented.

### 6.2 Selective Disclosure

[](#selective-disclosure)

Selective disclosure is a technique that enables the recipient of a previously-signed message (that is, a message signed by its creator) to reveal only parts of the message without disturbing the verifiability of those parts. For example, one might selectively disclose a digital driver\'s license for the purpose of renting a car. This could involve revealing only the issuing authority, license number, birthday, and authorized motor vehicle class from the license. Note that in this case, the license number is correlatable information, but some amount of privacy is preserved because the driver\'s full name and address are not shared.

Not all software or cryptosuites are capable of providing selective disclosure. If the author of a message wishes it to be selectively disclosable by its recipient, then they need to enable selective disclosure on the specific message, and both need to use a capable cryptosuite. The author might also make it mandatory to disclose certain parts of the message. A recipient that wants to selectively disclose partial content of the message needs to utilize software that is able to perform the technique. An example of a cryptosuite that supports selective disclosure is `bbs-2023`.

It is possible to selectively disclose information in a way that does not preserve unlinkability. For example, one might want to disclose the inspection results related to a shipment, which include the shipment identifier or lot number, which might have to be correlatable due to regulatory requirements. However, disclosure of the entire inspection result might not be required as selectively disclosing just the pass/fail status could be deemed adequate. For more information on disclosing information while preserving privacy, see Section [6.1 Unlinkability](#unlinkability).

### 6.3 Previous Proofs

[](#previous-proofs)

When using the `previousProof` feature defined in [2.1.2 Proof Chains](#proof-chains), implementations are required to digitally sign over one or more previous proofs, so as to include them in the secured payload. This inevitably exposes information related to each entity that added a previous proof.

At minimum, the [verification method](#dfn-verification-method) for the previous proof, such as a public key, is seen by the creator of the next proof in a proof chain. This can be a privacy concern if the creator of the previous proof did not intend to be included in a proof chain, but is an inevitable outcome when adding a non-repudiable digital signature to a document of any kind.

It is possible to use more advanced cryptographic mechanisms, such as a [group signature](https://en.wikipedia.org/wiki/Group_signature), to hide the identity of the signer of a message, and it is also possible for a Data Integrity cryptographic suite to mitigate this privacy concern.

### 6.4 Fingerprinting Network Requests

[](#fingerprinting-network-requests)

Fingerprinting concerns exist for any URL that might be loaded from the network during or after [proof verification](#verify-proof). This specification is designed in such a way that no network requests are necessary when verifying a proof on a [conforming secured document](#dfn-conforming-secured-document). Readers might note, however, that [JSON-LD contexts](#data-model) and [verification methods](#dfn-verification-method) can contain resource URLs that might be retrieved over a network connection leading to fingerprinting concerns.

For example, creators of [conforming secured documents](#dfn-conforming-secured-document) might craft unique per-document URLs for [JSON-LD contexts](#data-model) and [verification methods](#dfn-verification-method). When verifying such a document, a verifier fetching that information from the network would reveal their interest in the [conforming secured document](#dfn-conforming-secured-document) to the creator of the document, which might lead to a mismatch in privacy expectations for any entity that is not the creator of the document.

Implementers are urged to follow the guidance in Section [5.14 Network Requests](#network-requests) on URL caching and implementing defensively when fetching URLs from the network. Usage of techniques such as [Oblivious HTTP](https://datatracker.ietf.org/doc/html/draft-ietf-ohai-ohttp) to retrieve resources from the network, without revealing the client that is making the request, are encouraged. Additionally, heuristics might be used to determine whether creators of [conforming secured documents](#dfn-conforming-secured-document) are using fingerprinting URLs in a way that might violate privacy expectations. These heuristics could be used to display warnings to entities that might process documents containing suspected fingerprinting URLs.

### 6.5 Canonicalization Method Privacy

[](#canonicalization-method-privacy)

The way in which a transformation, namely canonicalization, is performed can affect the privacy characteristics of a system. Selecting the best canonicalization mechanism depends on the use case. This section attempts to provide simple guidance to help implementers pick between the two main canonicalization mechanisms referred to in this specification, namely JSON Canonicalization Scheme \[[RFC8785](#bib-rfc8785 "JSON Canonicalization Scheme (JCS)")\] and RDF Dataset Canonicalization \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\], from a privacy perspective.

If an application does not require performing a selective disclosure of information in a secured document, nor does it utilize JSON-LD, then JSON Canonicalization Scheme \[[RFC8785](#bib-rfc8785 "JSON Canonicalization Scheme (JCS)")\] is an attractive approach.

If an application uses JSON-LD and might require selective disclosure of information in a secured document, then using a cryptography suite that uses RDF Dataset Canonicalization \[[RDF-CANON](#bib-rdf-canon "RDF Dataset Canonicalization")\] is an attractive approach.

Implementers are also advised that other selective disclosure mechanisms that perform no transformations are available, that secure the data by wrapping it in a cryptographic envelope instead of embedding the proof in the data, such as SD-JWTs \[[SD-JWT](#bib-sd-jwt "Selective Disclosure for JWTs (SD-JWT)")\]. This approach has simplicity advantages in some use cases, at the expense of some of the benefits provided by the approach detailed in this specification.

### 6.6 Other Privacy Considerations

[](#other-privacy-considerations)

Since the technology to secure documents described by this specification is generalized in nature, the privacy implications of its use might not be immediately apparent to readers. To understand the sort of privacy concerns one might need to consider in a complete software system, implementers are urged to read about how this technology is used in the [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential) ecosystem \[[VC-DATA-MODEL-2.0](#bib-vc-data-model-2.0 "Verifiable Credentials Data Model v2.0")\]; see the section on [Verifiable Credential Privacy Considerations](https://www.w3.org/TR/vc-data-model-2.0/#privacy-considerations) for more information.

## 7. Accessibility Considerations

[](#accessibility-considerations)

The following section describes accessibility considerations that developers implementing this specification are urged to consider in order to ensure that their software is usable by people with different cognitive, motor, and visual needs. As a general rule, this specification is used by system software and does not directly expose individuals to information subject to accessibility considerations. However, there are instances where individuals might be indirectly exposed to information expressed by this specification and thus the guidance below is provided for those situations.

### 7.1 Presenting Time Values

[](#presenting-time-values)

This specification enables the expression of dates and times related to the validity period of cryptographic proofs. This information might be indirectly exposed to an individual if a proof is processed and is detected to be outside an allowable time range. When exposing these dates and times to an individual, implementers are urged to take into account [cultural normas and locales when representing dates and times](https://www.w3.org/TR/vc-data-model-2.0/#representing-time) in display software. In addition to these considerations, presenting time values in a way that eases the cognitive burden on the individual receiving the information is a suggested best practice.

For example, when conveying the expiration date for a particular set of digitally signed information, implementers are urged to present the time of expiration using language that is easier to understand rather than language that optimizes for accuracy. Presenting the expiration time as \"This ticket expired three days ago.\" is preferred over a phrase such as \"This ticket expired on July 25th 2023 at 3:43 PM.\" The former provides a relative time that is easier to comprehend than the latter time, which requires the individual to do the calculation in their head and presumes that they are capable of doing such a calculation.

## A. Understanding Proof Sets and Proof Chains

[](#understanding-proof-sets-and-proof-chains)

*This section is non-normative.*

Sections [2.1.1 Proof Sets](#proof-sets) and [2.1.2 Proof Chains](#proof-chains) describe how multiple proofs can be expressed in a [secured data document](#dfn-secured-data-document); that is, instead of a single [proof](#dfn-proof) included in the [secured data document](#dfn-secured-data-document), one can express multiple proofs in an [list](https://infra.spec.whatwg.org/#list) as shown in [Example 6](#example-a-proof-set-in-a-data-document) and [Example 7](#example-a-proof-chain-in-a-data-document). The elements of this [list](https://infra.spec.whatwg.org/#list) are members of a [proof set](#dfn-proof-set) and, optionally, a [proof chain](#dfn-proof-chain). The purpose of this section is to explain the intended use of each of these features and, in particular, their differing security properties. These differing security properties lead to differences in the processing in section [4.3 Add Proof Set/Chain](#add-proof-set-chain).

This section represents [secured data documents](#dfn-secured-data-document), including their proofs, in an abbreviated manner so that the important security properties can be observed.

Consider a scenario with three signatories: a CEO, a CFO, and a VP of Engineering. Each will need to have a public key and secret key pair for signing a document. We denote the secret/public keys of each of these signatories by *secretCEO/publicCEO*, *secretCFO/publicCFO*, and *secretVPE/publicVPE*, respectively.

When constructing a [proof set](#dfn-proof-set) where each of the signatories signs an `inputDocument` without concern, we construct a proof symbolically as:

[Example 9](#example-symbolic-expression-of-how-a-proof-is-created): Symbolic expression of how a proof is created

``` nohighlight
{
  "type": "DataIntegrityProof",
  "cryptosuite": "eddsa-jcs-2022",
  "created": "2023-03-05T19:23:24Z",
  "proofPurpose": "assertionMethod",
  "verificationMethod": publicCEO,
  "proofValue": signature(secretCEO, inputDocument)
}
```

Where *publicCEO* is used as a placeholder for a reference that resolves to the CEO\'s public key and **signature(`secretKey`, `inputDocument`)** denotes the computation of a digital signature by a particular data integrity cryptosuite using a particular secret key over a particular document. The `type`, `cryptosuite`, `created`, and `proofPurpose` attributes do not factor into our discussion so we will omit them. In particular, below we show all the proofs in a [proof set](#dfn-proof-set) on a document that has been signed by the VP of Engineering, the CFO, and the CEO:

[Example 10](#example-symbolic-expression-of-a-proof-set): Symbolic expression of a proof set

``` nohighlight
{
  // Remainder of secured data document not shown (above)
  "proof": [{
    "verificationMethod": publicVPE,
    "proofValue": signature(secretVPE, inputDocument)
  }, {
    "verificationMethod": publicCFO,
    "proofValue": signature(secretCFO, inputDocument)
  }, {
    "verificationMethod": publicCEO,
    "proofValue": signature(secretCEO, inputDocument)
  }]
}
```

A [holder](https://www.w3.org/TR/vc-data-model-2.0/#dfn-holders) or any other intermediary receiving a [secured data document](#dfn-secured-data-document) containing a [proof set](#dfn-proof-set) is able to remove any of the `proof` values within the set prior to passing it on to another entity and the [secured data document](#dfn-secured-data-document) will still verify. This might or might not have been the intent. For the signatories sending a birthday card to a valued employee, using a [proof set](#dfn-proof-set) is probably fine. If we are trying to model a business process where approvals ascend the company hierarchy, this would not be ideal, since any intermediary could remove signatures from the [proof set](#dfn-proof-set) and still have it verify; for instance, in the example below, it looks like the CFO and CEO approved something without the VP of Engineering\'s concurrence.

[Example 11](#example-removal-of-a-signature-in-a-proof-set): Removal of a signature in a proof set

``` nohighlight
{
  // Remainder of secured data document not shown (above)
  "proof": [{
    "verificationMethod": publicCFO,
    "proofValue": signature(secretCFO, inputDocument)
  }, {
    "verificationMethod": publicCEO,
    "proofValue": signature(secretCEO, inputDocument)
  }]
}
```

It is possible to introduce a dependency between [proofs](#dfn-proof) in a [proof set](#dfn-proof-set) by setting the `id` property of each proof such that another proof can reference it. In other words, a *dependent proof* will be referenced by other *relying proofs* by using the `previousProof` property. Such *dependency chains* can have arbitrary depth. The **intent** of such a [proof chain](#dfn-proof-chain) is to model an approval chain in a business process or a notary witnessing analog signatures.

The examples below demonstrate how a [proof chain](#dfn-proof-chain) can be constructed when the VP of Engineering signs off on the document first; based on the VP of Engineering\'s signature and a review, the CFO then signs off on the document; and finally, based on both prior signatures and a review, the CEO signs off on the document. Since others will be referring to the VP of Engineering\'s signature, we need to add an `id` to the proof. First the VP of Engineering signs the [input document](#dfn-input-document):

[Example 12](#example-proof-chain-containing-first-proof-with-id-property-set): Proof chain containing first proof with \`id\` property set

``` nohighlight
{
  // Remainder of secured data document not shown (above)
  "proof": {
    "id": "urn:proof-1",
    "verificationMethod": publicVPE,
    "proofValue": signature(secretVPE, inputDocument)
  }
}
```

Next, the CFO receives the document, verifies that the VP of Engineering signed it, and signs it based on a review and on the signature of the VP of Engineering. For this, we need to set up the [proof chain](#dfn-proof-chain) by indicating a dependency on the proof in the document just received. We do this by setting the `previousProof` property of the second proof to the value `urn:proof-1`, which \"binds\" the second proof to the first proof, which is then signed. The following example shows how the dependency on the first proof is created:

[Example 13](#example-proof-chain-containing-two-proofs): Proof chain containing two proofs

``` nohighlight
{
  // Remainder of secured data document not shown (above)
  "proof": [{
    "id": "urn:proof-1",
    "verificationMethod": publicVPE,
    "proofValue": signature(secretVPE, inputDocument)
  }, {
    "id": "urn:proof-2",
    "verificationMethod": publicCFO,
    "previousProof": "urn:proof-1",
    "proofValue": signature(secretCFO, inputDocumentWithProof1)
  }]
}
```

Now, when the CEO verifies the received [secured data document](#dfn-secured-data-document) with the above [proof chain](#dfn-proof-chain), they will check that the CFO signed based on the signature of the VP of Engineering. First, they will check the proof with an `id` property whose value is `urn:proof-1` against the public key of the VP of Engineering. Note that this proof is over the original document.

Next, the CEO will check the proof with an `id` property whose value is `urn:proof-2` against the public key of the CFO. However, to make sure that the CFO signed the document with proof that the VP of Engineering had already signed, we verify this proof over the combination of the document and `urn:proof-1`. If verification is successful, the CEO signs, producing a proof over the document which includes `urn:proof-1` and `urn:proof-2`. The final [proof chain](#dfn-proof-chain) looks like this:

[Example 14](#example-proof-chain-containing-three-proofs): Proof chain containing three proofs

``` nohighlight
{
  // Remainder of secured data document not shown (above)
  "proof": [{
    "id": "urn:proof-1",
    "verificationMethod": publicVPE,
    "proofValue": signature(secretVPE, inputDocument)
  }, {
    "id": "urn:proof-2",
    "verificationMethod": publicCFO,
    "previousProof": "urn:proof-1",
    "proofValue": signature(secretCFO, inputDocumentWithProof1)
  }, {
    "id": "urn:proof-3",
    "verificationMethod": publicCEO,
    "previousProof": "urn:proof-2",
    "proofValue": signature(secretCEO, inputDocumentWithProof2)
  }]
}
```

The recipient of this [secured data document](#dfn-secured-data-document) then validates it in a similar way, checking each proof in the chain.

## B. Revision History

[](#revision-history)

*This section is non-normative.*

This section contains the substantive changes that have been made to this specification over time.

Changes since the [Second Candidate Recommendation](https://www.w3.org/TR/2024/CR-vc-data-integrity-20241219/):

-   Various editorial changes in algorithms and descriptions to improve readability.
-   Remove numerical error codes since implementations were not using them.
-   Update to use newly published [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/) specification references.

Changes since the [First Candidate Recommendation](https://www.w3.org/TR/2023/CR-vc-data-integrity-20231121/):

-   Various editorial changes in algorithms and descriptions to improve readability.
-   Moved Multikey definitions to controlled identifier document.
-   Unify error handling between all Data Integrity cryptosuites.
-   Ensure that the `created` proof option is not required and additional proof options are included in the generated proof.
-   Add language related to context injection.
-   Update proof serialization format and algorithm arguments for cryptosuite interfaces.

Changes since the [First Public Working Draft](https://www.w3.org/TR/2022/WD-vc-data-integrity-20221110/):

-   Added a section on proof purpose validation.
-   Refactored chained proof functionality.
-   Added the algorithm to retrieve a verification method.
-   Added `JsonWebKey` and `Multikey` definitions and context files.
-   Deprecated `Ed25519Signature2020` and moved into separate specification.
-   Updated Multibase and Multihash references and add text to normatively define certain Multikey values.
-   Added Internationalization and Accessibility Considerations sections.
-   Added cryptographic hashes for context and vocabulary files and note that those files are to be cached.
-   Added section on relationship to Linked Data and [verifiable credentials](https://www.w3.org/TR/vc-data-model-2.0/#dfn-verifiable-credential).
-   Added algorithms for proof sets and chains.
-   Added `nonce` and `expires` to proofs.
-   Added requirement to protect terms in JSON-LD mode.
-   Required cryptosuite specifications that do RDF canonicalization to abort when poison graphs are detected.
-   Added section on how context injection is implemented.
-   Added error definitions with anchors to specification and vocabulary.
-   Added definitions for secret key expression.
-   Added `revoked` and `expires` to verification methods.
-   Updated proof `domain` property to allow for an array of values.
-   Added `cryptosuiteString` type to proofs to enable proof compression.
-   Added `digestMultibase` property and `multibase` data type for securing remote content, and guidance on adding `digestMultibase` to contexts.
-   Narrowed date time values to use `dateTimeStamp`.
-   Ensure that base URL is set to null when processing as RDF.
-   Added requirement that `DataIntegrityProof` objects need to contain the `cryptosuite` property.

## C. Acknowledgements

[](#acknowledgements)

*This section is non-normative.*

Work on this specification has been supported by the Rebooting the Web of Trust community facilitated by Christopher Allen, Shannon Appelcline, Kiara Robles, Brian Weller, Betty Dhamers, Kaliya Young, Manu Sporny, Drummond Reed, Joe Andrieu, Heather Vescent, Kim Hamilton Duffy, Samantha Chase, Andrew Hughes, Will Abramson, Erica Connell and Eric Schuh. The participants in the Internet Identity Workshop, facilitated by Phil Windley, Kaliya Young, Doc Searls, and Heidi Nobantu Saul, also supported the refinement of this work through numerous working sessions designed to educate about, debate on, and improve this specification.

The Working Group also thanks our Chair, Brent Zundel, our ex-Chair, Kristina Yasuda, as well as our W3C Staff Contact, Ivan Herman, for their expert management and steady guidance of the group through the W3C standardization process.

Portions of the work on this specification have been funded by the United States Department of Homeland Security\'s Science and Technology Directorate under contracts 70RSAT20T00000029, 70RSAT21T00000016, 70RSAT23T00000005, 70RSAT20T00000010/P00001, 70RSAT20T00000029, 70RSAT21T00000016/P00001, 70RSAT23T00000005, 70RSAT23C00000030, 70RSAT23R00000006, 70RSAT24T00000011, and the National Science Foundation through NSF 22-572. The content of this specification does not necessarily reflect the position or the policy of the U.S. Government and no official endorsement should be inferred.

The Working Group would like to thank the following individuals for reviewing and providing feedback on the specification (in alphabetical order by last name or their GitHub handle if a name was not provided):

Will Abramson, Mahmoud Alkhraishi, Christopher Allen, Joe Andrieu, Bohdan Andriyiv, George Aristy, [Anthony](https://github.com/Anthony), Greg Bernstein, [Bob420](https://github.com/Bob420), Sarven Capadisli, Melvin Carvalho, David Chadwick, Gabe Cohen, Matt Collier, Sebastian Crane, Kim Hamilton Duffy, Snorre Lothar von Gohren Edwin, Veikko Eeva, Eric Elliott, Raphael Flechtner, Julien Fraichot, Benjamin Goering, Kyle Den Hartog, Joseph Heenan, Helge Krueger, Ivan Herman, Michael Herman, Alen Horvat, Anil John, Andrew Jones, Michael B. Jones, Rieks Joosten, Gregory K., Gregg Kellogg, Filip Kolarik, David I. Lehn, Charles E. Lehner, Christine Lemmer-Webber, Eric Lim, Dave Longley, Tobias Looker, Jer Miller, [nightpool](https://github.com/nightpool), Bert Van Nuffelen, Luis Osta, Nate Otto, George J. Padayatti, Addison Phillips, Mike Prorock, Brian Richter, Anders Rundgren, Eugeniu Rusu, Markus Sabadello, [silverpill](https://github.com/silverpill), Wesley Smith, Manu Sporny, Orie Steele, Patrick St-Louis, Henry Story, Oliver Terbu, Ted Thibodeau Jr., John Toohey, Mike Varley, Jeffrey Yasskin, Kristina Yasuda, Benjamin Young, Dmitri Zagidulin, and Brent Zundel.

## D. References

[](#references)

### D.1 Normative references

[](#normative-references)

\[ASCII\]
:   [ISO/IEC 646:1991, Information technology \-- ISO 7-bit coded character set for information interchange](https://www.ecma-international.org/publications-and-standards/standards/ecma-6/). Ecma International. URL: <https://www.ecma-international.org/publications-and-standards/standards/ecma-6/>

\[CID\]
:   [Controlled Identifiers v1.0](https://www.w3.org/TR/cid-1.0/). Michael Jones; Manu Sporny. W3C. 15 May 2025. W3C Recommendation. URL: <https://www.w3.org/TR/cid-1.0/>

\[INFRA\]
:   [Infra Standard](https://infra.spec.whatwg.org/). Anne van Kesteren; Domenic Denicola. WHATWG. Living Standard. URL: <https://infra.spec.whatwg.org/>

\[JSON-LD11\]
:   [JSON-LD 1.1](https://www.w3.org/TR/json-ld11/). Gregg Kellogg; Pierre-Antoine Champin; Dave Longley. W3C. 16 July 2020. W3C Recommendation. URL: <https://www.w3.org/TR/json-ld11/>

\[JSON-LD11-API\]
:   [JSON-LD 1.1 Processing Algorithms and API](https://www.w3.org/TR/json-ld11-api/). Gregg Kellogg; Dave Longley; Pierre-Antoine Champin. W3C. 16 July 2020. W3C Recommendation. URL: <https://www.w3.org/TR/json-ld11-api/>

\[MIMESNIFF\]
:   [MIME Sniffing Standard](https://mimesniff.spec.whatwg.org/). Gordon P. Hemsley. WHATWG. Living Standard. URL: <https://mimesniff.spec.whatwg.org/>

\[RDF-CANON\]
:   [RDF Dataset Canonicalization](https://www.w3.org/TR/rdf-canon/). Gregg Kellogg; Dave Longley; Dan Yamamoto. W3C. 21 May 2024. W3C Recommendation. URL: <https://www.w3.org/TR/rdf-canon/>

\[RFC2119\]
:   [Key words for use in RFCs to Indicate Requirement Levels](https://www.rfc-editor.org/rfc/rfc2119). S. Bradner. IETF. March 1997. Best Current Practice. URL: <https://www.rfc-editor.org/rfc/rfc2119>

\[RFC8174\]
:   [Ambiguity of Uppercase vs Lowercase in RFC 2119 Key Words](https://www.rfc-editor.org/rfc/rfc8174). B. Leiba. IETF. May 2017. Best Current Practice. URL: <https://www.rfc-editor.org/rfc/rfc8174>

\[RFC8259\]
:   [The JavaScript Object Notation (JSON) Data Interchange Format](https://www.rfc-editor.org/rfc/rfc8259). T. Bray, Ed. IETF. December 2017. Internet Standard. URL: <https://www.rfc-editor.org/rfc/rfc8259>

\[RFC8785\]
:   [JSON Canonicalization Scheme (JCS)](https://www.rfc-editor.org/rfc/rfc8785). A. Rundgren; B. Jordan; S. Erdtman. IETF. June 2020. Informational. URL: <https://www.rfc-editor.org/rfc/rfc8785>

\[RFC9457\]
:   [Problem Details for HTTP APIs](https://www.rfc-editor.org/rfc/rfc9457). M. Nottingham; E. Wilde; S. Dalal. IETF. July 2023. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc9457>

\[URL\]
:   [URL Standard](https://url.spec.whatwg.org/). Anne van Kesteren. WHATWG. Living Standard. URL: <https://url.spec.whatwg.org/>

\[VC-DATA-MODEL-2.0\]
:   [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/vc-data-model-2.0/). Ivan Herman; Michael Jones; Manu Sporny; Ted Thibodeau Jr; Gabe Cohen. W3C. 15 May 2025. W3C Recommendation. URL: <https://www.w3.org/TR/vc-data-model-2.0/>

\[XMLSCHEMA11-2\]
:   [W3C XML Schema Definition Language (XSD) 1.1 Part 2: Datatypes](https://www.w3.org/TR/xmlschema11-2/). David Peterson; Sandy Gao; Ashok Malhotra; Michael Sperberg-McQueen; Henry Thompson; Paul V. Biron et al. W3C. 5 April 2012. W3C Recommendation. URL: <https://www.w3.org/TR/xmlschema11-2/>

### D.2 Informative references

[](#informative-references)

\[DI-ECDSA\]
:   [The Elliptic Curve Digital Signature Algorithm Cryptosuites v1.0](https://www.w3.org/TR/vc-di-ecdsa/). David Longley; Manu Sporny; Marty Reed. W3C Verifiable Credentials Working Group. W3C Working Draft. URL: <https://www.w3.org/TR/vc-di-ecdsa/>

\[DI-EDDSA\]
:   [The Edwards Digital Signature Algorithm Cryptosuites v1.0](https://www.w3.org/TR/vc-di-eddsa/). David Longley; Manu Sporny; Dmitri Zagidulin. W3C Verifiable Credentials Working Group. W3C Working Draft. URL: <https://www.w3.org/TR/vc-di-eddsa/>

\[DID\]
:   [Decentralized Identifiers (DIDs) v1.0](https://www.w3.org/TR/did-core/). Manu Sporny; Amy Guy; Markus Sabadello; Drummond Reed. W3C. 19 July 2022. W3C Recommendation. URL: <https://www.w3.org/TR/did-core/>

\[DID-KEY\]
:   [The did:key Method](https://w3c-ccg.github.io/did-key-spec/). Manu Sporny; Dmitri Zagidulin; Dave Longley; Orie Steele. W3C Credentials Community Group. CG-DRAFT. URL: <https://w3c-ccg.github.io/did-key-spec/>

\[HTML-RDFA\]
:   [HTML+RDFa 1.1 - Second Edition](https://www.w3.org/TR/html-rdfa/). Manu Sporny. W3C. 17 March 2015. W3C Recommendation. URL: <https://www.w3.org/TR/html-rdfa/>

\[LTLI\]
:   [Language Tags and Locale Identifiers for the World Wide Web](https://www.w3.org/TR/ltli/). Addison Phillips. W3C. 7 October 2020. W3C Working Draft. URL: <https://www.w3.org/TR/ltli/>

\[N-QUADS\]
:   [RDF 1.1 N-Quads](https://www.w3.org/TR/n-quads/). Gavin Carothers. W3C. 25 February 2014. W3C Recommendation. URL: <https://www.w3.org/TR/n-quads/>

\[RDF-CONCEPTS\]
:   [Resource Description Framework (RDF): Concepts and Abstract Syntax](https://www.w3.org/TR/rdf-concepts/). Graham Klyne; Jeremy Carroll. W3C. 10 February 2004. W3C Recommendation. URL: <https://www.w3.org/TR/rdf-concepts/>

\[RFC5280\]
:   [Internet X.509 Public Key Infrastructure Certificate and Certificate Revocation List (CRL) Profile](https://www.rfc-editor.org/rfc/rfc5280). D. Cooper; S. Santesson; S. Farrell; S. Boeyen; R. Housley; W. Polk. IETF. May 2008. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc5280>

\[RFC7517\]
:   [JSON Web Key (JWK)](https://www.rfc-editor.org/rfc/rfc7517). M. Jones. IETF. May 2015. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7517>

\[RFC7519\]
:   [JSON Web Token (JWT)](https://www.rfc-editor.org/rfc/rfc7519). M. Jones; J. Bradley; N. Sakimura. IETF. May 2015. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc7519>

\[RFC7696\]
:   [Guidelines for Cryptographic Algorithm Agility and Selecting Mandatory-to-Implement Algorithms](https://www.rfc-editor.org/rfc/rfc7696). R. Housley. IETF. November 2015. Best Current Practice. URL: <https://www.rfc-editor.org/rfc/rfc7696>

\[RFC8392\]
:   [CBOR Web Token (CWT)](https://www.rfc-editor.org/rfc/rfc8392). M. Jones; E. Wahlstroem; S. Erdtman; H. Tschofenig. IETF. May 2018. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc8392>

\[RFC9562\]
:   [Universally Unique IDentifiers (UUIDs)](https://www.rfc-editor.org/rfc/rfc9562). K. Davis; B. Peabody; P. Leach. IETF. May 2024. Proposed Standard. URL: <https://www.rfc-editor.org/rfc/rfc9562>

\[SD-JWT\]
:   [Selective Disclosure for JWTs (SD-JWT)](https://datatracker.ietf.org/doc/draft-ietf-oauth-selective-disclosure-jwt/). Daniel Fett; Kristina Yasuda; Brian Campbell. The IETF OAuth Working Group. I-D. URL: <https://datatracker.ietf.org/doc/draft-ietf-oauth-selective-disclosure-jwt/>

\[SECURITY-VOCABULARY\]
:   [The Security Vocabulary](https://w3id.org/security). Ivan Herman; Manu Sporny; David Longley. Verifiable Credentials Working Group. W3C Editor\'s Draft. URL: <https://w3id.org/security>

\[TURTLE\]
:   [RDF 1.1 Turtle](https://www.w3.org/TR/turtle/). Eric Prud\'hommeaux; Gavin Carothers. W3C. 25 February 2014. W3C Recommendation. URL: <https://www.w3.org/TR/turtle/>

\[VC-DI-ECDSA\]
:   [Data Integrity ECDSA Cryptosuites v1.0](https://www.w3.org/TR/vc-di-ecdsa/). Manu Sporny; Dave Longley; Greg Bernstein. W3C. 15 May 2025. W3C Recommendation. URL: <https://www.w3.org/TR/vc-di-ecdsa/>

\[VC-DI-EDDSA\]
:   [Data Integrity EdDSA Cryptosuites v1.0](https://www.w3.org/TR/vc-di-eddsa/). Manu Sporny; Ted Thibodeau Jr; Greg Bernstein. W3C. 15 May 2025. W3C Recommendation. URL: <https://www.w3.org/TR/vc-di-eddsa/>

\[VC-EXTENSIONS\]
:   [Verifiable Credential Extensions](https://w3c.github.io/vc-extensions/). Manu Sporny. W3C Verifiable Credentials Working Group. W3C Editor\'s Draft. URL: <https://w3c.github.io/vc-extensions/>

\[WEBCRYPTOAPI\]
:   [Web Cryptography API](https://www.w3.org/TR/WebCryptoAPI/). Mark Watson. W3C. 26 January 2017. W3C Recommendation. URL: <https://www.w3.org/TR/WebCryptoAPI/>

\[ZCAP\]
:   [Authorization Capabilities for Linked Data](https://w3c-ccg.github.io/zcap-spec/). Credentials Community Group. CGDRAFT. URL: <https://w3c-ccg.github.io/zcap-spec/>

[↑](#title)

[Permalink](#dfn-transformation)

**Referenced in:**

-   [§ 1.1 How it Works](#ref-for-dfn-transformation-1 "§ 1.1 How it Works")
-   [§ 5.7 Protected Information](#ref-for-dfn-transformation-2 "§ 5.7 Protected Information") [(2)](#ref-for-dfn-transformation-3 "Reference 2") [(3)](#ref-for-dfn-transformation-4 "Reference 3") [(4)](#ref-for-dfn-transformation-5 "Reference 4") [(5)](#ref-for-dfn-transformation-6 "Reference 5") [(6)](#ref-for-dfn-transformation-7 "Reference 6")
-   [§ 5.13 Canonicalization Method Correctness](#ref-for-dfn-transformation-8 "§ 5.13 Canonicalization Method Correctness") [(2)](#ref-for-dfn-transformation-9 "Reference 2")

[Permalink](#dfn-transformation-algorithm)

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-transformation-algorithm-1 "§ 3. Cryptographic Suites")
-   [§ 5.7 Protected Information](#ref-for-dfn-transformation-algorithm-2 "§ 5.7 Protected Information")

[Permalink](#dfn-hashing)

**Referenced in:**

-   [§ 1.1 How it Works](#ref-for-dfn-hashing-1 "§ 1.1 How it Works")
-   [§ 5.13 Canonicalization Method Correctness](#ref-for-dfn-hashing-2 "§ 5.13 Canonicalization Method Correctness")

[Permalink](#dfn-hashing-algorithm)

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-hashing-algorithm-1 "§ 3. Cryptographic Suites")

[Permalink](#dfn-proof-serialization-algorithm)

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-proof-serialization-algorithm-1 "§ 3. Cryptographic Suites")

[Permalink](#dfn-proof-verification-algorithm)

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-proof-verification-algorithm-1 "§ 3. Cryptographic Suites")

[Permalink](#dfn-conforming-secured-document)

**Referenced in:**

-   [§ 1.3 Conformance](#ref-for-dfn-conforming-secured-document-1 "§ 1.3 Conformance")
-   [§ 2.3 Resource Integrity](#ref-for-dfn-conforming-secured-document-2 "§ 2.3 Resource Integrity") [(2)](#ref-for-dfn-conforming-secured-document-3 "Reference 2")
-   [§ 2.4 Contexts and Vocabularies](#ref-for-dfn-conforming-secured-document-4 "§ 2.4 Contexts and Vocabularies")
-   [§ 2.4.1 Validating Contexts](#ref-for-dfn-conforming-secured-document-5 "§ 2.4.1 Validating Contexts")
-   [§ 2.4.3 Securing Data Losslessly](#ref-for-dfn-conforming-secured-document-6 "§ 2.4.3 Securing Data Losslessly") [(2)](#ref-for-dfn-conforming-secured-document-7 "Reference 2")
-   [§ 5.14 Network Requests](#ref-for-dfn-conforming-secured-document-8 "§ 5.14 Network Requests")
-   [§ 6.4 Fingerprinting Network Requests](#ref-for-dfn-conforming-secured-document-9 "§ 6.4 Fingerprinting Network Requests") [(2)](#ref-for-dfn-conforming-secured-document-10 "Reference 2") [(3)](#ref-for-dfn-conforming-secured-document-11 "Reference 3") [(4)](#ref-for-dfn-conforming-secured-document-12 "Reference 4")

[Permalink](#dfn-conforming-processor)

**Referenced in:**

-   [§ 2.1 Proofs](#ref-for-dfn-conforming-processor-1 "§ 2.1 Proofs") [(2)](#ref-for-dfn-conforming-processor-2 "Reference 2")
-   [§ 2.4.3 Securing Data Losslessly](#ref-for-dfn-conforming-processor-3 "§ 2.4.3 Securing Data Losslessly")
-   [§ 4.1 Processing Model](#ref-for-dfn-conforming-processor-4 "§ 4.1 Processing Model")

[Permalink](#dfn-controlled-identifier-document)

**Referenced in:**

-   [§ 2.1 Proofs](#ref-for-dfn-controlled-identifier-document-1 "§ 2.1 Proofs")
-   [§ 5.9 Verification Method Binding](#ref-for-dfn-controlled-identifier-document-2 "§ 5.9 Verification Method Binding") [(2)](#ref-for-dfn-controlled-identifier-document-3 "Reference 2")
-   [§ 5.10 Verification Relationship Validation](#ref-for-dfn-controlled-identifier-document-4 "§ 5.10 Verification Relationship Validation")

[Permalink](#dfn-cryptosuite) exported

**Referenced in:**

-   [§ 1.1 How it Works](#ref-for-dfn-cryptosuite-1 "§ 1.1 How it Works")
-   [§ 1.2 Design Goals and Rationale](#ref-for-dfn-cryptosuite-2 "§ 1.2 Design Goals and Rationale")
-   [§ 3. Cryptographic Suites](#ref-for-dfn-cryptosuite-3 "§ 3. Cryptographic Suites") [(2)](#ref-for-dfn-cryptosuite-4 "Reference 2")
-   [§ 3.1 DataIntegrityProof](#ref-for-dfn-cryptosuite-5 "§ 3.1 DataIntegrityProof") [(2)](#ref-for-dfn-cryptosuite-6 "Reference 2") [(3)](#ref-for-dfn-cryptosuite-7 "Reference 3") [(4)](#ref-for-dfn-cryptosuite-8 "Reference 4") [(5)](#ref-for-dfn-cryptosuite-9 "Reference 5") [(6)](#ref-for-dfn-cryptosuite-10 "Reference 6")
-   [§ 4.3 Add Proof Set/Chain](#ref-for-dfn-cryptosuite-11 "§ 4.3 Add Proof Set/Chain")
-   [§ 5.7 Protected Information](#ref-for-dfn-cryptosuite-12 "§ 5.7 Protected Information") [(2)](#ref-for-dfn-cryptosuite-13 "Reference 2") [(3)](#ref-for-dfn-cryptosuite-14 "Reference 3")

[Permalink](#dfn-data-integrity-proof) exported

**Referenced in:**

-   [§ 1.4 Terminology](#ref-for-dfn-data-integrity-proof-1 "§ 1.4 Terminology")
-   [§ 2. Data Model](#ref-for-dfn-data-integrity-proof-2 "§ 2. Data Model")
-   [§ 2.1 Proofs](#ref-for-dfn-data-integrity-proof-3 "§ 2.1 Proofs") [(2)](#ref-for-dfn-data-integrity-proof-4 "Reference 2") [(3)](#ref-for-dfn-data-integrity-proof-5 "Reference 3") [(4)](#ref-for-dfn-data-integrity-proof-6 "Reference 4")
-   [§ 2.1.3 Proof Graphs](#ref-for-dfn-data-integrity-proof-7 "§ 2.1.3 Proof Graphs") [(2)](#ref-for-dfn-data-integrity-proof-8 "Reference 2") [(3)](#ref-for-dfn-data-integrity-proof-9 "Reference 3") [(4)](#ref-for-dfn-data-integrity-proof-10 "Reference 4")
-   [§ 3. Cryptographic Suites](#ref-for-dfn-data-integrity-proof-11 "§ 3. Cryptographic Suites") [(2)](#ref-for-dfn-data-integrity-proof-12 "Reference 2")
-   [§ 3.1 DataIntegrityProof](#ref-for-dfn-data-integrity-proof-13 "§ 3.1 DataIntegrityProof")
-   [§ 5.7 Protected Information](#ref-for-dfn-data-integrity-proof-14 "§ 5.7 Protected Information") [(2)](#ref-for-dfn-data-integrity-proof-15 "Reference 2")
-   [§ 5.13 Canonicalization Method Correctness](#ref-for-dfn-data-integrity-proof-16 "§ 5.13 Canonicalization Method Correctness")

[Permalink](#dfn-proof-purpose) exported

**Referenced in:**

-   [§ 2.2 Proof Purposes](#ref-for-dfn-proof-purpose-1 "§ 2.2 Proof Purposes") [(2)](#ref-for-dfn-proof-purpose-2 "Reference 2") [(3)](#ref-for-dfn-proof-purpose-3 "Reference 3") [(4)](#ref-for-dfn-proof-purpose-4 "Reference 4") [(5)](#ref-for-dfn-proof-purpose-5 "Reference 5")
-   [§ 5.11 Proof Purpose Validation](#ref-for-dfn-proof-purpose-6 "§ 5.11 Proof Purpose Validation")

[Permalink](#dfn-public-key)

**Referenced in:**

-   [§ 1.4 Terminology](#ref-for-dfn-public-key-1 "§ 1.4 Terminology")
-   [§ 2.1 Proofs](#ref-for-dfn-public-key-2 "§ 2.1 Proofs") [(2)](#ref-for-dfn-public-key-3 "Reference 2")

[Permalink](#dfn-secret-key)

**Referenced in:**

-   [§ 1.4 Terminology](#ref-for-dfn-secret-key-1 "§ 1.4 Terminology") [(2)](#ref-for-dfn-secret-key-2 "Reference 2")
-   [§ 2.6 Relationship to Verifiable Credentials](#ref-for-dfn-secret-key-3 "§ 2.6 Relationship to Verifiable Credentials")

[Permalink](#dfn-verification-method) exported

**Referenced in:**

-   [§ 1.4 Terminology](#ref-for-dfn-verification-method-1 "§ 1.4 Terminology")
-   [§ 2.6 Relationship to Verifiable Credentials](#ref-for-dfn-verification-method-3 "§ 2.6 Relationship to Verifiable Credentials") [(2)](#ref-for-dfn-verification-method-4 "Reference 2") [(3)](#ref-for-dfn-verification-method-5 "Reference 3") [(4)](#ref-for-dfn-verification-method-6 "Reference 4") [(5)](#ref-for-dfn-verification-method-7 "Reference 5")
-   [§ 5.9 Verification Method Binding](#ref-for-dfn-verification-method-8 "§ 5.9 Verification Method Binding") [(2)](#ref-for-dfn-verification-method-9 "Reference 2") [(3)](#ref-for-dfn-verification-method-10 "Reference 3")
-   [§ 5.10 Verification Relationship Validation](#ref-for-dfn-verification-method-11 "§ 5.10 Verification Relationship Validation")
-   [§ 5.14 Network Requests](#ref-for-dfn-verification-method-12 "§ 5.14 Network Requests") [(2)](#ref-for-dfn-verification-method-13 "Reference 2") [(3)](#ref-for-dfn-verification-method-14 "Reference 3")
-   [§ 6.3 Previous Proofs](#ref-for-dfn-verification-method-15 "§ 6.3 Previous Proofs")
-   [§ 6.4 Fingerprinting Network Requests](#ref-for-dfn-verification-method-16 "§ 6.4 Fingerprinting Network Requests") [(2)](#ref-for-dfn-verification-method-17 "Reference 2")

[Permalink](#dfn-verifier)

**Referenced in:**

-   [§ 2.2 Proof Purposes](#ref-for-dfn-verifier-1 "§ 2.2 Proof Purposes")
-   [§ 2.3 Resource Integrity](#ref-for-dfn-verifier-2 "§ 2.3 Resource Integrity")
-   [§ 5.13 Canonicalization Method Correctness](#ref-for-dfn-verifier-3 "§ 5.13 Canonicalization Method Correctness")

[Permalink](#dfn-proof)

**Referenced in:**

-   [§ 2.2 Proof Purposes](#ref-for-dfn-proof-1 "§ 2.2 Proof Purposes") [(2)](#ref-for-dfn-proof-2 "Reference 2")
-   [§ 4.5 Verify Proof Sets and Chains](#ref-for-dfn-proof-3 "§ 4.5 Verify Proof Sets and Chains")
-   [§ 5.5 Safer Abstractions](#ref-for-dfn-proof-4 "§ 5.5 Safer Abstractions")
-   [§ A. Understanding Proof Sets and Proof Chains](#ref-for-dfn-proof-5 "§ A. Understanding Proof Sets and Proof Chains") [(2)](#ref-for-dfn-proof-6 "Reference 2")

[Permalink](#dfn-challenge)

**Referenced in:**

-   [§ 4.4 Verify Proof](#ref-for-dfn-challenge-1 "§ 4.4 Verify Proof")

[Permalink](#dfn-proof-set)

**Referenced in:**

-   [§ 4.5 Verify Proof Sets and Chains](#ref-for-dfn-proof-set-1 "§ 4.5 Verify Proof Sets and Chains")
-   [§ A. Understanding Proof Sets and Proof Chains](#ref-for-dfn-proof-set-2 "§ A. Understanding Proof Sets and Proof Chains") [(2)](#ref-for-dfn-proof-set-3 "Reference 2") [(3)](#ref-for-dfn-proof-set-4 "Reference 3") [(4)](#ref-for-dfn-proof-set-5 "Reference 4") [(5)](#ref-for-dfn-proof-set-6 "Reference 5") [(6)](#ref-for-dfn-proof-set-7 "Reference 6") [(7)](#ref-for-dfn-proof-set-8 "Reference 7")

[Permalink](#dfn-proof-chain)

**Referenced in:**

-   [§ 4.5 Verify Proof Sets and Chains](#ref-for-dfn-proof-chain-1 "§ 4.5 Verify Proof Sets and Chains")
-   [§ A. Understanding Proof Sets and Proof Chains](#ref-for-dfn-proof-chain-2 "§ A. Understanding Proof Sets and Proof Chains") [(2)](#ref-for-dfn-proof-chain-3 "Reference 2") [(3)](#ref-for-dfn-proof-chain-4 "Reference 3") [(4)](#ref-for-dfn-proof-chain-5 "Reference 4") [(5)](#ref-for-dfn-proof-chain-6 "Reference 5") [(6)](#ref-for-dfn-proof-chain-7 "Reference 6")

[Permalink](#dfn-proof-graph) exported

**Referenced in:**

-   [§ 2.1.3 Proof Graphs](#ref-for-dfn-proof-graph-1 "§ 2.1.3 Proof Graphs")
-   [§ 4.3 Add Proof Set/Chain](#ref-for-dfn-proof-graph-2 "§ 4.3 Add Proof Set/Chain")

[Permalink](#dfn-named-graph) exported

**Referenced in:**

-   [§ 2.1 Proofs](#ref-for-dfn-named-graph-1 "§ 2.1 Proofs")
-   [§ 4.3 Add Proof Set/Chain](#ref-for-dfn-named-graph-2 "§ 4.3 Add Proof Set/Chain")

[Permalink](#dfn-authentication)

**Referenced in:**

-   [§ 5.11 Proof Purpose Validation](#ref-for-dfn-authentication-1 "§ 5.11 Proof Purpose Validation") [(2)](#ref-for-dfn-authentication-2 "Reference 2")

[Permalink](#dfn-cryptosuite-instantiation-algorithm) exported

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-cryptosuite-instantiation-algorithm-1 "§ 3. Cryptographic Suites") [(2)](#ref-for-dfn-cryptosuite-instantiation-algorithm-2 "Reference 2")

[Permalink](#dfn-data-integrity-cryptographic-suite-instance) exported

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-data-integrity-cryptographic-suite-instance-1 "§ 3. Cryptographic Suites") [(2)](#ref-for-dfn-data-integrity-cryptographic-suite-instance-2 "Reference 2")
-   [§ 4.2 Add Proof](#ref-for-dfn-data-integrity-cryptographic-suite-instance-3 "§ 4.2 Add Proof")
-   [§ 4.3 Add Proof Set/Chain](#ref-for-dfn-data-integrity-cryptographic-suite-instance-4 "§ 4.3 Add Proof Set/Chain")
-   [§ 4.4 Verify Proof](#ref-for-dfn-data-integrity-cryptographic-suite-instance-5 "§ 4.4 Verify Proof")

[Permalink](#dfn-createproof)

**Referenced in:**

-   [§ 4.2 Add Proof](#ref-for-dfn-createproof-1 "§ 4.2 Add Proof")

[Permalink](#dfn-verifyproof)

**Referenced in:**

-   [§ 4.4 Verify Proof](#ref-for-dfn-verifyproof-1 "§ 4.4 Verify Proof")

[Permalink](#dfn-cryptosuite-verification-result)

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-cryptosuite-verification-result-1 "§ 3. Cryptographic Suites")

[Permalink](#dfn-verified)

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-verified-1 "§ 3. Cryptographic Suites")
-   [§ 5.7 Protected Information](#ref-for-dfn-verified-2 "§ 5.7 Protected Information")

[Permalink](#dfn-input-document)

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-input-document-1 "§ 3. Cryptographic Suites")
-   [§ 4.2 Add Proof](#ref-for-dfn-input-document-2 "§ 4.2 Add Proof") [(2)](#ref-for-dfn-input-document-3 "Reference 2")
-   [§ 4.4 Verify Proof](#ref-for-dfn-input-document-4 "§ 4.4 Verify Proof")
-   [§ 4.6 Context Validation](#ref-for-dfn-input-document-5 "§ 4.6 Context Validation")
-   [§ A. Understanding Proof Sets and Proof Chains](#ref-for-dfn-input-document-6 "§ A. Understanding Proof Sets and Proof Chains")

[Permalink](#dfn-secured-data-document) exported

**Referenced in:**

-   [§ 3. Cryptographic Suites](#ref-for-dfn-secured-data-document-1 "§ 3. Cryptographic Suites") [(2)](#ref-for-dfn-secured-data-document-2 "Reference 2")
-   [§ 4.2 Add Proof](#ref-for-dfn-secured-data-document-3 "§ 4.2 Add Proof") [(2)](#ref-for-dfn-secured-data-document-4 "Reference 2")
-   [§ 4.3 Add Proof Set/Chain](#ref-for-dfn-secured-data-document-5 "§ 4.3 Add Proof Set/Chain") [(2)](#ref-for-dfn-secured-data-document-6 "Reference 2") [(3)](#ref-for-dfn-secured-data-document-7 "Reference 3")
-   [§ 4.4 Verify Proof](#ref-for-dfn-secured-data-document-8 "§ 4.4 Verify Proof")
-   [§ 4.5 Verify Proof Sets and Chains](#ref-for-dfn-secured-data-document-9 "§ 4.5 Verify Proof Sets and Chains") [(2)](#ref-for-dfn-secured-data-document-10 "Reference 2") [(3)](#ref-for-dfn-secured-data-document-11 "Reference 3")
-   [§ A. Understanding Proof Sets and Proof Chains](#ref-for-dfn-secured-data-document-12 "§ A. Understanding Proof Sets and Proof Chains") [(2)](#ref-for-dfn-secured-data-document-13 "Reference 2") [(3)](#ref-for-dfn-secured-data-document-14 "Reference 3") [(4)](#ref-for-dfn-secured-data-document-15 "Reference 4") [(5)](#ref-for-dfn-secured-data-document-16 "Reference 5") [(6)](#ref-for-dfn-secured-data-document-17 "Reference 6") [(7)](#ref-for-dfn-secured-data-document-18 "Reference 7")

[Permalink](#dfn-verification-result)

**Referenced in:**

-   [§ 4.4 Verify Proof](#ref-for-dfn-verification-result-1 "§ 4.4 Verify Proof") [(2)](#ref-for-dfn-verification-result-2 "Reference 2")
-   [§ 4.5 Verify Proof Sets and Chains](#ref-for-dfn-verification-result-3 "§ 4.5 Verify Proof Sets and Chains") [(2)](#ref-for-dfn-verification-result-4 "Reference 2") [(3)](#ref-for-dfn-verification-result-5 "Reference 3") [(4)](#ref-for-dfn-verification-result-6 "Reference 4")

[Permalink](#dfn-verified-0)

**Referenced in:**

-   [§ 4.4 Verify Proof](#ref-for-dfn-verified-0-1 "§ 4.4 Verify Proof") [(2)](#ref-for-dfn-verified-0-2 "Reference 2") [(3)](#ref-for-dfn-verified-0-3 "Reference 3") [(4)](#ref-for-dfn-verified-0-4 "Reference 4")

[Permalink](#dfn-verifieddocument-0)

**Referenced in:**

-   [§ 4.4 Verify Proof](#ref-for-dfn-verifieddocument-0-1 "§ 4.4 Verify Proof")

[Permalink](#dfn-mediatype)

**Referenced in:**

-   [§ 4.4 Verify Proof](#ref-for-dfn-mediatype-1 "§ 4.4 Verify Proof")

[Permalink](#dfn-errors)

**Referenced in:**

-   [§ 4.4 Verify Proof](#ref-for-dfn-errors-1 "§ 4.4 Verify Proof")

[Permalink](#dfn-validated)

**Referenced in:**

-   [§ 4.6 Context Validation](#ref-for-dfn-validated-1 "§ 4.6 Context Validation")

[Permalink](#dfn-problemdetails)

**Referenced in:**

-   [§ 4.4 Verify Proof](#ref-for-dfn-problemdetails-1 "§ 4.4 Verify Proof") [(2)](#ref-for-dfn-problemdetails-2 "Reference 2")
-   [§ 4.6 Context Validation](#ref-for-dfn-problemdetails-3 "§ 4.6 Context Validation") [(2)](#ref-for-dfn-problemdetails-4 "Reference 2")
