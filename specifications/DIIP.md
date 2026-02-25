---
lang: en
title: Decentralized Identity Interop Profile
viewport: width=device-width, initial-scale=1, shrink-to-fit=no
---

![](data:image/svg+xml;base64,PHN2ZyBpZD0ic3ZnIiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiAgPHN5bWJvbCBpZD0ic3ZnLWdpdGh1YiIgdmlld2JveD0iMCAwIDIwIDIwIj4KICAgIDxwYXRoIGQ9Ik0xMCAwLjI0N2MtNS41MjIgMC0xMCA0LjQ3Ny0xMCAxMCAwIDQuNDE4IDIuODY1IDguMTY3IDYuODM5IDkuNDg5IDAuNSAwLjA5MyAwLjY4My0wLjIxNyAwLjY4My0wLjQ4MSAwLTAuMjM4LTAuMDA5LTEuMDI2LTAuMDE0LTEuODYyLTIuNzgyIDAuNjA1LTMuMzY5LTEuMTgtMy4zNjktMS4xOC0wLjQ1NS0xLjE1Ni0xLjExLTEuNDYzLTEuMTEtMS40NjMtMC45MDctMC42MjEgMC4wNjktMC42MDggMC4wNjktMC42MDggMS4wMDQgMC4wNzEgMS41MzMgMS4wMzEgMS41MzMgMS4wMzEgMC44OTIgMS41MjkgMi4zMzkgMS4wODcgMi45MSAwLjgzMSAwLjA5MC0wLjY0NiAwLjM0OS0xLjA4NyAwLjYzNS0xLjMzNy0yLjIyMS0wLjI1My00LjU1Ni0xLjExLTQuNTU2LTQuOTQyIDAtMS4wOTIgMC4zOTEtMS45ODQgMS4wMzAtMi42ODQtMC4xMDQtMC4yNTItMC40NDYtMS4yNjkgMC4wOTctMi42NDYgMCAwIDAuODQtMC4yNjkgMi43NTEgMS4wMjUgMC43OTgtMC4yMjIgMS42NTMtMC4zMzMgMi41MDMtMC4zMzcgMC44NSAwLjAwNCAxLjcwNiAwLjExNSAyLjUwNSAwLjMzNyAxLjkwOS0xLjI5NCAyLjc0Ny0xLjAyNSAyLjc0Ny0xLjAyNSAwLjU0NCAxLjM3OCAwLjIwMiAyLjM5NSAwLjA5OCAyLjY0NiAwLjY0MSAwLjcgMS4wMjkgMS41OTIgMS4wMjkgMi42ODQgMCAzLjg0MS0yLjMzOSA0LjY4Ny00LjU2NiA0LjkzNCAwLjM1OSAwLjMxIDAuNjc4IDAuOTE5IDAuNjc4IDEuODUyIDAgMS4zMzgtMC4wMTIgMi40MTUtMC4wMTIgMi43NDQgMCAwLjI2NiAwLjE4IDAuNTc4IDAuNjg3IDAuNDggMy45NzEtMS4zMjQgNi44MzMtNS4wNzEgNi44MzMtOS40ODggMC01LjUyMy00LjQ3Ny0xMC0xMC0xMHoiPjwvcGF0aD4KICA8L3N5bWJvbD4KICA8c3ltYm9sIGlkPSJzdmctbmVzdGVkLWxpc3QiIHZpZXdib3g9IjAgMCAzNyAzMiI+CiAgICA8cGF0aCBkPSJNMCAyLjI4NmMwLTEuMjYyIDEuMDIzLTIuMjg2IDIuMjg2LTIuMjg2czIuMjg2IDEuMDIzIDIuMjg2IDIuMjg2YzAgMS4yNjItMS4wMjMgMi4yODYtMi4yODYgMi4yODZzLTIuMjg2LTEuMDIzLTIuMjg2LTIuMjg2ek05LjE0MyAwaDI3LjQyOXY0LjU3MWgtMjcuNDI5ek05LjE0MyAxMS40MjljMC0xLjI2MiAxLjAyMy0yLjI4NiAyLjI4Ni0yLjI4NnMyLjI4NiAxLjAyMyAyLjI4NiAyLjI4NmMwIDEuMjYyLTEuMDIzIDIuMjg2LTIuMjg2IDIuMjg2cy0yLjI4Ni0xLjAyMy0yLjI4Ni0yLjI4NnpNMTguMjg2IDkuMTQzaDE4LjI4NnY0LjU3MWgtMTguMjg2ek05LjE0MyAyOS43MTRjMC0xLjI2MiAxLjAyMy0yLjI4NiAyLjI4Ni0yLjI4NnMyLjI4NiAxLjAyMyAyLjI4NiAyLjI4NmMwIDEuMjYyLTEuMDIzIDIuMjg2LTIuMjg2IDIuMjg2cy0yLjI4Ni0xLjAyMy0yLjI4Ni0yLjI4NnpNMTguMjg2IDI3LjQyOWgxOC4yODZ2NC41NzFoLTE4LjI4NnpNMTguMjg2IDIwLjU3MWMwLTEuMjYyIDEuMDIzLTIuMjg2IDIuMjg2LTIuMjg2czIuMjg2IDEuMDIzIDIuMjg2IDIuMjg2YzAgMS4yNjItMS4wMjMgMi4yODYtMi4yODYgMi4yODZzLTIuMjg2LTEuMDIzLTIuMjg2LTIuMjg2ek0yNy40MjkgMTguMjg2aDkuMTQzdjQuNTcxaC05LjE0M3oiPjwvcGF0aD4KICA8L3N5bWJvbD4KPC9zdmc+)

![](data:image/svg+xml;base64,PHN2ZyBpY29uPjx1c2UgeGxpbms6aHJlZj0iI3N2Zy1uZXN0ZWQtbGlzdCI+PC91c2U+PC9zdmc+) [![](logo.png)](https://github.com/FIDEScommunity/DIIP) ![](data:image/svg+xml;base64,PHN2ZyBpY29uPjx1c2UgeGxpbms6aHJlZj0iI3N2Zy1naXRodWIiPjwvdXNlPjwvc3ZnPg==)

# [§](#decentralized-identity-interop-profile) Decentralized Identity Interop Profile

**Profile Status:** Release v5

**Latest Release:** [https://FIDEScommunity.github.io/DIIP](https://FIDEScommunity.github.io/DIIP)

**Latest Draft:** [https://FIDEScommunity.github.io/DIIP/draft](https://FIDEScommunity.github.io/DIIP/draft)

Editors:
:   [Eelco Klaver](https://www.linkedin.com/in/eklaver/) (Credenco)
:   [Harmen van der Kooij](https://www.linkedin.com/in/harmenvanderkooij/) (FIDES Labs)
:   [Niels Klomp](https://www.linkedin.com/in/niels-klomp/) (4Sure Technology Solutions)
:   [Niels van Dijk](https://www.linkedin.com/in/creativethings/) (SURF)
:   [Samuel Rinnetmäki](https://www.linkedin.com/in/samuel/) (Findynet)
:   [Timo Glastra](https://www.linkedin.com/in/timoglastra/) (Animo Solutions)

Contributors and previous editors:
:   [Adam Eunson](https://www.linkedin.com/in/adameunson/) (Auvo)
:   [Jelle Millenaar](https://www.linkedin.com/in/jellefm/) (Impierce Technologies)
:   [Maaike van Leuken](https://www.linkedin.com/in/maaike-van-leuken-0b1b7011a/) (TNO)
:   [Thierry Thevenet](https://www.linkedin.com/in/thierrythevenet/) (Talao)

------------------------------------------------------------------------

## [§](#abstract) Abstract

The Decentralized Identity Interop Profile, or DIIP for short, defines requirements against existing specifications to enable the interoperable issuance and presentation of [Digital Credential](#term:digital-credential)s between [Issuer](#term:issuer)s, [Holder](#term:holder)s, and [Verifier](#term:verifier)s.

  Purpose                                                                                         Specification
  ----------------------------------------------------------------------------------------------- ---------------------------------------------------------------------------------------------------------------------
  Credential format                                                                               [W3C VCDM](#term:w3c-vcdm) 2.0 (20 March 2025) and [SD-JWT VC](#term:sd-jwt-vc) (draft 13)
  Signature scheme                                                                                SD-JWT as specified in [VC-JOSE-COSE](#term:vc-jose-cose) (15 May 2025) and [SD-JWT VC](#term:sd-jwt-vc) (draft 13)
  Signature algorithm                                                                             [ES256](#term:es256) (RFC 7518 May 2015)
  Identifying [Issuer](#term:issuer)s, [Holder](#term:holder)s, and [Verifier](#term:verifier)s   [did:jwk](#term:did:jwk) (Commit 8137ac4, Apr 14 2022) and [did:web](#term:did:web) (31 July 2024)
  Issuance protocol                                                                               OpenID for Verifiable Credentials Issuance 1.0 ([OID4VCI](#term:oid4vci)) (Final).
  Presentation protocol                                                                           OpenID for Verifiable Presentations 1.0 ([OID4VP](#term:oid4vp)) (Final)
  Revocation mechanism                                                                            [IETF Token Status List](#term:ietf-token-status-list) (Draft 15, 2026-01-08)
  Trust Establishment (optional)                                                                  [OpenID Fed DCP](#term:openid-fed-dcp) (Appendix B of this profile)

The [Normative References](#normative-references) section links to the versions of specifications that DIIP-compliant implementations must support.

This document is not a specification but a **profile**. It outlines existing specifications required for implementations to interoperate with each other. It also clarifies mandatory features for the options mentioned in the referenced specifications.

The main objective of this profile is to allow for easy adoption and use the minimum amount of functionality for a working [Digital Credential](#term:digital-credential) ecosystem.

### [§](#status-of-this-document) Status of this Document

The Decentralized Identity Interop Profile v5 is approved by the FIDES Community on January 15^th^, 2026.

The latest published DIIP profile can be found at [https://FIDEScommunity.github.io/DIIP/](https://FIDEScommunity.github.io/DIIP/). The latest working group draft can be found at [https://FIDEScommunity.github.io/DIIP/draft](https://FIDEScommunity.github.io/DIIP/draft).

### [§](#audience) Audience

The audience of this document includes organisations aiming to issue or verify [Digital Credential](#term:digital-credential)s, as well as the implementers of [Digital Credential](#term:digital-credential) solutions ([Wallet](#term:wallet)s and [Agent](#term:agent)s).

### [§](#development-of-the-diip-profile) Development of the DIIP Profile

Participate:
:   [GitHub repo](https://github.com/FIDEScommunity/DIIP.git)
:   [File a bug](https://github.com/FIDEScommunity/DIIP/issues)
:   [Commit history](https://github.com/FIDEScommunity/DIIP/commits/main)

The development of this interoperability profile is a collaborative process. Anyone can suggest new specifications and restrictions. The suggestions are reviewed by the community, and decisions are made through discussions.

Feel free to join the [FIDES Community Discord](https://discord.gg/dSNbNadE6W) to participate in the discussions.

There are also monthly DIIP meetings. Contact [Harmen van der Kooij](mailto:harmen@fides.community) if you want to be invited to the meetings.

The authors intend to release new versions of the DIIP profile twice a year.

Some plans and ideas for the next version are documented in the [Appendix A: Future Directions](#appendix-a-future-directions).

## [§](#structure-of-this-document) Structure of this Document

The [Goals](#goals) section explains the design of the DIIP profile.

The [Profile](#profile) section defines the requirements for compliant solutions and explains the choices.

The [References](#references) section defines the specifications and their versions.

The [Terminology](#terminology) section explains the key terms used in this profile.

## [§](#goals) Goals

The [W3C VCDM](#term:w3c-vcdm) specification defines a data model for [Digital Credential](#term:digital-credential)s but does not prescribe standards for transport protocol, key management, authentication, query language, etc.

The [OID4VCI](#term:oid4vci) and [OID4VP](#term:oid4vp) protocols define the interaction between [Wallet](#term:wallet)s and [Agent](#term:agent)s but don't specify a data model or a credential format.

This interoperability profile makes selections by combining a set of specifications. It chooses standards for credential format, signature algorithm, identifying actors, and issuance and presentation protocols. Instead of saying, "*We use [W3C VCDM](#term:w3c-vcdm) credentials signed with [VC-JOSE-COSE](#term:vc-jose-cose) using [ES256](#term:es256) as the signature algorithm, [OID4VCI](#term:oid4vci) as the issuance protocol, and [OID4VP](#term:oid4vp) as the presentation protocol, and [OpenID Federation](#term:openid-federation) for trust establishment*", you can just say, "*We use DIIP v5*".

In addition, the DIIP profile makes selections *within* the specifications. When a standard allows multiple ways of implementing something, DIIP makes one of those ways mandatory. As an implementer, you don't need to fully support all specifications to be DIIP-compliant. DIIP makes these choices to accelerate adoption and interoperability -- defining the minimum required functionality.

DIIP does not exclude anything. For example, when DIIP says that compliant implementations MUST support [did:jwk](#term:did:jwk) as an identifier of the [Issuer](#term:issuer)s, [Holder](#term:holder)s, and [Verifier](#term:verifier)s, it doesn't say that other identifiers cannot be used. The [Wallet](#term:wallet)s and [Agent](#term:agent)s can support other identifiers as well and still be DIIP-compliant.

Trust ecosystems can also easily extend DIIP by saying, "We use the DIIP v5 profile *and allow `mDocs` as an additional credential format*". They can also switch requirements by saying, "We use the DIIP v5 profile *but use [VC-DATA-INTEGRITY](#term:vc-data-integrity) as an embedded proof mechanism*".

The design goal for DIIP is to ensure interoperability between [Wallet](#term:wallet)s and [Agent](#term:agent)s in cases where device binding of [Digital Credential](#term:digital-credential)s is not required and the [Wallet](#term:wallet) doesn't need to be trusted. Issuing, holding, and presenting certifications, diplomas, licenses, permits, etc., fit into the scope of DIIP. Using a [Wallet](#term:wallet) for strong customer authentication or for sharing Person Identification Data (PID) is out of DIIP's scope, and you should look into [HAIP](#term:haip) instead.

### [§](#relationship-to-eidas-regulation-and-haip-profile) Relationship to eIDAS Regulation and HAIP Profile

The DIIP profile is intended to be complementary to the OpenID4VC High Assurance Interoperability Profile ([HAIP](#term:haip)). Both profiles build on the OpenID for Verifiable Credentials specifications ([OID4VCI](#term:oid4vci) and [OID4VP](#term:oid4vp)), but they address different interoperability scopes and assurance needs.

DIIP is an interoperability profile that includes the W3C Decentralized Identifiers specification ([DID Core](#term:did-core)) and the Verifiable Credentials Data Model 2.0 ([W3C VCDM](#term:w3c-vcdm)). These specifications are not part of the HAIP interoperability profile.

DIIP explicitly targets multi-party interoperability scenarios in cross-domain and global ecosystems. These scenarios require identifier-agnostic and Linked Data--based interoperability, with a focus on business wallets, rather than personal wallets only. This makes DIIP particularly relevant for ecosystems such as [Gaia-X](https://gaia-x.eu/), [Catena-X](https://catena-x.net/), [IDSA](https://internationaldataspaces.org/) and [SIMPL](https://simpl-programme.ec.europa.eu/) (Data Spaces), [UNTP](https://untp.unece.org/) and [CIRPASS-2](https://cirpass2.eu/) (Digital Product Passports), [MOSIP](https://www.mosip.io/) (Digital Public Infrastructure), [EU CitiVERSE](https://www.cu-project.eu/citiverse) (Local Digital Twins), [Open Badges](https://www.1edtech.org/standards/open-badges) (Skills/Certificates/Diplomas), and the [Swiss E-ID](https://www.eid.admin.ch/en/e-id-e) Ecosystem.

In the European context, [HAIP](#term:haip) is typically used for high-assurance use cases, including those involving Qualified Electronic Attestations of Attributes (*QEAA*). DIIP can be applied to use cases with lower assurance requirements, for example where Electronic Attestations of Attributes (*EAA*) are sufficient, or where device binding or qualified attestation is not required, while still enabling interoperability based on the same foundations of [OID4VCI](#term:oid4vci), [OID4VP](#term:oid4vp), and [SD-JWT VC](#term:sd-jwt-vc).

While DIIP is a standalone interoperability profile and enables interoperability on its own, it is designed to coexist with and complement [HAIP](#term:haip). Wallets and agents may therefore support both profiles, selecting the appropriate one based on the assurance requirements of a given use case.

Wallet providers are encouraged to implement both [HAIP](#term:haip) and DIIP, enabling them to support a broad range of assurance levels, use cases, and ecosystems using a common foundation.

## [§](#profile) Profile

In this section, we describe the interoperability profile.

### [§](#credential-format) Credential Format

The W3C Verifiable Credential Data Model ([W3C VCDM](#term:w3c-vcdm)) defines structure and vocabulary well suited for [Digital Credential](#term:digital-credential)s in DIIP's scope. For example, the [Open Badges 3](#term:open-badges-3) credentials use [W3C VCDM](#term:w3c-vcdm) as the data format.

The SD-JWT-based Verifiable Credentials specification ([SD-JWT VC](#term:sd-jwt-vc)) defines a credential format that are serialized in JSON Web Tokens ([JWT](#term:jwt)s) and enable selective disclosure. [SD-JWT VC](#term:sd-jwt-vc) is used as a credential format for person identification data (PID) in [HAIP](#term:haip) and [ARF](#term:arf) (in addition to `mDocs`).

[W3C VCDM](#term:w3c-vcdm) recommends securing Verifiable Credentials using JOSE and COSE ([VC-JOSE-COSE](#term:vc-jose-cose)) as an *enveloping proof* mechanism and Verifiable Credential Data Integrity 1.0 ([VC-DATA-INTEGRITY](#term:vc-data-integrity)) as an *embedded proof* mechanism.

To keep things as simple as possible, DIIP requires implementations to use `SD-JWT` as the mechanism to secure also [W3C VCDM](#term:w3c-vcdm)-based credentials.

**Requirement: DIIP-compliant implementations MUST support [SD-JWT VC](#term:sd-jwt-vc) as a credential format.**

**Requirement: DIIP-compliant implementations MUST support [W3C VCDM](#term:w3c-vcdm) and more specifically [Securing JSON-LD Verifiable Credentials with SD-JWT](https://www.w3.org/TR/vc-jose-cose/#secure-with-sd-jwt) as specified in ([VC-JOSE-COSE](#term:vc-jose-cose)).**

### [§](#signature-algorithm) Signature Algorithm

The DIIP profile chooses one key type [Secp256r1](#term:secp256r1) and one signature method [ES256](#term:es256) that all implementations must support.

**Requirement: DIIP-compliant implementations MUST support [ES256](#term:es256) (`ECDSA` using [Secp256r1](#term:secp256r1) curve and `SHA-256` message digest algorithm).**

### [§](#identifiers) Identifiers

DIIP prefers decentralized identifiers ([DID](#term:did)s) as identifiers. An entity identified by a [DID](#term:did) publishes a [DID Document](https://www.w3.org/TR/did-1.0/#dfn-did-documents), which can contain useful metadata about the entity, e.g., various endpoints. There are many DID methods defined. The DIIP profile requires support for two of them: [did:jwk](#term:did:jwk) and [did:web](#term:did:web). In many use cases, organizations are identified by [did:web](#term:did:web), and the natural persons are identified by [did:jwk](#term:did:jwk).

**Requirement: DIIP-compliant implementations MUST support [did:jwk](#term:did:jwk) and [did:web](#term:did:web) as the identifiers of the [Issuer](#term:issuer)s, [Holder](#term:holder)s, and [Verifier](#term:verifier)s.**

### [§](#trust-establishment) Trust Establishment

Signatures in [Digital Credential](#term:digital-credential)s can be used to verify that the content of a credential has not been tampered with. But anyone can sign a credential and put anything in the issuer field. [Digital Credential](#term:digital-credential) ecosystems require that there is a way for a [Verifier](#term:verifier) to check who is the [Issuer](#term:issuer) of a [Digital Credential](#term:digital-credential). Equally, a user might want to be informed about the trustworthiness of a [Verifier](#term:verifier) before choosing to share credentials.

DIIP enables trust ecosystems to use [OpenID Fed DCP](#term:openid-fed-dcp) -- a light-weight profile of the [OpenID Federation](#term:openid-federation), authored for use cases including [Agent](#term:agent)s, [Wallet](#term:wallet)s and [Digital Credential](#term:digital-credential)s. The [OpenID Fed DCP](#term:openid-fed-dcp) specification is an appendix to this version of the DIIP profile. In the future, the [OpenID Fed DCP](#term:openid-fed-dcp) profile will probably be donated to be maintained elsewhere.

The requirements regarding Trust Establishment are OPTIONAL in DIIP v5.

**Requirement: DIIP-compliant [Issuer](#term:issuer) [Agent](#term:agent)s MUST support publishing OpenID Federation Entity Configuration as defined in [OpenID Fed DCP](#term:openid-fed-dcp).**

**Requirement: DIIP-compliant [Issuer](#term:issuer) [Agent](#term:agent)s MUST support issuing the `fed` claim as defined in [OpenID Fed DCP](#term:openid-fed-dcp) when issuing [SD-JWT VC](#term:sd-jwt-vc) credentials.**

**Requirement: DIIP-compliant [Issuer](#term:issuer) [Agent](#term:agent)s MUST support issuing the `termsOfUse` attribute as defined in [OpenID Fed DCP](#term:openid-fed-dcp) when issuing [W3C VCDM](#term:w3c-vcdm) credentials.**

**Requirement: DIIP-compliant [Verifier](#term:verifier) [Agent](#term:agent)s MUST support publishing OpenID Federation Entity Configuration as defined in [OpenID Fed DCP](#term:openid-fed-dcp).**

### [§](#issuance) Issuance

The issuance of [Digital Credential](#term:digital-credential)s from the [Issuer](#term:issuer) to the [Holder](#term:holder)\'s [Wallet](#term:wallet) is done along the [OID4VCI](#term:oid4vci) specification. Other protocols exist, but [OID4VCI](#term:oid4vci) is very broadly supported and also required by [HAIP](#term:haip).

#### [§](#oid4vci) OID4VCI

OpenID for Verifiable Credential Issuance ([OID4VCI](#term:oid4vci)) defines an API for the issuance of [Digital Credential](#term:digital-credential)s. OID4VCI [issuance flow variations](https://openid.net/specs/openid-4-verifiable-credential-issuance-1_0-ID2.html#name-issuance-flow-variations) leave room for optionality.

In many situations, [Digital Credential](#term:digital-credential)s are issued on the [Issuer](#term:issuer)\'s online service (website). This online service may have already authenticated and authorized the user before displaying the credential offer. Another authentication or authorization is not needed in those situations.

Authorization Code Flow provides a more advanced way of implementing credential issuance. Proof Key for Code Exchange ([PKCE](#term:pkce)) defines a way to mitigate against authorization code interception attack. Pushed authorization request ([PAR](#term:par)) allows clients to push the payload of an authorization request directly to the authorization server. These features may be needed in higher assurance use cases or for protecting privacy.

**Requirement: DIIP-compliant implementations MUST support both *Pre-Authorized Code Flow* and *Authorization Code Flow*.**

**Requirement: DIIP-compliant implementations MUST support the `tx_code` when using *Pre-Authorized Code Flow*.**

**Requirement: DIIP-compliant [Wallet](#term:wallet)s MUST NOT assume the Authorization Server is on the same domain as the [Issuer](#term:issuer).**

**Requirement: DIIP-compliant implementations MUST support [PKCE](#term:pkce) with Code Challenge Method Parameter `S256` to prevent authorization code interception attacks.**

**Requirement: DIIP-compliant implementations MUST support [PAR](#term:par) with the [Issuer](#term:issuer)\'s Authorization Server using `require_pushed_authorization_requests` set to `true` ensuring integrity and authenticity of the authorization request.**

It should be noted that various [Security Considerations](https://openid.net/specs/openid-4-verifiable-credential-issuance-1_0.html#name-pre-authorized-code-flow-2) have been described in the [OID4VCI](#term:oid4vci) specification with respect to implementing *Pre-Authorized Code Flow*. Parties implementing DIIP are strongly suggested to implement mitigating measures, like the use of a Transaction Code.

[OID4VCI](#term:oid4vci) defines *Wallet-initiated* and *Issuer-initiated* flows. *Wallet-initiated* means that the [Wallet](#term:wallet) can start the flow without any activity from the [Issuer](#term:issuer). The *Issuer-initiated* flow seems to be more common in many use cases and seems to be supported more widely. It also aligns better with the use cases where the [Holder](#term:holder) is authenticated and authorized in an online service before the credential offer is created and shown.

**Requirement: DIIP-compliant implementations MUST support the *Issuer-initiated* flow.**

[OID4VCI](#term:oid4vci) defines *Same-device* and *Cross-device* Credential Offer. People should be able to use both their desktop browser and their mobile device's browser when interacting with the [Issuer](#term:issuer)\'s online service.

**Requirement: DIIP-compliant implementations MUST support both *Same-device* and *Cross-device* Credential Offer.**

[OID4VCI](#term:oid4vci) defines *Immediate* and *Deferred* flows. *Deferred* is more complex to implement and not required in most use cases.

**Requirement: DIIP-compliant implementations MUST support the *Immediate* flow.**

[OID4VCI](#term:oid4vci) states that there are two possible methods for requesting the issuance of a specific credential type in an *Authorization Request*: either by utilizing the `authorization_details` parameter or by utilizing the `scope` parameter.

The `scope` parameter is a light-weight way of using an external authorization server. The `authorization_details` makes the flow much more configurable and structured. If an issuer agent does not support an external authorization server, the scope parameter is not needed.

**Requirement: DIIP-compliant [Wallet](#term:wallet)s MUST support the `authorization_details` parameter using the `credential_configuration_id` parameter in the Authorization Request.**

**Requirement: DIIP-compliant [Wallet](#term:wallet)s MUST support the `scope` parameter in the Authorization Request.**

**Requirement: DIIP-compliant [Issuer](#term:issuer) [Agent](#term:agent)s MUST support the `authorization_details` parameter in the Authorization Request.**

[OID4VCI](#term:oid4vci) defines proof types `jwt`, `ldp_vp`, and `attestation` for binding the issued credential to the identifier of the end-user possessing that credential. DIIP requires compliant implementations to support [did:jwk](#term:did:jwk) as an identifier. Thus, in cases where cryptographic holder-binding is needed, implementations should be able to bind a credential to the [Holder](#term:holder)\'s [did:jwk](#term:did:jwk).

**Requirement: DIIP-compliant implementations MUST support the `jwt` proof type with a [did:jwk](#term:did:jwk) or [did:web](#term:did:web) as the `iss` value and use a `kid` from the `assertionMethod` Verification Method relationship of the respective [Issuer](#term:issuer)\'s [DID](#term:did) document.**

**Requirement: DIIP-compliant implementations MUST support a `cnf` holder binding claim in the [Issuer](#term:issuer)\'s `jwt` and it MUST include a `kid` value from the `authentication` Verification Method relationship of the respective [Holder](#term:holder)\'s [DID](#term:did) document.**

### [§](#presentation) Presentation

The presentation of claims from the [Holder](#term:holder)\'s [Wallet](#term:wallet) to the [Verifier](#term:verifier) is done along the [OID4VP](#term:oid4vp). Other protocols exist, but [OID4VP](#term:oid4vp) is very broadly supported and also required by [HAIP](#term:haip).

#### [§](#oid4vp) OID4VP

Using [OID4VP](#term:oid4vp), the [Holder](#term:holder)s can also present cryptographically verifiable claims issued by third-party [Issuer](#term:issuer)s, such that the [Verifier](#term:verifier) can place trust in those [Issuer](#term:issuer)s instead of the subject ([Holder](#term:holder)).

[OID4VP](#term:oid4vp) supports scenarios where the *Authorization Request* is sent both when the [Verifier](#term:verifier) is interacting with the [Holder](#term:holder) using the device that is the same or different from the device on which requested [Digital Credential](#term:digital-credential)s are stored.

**Requirement: DIIP-compliant implementations MUST support both *Same-device Flow* and *Cross-device Flow*.**

According to [OID4VP](#term:oid4vp), the [Verifier](#term:verifier) may send an *Authorization Request* using either of these 3 options:

-   Passing as URL with encoded parameters
-   Passing a request object as value
-   Passing a request object by reference

DIIP only requires support for the last option. **Requirement: DIIP-compliant implementations MUST support passing the *Authorization Request* object by reference.**

[OID4VP](#term:oid4vp) defines two values for the `request_uri_method` in the *Authorization Request*: `get` and `post`. DIIP requires support for only the `get` method.

**Requirement: DIIP-compliant implementations MUST support the `get` value for the `request_uri_method` in the *Authorization Request*.**

[OID4VP](#term:oid4vp) defines many [Client Identifier Schemes](https://openid.net/specs/openid-4-verifiable-presentations-1_0-ID3.html#name-defined-client-identifier-s). One way to identify [Verifier](#term:verifier)s is through [OpenID Federation](#term:openid-federation). Since DIIP uses [DID](#term:did)s, it is natural to require support for the corresponding Client Identifier Scheme.

**Requirement: DIIP-compliant implementations MUST support the `did` *Client Identifier Scheme*.**

The following features of [OID4VP](#term:oid4vp) are **not** required by this version of the DIIP profile:

-   Presentations Without Holder Binding Proofs (section 5.3, requirements for the `state` parameter)
-   Verifier Attestations (section 5.11)
-   SIOPv2 (section 8, *Response Type* value `vp_token id_token` and `scope` containing `openid`)
-   Encrypted Responses (section 8.3)
-   Transaction Data (section 8.4)
-   Digital Credentials API (Appendix A)

### [§](#validity-and-revocation-algorithm) Validity and Revocation Algorithm

Expiration algorithms using [validFrom](https://www.w3.org/TR/vc-data-model-2.0/#defn-validFrom) and [validUntil](https://www.w3.org/TR/vc-data-model-2.0/#defn-validUntil) are a powerful mechanism to establish the validity of credentials. Evaluating the expiration of a credential is much more efficient than using revocation mechanisms. While the absence of `validFrom` and `validUntil` would suggest a credential is considered valid indefinitely, it is recommended that all implementations set validity expiration whenever possible to allow for clear communication to [Holder](#term:holder)s and [Verifier](#term:verifier)s.

**Requirement: DIIP-compliant implementations MUST support checking the validity status of a [Digital Credential](#term:digital-credential) using `validFrom` and `validUntil` when they are specified.**

The [IETF Token Status List](#term:ietf-token-status-list) defines a mechanism, data structures, and processing rules for representing the status of [Digital Credential](#term:digital-credential)s (and other "Tokens"). The statuses of Tokens are conveyed via a bit array in the Status List. The Status List is embedded in a Status List Token.

The [Bitstring Status List](#term:bitstring-status-list) is based on the same idea as the [IETF Token Status List](#term:ietf-token-status-list) and is simpler to implement since it doesn't require signing of the status list. The [IETF Token Status List](#term:ietf-token-status-list) may gain more support since it is recommended by [HAIP](#term:haip).

**Requirement: DIIP-compliant implementations MUST support [IETF Token Status List](#term:ietf-token-status-list) as a status list mechanism.**

## [§](#terminology) Terminology

This section consolidates in one place common terms used across open standards that this profile consists of. For the details of these, as well as other useful terms, see the text within each of the specifications listed in [References](#references).

Agent
:   A software application or component that an [Issuer](#term:issuer) uses to issue [Digital Credential](#term:digital-credential)s or that a [Verifier](#term:verifier) uses to request and verify them.

Holder
:   An entity that possesses or holds [Digital Credential](#term:digital-credential)s and can present them to [Verifier](#term:verifier)s.

DID
:   Decentralized Identifier as defined in [DID Core](#term:did-core).

Issuer
:   A role an entity can perform by asserting claims about one or more subjects, creating a [Digital Credential](#term:digital-credential) from these claims, and transmitting the [Digital Credential](#term:digital-credential) to a [Holder](#term:holder), as defined in [W3C VCDM](#term:w3c-vcdm).

Digital Credential
:   A set of one or more Claims made by an [Issuer](#term:issuer) that is tamper-evident and has authorship that can be cryptographically verified.

&nbsp;

Verifier
:   An entity that requests and receives one or more [Digital Credential](#term:digital-credential)s for processing.

Wallet
:   A software application or component that receives, stores, presents, and manages credentials and key material of an entity.

## [§](#references) References

### [§](#normative-references) Normative References

DID Core
:   [Decentralized Identifiers (DIDs) v1.0](https://www.w3.org/TR/did-1.0/). Status: W3C Recommendation.

did:jwk
:   [did:jwk Method Specification](https://github.com/quartzjer/did-jwk/blob/main/spec.md). Status: Draft.

did:web
:   [did:web Method Specification](https://w3c-ccg.github.io/did-method-web/). Status: Unofficial working group draft.

ES256
:   `ECDSA` using `P-256` ([Secp256r1](#term:secp256r1)) and `SHA-256` as specified in [RFC 7518 JSON Web Algorithms (JWA)](https://datatracker.ietf.org/doc/html/rfc7518). Status: RFC - Proposed Standard.

IETF Token Status List
:   [Token Status List - draft 15](https://datatracker.ietf.org/doc/draft-ietf-oauth-status-list/15/). Status: Internet-Draft.

OID4VCI
:   [OpenID for Verifiable Credential Issuance 1.0](https://openid.net/specs/openid-4-verifiable-credential-issuance-1_0.html). Status: Final.

OID4VP
:   [OpenID for Verifiable Presentations 1.0](https://openid.net/specs/openid-4-verifiable-presentations-1_0.html). Status: Final.

OpenID Federation
:   [OpenID Federation 1.0 - draft 42](https://openid.net/specs/openid-federation-1_0.html). Status: draft.

OpenID Fed DCP
:   [OpenID Federation Digital Credentials Profile - draft 0.1.0](#appendix-b-openid-federation-digital-credentials-profile). Status: draft.

PAR
:   [RFC 9126 Pushed Authorization Requests](https://datatracker.ietf.org/doc/html/rfc9126). Status: RFC - Proposed Standard.

PKCE
:   [RFC 7636 Proof Key for Code Exchange by OAuth Public Clients](https://datatracker.ietf.org/doc/html/rfc7636). Status: RFC - Proposed Standard.

SD-JWT VC
:   [SD-JWT-based Verifiable Credentials (SD-JWT VC) - draft 13](hhttps://datatracker.ietf.org/doc/draft-ietf-oauth-sd-jwt-vc/13/). Status: WG Document.

Secp256r1
:   `Secp256r1` curve in [RFC 5480 ECC SubjectPublicKeyInfo Format](https://datatracker.ietf.org/doc/html/rfc5480). Status: RFC - Proposed Standard.
:   This curve is called `P-256` in [RFC 7518 JSON Web Algorithms (JWA)](https://datatracker.ietf.org/doc/html/rfc7518). Status: RFC - Proposed Standard.

W3C VCDM
:   [Verifiable Credentials Data Model v2.0](https://www.w3.org/TR/2025/REC-vc-data-model-2.0-20250515/). Status: W3C Recommendation.

VC-JOSE-COSE
:   [Securing Verifiable Credentials using JOSE and COSE](https://www.w3.org/TR/2025/REC-vc-jose-cose-20250515/). Status: W3C Recommendation.

### [§](#non-normative-references) Non-Normative References

ARF
:   [Architecture and Reference Framework](https://eu-digital-identity-wallet.github.io/eudi-doc-architecture-and-reference-framework/latest/architecture-and-reference-framework-main/). Status: Draft.

Bitstring Status List
:   [Bitstring Status List v1.0](https://www.w3.org/TR/vc-bitstring-status-list/). Status: W3C Proposed Recommendation.

HAIP
:   [OpenID4VC High Assurance Interoperability Profile](https://openid.net/specs/openid4vc-high-assurance-interoperability-profile-1_0.html). Status: Draft.

JWT
:   [RFC 7519 JSON Web Token (JWT)](https://datatracker.ietf.org/doc/html/rfc7519). Status: RFC - Proposed Standard.

Open Badges 3
:   [Open Badges Specification, Spec Version 3.0, Document Version 1.2](https://www.imsglobal.org/spec/ob/v3p0). Status: This document is made available for adoption by the public community at large.

VC-DATA-INTEGRITY
:   [Verifiable Credential Data Integrity 1.0](https://www.w3.org/TR/vc-data-integrity/). Status: W3C Proposed Recommendation.

## [§](#appendix-a-future-directions) Appendix A: Future Directions

### [§](#identifiers-2) Identifiers

A near-future version of DIIP will probably require support for [did:webvh](#term:did:webvh) instead of [did:web](#term:did:web).

### [§](#trust-establishment-2) Trust Establishment

The next version of the DIIP profile will likely require also [Wallet](#term:wallet)s to support [OpenID Federation](#term:openid-federation).

### [§](#digital-credentials-api) Digital Credentials API

[DC API](#term:dc-api) is a new W3C specification. The next versions of the DIIP protocol will most likely require compliant solutions to support [DC API](#term:dc-api).

### [§](#references-2) References

DC API
:   [Digital Credentials](https://wicg.github.io/digital-credentials/). Status: Draft Community Group Report.

did:webvh
:   [The did:webvh DID Method v0.5](https://identity.foundation/didwebvh/). Status: CURRENT STABLE.

## [§](#appendix-b-openid-federation-digital-credentials-profile) Appendix B: OpenID Federation Digital Credentials Profile

### [§](#introduction) Introduction

This specification profiles [OpenID Federation](#term:openid-federation) for use with digital credentials, specifically focusing on credential issuance via OpenID for Verifiable Credential Issuance ([OID4VCI](#term:oid4vci)) and credential presentation via OpenID for Verifiable Presentations ([OID4VP](#term:oid4vp)).

The profile simplifies federation usage by limiting scope to trust chain resolution and metadata discovery, while omitting more complex features such as federation policies and trust marks.

#### [§](#scope) Scope

This profile applies to:

-   Credential issuers publishing OID4VCI metadata
-   Credential verifiers publishing verifier metadata
-   Wallets resolving trust chains to establish trust in issuers and verifiers

This profile does not apply to and does not require conforming implementations to support:

-   Chapter 6 (Federation Policy)
-   Chapter 7 (Trust Marks)
-   Chapter 12 (OpenID Connect Client Registration)

of [OpenID Federation](#term:openid-federation).

### [§](#terminology-2) Terminology

This specification uses the terms defined in [OpenID Federation](#term:openid-federation), [OID4VCI](#term:oid4vci), [OID4VP](#term:oid4vp), and [SD-JWT VC](#term:sd-jwt-vc).

**Entity Identifier**: As defined in [OpenID Federation](#term:openid-federation), a URL that uniquely identifies a federation entity.

**Entity Configuration**: As defined in [OpenID Federation](#term:openid-federation), a signed JWT published at the Entity Identifier's `/.well-known/openid-federation` endpoint containing metadata and authority hints.

**Trust Chain**: As defined in [OpenID Federation](#term:openid-federation), a sequence of Entity Statements from a Leaf Entity through intermediate entities to a Trust Anchor.

**Issuer**: The role of the entity issuing credentials as defined in [W3C VCDM](#term:w3c-vcdm)

**Credential Issuer**: The technical service used to issue credentials as defined in [OID4VCI](#term:oid4vci)

**Verifier**: The entity that requests, receives, and validates Presentations as defined in [OID4VCI](#term:oid4vci). Note that this specification does not distinguish the role and the technical service of the verifier the same way it does for Issuer and Credential Issuer. For the purposes of this specification Verifier may refer to either the role or the technical service. (Thus, the reference to the definition in [OID4VCI](#term:oid4vci) and not the one in [OID4VP](#term:oid4vp).)

### [§](#credential-issuance) Credential Issuance

#### [§](#entity-identifier) Entity Identifier

**Requirement: The Credential Issuer MUST use the value of the `credential_issuer` in its OID4VCI issuer metadata as its Entity Identifier.**

The Credential Issuer's Entity Configuration can be found using the method described in the chapter 9 of [OpenID Federation](#term:openid-federation).

#### [§](#issuer-metadata-publication) Issuer Metadata Publication

**Requirement: The Credential Issuer MUST place the OpenID4VCI issuer metadata into the Entity Configuration, in the `openid_credential_issuer` Entity Type Identifier.**

**Requirement: If the `openid_credential_issuer` Entity Type Identifier is found in the Entity Configuration, the Wallet MUST use only this medatada and ignore the regular issuer metadata published in the well-known location defined in [OID4VCI](#term:oid4vci).**

**Requirement: The Credential Issuer MUST place the public key material of the keys it uses to sign Digital Credentials in the `jwks` property of the `vc_issuer` Entity Type Identifier using the syntax specified in the chapter 5.2.1. of the [OpenID Federation](#term:openid-federation).**

**Requirement: The Credential Issuer MUST include the `federation_entity` Entity Type Identifier with the `display_name` property in the Entity Configuration.**

The Credential Issuer MAY place additional metadata into the `federation_entity` Entity Type Identifier.

**Requirement: The Wallet MUST use the value of the `display_name` property in the `federation_entity` Entity Type Identifier when showing information about the Credential Issuer to the user of the Wallet.**

#### [§](#example-credential-issuer-entity-configuration) Example: Credential Issuer Entity Configuration

The following JSON document is a non-normative example of the decoded payload of a Credential Issuer's Entity Configuration.

``` language-json
{
  "iss": "https://credential-issuer.example",
  "sub": "https://credential-issuer.example",
  "iat": 1616239022,
  "exp": 1616239322,
  "metadata": {
    "federation_entity": {
      "display_name": "Example Issuer",
      "logo_uri": "https://credential-issuer.example/logo.png",
      "organization_name": "Example Credential Issuer",
      "contacts": ["support@credential-issuer.example"]
    },
    "openid_credential_issuer": {
      "issuer": "https://credential-issuer.example",
      "display": [
        {
          "name": "Example Issuer",
          "locale": "en-US",
          "logo": {
            "uri": "https://credential-issuer.example/logo.png",
            "alt_text": "Example Logo"
          }
        }
      ],
      "credential_issuer": "https://credential-issuer.example",
      "authorization_endpoint": "https://credential-issuer.example/authorize",
      "authorization_servers": ["https://credential-issuer.example/authorize"],
      "credential_endpoint": "https://credential-issuer.example/credential",
      "credential_configurations_supported": {
        "sd_jwt_vc_example": "..."
      }
    },
    "vc_issuer": {
      "jwks": [
        {
          "kty": "EC",
          "kid": "MJ2BW-rNshp9sjh3SvwnBIkEsYsU92xVtC3-Fv_lcKc",
          "alg": "ES256",
          "crv": "P-256",
          "x": "JTEE5QghmkA_-7_pZoKIluRzGNvQGtzmpNvb_nAswhE",
          "y": "A_iBfIseHsdfE7CmI3lIYtKMdfyXXOIpPX_o6O0h0wY",
          "use": "sig"
        }
      ]
    }
  },
  "jwks": [
    {
      "kty": "EC",
      "kid": "MJ2BW-rNshp9sjh3SvwnBIkEsYsU92xVtC3-Fv_lcKc",
      "alg": "ES256",
      "crv": "P-256",
      "x": "JTEE5QghmkA_-7_pZoKIluRzGNvQGtzmpNvb_nAswhE",
      "y": "A_iBfIseHsdfE7CmI3lIYtKMdfyXXOIpPX_o6O0h0wY",
      "use": "sig"
    }
  ],
  "authority_hints": [
    "https://trustregistry.example"
  ]
}
```

The JWKS in the `vc_issuer` Entity Type Identifier contains information about the keys used to sign Digital Credentials. The JWKS on the root level of the Entity Configuration contains information about the key used to sign the Entity Configuration. In the example, the same key is used, but the keys MAY be different.

#### [§](#sd-jwt-vc-credentials) SD-JWT VC Credentials

**Requirement: When the Issuer issues credentials in the [SD-JWT VC](#term:sd-jwt-vc) format, the Issuer MUST place its Entity Identifier in the `fed` claim of the credential.**

##### [§](#example-sd-jwt-vc-with-federation-claim) Example: SD-JWT VC with Federation Claim

The following non-normative example shows a decoded payload of an [SD-JWT VC](#term:sd-jwt-vc) credential with the `fed` claim:

``` language-json
{
  "iss": "https://credential-issuer.example",
  "fed": "https://credential-issuer.example",
  "iat": 1683000000,
  "exp": 1883000000,
  "vct": "https://credentials.example.com/identity_credential",
  "is_over_65": true,
  "address": {
    "street_address": "123 Main St",
    "locality": "Anytown",
    "region": "Anystate",
    "country": "US"
  }
}
```

#### [§](#w3c-vcdm-credentials) W3C VCDM Credentials

**Requirement: When the Issuer issues credentials in the [W3C VCDM](#term:w3c-vcdm) format, the Issuer MUST place a `termsOfUse` property into the credential. The `type` of this `termsOfUse` property MUST be the string `OpenIDFederation` and the `policyId` MUST be the Issuer's Entity Identifier.**

##### [§](#example-w3c-vcdm-credential-with-termsofuse) Example: W3C VCDM Credential with termsOfUse

The following non-normative example illustrates the use of the `termsOfUse` property:

``` language-json
{
  "@context": [
    "https://www.w3.org/ns/credentials/v2"
  ],
  "id": "urn:uuid:c65d364e-2560-4e08-be03-9c3d944d609d",
  "type": [
    "VerifiableCredential"
  ],
  "issuer": "did:web:credential-issuer.example",
  "validFrom": "2025-12-01T00:00:00Z",
  "validUntil": "2028-12-31T23:59:59Z",
  "credentialSubject": {
  },
  "termsOfUse": {
    "type": "OpenIDFederation",
    "trustFramework": "Example Federation",
    "policyId": "https://credential-issuer.example"
  }
}
```

#### [§](#note-on-credential-issuer-vs-issuer) Note on Credential Issuer vs. Issuer

The Issuer defined in the digital credential (the value of the `iss` claim in SD-JWT VC credentials or the value of the `id` of the `issuer` property in W3C VCDM credentials) is not necessarily the same entity as the Credential Issuer defined in the property `credential_issuer` in the OID4VCI metadata.

For example, the [OpenID Federation](#term:openid-federation) Entity Identifier of the Issuer of the credential could be `https://university-of-utopia.example.edu` and the [OpenID Federation](#term:openid-federation) Entity Identifier of the Credential Issuer could be `https://credentials.ministryofeducation.example.edu`.

If the Issuer chooses to make this kind of distinction between the entity issuing the credential and the technical service used for issuance, it is RECOMMENDED that the Entity Configuration of the technical service has an `authority_hints` value pointing to the Issuer's Entity Identifier and the Issuer publishes a Subordinate Statement about the technical service.

### [§](#credential-presentation-and-verification) Credential Presentation and Verification

#### [§](#client-identifier-scheme) Client Identifier Scheme

**Requirement: The Verifier MUST use the `openid_federation:` prefix as defined in [OID4VP](#term:oid4vp) Section 5.9.3.**

#### [§](#verifier-metadata-publication) Verifier Metadata Publication

**Requirement: The Verifier MUST place verifier metadata into the Entity Configuration, in the `openid_credential_verifier` Entity Type Identifier.**

**Requirement: The Verifier MUST include the `federation_entity` Entity Type Identifier with the `display_name` property in the Entity Configuration.**

The Verifier MAY place additional metadata into the `federation_entity` Entity Type Identifier.

**Requirement: The Wallet MUST use the value of the `display_name` property in the `federation_entity` Entity Type Identifier when showing information about the Verifier to the user of the Wallet.**

### [§](#trust-establishment-3) Trust Establishment

**Requirement: To establish trust with the Issuer (ensure that the Issuer can be trusted), the Verifier MUST resolve the Trust Chain from the Issuer's Entity Configuration until it finds a Federation Entity it trusts.** **Requirement: The Verifier MUST verify that the credential is signed with a key included in the `vc_issuer` metadata in the Entity Configuration of the Issuer. The `jwks` property in the `vc_issuer` must contain a key with the `kid` value equal to the `kid` header in the [SD-JWT VC](#term:sd-jwt-vc) credential.**

#### [§](#example-verifier-entity-configuration) Example: Verifier Entity Configuration

The following JSON document is a non-normative example of the decoded payload of a Verifier's Entity Configuration.

``` language-json
{
  "iss": "https://credential-verifier.example",
  "sub": "https://credential-verifier.example",
  "iat": 1616239023,
  "exp": 1616239323,
  "metadata": {
    "federation_entity": {
      "display_name": "Example Credential Verifier",
      "logo_uri": "https://credential-verifier.example/logo.png",
      "organization_name": "Example Credential Verifier",
      "contacts": ["support@credential-verifier.example"]
    },
    "openid_credential_verifier": {
      "jwks": {
        "keys": [
          {
            "kty": "EC",
            "crv": "P-256",
            "x": "f83OJ3D2xF4Z1s3QpLQe4qVb8K7q6y1v3z4Yb6k9J0",
            "y": "x_FEzRu9q3u4bWz5n9X2L4q1U8T7c6v5s2d1a0b3C4",
            "alg": "ES256",
            "use": "enc",
            "kid": "ec-key-1"
          }
        ]
      },
      "encrypted_response_enc_values_supported": [
        "A128GCM",
        "A192GCM",
        "A256GCM"
      ],
      "vp_formats_supported": {
        "dc+sd-jwt": {
          "sd-jwt_alg_values": ["ES256"],
          "kb-jwt_alg_values": ["ES256"]
        }
      }
    }
  },
  "jwks": [
    {
      "kty": "EC",
      "kid": "y4nC8uTvcM5uJxOIvUqFjXb2EA6xPGdnt8zvjW94m6U",
      "alg": "ES256",
      "crv": "P-256",
      "x": "K6dA9ayt4P8xBN6SFiCZYOI2qeaFda7VV5wnmHWcl7w",
      "y": "CdE30dUX0geK4NL8IMC9u-rRMOLC9WaScJIGK5rxtKI"
    }
  ],
  "authority_hints": [
    "https://trustregistry.example"
  ]
}
```

### [§](#subordinate-statements) Subordinate Statements

Intermediaries and Trust Anchors MAY authorize their subordinates to issue or request certain credential types by placing metadata values like `openid_credential_issuer.credential_configurations_supported` or `openid_credential_verifier.dcql_query` in the Subordinate Statements.

The meaning of such statements SHOULD be specified in ecosystem rulebooks and are out of the scope of this specification.

![](data:image/svg+xml;base64,PHN2ZyBpY29uPjx1c2UgeGxpbms6aHJlZj0iI3N2Zy1naXRodWIiPjwvdXNlPjwvc3ZnPg==) ✕

Table of Contents ✕

-   [Abstract](#abstract)
    -   [Status of this Document](#status-of-this-document)
    -   [Audience](#audience)
    -   [Development of the DIIP Profile](#development-of-the-diip-profile)
-   [Structure of this Document](#structure-of-this-document)
-   [Goals](#goals)
    -   [Relationship to eIDAS Regulation and HAIP Profile](#relationship-to-eidas-regulation-and-haip-profile)
-   [Profile](#profile)
    -   [Credential Format](#credential-format)
    -   [Signature Algorithm](#signature-algorithm)
    -   [Identifiers](#identifiers)
    -   [Trust Establishment](#trust-establishment)
    -   [Issuance](#issuance)
        -   [OID4VCI](#oid4vci)
    -   [Presentation](#presentation)
        -   [OID4VP](#oid4vp)
    -   [Validity and Revocation Algorithm](#validity-and-revocation-algorithm)
-   [Terminology](#terminology)
-   [References](#references)
    -   [Normative References](#normative-references)
    -   [Non-Normative References](#non-normative-references)
-   [Appendix A: Future Directions](#appendix-a-future-directions)
    -   [Identifiers](#identifiers-2)
    -   [Trust Establishment](#trust-establishment-2)
    -   [Digital Credentials API](#digital-credentials-api)
    -   [References](#references-2)
-   [Appendix B: OpenID Federation Digital Credentials Profile](#appendix-b-openid-federation-digital-credentials-profile)
    -   [Introduction](#introduction)
        -   [Scope](#scope)
    -   [Terminology](#terminology-2)
    -   [Credential Issuance](#credential-issuance)
        -   [Entity Identifier](#entity-identifier)
        -   [Issuer Metadata Publication](#issuer-metadata-publication)
        -   [Example: Credential Issuer Entity Configuration](#example-credential-issuer-entity-configuration)
        -   [SD-JWT VC Credentials](#sd-jwt-vc-credentials)
        -   [W3C VCDM Credentials](#w3c-vcdm-credentials)
        -   [Note on Credential Issuer vs. Issuer](#note-on-credential-issuer-vs-issuer)
    -   [Credential Presentation and Verification](#credential-presentation-and-verification)
        -   [Client Identifier Scheme](#client-identifier-scheme)
        -   [Verifier Metadata Publication](#verifier-metadata-publication)
    -   [Trust Establishment](#trust-establishment-3)
        -   [Example: Verifier Entity Configuration](#example-verifier-entity-configuration)
    -   [Subordinate Statements](#subordinate-statements)

undefined
