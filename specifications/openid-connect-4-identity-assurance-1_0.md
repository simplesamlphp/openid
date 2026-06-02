---
author:
- Torsten Lodderstedt
- Daniel Fett
- Mark Haine
- Alberto Pulido
- Kai Lehmann
- Kosuke Koiwai
description:  This document defines an extension of OpenID Connect protocol for providing relying parties with claims about end-users that have a certain level of verification and/or additional metadata about the claim or the process of verification for access control, entitlement decisions or input to further verification processes.
generator: xml2rfc 3.16.0
ietf.draft: openid-connect-4-identity-assurance-1_0-16
keyword:
- security
- openid
- identity assurance
- ekyc
lang: en
scripts: Common,Latin
title: OpenID Connect for Identity Assurance 1.0
viewport: initial-scale=1.0
---

                        openid-connect-4-identity-assurance-1_0   October 2024
  --------------------- ----------------------------------------- --------------
  Lodderstedt, et al.   Standards Track                           \[Page\]

Workgroup:
:   eKYC-IDA

Published:
:   1 October 2024

Status:
:   Final

Authors:

:   T. Lodderstedt

    sprind.org

    D. Fett

    Authlete

    M. Haine

    Considrd.Consulting Ltd

    A. Pulido

    Santander

    K. Lehmann

    1&1 Mail & Media Development & Technology GmbH

    K. Koiwai

    KDDI Corporation

# OpenID Connect for Identity Assurance 1.0

## [Abstract](#abstract)

This document defines an extension of OpenID Connect protocol for providing relying parties with claims about end-users that have a certain level of verification and/or additional metadata about the claim or the process of verification for access control, entitlement decisions or input to further verification processes.[¶](#section-abstract-1)

## [Foreword](#name-foreword)

The OpenID Foundation (OIDF) promotes, protects, and nurtures the OpenID community and technologies. As a non-profit international standardizing body, it is comprised by over 160 participating entities (workgroup participant). The work of preparing implementer drafts and final international standards is carried out through OIDF workgroups in accordance with the OpenID Process. Participants interested in a subject for which a workgroup has been established have the right to be represented in that workgroup. International organizations, governmental and non-governmental, in liaison with OIDF, also take part in the work. OIDF collaborates closely with other standardizing bodies in the related fields.[¶](#section-note.1-1)

Final drafts adopted by the Workgroup through consensus are circulated publicly for the public review for 60 days and for the OIDF members for voting. Publication as an OIDF Standard requires approval by at least 50% of the members casting a vote. There is a possibility that some of the elements of this document may be subject to patent rights. OIDF shall not be held responsible for identifying any or all such patent rights.[¶](#section-note.1-2)

## [Introduction](#name-introduction)

This extension to OpenID Connect standardizes how relying parties request and receive identity information with additional assurance metadata. This document is aimed at enabling use cases requiring strong assurance, for example, to comply with regulatory requirements such as anti-money laundering laws or access to health data, risk mitigation, or fraud prevention.[¶](#section-note.2-1)

In such use cases, the relying party (RP) needs to understand the trustworthiness or assurance level of the claims about the end-user that the OpenID provider (OP) is willing to communicate, along with process-related information and evidence used to verify the end-user claims.[¶](#section-note.2-2)

The `acr` claim, as defined in section 2 of the OpenID Connect specification \[[OpenID](#OpenID)\], is suited to assure information about the authentication performed in an OpenID Connect transaction. Identity assurance, however, requires a different representation. While authentication is an aspect of an OpenID Connect transaction, assurance and associated verification and validation details, are properties of a certain claim or a group of claims. Several of them will typically be conveyed to the RP as the result of an OpenID Connect transaction.[¶](#section-note.2-3)

For example, the assurance an OP typically will be able to give for an e-mail address will be "self-asserted" or \"verified\". The family name of an end-user, in contrast, might have been verified in accordance with the respective anti-money laundering law by showing an ID card to a trained employee of the OP operator.[¶](#section-note.2-4)

Identity assurance requires a way to convey assurance data along with and coupled to the respective claims about the end-user. This document defines a suitable representation and mechanisms the RP will utilize to request verified claims about an end-user along with assurance data and for the OP to represent these verified claims and accompanying assurance data.[¶](#section-note.2-5)

## [Warning](#name-warning)

This document is not an OIDF International Standard. It is distributed for review and comment. It is subject to change without notice and may not be referred to as an International Standard.[¶](#section-note.3-1)

Recipients of this draft are invited to submit, with their comments, notification of any relevant patent rights of which they are aware and to provide supporting documentation.[¶](#section-note.3-2)

## [Notational conventions](#name-notational-conventions)

The keywords \"shall\", \"shall not\", \"should\", \"should not\", \"may\", and \"can\" in this document are to be interpreted as described in ISO Directive Part 2 \[[ISODIR2](#ISODIR2)\]. These keywords are not used as dictionary terms such that any occurrence of them shall be interpreted as keywords and are not to be interpreted with their natural language meanings.[¶](#section-note.4-1)

[▲](#)

## [Table of Contents](#name-table-of-contents)

-   [1](#section-1).  [Scope](#name-scope)

-   [2](#section-2).  [Normative references](#name-normative-references)

-   [3](#section-3).  [Terms and definitions](#name-terms-and-definitions)

    -   [3.1](#section-3.1).  [claim](#name-claim)

    -   [3.2](#section-3.2).  [identity proofing](#name-identity-proofing)

    -   [3.3](#section-3.3).  [identity verification](#name-identity-verification)

    -   [3.4](#section-3.4).  [identity assurance](#name-identity-assurance)

    -   [3.5](#section-3.5).  [verified claim](#name-verified-claim)

    -   [3.6](#section-3.6).  [claim provider](#name-claim-provider)

-   [4](#section-4).  [Requirements](#name-requirements)

-   [5](#section-5).  [Verified claims](#name-verified-claims)

    -   [5.1](#section-5.1).  [Verified claims schema](#name-verified-claims-schema)

    -   [5.2](#section-5.2).  [Verified claims delivery](#name-verified-claims-delivery)

    -   [5.3](#section-5.3).  [Requesting end-user claims](#name-requesting-end-user-claims)

    -   [5.4](#section-5.4).  [Requesting verification data](#name-requesting-verification-dat)

    -   [5.5](#section-5.5).  [Defining further constraints on verification data](#name-defining-further-constraint)

        -   [5.5.1](#section-5.5.1).  [Value/values](#name-value-values)

        -   [5.5.2](#section-5.5.2).  [Max_age](#name-max_age)

    -   [5.6](#section-5.6).  [Requesting claims sets with different verification requirements](#name-requesting-claims-sets-with)

    -   [5.7](#section-5.7).  [Returning less data than requested](#name-returning-less-data-than-re)

        -   [5.7.1](#section-5.7.1).  [General requirements](#name-general-requirements)

        -   [5.7.2](#section-5.7.2).  [Unavailable data](#name-unavailable-data)

        -   [5.7.3](#section-5.7.3).  [Non-consented data](#name-non-consented-data)

        -   [5.7.4](#section-5.7.4).  [Data not matching requirements](#name-data-not-matching-requireme)

        -   [5.7.5](#section-5.7.5).  [Omitting elements](#name-omitting-elements)

        -   [5.7.6](#section-5.7.6).  [Error handling](#name-error-handling)

    -   [5.8](#section-5.8).  [Requesting sets of claims by scope](#name-requesting-sets-of-claims-b)

-   [6](#section-6).  [Aggregated and distributed claims](#name-aggregated-and-distributed-)

    -   [6.1](#section-6.1).  [Aggregated and distributed claims assertions](#name-aggregated-and-distributed-c)

    -   [6.2](#section-6.2).  [Aggregated and distributed claims validation](#name-aggregated-and-distributed-cl)

-   [7](#section-7).  [Requesting verified claims](#name-requesting-verified-claims)

-   [8](#section-8).  [OP metadata](#name-op-metadata)

-   [9](#section-9).  [Privacy consideration](#name-privacy-consideration)

-   [10](#section-10). [Security considerations](#name-security-considerations)

    -   [10.1](#section-10.1).  [Security profile](#name-security-profile)

    -   [10.2](#section-10.2).  [End-user authentication](#name-end-user-authentication)

-   [11](#section-11). [Implementation and interoperability](#name-implementation-and-interope)

-   [12](#section-12). [Predefined values](#name-predefined-values)

-   [13](#section-13). [Normative References](#name-normative-references-2)

-   [14](#section-14). [Informative References](#name-informative-references)

-   [Appendix A](#appendix-A).  [IANA considerations](#name-iana-considerations)

    -   [A.1](#appendix-A.1).  [Media type registration](#name-media-type-registration)

-   [Appendix B](#appendix-B).  [Example requests](#name-example-requests)

    -   [B.1](#appendix-B.1).  [Verification of claims by a document](#name-verification-of-claims-by-a)

    -   [B.2](#appendix-B.2).  [Verification of claims by trust framework and evidence types](#name-verification-of-claims-by-t)

    -   [B.3](#appendix-B.3).  [Verification of claims by trust framework and check method](#name-verification-of-claims-by-tr)

    -   [B.4](#appendix-B.4).  [Verification of claims by electronic signature](#name-verification-of-claims-by-e)

-   [Appendix C](#appendix-C).  [Example responses](#name-example-responses)

    -   [C.1](#appendix-C.1).  [Document](#name-document)

    -   [C.2](#appendix-C.2).  [Document and verifier details](#name-document-and-verifier-detai)

    -   [C.3](#appendix-C.3).  [Evidence with all assurance details](#name-evidence-with-all-assurance)

    -   [C.4](#appendix-C.4).  [Notified eID system (eIDAS)](#name-notified-eid-system-eidas)

    -   [C.5](#appendix-C.5).  [Electronic_record](#name-electronic_record)

    -   [C.6](#appendix-C.6).  [Vouch](#name-vouch)

    -   [C.7](#appendix-C.7).  [Multiple verified claims](#name-multiple-verified-claims)

    -   [C.8](#appendix-C.8).  [Claims provided by the OP and external sources](#name-claims-provided-by-the-op-a)

    -   [C.9](#appendix-C.9).  [Self-Issued OpenID provider and external claims](#name-self-issued-openid-provider)

-   [Appendix D](#appendix-D).  [Example requests and responses](#name-example-requests-and-respon)

    -   [D.1](#appendix-D.1).  [verified claims in UserInfo response](#name-verified-claims-in-userinfo)

        -   [D.1.1](#appendix-D.1.1).  [Request](#name-request)

        -   [D.1.2](#appendix-D.1.2).  [Response](#name-response)

    -   [D.2](#appendix-D.2).  [verified claims in ID Tokens](#name-verified-claims-in-id-token)

        -   [D.2.1](#appendix-D.2.1).  [Request](#name-request-2)

        -   [D.2.2](#appendix-D.2.2).  [Response](#name-response-2)

-   [Appendix E](#appendix-E).  [Acknowledgements](#name-acknowledgements)

-   [Appendix F](#appendix-F).  [Notices](#name-notices)

-   [](#appendix-G)[Authors\' Addresses](#name-authors-addresses)

## [1.](#section-1) [Scope](#name-scope)

This document is a definition of the technical mechanism to allow a relying party to request one or more verified claim about the end-user and to enable an OpenID provider to provide a relying party with a verified claim (\"the tools\").[¶](#section-1-1)

Additional facets needed to deploy a complete solution for identity assurance, such as legal aspects (including liability), trust frameworks, or commercial agreements are out of scope. It is up to the particular deployment to complement the technical solution based on this document with the respective definitions (\"the rules\").[¶](#section-1-2)

Note: Although such aspects are out of scope, the aim of the specification is to enable implementations of the technical mechanism to be flexible enough to fulfill different legal and commercial requirements in jurisdictions around the world. Consequently, such requirements will be discussed in this document as examples.[¶](#section-1-3)

## [2.](#section-2) [Normative references](#name-normative-references)

See section 13 for normative references.[¶](#section-2-1)

## [3.](#section-3) [Terms and definitions](#name-terms-and-definitions)

For the purposes of this document, the following terms and definitions apply.[¶](#section-3-1)

### [3.1.](#section-3.1) [claim](#name-claim)

piece of information asserted about an entity[¶](#section-3.1-1)

### [3.2.](#section-3.2) [identity proofing](#name-identity-proofing)

process in which an end-user provides evidence to an OpenID Connect provider (OP) or claim provider reliably identifying themselves, thereby allowing the OP or claim provider to assert that identification at a useful assurance level[¶](#section-3.2-1)

### [3.3.](#section-3.3) [identity verification](#name-identity-verification)

process conducted by the OP or a claim provider to verify the end-user\'s identity[¶](#section-3.3-1)

### [3.4.](#section-3.4) [identity assurance](#name-identity-assurance)

process in which the OP or a claim provider asserts identity data of a certain end-user with a certain assurance towards an RP, typically expressed by way of an assurance level. Depending on legal requirements, the OP can be required to provide evidence of the identity verification process to the RP[¶](#section-3.4-1)

### [3.5.](#section-3.5) [verified claim](#name-verified-claim)

claim about an end-user, typically a natural person, whose binding to a particular end-user account was verified in the course of an identity verification process[¶](#section-3.5-1)

### [3.6.](#section-3.6) [claim provider](#name-claim-provider)

server that can provide claim information about a entity; synonomous with \"claims provider\" in OpenID Connect core[¶](#section-3.6-1)

## [4.](#section-4) [Requirements](#name-requirements)

The RP will be able to request the minimal data set it needs (data minimization) and to express requirements regarding this data, the evidence and the identity verification processes employed by the OP.[¶](#section-4-1)

This extension will be usable by OPs operating under a certain regulation related to identity assurance, such as eIDAS, as well as other OPs operating without such a regulation.[¶](#section-4-2)

It is assumed that OPs operating under a suitable regulation can assure identity data without the need to provide further evidence since they are approved to operate according to well-defined rules with clearly defined liability. For example in the case of eIDAS, the peer review ensures eIDAS compliance and the respective member state assumes the liability for the identities asserted by its notified eID system.[¶](#section-4-3)

Every other OP not operating under such well-defined conditions could receive a request to provide the RP data about the identity verification process along with identity evidence to allow the RP to conduct their own risk assessment and to map the data obtained from the OP to other laws. For example, if an OP verifies and maintains identity data in accordance with an anti-money laundering law, an RP might choose to use the identity attributes in a different regulatory context, such as eHealth or the previously mentioned eIDAS.[¶](#section-4-4)

The concept of this document is that the OP can provide identity data along with metadata about the identity assurance process. It is the responsibility of the RP to assess this data and map it into its own legal context.[¶](#section-4-5)

From a technical perspective, this means this document allows the OP to provide verified claims along with information about the respective trust framework, but also supports the externalization of information about the identity verification process.[¶](#section-4-6)

The representation defined in this document can be used to provide RPs with verified claims about the end-user via any appropriate channel. In the context of OpenID Connect, verified claims can be provided in ID Tokens or as part of the UserInfo response. It is also possible to utilize the format described here in OAuth access tokens or token introspection responses to provide resource servers with verified claims.[¶](#section-4-7)

This extension is intended to be truly international and support identity assurance across different jurisdictions. The extension is therefore extensible to support various trust frameworks, identity evidence and assurance processes.[¶](#section-4-8)

In order to give implementers as much flexibility as possible, this extension can be used in conjunction with existing OpenID Connect claims and other extensions within the same OpenID Connect assertion (e.g., ID Token or UserInfo response) utilized to convey claims about end-users.[¶](#section-4-9)

For example, OpenID Connect \[[OpenID](#OpenID)\] defines claims for representing family name and given name of an end-user without a verification status. These claims can be used in the same OpenID Connect assertion beside verified claims represented according to this extension.[¶](#section-4-10)

In the same way, existing claims to inform the RP of the verification status of the `phone_number` and `email` claims can be used together with this extension.[¶](#section-4-11)

Even for representing verified claims, this extension utilizes existing OpenID Connect claims if possible and reasonable. The extension will, however, ensure RPs cannot (accidentally) interpret unverified claims as verified claims.[¶](#section-4-12)

In order to fulfill the requirements of some jurisdictions on identity assurance, the OpenID Connect for IDA claims \[[OpenID4IDAClaims](#OpenID4IDAClaims)\] specification defines a number of claims for conveying end-user data in addition to the claims defined in the OpenID Connect specification \[[OpenID](#OpenID)\].[¶](#section-4-13)

## [5.](#section-5) [Verified claims](#name-verified-claims)

### [5.1.](#section-5.1) [Verified claims schema](#name-verified-claims-schema)

The basic idea is to use a container element, called `verified_claims`, to provide the RP with a set of claims along with the respective metadata and verification evidence related to the verification of these claims. This way, it is explicit which claims are verified, reducing the risk of RPs accidentally processing unverified claims as verified claims.[¶](#section-5.1-1)

This document uses the \[[IDA-verified-claims](#IDA-verified-claims)\] document as the definition of the schema for representation of assured digital identity attributes and identity assurance metadata.[¶](#section-5.1-2)

The following example would assert to the RP that the OP has verified the claims provided (`given_name` and `family_name`) according to an example trust framework `trust_framework_example`:[¶](#section-5.1-3)

    {
      "verified_claims": {
        "verification": {
          "trust_framework": "trust_framework_example"
        },
        "claims": {
          "given_name": "Max",
          "family_name": "Meier"
        }
      }
    }

[¶](#section-5.1-4)

This document requires that RPs use the schema defined in \[[IDA-verified-claims](#IDA-verified-claims)\]. There are places in the JSON structure where that schema can be extended by implementers but deviation from the schema as defined would not be correct use of this document.[¶](#section-5.1-5)

### [5.2.](#section-5.2) [Verified claims delivery](#name-verified-claims-delivery)

A `verified_claims` element can be added to an OpenID Connect UserInfo response and/or an ID Token.[¶](#section-5.2-1)

Here is an example of the payload of an ID token including verified claims:[¶](#section-5.2-2)

    {
      "iss": "https://server.example.com",
      "sub": "248289761",
      "aud": "https://rs.example.com/",
      "exp": 1544645174,
      "client_id": "s6BhdRkqt3_",
      "verified_claims": {
        "verification": {
          "trust_framework": "example"
        },
        "claims": {
          "given_name": "Max",
          "family_name": "Mustermann"
        }
      }
    }

[¶](#section-5.2-3)

An OP or Authorization Server (AS) can also include aggregated or distributed `verified_claims` in the above assertions (see [Section 6](#aggregated_distributed_claims) for more details).[¶](#section-5.2-4)

### [5.3.](#section-5.3) [Requesting end-user claims](#name-requesting-end-user-claims)

Verified claims can be requested on the level of individual claims about the end-user by utilizing the `claims` parameter as defined in section 5.5 of the OpenID Connect specification \[[OpenID](#OpenID)\].[¶](#section-5.3-1)

Note: A machine-readable definition of the syntax to be used to request `verified_claims` is given as JSON schema in \[[verified_claims_request.json](#verified_claims_request.json)\], which can be used to automatically validate `claims` request parameters. The provided JSON schema files are a non-normative implementation of this document and any discrepancies that exist are either implementation bugs or interpretations.[¶](#section-5.3-2)

To request verified claims, the `verified_claims` element is added to the `userinfo` or the `id_token` element of the `claims` parameter.[¶](#section-5.3-3)

Since `verified_claims` contains the effective claims about the end-user in a nested `claims` element, the syntax is extended to include expressions on nested elements as follows. The `verified_claims` element includes a `claims` element, which in turn includes the desired claims as keys. For each claim, the value is either `null` (default), or an object. The object may contain restrictions using `value` or `values` as defined in \[[OpenID](#OpenID)\] and/or the `essential` key as described below. An example is shown in the following:[¶](#section-5.3-4)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": null
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#section-5.3-5)

Use of the `claims` parameter allows the RP to request specified claims about the end-user needed for its use case. This allows RPs to fulfill the requirements for data minimization by requesting only claims needed for its use case.[¶](#section-5.3-6)

Note: it is not possible to request sub-claims (for example the `country` subclaim of the `address` claim) using mechanisms from OpenID Connect Core or this document.[¶](#section-5.3-7)

RPs can use the `essential` field as defined in section 5.5.1 of the OpenID Connect specification \[[OpenID](#OpenID)\]. The following example shows this for the family and given names.[¶](#section-5.3-8)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": null
          },
          "claims": {
            "given_name": {
              "essential": true
            },
            "family_name": {
              "essential": true
            },
            "birthdate": null
          }
        }
      }
    }

[¶](#section-5.3-9)

### [5.4.](#section-5.4) [Requesting verification data](#name-requesting-verification-dat)

RPs request verification data in the same way they request claims about the end-user. When the claims request parameter is being used, the syntax is based on the rules given in [Section 5.3](#req_claims) and extends them for navigation into the structure of the `verification` element.[¶](#section-5.4-1)

Elements within `verification` are requested by adding the respective element as shown in the following example:[¶](#section-5.4-2)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": null,
            "time": null
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#section-5.4-3)

It requests the trust framework the OP complies with and the date of the verification of the end-user claims.[¶](#section-5.4-4)

The RP shall explicitly request any data it wants the OP to add to the `verification` element.[¶](#section-5.4-5)

Therefore, the RP shall set fields one step deeper into the structure if it wants to obtain evidence. One or more entries in the `evidence` array are used as filter criteria and templates for all entries in the result array. The following example shows a request asking for evidence of type `document` only.[¶](#section-5.4-6)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": null,
            "time": null,
            "evidence": [
              {
                "type": {
                  "value": "document"
                },
                "method": null,
                "document_details": {
                  "type": null
                }
              }
            ]
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#section-5.4-7)

The example also requests the OP to add the respective `method` and the `document` elements (including data about the document type), for every evidence array member, to the resulting `verified_claims` claim.[¶](#section-5.4-8)

A single entry in the `evidence` array represents a filter over elements of a certain evidence type. The RP therefore shall specify this type by including the `type` field including a suitable `value` sub-element value. The `values` sub-element shall not be used for the `evidence/type` field.[¶](#section-5.4-9)

If multiple entries are present in `evidence`, these filters are linked by a logical OR.[¶](#section-5.4-10)

`check_details` is an array of the processes that have been applied to the `evidence`. An RP can filter `check_details` by requesting a particular value for one or more of its sub-elements. If multiple entries for the same sub-element are present this acts as a logical OR between them.[¶](#section-5.4-11)

`assurance_details` is an array representing how the `evidence` and `check_details` fulfill the requirements of the `trust_framework`. RP should only request this where they need to know this information. Where `assurance_details` has been requested by an RP the OP shall return the `assurance_details` element along with all sub-elements that it has. If an RP wants to filter what types of `evidence` and `check_methods` they shall use those methods to do so, e.g. requesting an `assurance_type` should have no filtering effect.[¶](#section-5.4-12)

The RP can also request certain data within the `document` element to be present. This again follows the syntax rules used above:[¶](#section-5.4-13)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": null,
            "time": null,
            "evidence": [
              {
                "type": {
                  "value": "document"
                },
                "method": null,
                "document_details": {
                  "type": null,
                  "issuer": {
                    "country": null,
                    "name": null
                  },
                  "document_number": null,
                  "date_of_issuance": null
                }
              }
            ]
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#section-5.4-14)

### [5.5.](#section-5.5) [Defining further constraints on verification data](#name-defining-further-constraint)

#### [5.5.1.](#section-5.5.1) [Value/values](#name-value-values)

The RP can limit the possible values of the elements `trust_framework`, `evidence/method`, `evidence/check_details`, and `evidence/document/type` by utilizing the `value` or `values` fields and the element `evidence/type` by utilizing the `value` field.[¶](#section-5.5.1-1)

Note: Examples on the usage of a restriction on `evidence/type` were given in the previous section.[¶](#section-5.5.1-2)

The following example shows how an RP requests claims either complying with trust framework `gold` or `silver`.[¶](#section-5.5.1-3)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": {
              "values": [
                "gold",
                "silver"
              ]
            }
          },
          "claims": {
            "given_name": null,
            "family_name": null
          }
        }
      }
    }

[¶](#section-5.5.1-4)

The following example shows that the RP wants to obtain an attestation based on the German anti-money laundering law (trust framework `de_aml`) and limited to end-users who were identified in a bank branch in person (physical in person proofing - method `pipp`) using either an `idcard` or a `passport`.[¶](#section-5.5.1-5)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": {
              "value": "de_aml"
            },
            "evidence": [
              {
                "type": {
                  "value": "document"
                },
                "method": {
                  "value": "pipp"
                },
                "document": {
                  "type": {
                    "values": [
                      "idcard",
                      "passport"
                    ]
                  }
                }
              }
            ]
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#section-5.5.1-6)

The OP shall not ignore some or all of the query restrictions on possible values and shall not deliver available verified/verification data that does not match these constraints.[¶](#section-5.5.1-7)

#### [5.5.2.](#section-5.5.2) [Max_age](#name-max_age)

The RP can also express a requirement regarding the age of certain data, like the time elapsed since the issuance/expiry of certain evidence types or since the verification process asserted in the `verification` element took place. Section 5.5.1 of the OpenID Connect specification \[[OpenID](#OpenID)\] defines a query syntax that allows for new special query members to be defined. This document introduces a new such member `max_age`, which is applicable to the possible values of any elements containing dates or timestamps (e.g., `time`, `date_of_issuance` and `date_of_expiry` elements of evidence of type `document`).[¶](#section-5.5.2-1)

`max_age`: Optional. JSON number value only applicable to claims that contain dates or timestamps. It defines the maximum time (in seconds) to be allowed to elapse since the value of the date/timestamp up to the point in time of the request. The OP should make the calculation of elapsed time starting from the last valid second of the date value.[¶](#section-5.5.2-2)

The following is an example of a request for claims where the verification process of the data is not allowed to be older than 63113852 seconds:[¶](#section-5.5.2-3)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": {
              "value": "jp_aml"
            },
            "time": {
              "max_age": 63113852
            }
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#section-5.5.2-4)

The OP should try to fulfill this requirement. If the verification data of the end-user is older than the requested `max_age`, the OP can attempt to refresh the end-user's verification by sending them through an online identity verification process, e.g., by utilizing an electronic ID card or a video identification approach.[¶](#section-5.5.2-5)

### [5.6.](#section-5.6) [Requesting claims sets with different verification requirements](#name-requesting-claims-sets-with)

It is also possible to request different trust frameworks, assurance levels, and methods for different claim sets. This requires the RP to send an array of `verified_claims` objects instead of passing a single object.[¶](#section-5.6-1)

The following example illustrates this functionality.[¶](#section-5.6-2)

    {
      "userinfo": {
        "verified_claims": [
          {
            "verification": {
              "trust_framework": {
                "value": "eidas"
              },
              "assurance_level": {
                "value": "high"
              }
            },
            "claims": {
              "given_name": null,
              "family_name": null
            }
          },
          {
            "verification": {
              "trust_framework": {
                "value": "eidas"
              },
              "assurance_level":{
                "values":[
                  "high",
                  "substantial"
                ]
              }
            },
            "claims": {
              "birthdate": null
            }
          }
        ]
      }
    }

[¶](#section-5.6-3)

When the RP requests multiple verifications as described above, the OP will process each element in the array independently. The OP will provide `verified_claims` response elements for every `verified_claims` request element whose requirements it is able to fulfill. This also means if multiple `verified_claims` elements contain the same end-user claim(s), the OP delivers the claim in as many verified claims response objects it can fulfill. For example, if the trust framework the OP uses is compatible with multiple of the requested trust frameworks, it provides a `verified_claims` element for each of them.[¶](#section-5.6-4)

The RP can combine multiple `verified_claims` claims in the request with multiple `trust_framework` and/or `assurance_level` values using the `values` element. In that case, the rules given above for processing `values` are applied for the particular `verified_claims` request object.[¶](#section-5.6-5)

    {
      "userinfo": {
        "verified_claims": [
          {
            "verification": {
              "trust_framework": {
                "value": "gold"
              },
              "evidence": [
                {
                  "type": {
                    "value": "document"
                  }
                }
              ]
            },
            "claims": {
              "given_name": null,
              "family_name": null
            }
          },
          {
            "verification": {
              "trust_framework": {
                "values": [
                  "silver",
                  "bronze"
                ]
              },
              "evidence": [
                {
                  "type": {
                    "value": "electronic_record"
                  }
                }
              ]
            },
            "claims": {
              "given_name": null,
              "family_name": null
            }
          }
        ]
      }
    }

[¶](#section-5.6-6)

In the above example, the RP asks for family and given name either under trust framework `gold` with an evidence of type `document` or under trust framework `silver` or `bronze` but with an evidence `electronic_record`.[¶](#section-5.6-7)

### [5.7.](#section-5.7) [Returning less data than requested](#name-returning-less-data-than-re)

#### [5.7.1.](#section-5.7.1) [General requirements](#name-general-requirements)

As stated in section 3.3.3.6 of \[[OpenID](#OpenID)\], \"the OP may choose to return fewer claims about the end-user from the authorization endpoint\". This document makes no change to that provision. The OP may also choose to return a subset of the `verification` element of any `verified_claims` providing it remains compliant with the `verified_claims` JSON schema defined in \[[OpenID4IDAClaims](#OpenID4IDAClaims)\].[¶](#section-5.7.1-1)

In some cases, OPs cannot deliver the requested data to an RP, for example, because the data is not available or does not match the RP\'s requirements. The rules for handling these cases are described in the following.[¶](#section-5.7.1-2)

Extensions of this document can define additional rules or override these rules, for example[¶](#section-5.7.1-3)

-   to allow or disallow the use of claims depending on scheme-specific checks,[¶](#section-5.7.1-4.1)
-   to enable a finer-grained control of the RP over the behavior of the OP when data is unavailable or does not match the criteria, or[¶](#section-5.7.1-4.2)
-   to abort transactions (return error codes) in cases where requests cannot be fulfilled.[¶](#section-5.7.1-4.3)

Important: The behavior described below is independent from the use of `essential` (as defined in section 5.5.1 of \[[OpenID](#OpenID)\]).[¶](#section-5.7.1-5)

#### [5.7.2.](#section-5.7.2) [Unavailable data](#name-unavailable-data)

If the OP does not have data about a certain claim, does not understand/support the respective claim, OPs shall omit the respective claim from any corresponding ID Token or UserInfo response.[¶](#section-5.7.2-1)

#### [5.7.3.](#section-5.7.3) [Non-consented data](#name-non-consented-data)

When relying on end-user consent to determine the specific data to be shared the end-user may make a choice to release only a subset of the data requested. In this case the OP shall omit from any corresponding ID Token or UserInfo response data that has not had end-user consent for sharing.[¶](#section-5.7.3-1)

Alternatively, when relying on end-user consent to determine the specific data to be shared the end-user may choose to release none of the data requested. In this case standard OpenID Connect authentication error response logic applies, as defined in section 3.1.2.6 of \[[OpenID](#OpenID)\].[¶](#section-5.7.3-2)

#### [5.7.4.](#section-5.7.4) [Data not matching requirements](#name-data-not-matching-requireme)

When the available data does not fulfill the requirements of the RP expressed through `value`, `values`, or `max_age`, the following logic applies:[¶](#section-5.7.4-1)

-   If the respective requirement was expressed for a claim within `verified_claims/verification`, the OP shall omit the whole `verified_claims` element.[¶](#section-5.7.4-2.1)
-   Otherwise, the OP shall omit the respective claim from the response.[¶](#section-5.7.4-2.2)

In both cases, the OP shall not return an error to the RP.[¶](#section-5.7.4-3)

#### [5.7.5.](#section-5.7.5) [Omitting elements](#name-omitting-elements)

If an element is to be omitted according to the rules above, but is a requirement for a valid response, the OP shall omit its parent element as well. This OP shall repeat this process until the response is valid.[¶](#section-5.7.5-1)

#### [5.7.6.](#section-5.7.6) [Error handling](#name-error-handling)

If the OP encounters an error, standard OpenID Connect authentication error response logic applies, as defined in section 3.1.2.6 of \[[OpenID](#OpenID)\].[¶](#section-5.7.6-1)

### [5.8.](#section-5.8) [Requesting sets of claims by scope](#name-requesting-sets-of-claims-b)

Verified claims about the end-user can be requested as part of a pre-defined set by utilizing the `scope` parameter as defined in section 5.4 of the OpenID Connect specification \[[OpenID](#OpenID)\].[¶](#section-5.8-1)

When using this approach the claims associated with a `scope` value are administratively defined at the OP. The OP configuration and RP request parameters will determine whether the claims are returned via the ID Token or UserInfo endpoint as defined in section 5.3.2 of the OpenID Connect specification \[[OpenID](#OpenID)\].[¶](#section-5.8-2)

## [6.](#section-6) [Aggregated and distributed claims](#name-aggregated-and-distributed-)

### [6.1.](#section-6.1) [Aggregated and distributed claims assertions](#name-aggregated-and-distributed-c)

When distributed claims are used the URL that is the value of the `endpoint` element in any distributed `_claim_source` sub-element shall use the https URI scheme and the JWT returned should not be accessible via any other URI scheme.[¶](#section-6.1-1)

For aggregated or distributed claims, every assertion provided by the external claims source shall contain:[¶](#section-6.1-2)

-   a `typ` header parameter with the value `provided-claims+jwt`,[¶](#section-6.1-3.1)
-   an `iss` claim identifying the claims source,[¶](#section-6.1-3.2)
-   a `sub` claim identifying the end-user in the context of the claim source, and[¶](#section-6.1-3.3)
-   a `verified_claims` element containing one or more `verified_claims` objects.[¶](#section-6.1-3.4)

To ensure that assertions cannot be confused with OpenID Connect ID Tokens, assertions shall not contain:[¶](#section-6.1-4)

-   an `exp` claim, or[¶](#section-6.1-5.1)
-   an `aud` claim.[¶](#section-6.1-5.2)

The `verified_claims` element in an aggregated or distributed claims object shall have one of the following forms:[¶](#section-6.1-6)

-   a JSON string referring to a certain claim source (as defined in \[[OpenID](#OpenID)\])[¶](#section-6.1-7.1)
-   a JSON array of strings referring to the different claim sources[¶](#section-6.1-7.2)
-   a JSON object composed of sub-elements formatted with the syntax as defined for requesting `verified_claims` where the name of each object is a name for the respective claim source. Every such named object contains sub-objects called `claims` and `verification` expressing data provided by the respective claims source. This allows the RP to look ahead before it actually requests distributed claims in order to prevent extra time, cost, data collisions, etc. caused by these requests.[¶](#section-6.1-7.3)

Note: The two later forms extend the syntax as defined in section 5.6.2 of the OpenID Connect specification \[[OpenID](#OpenID)\]) in order to accommodate the specific use cases for `verified_claims`.[¶](#section-6.1-8)

The following are examples of assertions including verified claims as aggregated claims[¶](#section-6.1-9)

    {
      "iss": "https://server.example.com",
      "sub": "248289761001",
      "email": "janedoe@example.com",
      "email_verified": true,
      "_claim_names": {
        "verified_claims": "src1"
      },
      "_claim_sources": {
        "src1": {
          "JWT": "eyJhbGciOiJQUzI1NiIsImtpZCI6IjFlOWdkazciLCJ0eXAiOiJwcm92aWRlZC1jbGFpbXMrand0In0.eyJpc3MiOiJodHRwczovL3NlcnZlci5vdGhlcm9wLmNvbSIsInN1YiI6ImU4MTQ4NjAzLTg5MzQtNDI0NS04MjViLWMxMDhiOGI2Yjk0NSIsInZlcmlmaWVkX2NsYWltcyI6eyJ2ZXJpZmljYXRpb24iOnsidHJ1c3RfZnJhbWV3b3JrIjoiaWFsX2V4YW1wbGVfZ29sZCJ9LCJjbGFpbXMiOnsiZ2l2ZW5fbmFtZSI6Ik1heCIsImZhbWlseV9uYW1lIjoiTWVpZXIiLCJiaXJ0aGRhdGUiOiIxOTU2LTAxLTI4In19fQ.VAtHwi85ihW98uulbNOBCkyCyD4jeDrTeaMNdI3Wllks1z-LT8kyzN5Iz7Nu2HpMmmCKZpgY552O0fm_-Fr3Vls3BvmJsh1A524jh9VlsCL-1WezJ-DShjMUyP76_3Xbdl-iYHdWLjoQ5hFZQg6GLrLxOGlQXX9b-kxtQm3DV9nFJhOqMl_5_U8IU_A1LfypmRvXuD1Frw8ASS7OmyGOCkuFDOaV7Uu0BuZjYWiMC8Eem4M2A9RhuoLKDBYuVlwIFaHx-cuGcRJZWDg9K5DekIuLE73Iz1Cuh49HumkC9qGqkTV6EARSJeqFxPhjnZNkJY1e1P7Q7cgyT2HywjR6Tw"
        }
      }
    }

[¶](#section-6.1-10)

and distributed claims.[¶](#section-6.1-11)

    {
      "iss": "https://server.example.com",
      "sub": "248289761001",
      "email": "janedoe@example.com",
      "email_verified": true,
      "_claim_names": {
        "verified_claims": "src1"
      },
      "_claim_sources": {
        "src1": {
          "endpoint": "https://server.yetanotherop.com/claim_source",
          "access_token": "ksj3n283dkeafb76cdef"
        }
      }
    }

[¶](#section-6.1-12)

The following example shows an ID Token containing `verified_claims` from two different external claim sources, one as aggregated and the other as distributed claims.[¶](#section-6.1-13)

    {
      "iss": "https://server.example.com",
      "sub": "248289761001",
      "email": "janedoe@example.com",
      "email_verified": true,
      "_claim_names": {
        "verified_claims": [
          "src1",
          "src2"
        ]
      },
      "_claim_sources": {
        "src1": {
          "JWT": "eyJhbGciOiJQUzI1NiIsImtpZCI6IjFlOWdkazciLCJ0eXAiOiJwcm92aWRlZC1jbGFpbXMrand0In0.eyJpc3MiOiJodHRwczovL3NlcnZlci5vdGhlcm9wLmNvbSIsInN1YiI6ImU4MTQ4NjAzLTg5MzQtNDI0NS04MjViLWMxMDhiOGI2Yjk0NSIsInZlcmlmaWVkX2NsYWltcyI6eyJ2ZXJpZmljYXRpb24iOnsidHJ1c3RfZnJhbWV3b3JrIjoiaWFsX2V4YW1wbGVfZ29sZCJ9LCJjbGFpbXMiOnsiZ2l2ZW5fbmFtZSI6Ik1heCIsImZhbWlseV9uYW1lIjoiTWVpZXIiLCJiaXJ0aGRhdGUiOiIxOTU2LTAxLTI4In19fQ.FPYS2Xjz9y9qEOJhBe5nMfL2mTagLDxwISxjM6gv3zRUvU2YBK-GHI_byvK8h46ly1C90ie-X9gOp-DLvpURvyAlZTsvxNL8s0Hi3-SRZCs5huhiCZr5s4FJBG-l0PNrYOIZAHfeQtobJ7muDld3BytS628140V0CHgh_EM8UUjzQmN8NpDaR9HdH0tIeUFqIZEwBluctgwek9eomg3k10dj6NzBUQSSnpgGf_o6f_sYoIAkBhpgRursD5pHbPSOKTGE9cJ882BbHeido746XLxjEfrU5yQwfA0ggVk5I_e-wv-xVXfVGda4WySZfbkwS5PMCMgMJM9ZT_L1pci0yQ"
        },
        "src2": {
          "endpoint": "https://server.yetanotherop.com/claim_source",
          "access_token": "ksj3n283dkeafb76cdef"
        }
      }
    }

[¶](#section-6.1-14)

The next example shows an ID Token containing `verified_claims` from two different external claim sources along with additional data about the content of the verified claims (look ahead).[¶](#section-6.1-15)

    {
      "iss": "https://server.example.com",
      "sub": "248289761001",
      "email": "janedoe@example.com",
      "email_verified": true,
      "_claim_names": {
        "verified_claims": {
          "src1": {
            "verification": {
              "trust_framework": {
                "value": "trust_framework_example"
              }
            },
            "claims": {
              "given_name": null,
              "family_name": null
            }
          },
          "src2": {
            "verification": {
              "trust_framework": {
                "value": "grids_kyb"
              },
              "evidence": [
                {
                  "type": {
                    "value": "document"
                  },
                  "registry": {
                    "country": {
                      "essential": true,
                      "value": "ES"
                    }
                  },
                  "document": {
                    "SKU": {
                      "value": "REX"
                    }
                  }
                }
              ]
            },
            "claims": {
              "given_name": null,
              "family_name": null,
              "email": null,
              "nationalities": null
            }
          }
        }
      },
      "_claim_sources": {
        "src1": {
          "JWT": "eyJraWQiOiJmMDgxZDI5OC1jNTgzLTQ3NDAtYWQ1NC02ZDUzMTljZjhiNWQiLCJhbGciOiJSUzI1NiJ9.ew0KICAgImlzcyI6ICJodHRwczovL3NlcnZlci5leGFtcGxlLmNvbSIsDQogICAic3ViIjogIjE0ODI4OTc2MiIsDQogICAidmVyaWZpZWRfY2xhaW1zIjogew0KICAgICJ2ZXJpZmljYXRpb24iOiB7DQogICAgICAidHJ1c3RfZnJhbWV3b3JrIjogInRydXN0X2ZyYW1ld29ya19leGFtcGxlIg0KICAgIH0sDQogICAgImNsYWltcyI6IHsNCiAgICAgICJnaXZlbl9uYW1lIjogIk1heCIsDQogICAgICAiZmFtaWx5X25hbWUiOiAiTWVpZXIiDQogICAgfQ0KICB9DQp9.jg_qxYfV0M2IU8On1iK9RBY0Cx9u3jRJ0Qzxe19Ol5VLoUTM7Uxbr3E0ZFCASHWpmz9d2g67XKGHQMppnJKX4SnEdphm6MqjnmZ9E0cirALrC016DX5geFy_0QFC8PAnCttcDgyVCyzCcDxUCaHBSRsrDwGjYe5AjgaDL8S-R72lFQHqch6uj9nhiFBtG24_0EsF6msssQ61WyqS6aju0F0PJms8danIfwc5lHyv-zKuDlY-0kw-fVn4274jY-VofElm4mhsrpo-YJhCKlz0O3CV0g9AW_60TQHCmhn6yoosaTbjlQqh5lREpqULz-MQKnD0wRmYLgBZXtzIhxb7ZA"
        },
        "src2": {
          "endpoint": "https://server.yetanotherop.com/claim_source",
          "access_token": "ksj3n283dkeafb76cdef"
        }
      }
    }

[¶](#section-6.1-16)

Claim sources should sign the assertions containing `verified_claims` in order to demonstrate authenticity and provide for non-repudiation. RP should determine the key material used for validation of the signed assertions is via the claim source\'s public keys. These keys should be available in the JSON web key set available in the `jwks_uri` metadata value in the `openid-configuration` metadata document. This document can be discovered using the `iss` claim of the particular JWT.[¶](#section-6.1-17)

The OP can combine aggregated and distributed claims with `verified_claims` provided by itself (see [Appendix C.8](#op_attested_and_external_claims)).[¶](#section-6.1-18)

If `verified_claims` elements are contained in multiple places of a response, e.g., in the ID Token and an embedded aggregated claim, the RP shall preserve the claims source as context of the particular `verified_claims` element.[¶](#section-6.1-19)

Note: Any assertion provided by an OP or AS including aggregated or distributed claims can contain multiple instances of the same end-user claim. It is up to the RP to decide how to process these different instances.[¶](#section-6.1-20)

### [6.2.](#section-6.2) [Aggregated and distributed claims validation](#name-aggregated-and-distributed-cl)

Clients shall validate any aggregated and distributed `verified_claims` they wish to rely on in the following manner:[¶](#section-6.2-1)

1.  Ensure that both the `_claim_names` and `_claim_sources` are present in the response.[¶](#section-6.2-2.1)
2.  Ensure that there is a `verified_claims` element present in the `_claim_names` member of the response.[¶](#section-6.2-2.2)
3.  Ensure that the `verified_claims` element contains a value that is one of the following: a. a string that exists as a key name in the `_claim_sources` element of the response. b. a JSON array containing members that all exist as key names in the `_claim_sources` element of the response. c. a JSON object containing elements that all exist as key names in the `_claim_sources` element of the response and each element is formatted with the syntax as defined for requesting `verified_claims`.[¶](#section-6.2-2.3)
4.  Ensure that the `_claim_sources` element is a JSON structured object that has one or more sub-elements.[¶](#section-6.2-2.4)
5.  Ensure that the sub-elements of the `_claim_sources` element have matching values in the `_claim_names` element of the response.[¶](#section-6.2-2.5)

When `verified_claims` are delivered as distributed claims, i.e., when a sub-element of the `_claim_sources` contains the `endpoint` claim, clients shall also:[¶](#section-6.2-3)

1.  Ensure that the `endpoint` element defined in any distributed `_claim_sources` uses the https URI scheme.[¶](#section-6.2-4.1)
2.  Retrieve the distributed claims object from the `endpoint` element defined in any distributed `_claim_sources`.[¶](#section-6.2-4.2)
3.  Ensure that the object returned from the `endpoint` is a JWT as per \[[RFC7519](#RFC7519)\].[¶](#section-6.2-4.3)

When `verified_claims` are delivered as aggregated claims, i.e., when a sub-element of the `_claim_sources` contains the `JWT` claim, clients shall also:[¶](#section-6.2-5)

1.  Ensure that the value in the `JWT` claim is a valid JWT as per \[[RFC7519](#RFC7519)\].[¶](#section-6.2-6.1)

Once the JWT has been delivered either via distributed or aggregated mechanism the client shall:[¶](#section-6.2-7)

1.  Verify the signature of the returned JWT.[¶](#section-6.2-8.1)
2.  Ensure that the JWT includes the `typ`, `iss`, `sub`, and `verified_claims` elements; and that their values are not null or empty.[¶](#section-6.2-8.2)
3.  Ensure that the JWT does not contain either an `exp` claim or an `aud` claim.[¶](#section-6.2-8.3)
4.  Ensure that the value of the `typ` header parameter in the JWT is `provided-claims+jwt`.[¶](#section-6.2-8.4)

## [7.](#section-7) [Requesting verified claims](#name-requesting-verified-claims)

Making a request for verified claims and related verification data can be explicitly requested on the level of individual data elements by utilizing the `claims` parameter as defined in section 5.5 of the OpenID Connect specification \[[OpenID](#OpenID)\].[¶](#section-7-1)

It is also possible to use the `scope` parameter to request one or more specific pre-defined claim sets as defined in section 5.4 of the OpenID Connect specification \[[OpenID](#OpenID)\].[¶](#section-7-2)

Note: The OP shall not provide the RP with any data it did not request. However, the OP may at its discretion omit claims from the response.[¶](#section-7-3)

The example authorize call in this section will use the following unencoded example claims request parameter:[¶](#section-7-4)

    {
        "id_token": {
          "given_name": null,
          "verified_claims": {
            "verification": {
              "trust_framework": null
            },
            "claims": {
              "family_name": null
            }
          }
        }
      }

[¶](#section-7-5)

The following is the non-normative example request that would be sent by the user agent to the authorization server in response to the HTTP 302 redirect from the client initiating the authorization code flow (with line wraps within values for display purposes only):[¶](#section-7-6)

      GET /authorize?
         response_type=code
         &scope=openid%20email
         &client_id=s6BhdRkqt3
         &state=af0ifjsldkj
         &redirect_uri=https%3A%2F%2Fclient.example.org%2Fcb
         &claims=%7B%22id_token%22%3A%20%7B%22
         given_name%22%3A%20null%2C%22
         verified_claims%22%3A%20%7B%22
         verification%22%3A%20%7B%22
         trust_framework%22%3A%20null%7D%2C%22
         claims%22%3A%20%7B%22
         family_name%22%3A%20null%7D%7D%7D%7D HTTP/1.1
      Host: server.example.com

[¶](#section-7-7)

## [8.](#section-8) [OP metadata](#name-op-metadata)

The OP advertises its capabilities with respect to verified claims in its openid-configuration (see \[[OpenID-Discovery](#OpenID-Discovery)\]) using the following new elements:[¶](#section-8-1)

`trust_frameworks_supported`: Required. JSON array containing all supported trust frameworks. This array shall have at least one member.[¶](#section-8-2)

`claims_in_verified_claims_supported`: Required. JSON array containing all claims supported within `verified_claims`. claims that are not present in this array shall not be returned within the `verified_claims` object. This array shall have at least one member.[¶](#section-8-3)

`evidence_supported`: Required when one or more type of evidence is supported. JSON array containing all types of identity evidence the OP uses. This array shall have at least one member. Members of this array should only be the types of evidence supported by the OP in the `evidence` element (see section 5.4.4 of \[[IDA-verified-claims](#IDA-verified-claims)\]).[¶](#section-8-4)

`documents_supported`: Required when `evidence_supported` contains \"document\". JSON array containing all identity document types utilized by the OP for identity verification. This array shall have at least one member.[¶](#section-8-5)

`documents_methods_supported`: Optional. JSON array containing the verification methods the OP supports for evidences of type \"document\" (see \[[predefined_values_page](#predefined_values_page)\]). When present this array shall have at least one member.[¶](#section-8-6)

`documents_check_methods_supported`: Optional. JSON array containing the check methods the OP supports for evidences of type \"document\" (see \[[predefined_values_page](#predefined_values_page)\]). When present this array shall have at least one member.[¶](#section-8-7)

`electronic_records_supported`: Required when `evidence_supported` contains \"electronic_record\". JSON array containing all electronic record types the OP supports (see \[[predefined_values_page](#predefined_values_page)\]). When present this array shall have at least one member.[¶](#section-8-8)

This is an example openid-configuration snippet:[¶](#section-8-9)

    {
    ...
       "trust_frameworks_supported":[
         "nist_800_63A"
       ],
       "evidence_supported": [
          "document",
          "electronic_record",
          "vouch",
          "electronic_signature"
       ],
       "documents_supported": [
           "idcard",
           "passport",
           "driving_permit"
       ],
       "documents_methods_supported": [
           "pipp",
           "sripp",
           "eid"
       ],
       "electronic_records_supported": [
           "secure_mail"
       ],
       "claims_in_verified_claims_supported": [
          "given_name",
          "family_name",
          "birthdate",
          "place_of_birth",
          "nationalities",
          "address"
       ],
    ...
    }

[¶](#section-8-10)

If the OP supports the `claims` parameter as defined in section 5.5 of the OpenID Connect specification \[[OpenID](#OpenID)\], the OP shall advertise this in its OP metadata using the `claims_parameter_supported` element.[¶](#section-8-11)

If the OP supports distributed and/or aggregated claim types, as defined in section 5.6.2 of the OpenID Connect specification \[[OpenID](#OpenID)\], in `verified_claims`, the OP shall advertise this in its metadata using the `claim_types_supported` element.[¶](#section-8-12)

## [9.](#section-9) [Privacy consideration](#name-privacy-consideration)

The use of scopes is a potential shortcut to request a pre-defined set of claims, however, the use of scopes might result in more data being returned to the RP than is strictly necessary and not achieving the goal of data minimization. The RP should only request end-user claims and metadata it requires.[¶](#section-9-1)

Timestamps with a time zone component can potentially reveal the person's location. To preserve the person's privacy, timestamps within the verification element and verified claims that represent times should be represented in Coordinated Universal Time (UTC), unless there is a specific reason to include the time zone, such as the time zone being an essential part of a consented time related claim in verified data.[¶](#section-9-2)

## [10.](#section-10) [Security considerations](#name-security-considerations)

### [10.1.](#section-10.1) [Security profile](#name-security-profile)

This document focuses on mechanisms to carry end-user claims and accompanying metadata in JSON objects and JSON Web Tokens, typically as part of an OpenID Connect protocol exchange. Since such an exchange is supposed to take place in security sensitive use cases, implementers shall:[¶](#section-10.1-1)

-   combine this document with an appropriate security profile for OpenID Connect, and[¶](#section-10.1-2.1)
-   ensure end-users are authenticated using appropriately strong authentication methods.[¶](#section-10.1-2.2)

This document does not define or require a particular security profile since there are several security profiles and new security profiles under development. Implementers have the flexibility to select the security profile that best suits their needs. Implementers might consider \[[FAPI-1-SP](#FAPI-1-SP)\] or \[[FAPI-2-SP](#FAPI-2-SP)\].[¶](#section-10.1-3)

Implementers should select a security profile that has a certification program or other resources that allow both OpenID providers and relying parties to ensure they have complied with the profile's security and interoperability requirements, such as the OpenID Foundation Certification Program, <https://openid.net/certification/>.[¶](#section-10.1-4)

Receiving parties shall ensure the integrity and authenticity of the issued assertions in order to prevent identity spoofing.[¶](#section-10.1-5)

Receiving parties shall ensure the confidentiality of all end-user data exchanged between the protocol parties using suitable methods at transport or application layer.[¶](#section-10.1-6)

### [10.2.](#section-10.2) [End-user authentication](#name-end-user-authentication)

Secure identification of end-users not only depends on the identity verification at the OP but also on the strength of the user authentication at the OP. Combining a strong identification with weak authentication creates a false impression of security while being open to attacks. For example if an OP uses a simple PIN login, an attacker could guess the PIN of another user and identify himself as the other user at an RP with a high identity assurance level. To prevent this kind of attack, RPs should request the OP to authenticate the user at a reasonable level, typically using multi-factor authentication, when requesting verified end-user claims. OpenID Connect supports this by way of the `acr_values` request parameter.[¶](#section-10.2-1)

## [11.](#section-11) [Implementation and interoperability](#name-implementation-and-interope)

To achieve the full security and interoperability benefits, it is important the implementation of this document, and the underlying OpenID Connect and OAuth specifications, and selected security profile, are complete and correct. The OpenID Foundation provides tools that should be used to confirm that deployments behave as described in the specifications, with information available at: <https://openid.net/certification/>.[¶](#section-11-1)

## [12.](#section-12) [Predefined values](#name-predefined-values)

This document focuses on the technical mechanisms to convey verified claims and thus does not define any identifiers for trust frameworks, documents, methods, check methods. This is left to adopters of the technical specification, e.g., implementers, identity schemes, or jurisdictions.[¶](#section-12-1)

Each party defining such identifiers shall ensure the collision resistance of these identifiers. This is achieved by including a domain name under the control of this party into the identifier name, e.g., `https://mycompany.com/identifiers/cool_check_method`.[¶](#section-12-2)

The eKYC and Identity Assurance Working Group maintains a wiki page \[[predefined_values_page](#predefined_values_page)\] that can be utilized to share predefined values with other parties.[¶](#section-12-3)

## [13.](#section-13) [Normative References](#name-normative-references-2)

\[IDA-verified-claims\]
:   Lodderstedt, T., Fett, D., Haine, M., Pulido, A., Lehmann, K., and K. Koiwai, \"OpenID Identity Assurance Schema Definition\", 9 August 2023, \<<https://openid.net/specs/openid-ida-verified-claims-1_0.html>\>.
:   

\[ISODIR2\]
:   ISO/IEC, \"ISO/IEC Directives, Part 2 - Principles and rules for the structure and drafting of ISO and IEC documents\", \<<https://www.iso.org/sites/directives/current/part2/index.xhtml>\>.
:   

\[OpenID\]
:   Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, \"OpenID Connect Core 1.0 incorporating errata set 2\", 8 November 2014, \<<https://openid.net/specs/openid-connect-core-1_0.html>\>.
:   

\[OpenID-Discovery\]
:   Sakimura, N., Bradley, J., Jones, M., and E. Jay, \"OpenID Connect Discovery 1.0 incorporating errata set 2\", 8 November 2014, \<<https://openid.net/specs/openid-connect-discovery-1_0.html>\>.
:   

\[OpenID4IDAClaims\]
:   Lodderstedt, T., Fett, D., Haine, M., Pulido, A., Lehmann, K., and K. Koiwai, \"OpenID Connect for Identity Assurance Claims Registration 1.0\", 16 June 2023, \<<https://openid.net/specs/openid-connect-4-ida-claims-1_0.html>\>.
:   

\[predefined_values_page\]
:   OpenID Foundation, \"Overview page for predefined values\", 2021, \<<https://openid.net/wg/ekyc-ida/identifiers/>\>.
:   

## [14.](#section-14) [Informative References](#name-informative-references)

\[FAPI-1-SP\]
:   Sakimura, N., Bradley, J., and E. Jay, \"Financial-grade API (FAPI) Security Profile 1.0 - Part 2: Advanced\", 12 March 2021, \<<https://openid.net/specs/openid-financial-api-part-2-1_0.html>\>.
:   

\[FAPI-2-SP\]
:   Fett, D., Tonge, D., and J. Heenan, \"FAPI 2.0 Security Profile - draft\", 3 April 2024, \<<https://openid.bitbucket.io/fapi/fapi-2_0-security-profile.html>\>.
:   

\[IANA.MediaTypes\]
:   IANA, \"Media Types\", \<<https://www.iana.org/assignments/media-types>\>.
:   

\[RFC2046\]
:   Freed, N. and N. Borenstein, \"Multipurpose Internet Mail Extensions (MIME) Part Two: Media Types\", RFC 2046, DOI 10.17487/RFC2046, November 1996, \<<https://www.rfc-editor.org/info/rfc2046>\>.
:   

\[RFC6838\]
:   Freed, N., Klensin, J., and T. Hansen, \"Media Type Specifications and Registration Procedures\", BCP 13, RFC 6838, DOI 10.17487/RFC6838, January 2013, \<<https://www.rfc-editor.org/info/rfc6838>\>.
:   

\[RFC7519\]
:   Jones, M., Bradley, J., and N. Sakimura, \"JSON Web Token (JWT)\", RFC 7519, DOI 10.17487/RFC7519, May 2015, \<<https://www.rfc-editor.org/info/rfc7519>\>.
:   

\[verified_claims_request.json\]
:   OpenID Foundation, \"JSON Schema for requesting verified_claims\", 2020, \<<https://openid.net/wg/ekyc-ida/references/>\>.
:   

## [Appendix A.](#appendix-A) [IANA considerations](#name-iana-considerations)

### [A.1.](#appendix-A.1) [Media type registration](#name-media-type-registration)

This section registers the `application/provided-claims+jwt` media type \[[RFC2046](#RFC2046)\] in the IANA \"Media Types\" registry \[[IANA.MediaTypes](#IANA.MediaTypes)\] in the manner described in \[[RFC6838](#RFC6838)\], which is used to indicate that the content is a JWT describing aggregated claims.[¶](#appendix-A.1-1)

-   Type name: application[¶](#appendix-A.1-2.1)

-   Subtype name: provided-claims+jwt[¶](#appendix-A.1-2.2)

-   Required parameters: n/a[¶](#appendix-A.1-2.3)

-   Optional parameters: n/a[¶](#appendix-A.1-2.4)

-   Encoding considerations: binary; An external claims JWT is a JWT; JWT values are encoded as a series of base64url-encoded values (some of which may be the empty string) separated by period (\'.\') characters.[¶](#appendix-A.1-2.5)

-   Security considerations: See <https://openid.net/specs/openid-connect-4-identity-assurance-1_0.html#name-representing-verified-claim>[¶](#appendix-A.1-2.6)

-   Interoperability considerations: n/a[¶](#appendix-A.1-2.7)

-   Published specification: [Section 5.2](#verified_claims_delivery) of \[\[ this specification \]\][¶](#appendix-A.1-2.8)

-   Applications that use this media type: When using \[\[ this specification \]\], this media type is used in the `typ` header of assertions provided as aggregated or distributed claims (see section 5.6.2 of the OpenID Connect specification \[[OpenID](#OpenID)\]).[¶](#appendix-A.1-2.9)

-   Fragment identifier considerations: n/a[¶](#appendix-A.1-2.10)

-   Additional information:[¶](#appendix-A.1-2.11.1)

    -   File extension(s): n/a[¶](#appendix-A.1-2.11.2.1)
    -   Macintosh file type code(s): n/a[¶](#appendix-A.1-2.11.2.2)

-   Person & email address to contact for further information: Daniel Fett, mail@danielfett.de[¶](#appendix-A.1-2.12)

-   Intended usage: COMMON[¶](#appendix-A.1-2.13)

-   Restrictions on usage: none[¶](#appendix-A.1-2.14)

-   Author: Daniel Fett, mail@danielfett.de[¶](#appendix-A.1-2.15)

-   Change controller: IETF[¶](#appendix-A.1-2.16)

-   Provisional registration? No[¶](#appendix-A.1-2.17)

## [Appendix B.](#appendix-B) [Example requests](#name-example-requests)

This section shows examples of requests for `verified_claims`.[¶](#appendix-B-1)

### [B.1.](#appendix-B.1) [Verification of claims by a document](#name-verification-of-claims-by-a)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": null,
            "time": null,
            "evidence": [
              {
                "type": {
                  "value": "document"
                },
                "method": null,
                "document_details": {
                  "type": null
                }
              }
            ]
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#appendix-B.1-1)

Note that, as shown in the above example, this document requires that implementations receiving requests are able to distinguish between JSON objects where a key is not present versus where a key is present with a null value.[¶](#appendix-B.1-2)

Support for these null value requests is mandatory for identity providers, so implementers are encouraged to test this behaviour early in their development process. In some programming languages many JSON libraries or HTTP frameworks will, at least by default, ignore null values and omit the relevant key when parsing the JSON.[¶](#appendix-B.1-3)

### [B.2.](#appendix-B.2) [Verification of claims by trust framework and evidence types](#name-verification-of-claims-by-t)

    {
      "userinfo": {
        "verified_claims": [
          {
            "verification": {
              "trust_framework": {
                "value": "gold"
              },
              "evidence": [
                {
                  "type": {
                    "value": "document"
                  }
                }
              ]
            },
            "claims": {
              "given_name": null,
              "family_name": null
            }
          },
          {
            "verification": {
              "trust_framework": {
                "values": [
                  "silver",
                  "bronze"
                ]
              },
              "evidence": [
                {
                  "type": {
                    "value": "vouch"
                  }
                }
              ]
            },
            "claims": {
              "given_name": null,
              "family_name": null
            }
          }
        ]
      }
    }

[¶](#appendix-B.2-1)

### [B.3.](#appendix-B.3) [Verification of claims by trust framework and check method](#name-verification-of-claims-by-tr)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": {
              "value": "it_spid"
            },
            "time": null,
            "evidence": [
              {
                "type": {
                  "value": "document"
                },
                "check_details": [
                  {
                    "check_method": {
                      "value": "bvr"
                    }
                  }
                ],
                "document_details": {
                  "type": null,
                  "issuer": {
                    "country": null,
                    "name": null
                  },
                  "document_number": null,
                  "date_of_issuance": null
                }
              }
            ]
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#appendix-B.3-1)

### [B.4.](#appendix-B.4) [Verification of claims by electronic signature](#name-verification-of-claims-by-e)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": null,
            "time": null,
            "evidence": [
              {
                "type": {
                  "value": "electronic_signature"
                },
                "issuer": null,
                "serial_number": null,
                "created_at": null
              }
            ]
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#appendix-B.4-1)

## [Appendix C.](#appendix-C) [Example responses](#name-example-responses)

This section shows examples of responses containing `verified_claims`.[¶](#appendix-C-1)

The first and second subsections show JSON snippets of the general identity assurance case, where the RP is provided with verification evidence for different methods along with the actual claims about the end-user.[¶](#appendix-C-2)

The third subsection illustrates the possible contents of this object in case of a notified eID system under eIDAS, where the OP does not need to provide evidence of the identity verification process to the RP.[¶](#appendix-C-3)

Subsequent subsections contain examples for using the `verified_claims` claim on different channels and in combination with other (unverified) claims.[¶](#appendix-C-4)

### [C.1.](#appendix-C.1) [Document](#name-document)

    {
      "verified_claims": {
        "verification": {
          "trust_framework": "nist_800_63A",
          "assurance_level": "ial2",
          "assurance_process": {
            "assurance_details": [
              {
                "assurance_type": "evidence_validation",
                "assurance_classification": "strong",
                "evidence_ref": [
                  {
                    "check_id": "DL1-93h506th2f45hf"
                  }
                ]
              },
              {
                "assurance_type": "verification",
                "assurance_classification": "strong",
                "evidence_ref": [
                  {
                    "check_id": "v-93jfk284ugjfj2093"
                  }
                ]
              }
            ]
          },
          "time": "2021-06-06T05:32Z",
          "verification_process": "7675D80F-57E0-AB14-9543-26B41FC22",
          "evidence": [
            {
              "type": "document",
              "check_details": [
                {
                  "check_method": "vpiruv",
                  "organization": "doc_checker",
                  "check_id": "DL1-93h506th2f45hf"
                },
                {
                  "check_method": "pvp",
                  "organization": "face_checker",
                  "check_id": "v-93jfk284ugjfj2093"
                }
              ],
              "time": "2021-06-06T05:33Z",
              "document_details": {
                "type": "driving_permit",
                "document_number": "I1234568",
                "date_of_issuance": "2019-09-05",
                "date_of_expiry": "2024-08-01",
                "issuer": {
                    "name": "CA DMV",
                    "country": "US",
                    "country_code": "USA",
                    "jurisdiction": "CA"
                }
              }
            }
          ]
        },
        "claims": {
          "given_name": "Inga",
          "family_name": "Silverstone",
          "birthdate": "1991-11-06",
          "place_of_birth": {
            "country": "USA"
          },
          "address": {
            "locality": "Shoshone",
            "postal_code": "CA 92384",
            "country": "USA",
            "street_address": "114 Old State Hwy 127"
          }
        }
      }
    }

[¶](#appendix-C.1-1)

Same document under a different `trust_framework`[¶](#appendix-C.1-2)

    {
      "verified_claims": {
        "verification": {
          "trust_framework": "uk_diatf",
          "assurance_level": "medium",
          "assurance_process": {
            "policy": "gpg45",
            "procedure": "m1c",
            "assurance_details": [
              {
                "assurance_type": "evidence_validation",
                "assurance_classification": "score_3",
                "evidence_ref": [
                  {
                    "check_id": "DL1-93h506th2f45hf",
                    "evidence_metadata": {
                      "evidence_classification": "score_3_strength"
                    }
                  }
                ]
              },
              {
                "assurance_type": "verification",
                "assurance_classification": "score_3",
                "evidence_ref": [
                  {
                    "check_id": "v-93jfk284ugjfj2093"
                  }
                ]
              }
            ]
          },
          "time": "2021-06-06T05:32Z",
          "verification_process": "7675D80F-57E0-AB14-9543-26B41FC22",
          "evidence": [
            {
              "type": "document",
              "check_details": [
                {
                  "check_method": "vpiruv",
                  "organization": "doc_checker",
                  "check_id": "DL1-93h506th2f45hf",
                  "time": "2021-06-08T11:41Z"
                },
                {
                  "check_method": "pvp",
                  "organization": "face_checker",
                  "check_id": "v-93jfk284ugjfj2093",
                  "time": "2021-06-08T11:42Z"
                }
              ],
              "time": "2021-06-06T05:33Z",
              "document_details": {
                "type": "driving_permit",
                "document_number": "I1234568",
                "date_of_issuance": "2019-09-05",
                "date_of_expiry": "2024-08-01",
                "issuer": {
                    "name": "CA DMV",
                    "country": "US",
                    "country_code": "USA",
                    "jurisdiction": "CA"
                }
              }
            }
          ]
        },
        "claims": {
          "given_name": "Inga",
          "family_name": "Silverstone",
          "birthdate": "1991-11-06",
          "place_of_birth": {
            "country": "USA"
          },
          "address": {
            "locality": "Shoshone",
            "postal_code": "CA 92384",
            "country": "USA",
            "street_address": "114 Old State Hwy 127"
          }
        }
      }
    }

[¶](#appendix-C.1-3)

### [C.2.](#appendix-C.2) [Document and verifier details](#name-document-and-verifier-detai)

    {
      "verified_claims": {
        "verification": {
          "trust_framework": "de_aml",
          "time": "2012-04-23T18:25Z",
          "verification_process": "f24c6f-6d3f-4ec5-973e-b0d8506f3bc7",
          "evidence": [
            {
              "type": "document",
              "method": "pipp",
              "organization": "Deutsche Post",
              "check_id": "1aa05779-0775-470f-a5c4-9f1f5e56cf06",
              "time": "2012-04-22T11:30Z",
              "document_details": {
                "type": "idcard",
                "issuer": {
                  "name": "Stadt Augsburg",
                  "country": "DE"
                },
                "document_number": "53554554",
                "date_of_issuance": "2010-03-23",
                "date_of_expiry": "2020-03-22"
              }
            }
          ]
        },
        "claims": {
          "given_name": "Max",
          "family_name": "Meier",
          "birthdate": "1956-01-28",
          "place_of_birth": {
            "country": "DE",
            "locality": "Musterstadt"
          },
          "nationalities": [
            "DE"
          ],
          "address": {
            "locality": "Maxstadt",
            "postal_code": "12344",
            "country": "DE",
            "street_address": "An der Weide 22"
          }
        }
      }
    }

[¶](#appendix-C.2-1)

### [C.3.](#appendix-C.3) [Evidence with all assurance details](#name-evidence-with-all-assurance)

    {
      "verified_claims": {
        "verification": {
          "trust_framework": "uk_diatf",
          "assurance_level": "medium",
          "assurance_process": {
            "policy": "gpg45",
            "procedure": "m1b",
            "assurance_details": [
              {
                "assurance_type": "evidence_validation",
                "assurance_classification": "score_2",
                "evidence_ref": [
                  {
                    "check_id": "DL1-85762937582385820",
                    "evidence_metadata": {
                      "evidence_classification": "score_3_strength"
                    }
                  }
                ]
              },
              {
                "assurance_type": "verification",
                "assurance_classification": "score_2",
                "evidence_ref": [
                  {
                    "check_id": "kbv1-hf934hn09234ng03jj3",
                    "evidence_metadata": {
                      "evidence_classification": "high_kbv"
                    }
                  },
                  {
                    "check_id": "kbv2-nm0f23u9459fj38u5j6",
                    "evidence_metadata": {
                      "evidence_classification": "medium_kbv"
                    }
                  },
                  {
                    "check_id": "kbv3-jf9028h023hj0f9jh23",
                    "evidence_metadata": {
                      "evidence_classification": "medium_kbv"
                    }
                  }
                ]
              },
              {
                "assurance_type": "counter_fraud",
                "assurance_classification": "score_2",
                "evidence_ref": [
                  {
                    "check_id": "GRO-9824hngvp9278hf5tmp924y5h",
                    "evidence_metadata": {
                      "evidence_classification": "mortality_check"
                    }
                  },
                  {
                    "check_id": "fi-2nbf02hfn384ufn",
                    "evidence_metadata": {
                      "evidence_classification": "id_fraud"
                    }
                  }
                ]
              }
            ]
          },
          "time": "2021-05-11T14:29Z",
          "verification_process": "7675D80F-57E0-AB14-9543-26B41FC22",
          "evidence": [
            {
              "type": "document",
              "check_details": [
                {
                  "check_method": "data",
                  "organization": "DVLA",
                  "time": "2021-04-09T14:15Z",
                  "check_id": "DL1-85762937582385820"
                }
              ],
              "time": "2021-04-09T14:12Z",
              "document_details": {
                "type": "driving_permit",
                "personal_number": "MORGA753116SM9IJ",
                "document_number": "MORGA753116SM9IJ35",
                "serial_number": "ZG21000001",
                "date_of_issuance": "2021-01-01",
                "date_of_expiry": "2030-12-31",
                "issuer": {
                  "name": "DVLA",
                  "country": "UK",
                  "country_code": "GBR",
                  "jurisdiction": "GB-GBN"
                }
              }
            },
            {
              "type": "electronic_record",
              "check_details": [
                {
                  "check_method": "kbv",
                  "organization": "TheCreditBureau",
                  "check_id": "kbv1-hf934hn09234ng03jj3"
                }
              ],
              "time": "2021-04-09T14:12Z",
              "record": {
                "type": "mortgage_account",
                "source": {
                  "name": "TheCreditBureau"
                }
              }
            },
            {
              "type": "electronic_record",
              "check_details": [
                {
                  "check_method": "kbv",
                  "organization": "OpenBankingTPP",
                  "check_id": "kbv2-nm0f23u9459fj38u5j6"
                }
              ],
              "time": "2021-04-09T14:12Z",
              "record": {
                "type": "bank_account",
                "source": {
                  "name": "TheBank"
                }
              }
            },
            {
              "type": "electronic_record",
              "check_details": [
                {
                  "check_method": "kbv",
                  "organization": "GSMA",
                  "check_id": "kbv3-jf9028h023hj0f9jh23"
                }
              ],
              "time": "2021-04-09T15:42Z",
              "record": {
                "type": "mno",
                "source": {
                  "name": "Vodafone"
                }
              }
            },
            {
              "type": "electronic_record",
              "check_details": [
                {
                  "check_method": "data",
                  "organization": "GRO",
                  "check_id": "GRO-9824hngvp9278hf5tmp924y5h"
                }
              ],
              "time": "2021-04-09T16:12Z",
              "record": {
                "type": "death_register",
                "source": {
                  "name": "General Register Office",
                  "street_address": "PO BOX 2",
                  "locality": "Southport",
                  "postal_code": "PR8 2JD",
                  "country": "UK",
                  "country_code": "GBR",
                  "jurisdiction": "GB-EAW"
                }
              }
            },
            {
              "type": "electronic_record",
              "check_details": [
                {
                  "check_method": "data",
                  "organization": "NextLex",
                  "check_id": "fi-2nbf02hfn384ufn"
                }
              ],
              "time": "2021-04-09T16:51Z",
              "record": {
                "type": "fraud_register",
                "source": {
                  "name": "National Fraud Database",
                  "jurisdiction": "UK"
                }
              }
            }
          ]
        },
        "claims": {
          "given_name": "Sarah",
          "family_name": "Meredyth",
          "birthdate": "1976-03-11",
          "place_of_birth": {
            "country": "UK"
          },
          "address": {
            "locality": "Edinburgh",
            "postal_code": "EH1 9GP",
            "country": "UK",
            "street_address": "122 Burns Crescent"
          }
        }
      }
    }

[¶](#appendix-C.3-1)

### [C.4.](#appendix-C.4) [Notified eID system (eIDAS)](#name-notified-eid-system-eidas)

    {
      "verified_claims": {
        "verification": {
          "trust_framework": "eidas",
          "assurance_level": "substantial"
        },
        "claims": {
          "given_name": "Max",
          "family_name": "Meier",
          "birthdate": "1956-01-28",
          "place_of_birth": {
            "country": "DE",
            "locality": "Musterstadt"
          },
          "nationalities": [
            "DE"
          ]
        }
      }
    }

[¶](#appendix-C.4-1)

### [C.5.](#appendix-C.5) [Electronic_record](#name-electronic_record)

    {
      "verified_claims": {
        "verification": {
          "trust_framework": "se_bankid",
          "assurance_level": "al_2",
          "time": "2021-03-03T09:42Z",
          "verification_process": "4346D80F-57E0-4E26-9543-26B41FC22",
          "evidence": [
            {
              "type": "electronic_record",
              "check_details": [
                {
                  "check_method": "data"
                },
                {
                  "check_method": "token"
                }
              ],
              "time": "2021-02-15T16:51Z",
              "record": {
                "type": "population_register",
                "source": {
                    "name": "Skatteverket",
                    "country": "Sverige",
                    "country_code": "SWE"
                },
                "personal_number": "4901224131",
                "created_at": "1979-01-22"
              }
            }
          ]
        },
        "claims": {
          "given_name": "Fredrik",
          "family_name": "Strömberg",
          "birthdate": "1979-01-22",
          "place_of_birth": {
            "country": "SWE",
            "locality": "Örnsköldsvik"
          },
          "nationalities": [
            "SE"
          ],
          "address": {
            "locality": "Karlstad",
            "postal_code": "65344",
            "country": "SWE",
            "street_address": "Gatunamn 221b"
          }
        }
      }
    }

[¶](#appendix-C.5-1)

### [C.6.](#appendix-C.6) [Vouch](#name-vouch)

    {
      "verified_claims": {
        "verification": {
          "trust_framework": "uk_diatf",
          "assurance_level": "very_high",
          "time": "2020-03-19T13:05Z",
          "verification_process": "76755DA2-81E1-5N14-9543-26B415B77",
          "evidence": [
            {
              "type": "vouch",
              "check_details": [
                {
                  "check_method": "vcrypt"
                },
                {
                  "check_method": "bvr"
                }
              ],
              "time": "2020-03-19T12:42Z",
              "attestation": {
                "type": "digital_attestation",
                "reference_number": "6485-1619-3976-6671",
                "date_of_issuance": "2021-06-04",
                "voucher": {
                    "organization": "HMP Dartmoor"
                }
              }
            }
          ]
        },
        "claims": {
          "given_name": "Sam",
          "family_name": "Lawler",
          "birthdate": "1981-04-13",
          "place_of_birth": {
            "country": "GBR"
          },
          "address": {
            "postal_code": "98015",
            "country": "Monaco"
          }
        }
      }
    }

[¶](#appendix-C.6-1)

### [C.7.](#appendix-C.7) [Multiple verified claims](#name-multiple-verified-claims)

    {
      "verified_claims": [
        {
          "verification": {
            "trust_framework": "eidas",
            "assurance_level": "substantial"
          },
          "claims": {
            "given_name": "Max",
            "family_name": "Meier",
            "birthdate": "1956-01-28",
            "place_of_birth": {
              "country": "DE",
              "locality": "Musterstadt"
            },
            "nationalities": [
              "DE"
            ]
          }
        },
        {
          "verification": {
            "trust_framework": "de_aml",
            "time": "2012-04-23T18:25Z",
            "verification_process": "f24c6f-6d3f-4ec5-973e-b0d8506f3bc7",
            "evidence": [
              {
                "type": "document",
                "method": "pipp",
                "time": "2012-04-22T11:30Z",
                "document": {
                  "type": "idcard"
                }
              }
            ]
          },
          "claims": {
            "address": {
              "locality": "Maxstadt",
              "postal_code": "12344",
              "country": "DE",
              "street_address": "An der Weide 22"
            }
          }
        }
      ]
    }

[¶](#appendix-C.7-1)

### [C.8.](#appendix-C.8) [Claims provided by the OP and external sources](#name-claims-provided-by-the-op-a)

This example shows how an OP can mix own claims and claims provided by external sources in a single ID Token.[¶](#appendix-C.8-1)

    {
      "iss": "https://server.example.com",
      "sub": "248289761001",
      "email": "janedoe@example.com",
      "email_verified": true,
      "verified_claims": {
        "verification": {
          "trust_framework": "trust_framework_example"
        },
        "claims": {
          "given_name": "Max",
          "family_name": "Meier"
        }
      },
      "_claim_names": {
        "verified_claims": [
          "src1",
          "src2"
        ]
      },
      "_claim_sources": {
        "src1": {
          "JWT": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3NlcnZlci5vdGhlcm9wLmNvbSIsInN1YiI6ImU4MTQ4NjAzLTg5MzQtNDI0NS04MjViLWMxMDhiOGI2Yjk0NSIsInZlcmlmaWVkX2NsYWltcyI6eyJ2ZXJpZmljYXRpb24iOnsidHJ1c3RfZnJhbWV3b3JrIjoiaWFsX2V4YW1wbGVfZ29sZCJ9LCJjbGFpbXMiOnsiZ2l2ZW5fbmFtZSI6Ik1heCIsImZhbWlseV9uYW1lIjoiTWVpZXIiLCJiaXJ0aGRhdGUiOiIxOTU2LTAxLTI4In19fQ.FArlPUtUVn95HCExePlWJQ6ctVfVpQyeSbe3xkH9MH1QJjnk5GVbBW0qe1b7R3lE-8iVv__0mhRTUI5lcFhLjoGjDS8zgWSarVsEEjwBK7WD3r9cEw6ZAhfEkhHL9eqAaED2rhhDbHD5dZWXkJCuXIcn65g6rryiBanxlXK0ZmcK4fD9HV9MFduk0LRG_p4yocMaFvVkqawat5NV9QQ3ij7UBr3G7A4FojcKEkoJKScdGoozir8m5XD83Sn45_79nCcgWSnCX2QTukL8NywIItu_K48cjHiAGXXSzydDm_ccGCe0sY-Ai2-iFFuQo2PtfuK2SqPPmAZJxEFrFoLY4g"
        },
        "src2": {
          "endpoint": "https://server.yetanotherop.com/claim_source",
          "access_token": "ksj3n283dkeafb76cdef"
        }
      }
    }

[¶](#appendix-C.8-2)

### [C.9.](#appendix-C.9) [Self-Issued OpenID provider and external claims](#name-self-issued-openid-provider)

This example shows how a Self-Issued OpenID provider (SIOP) may include verified claims obtained from different external claim sources into an ID Token.[¶](#appendix-C.9-1)

    {
      "iss": "https://self-issued.me",
      "sub": "248289761001",
      "preferred_username": "superman445",
      "_claim_names": {
        "verified_claims": [
          "src1",
          "src2"
        ]
      },
      "_claim_sources": {
        "src1": {
          "JWT": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3NlcnZlci5vdGhlcm9wLmNvbSIsInN1YiI6ImU4MTQ4NjAzLTg5MzQtNDI0NS04MjViLWMxMDhiOGI2Yjk0NSIsInZlcmlmaWVkX2NsYWltcyI6eyJ2ZXJpZmljYXRpb24iOnsidHJ1c3RfZnJhbWV3b3JrIjoiaWFsX2V4YW1wbGVfZ29sZCJ9LCJjbGFpbXMiOnsiZ2l2ZW5fbmFtZSI6Ik1heCIsImZhbWlseV9uYW1lIjoiTWVpZXIiLCJiaXJ0aGRhdGUiOiIxOTU2LTAxLTI4In19fQ.FArlPUtUVn95HCExePlWJQ6ctVfVpQyeSbe3xkH9MH1QJjnk5GVbBW0qe1b7R3lE-8iVv__0mhRTUI5lcFhLjoGjDS8zgWSarVsEEjwBK7WD3r9cEw6ZAhfEkhHL9eqAaED2rhhDbHD5dZWXkJCuXIcn65g6rryiBanxlXK0ZmcK4fD9HV9MFduk0LRG_p4yocMaFvVkqawat5NV9QQ3ij7UBr3G7A4FojcKEkoJKScdGoozir8m5XD83Sn45_79nCcgWSnCX2QTukL8NywIItu_K48cjHiAGXXSzydDm_ccGCe0sY-Ai2-iFFuQo2PtfuK2SqPPmAZJxEFrFoLY4g"
        },
        "src2": {
          "endpoint": "https://op.mymno.com/claim_source",
          "access_token": "ksj3n283dkeafb76cdef"
        }
      }
    }

[¶](#appendix-C.9-2)

## [Appendix D.](#appendix-D) [Example requests and responses](#name-example-requests-and-respon)

This section shows examples of pairs of requests and responses containing `verified_claims`.[¶](#appendix-D-1)

### [D.1.](#appendix-D.1) [verified claims in UserInfo response](#name-verified-claims-in-userinfo)

#### [D.1.1.](#appendix-D.1.1) [Request](#name-request)

In this example we assume the RP uses the `scope` parameter to request the email address and, additionally, the `claims` parameter, to request verified claims.[¶](#appendix-D.1.1-1)

The scope value is: `scope=openid email`[¶](#appendix-D.1.1-2)

The value of the `claims` parameter is:[¶](#appendix-D.1.1-3)

    {
      "userinfo": {
        "verified_claims": {
          "verification": {
            "trust_framework": null
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#appendix-D.1.1-4)

#### [D.1.2.](#appendix-D.1.2) [Response](#name-response)

The respective UserInfo response would be[¶](#appendix-D.1.2-1)

    {
      "sub": "248289761001",
      "email": "janedoe@example.com",
      "email_verified": true,
      "verified_claims": {
        "verification": {
          "trust_framework": "de_aml"
        },
        "claims": {
          "given_name": "Max",
          "family_name": "Meier",
          "birthdate": "1956-01-28"
        }
      }
    }

[¶](#appendix-D.1.2-2)

### [D.2.](#appendix-D.2) [verified claims in ID Tokens](#name-verified-claims-in-id-token)

#### [D.2.1.](#appendix-D.2.1) [Request](#name-request-2)

In this case, the RP requests verified claims along with other claims about the end-user in the `claims` parameter and allocates the response to the ID Token (delivered from the token endpoint in case of grant type `authorization_code`).[¶](#appendix-D.2.1-1)

The `claims` parameter value is[¶](#appendix-D.2.1-2)

    {
      "id_token": {
        "email": null,
        "preferred_username": null,
        "picture": null,
        "verified_claims": {
          "verification": {
            "trust_framework": null,
            "time": null,
            "verification_process": null,
            "evidence": [
              {
                "type": {
                  "value": "document"
                },
                "method": null,
                "time": null,
                "document_details": {
                  "type": null,
                  "issuer": {
                    "name": null,
                    "country": null
                  },
                  "document_number": null,
                  "date_of_issuance": null,
                  "date_of_expiry": null
                }
              }
            ]
          },
          "claims": {
            "given_name": null,
            "family_name": null,
            "birthdate": null
          }
        }
      }
    }

[¶](#appendix-D.2.1-3)

#### [D.2.2.](#appendix-D.2.2) [Response](#name-response-2)

The decoded body of the respective ID Token could be[¶](#appendix-D.2.2-1)

    {
      "iss": "https://server.example.com",
      "sub": "24400320",
      "aud": "s6BhdRkqt3",
      "nonce": "n-0S6_WzA2Mj",
      "exp": 1311281970,
      "iat": 1311280970,
      "auth_time": 1311280969,
      "acr": "urn:mace:incommon:iap:silver",
      "email": "janedoe@example.com",
      "preferred_username": "j.doe",
      "picture": "http://example.com/janedoe/me.jpg",
      "verified_claims": {
        "verification": {
          "trust_framework": "de_aml",
          "time": "2012-04-23T18:25Z",
          "verification_process": "f24c6f-6d3f-4ec5-973e-b0d8506f3bc7",
          "evidence": [
            {
              "type": "document",
              "method": "pipp",
              "time": "2012-04-22T11:30Z",
              "document_details": {
                "type": "idcard",
                "issuer": {
                  "name": "Stadt Augsburg",
                  "country": "DE"
                },
                "document_number": "53554554",
                "date_of_issuance": "2010-03-23",
                "date_of_expiry": "2020-03-22"
              }
            }
          ]
        },
        "claims": {
          "given_name": "Jane",
          "family_name": "Doe",
          "birthdate": "1956-01-28"
        }
      }
    }

[¶](#appendix-D.2.2-2)

## [Appendix E.](#appendix-E) [Acknowledgements](#name-acknowledgements)

The following people at yes.com and partner companies contributed to the concept described in the initial contribution to this document: Karsten Buch, Lukas Stiebig, Sven Manz, Waldemar Zimpfer, Willi Wiedergold, Fabian Hoffmann, Daniel Keijsers, Ralf Wagner, Sebastian Ebling, Peter Eisenhofer.[¶](#appendix-E-1)

We would like to thank Julian White, Bjorn Hjelm, Stephane Mouy, Alberto Pulido, Joseph Heenan, Vladimir Dzhuvinov, Azusa Kikuchi, Naohiro Fujie, Takahiko Kawasaki, Sebastian Ebling, Marcos Sanz, Tom Jones, Mike Pegman, Michael B. Jones, Jeff Lombardo, Taylor Ongaro, Peter Bainbridge-Clayton, Adrian Field, George Fletcher, Tim Cappalli, Michael Palage, Sascha Preibisch, Giuseppe De Marco, Nick Mothershaw, Hodari McClain, Dima Postnikov and Nat Sakimura for their valuable feedback and contributions that helped to evolve this document.[¶](#appendix-E-2)

## [Appendix F.](#appendix-F) [Notices](#name-notices)

Copyright (c) 2024 The OpenID Foundation.[¶](#appendix-F-1)

The OpenID Foundation (OIDF) grants to any Contributor, developer, implementer, or other interested party a non-exclusive, royalty free, worldwide copyright license to reproduce, prepare derivative works from, distribute, perform and display, this Implementers Draft or Final Specification solely for the purposes of (i) developing specifications, and (ii) implementing Implementers Drafts and Final Specifications based on such documents, provided that attribution be made to the OIDF as the source of the material, but that such attribution does not indicate an endorsement by the OIDF.[¶](#appendix-F-2)

The technology described in this document was made available from contributions from various sources, including members of the OpenID Foundation and others. Although the OpenID Foundation has taken steps to help ensure that the technology is available for distribution, it takes no position regarding the validity or scope of any intellectual property or other rights that might be claimed to pertain to the implementation or use of the technology described in this document or the extent to which any license under such rights might or might not be available; neither does it represent that it has made any independent effort to identify any such rights. The OpenID Foundation and the contributors to this document make no (and hereby expressly disclaim any) warranties (express, implied, or otherwise), including implied warranties of merchantability, non-infringement, fitness for a particular purpose, or title, related to this document to offer a patent promise not to assert certain patent claims against other contributors and against implementers. The OpenID Foundation invites any interested party to bring to its attention any copyrights, patents, patent applications, or other proprietary rights that may cover technology that may be required to practice this document.[¶](#appendix-F-3)

## [Authors\' Addresses](#name-authors-addresses)

Torsten Lodderstedt

sprind.org

Email: <torsten@lodderstedt.net>

Daniel Fett

Authlete

Email: <mail@danielfett.de>

Mark Haine

Considrd.Consulting Ltd

Email: <mark@considrd.consulting>

Alberto Pulido

Santander

Email: <alberto.pulido@santander.co.uk>

Kai Lehmann

1&1 Mail & Media Development & Technology GmbH

Email: <kai.lehmann@1und1.de>

Kosuke Koiwai

KDDI Corporation

Email: <ko-koiwai@kddi.com>
