---
description: OpenID Connect Discovery 1.0 incorporating errata set 2
generator: "xml2rfc v1.37pre1 (http://xml.resource.org/)"
lang: en
title: "Final: OpenID Connect Discovery 1.0 incorporating errata set 2"
---

  ---------------
  [ TOC ](#toc)
  ---------------

+-----------------------------------------------------------------------+
|   ------- --------------------------------                            |
|   Final   N. Sakimura                                                 |
|           NAT.Consulting (was at NRI)                                 |
|           J. Bradley                                                  |
|           Yubico (was at Ping Identity)                               |
|           M. Jones                                                    |
|           Self-Issued Consulting (was at                              |
|           Microsoft)                                                  |
|           E. Jay                                                      |
|           Illumila                                                    |
|           December 15, 2023                                           |
|   ------- --------------------------------                            |
+-----------------------------------------------------------------------+

#  OpenID Connect Discovery 1.0 incorporating errata set 2

### Abstract

OpenID Connect 1.0 is a simple identity layer on top of the OAuth 2.0 protocol. It enables Clients to verify the identity of the End-User based on the authentication performed by an Authorization Server, as well as to obtain basic profile information about the End-User in an interoperable and REST-like manner.

This specification defines a mechanism for an OpenID Connect Relying Party to discover the End-User\'s OpenID Provider and obtain information needed to interact with it, including its OAuth 2.0 endpoint locations.

\

------------------------------------------------------------------------

### Table of Contents

[1.](#Introduction)  Introduction\
    [1.1.](#rnc)  Requirements Notation and Conventions\
    [1.2.](#Terminology)  Terminology\
[2.](#IssuerDiscovery)  OpenID Provider Issuer Discovery\
    [2.1.](#IdentifierNormalization)  Identifier Normalization\
        [2.1.1.](#IdentifierTypes)  User Input Identifier Types\
        [2.1.2.](#NormalizationSteps)  Normalization Steps\
    [2.2.](#Examples)  Non-Normative Examples\
        [2.2.1.](#EmailSyntax)  User Input using E-Mail Address Syntax\
        [2.2.2.](#URLSyntax)  User Input using URL Syntax\
        [2.2.3.](#HostPortExample)  User Input using Hostname and Port Syntax\
        [2.2.4.](#AcctURISyntax)  User Input using \"acct\" URI Syntax\
[3.](#ProviderMetadata)  OpenID Provider Metadata\
[4.](#ProviderConfig)  Obtaining OpenID Provider Configuration Information\
    [4.1.](#ProviderConfigurationRequest)  OpenID Provider Configuration Request\
    [4.2.](#ProviderConfigurationResponse)  OpenID Provider Configuration Response\
    [4.3.](#ProviderConfigurationValidation)  OpenID Provider Configuration Validation\
[5.](#StringOps)  String Operations\
[6.](#ImplementationConsiderations)  Implementation Considerations\
    [6.1.](#CompatibilityNotes)  Compatibility Notes\
[7.](#Security)  Security Considerations\
    [7.1.](#TLSRequirements)  TLS Requirements\
    [7.2.](#Impersonation)  Impersonation Attacks\
[8.](#IANA)  IANA Considerations\
    [8.1.](#WellKnownRegistry)  Well-Known URI Registry\
        [8.1.1.](#WellKnownContents)  Registry Contents\
    [8.2.](#MetadataRegistry)  OAuth Authorization Server Metadata Registry\
        [8.2.1.](#MetadataContents)  Registry Contents\
[9.](#rfc.references1)  References\
    [9.1.](#rfc.references1)  Normative References\
    [9.2.](#rfc.references2)  Informative References\
[Appendix A.](#Acknowledgements)  Acknowledgements\
[Appendix B.](#Notices)  Notices\
[§](#rfc.authors)  Authors\' Addresses\

\
\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 1.  Introduction

OpenID Connect 1.0 is a simple identity layer on top of the OAuth 2.0 [\[RFC6749\] (Hardt, D., Ed., "The OAuth 2.0 Authorization Framework," October 2012.)](#RFC6749) protocol. It enables Clients to verify the identity of the End-User based on the authentication performed by an Authorization Server, as well as to obtain basic profile information about the End-User in an interoperable and REST-like manner.

In order for an OpenID Connect Relying Party to utilize OpenID Connect services for an End-User, the RP needs to know where the OpenID Provider is. OpenID Connect uses [WebFinger (Jones, P., Salgueiro, G., Jones, M., and J. Smarr, "WebFinger," September 2013.)](#RFC7033) \[RFC7033\] to locate the OpenID Provider for an End-User. This process is described in [Section 2 (OpenID Provider Issuer Discovery)](#IssuerDiscovery).

Once the OpenID Provider has been identified, the configuration information for that OP is retrieved from a well-known location as a JSON [\[RFC8259\] (Bray, T., Ed., "The JavaScript Object Notation (JSON) Data Interchange Format," December 2017.)](#RFC8259) document, including its OAuth 2.0 endpoint locations. This process is described in [Section 4 (Obtaining OpenID Provider Configuration Information)](#ProviderConfig).

The previous versions of this specification are:

-   [OpenID Connect Discovery 1.0 incorporating errata set 1 (Sakimura, N., Bradley, J., Jones, M., and E. Jay, "OpenID Connect Discovery 1.0 incorporating errata set 1," November 2014.)](#OpenID.Discovery.Errata1) \[OpenID.Discovery.Errata1\]
-   [OpenID Connect Discovery 1.0 (final) (Sakimura, N., Bradley, J., Jones, M., and E. Jay, "OpenID Connect Discovery 1.0 (final)," February 2014.)](#OpenID.Discovery.Final) \[OpenID.Discovery.Final\]

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 1.1.  Requirements Notation and Conventions

The key words \"MUST\", \"MUST NOT\", \"REQUIRED\", \"SHALL\", \"SHALL NOT\", \"SHOULD\", \"SHOULD NOT\", \"RECOMMENDED\", \"NOT RECOMMENDED\", \"MAY\", and \"OPTIONAL\" in this document are to be interpreted as described in [RFC 2119 (Bradner, S., "Key words for use in RFCs to Indicate Requirement Levels," March 1997.)](#RFC2119) \[RFC2119\].

In the .txt version of this specification, values are quoted to indicate that they are to be taken literally. When using these values in protocol messages, the quotes MUST NOT be used as part of the value. In the HTML version of this specification, values to be taken literally are indicated by the use of `this fixed-width font`.

All uses of [JSON Web Signature (JWS) (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Signature (JWS)," May 2015.)](#JWS) \[JWS\] and [JSON Web Encryption (JWE) (Jones, M. and J. Hildebrand, "JSON Web Encryption (JWE)," May 2015.)](#JWE) \[JWE\] data structures in this specification utilize the JWS Compact Serialization or the JWE Compact Serialization; the JWS JSON Serialization and the JWE JSON Serialization are not used.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 1.2.  Terminology

This specification uses the terms \"Authorization Code\", \"Authorization Endpoint\", \"Authorization Server\", \"Client\", \"Client Authentication\", \"Client Secret\", \"Grant Type\", \"Response Type\", and \"Token Endpoint\" defined by [OAuth 2.0 (Hardt, D., Ed., "The OAuth 2.0 Authorization Framework," October 2012.)](#RFC6749) \[RFC6749\], the terms \"Claim Name\", \"Claim Value\", and \"JSON Web Token (JWT)\" defined by [JSON Web Token (JWT) (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Token (JWT)," May 2015.)](#JWT) \[JWT\], and the terms defined by [OpenID Connect Core 1.0 (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core) \[OpenID.Core\] and [OAuth 2.0 Multiple Response Type Encoding Practices (de Medeiros, B., Ed., Scurtescu, M., Tarjan, P., and M. Jones, "OAuth 2.0 Multiple Response Type Encoding Practices," February 2014.)](#OAuth.Responses) \[OAuth.Responses\].

This specification also defines the following terms:

> Resource
> :   Entity that is the target of a request in WebFinger.
>
> Host
> :   Server where a WebFinger service is hosted.
>
> Identifier
> :   Value that uniquely characterizes an Entity in a specific context.
>
> :   NOTE: This specification defines various kinds of Identifiers, designed for use in different contexts. Examples include URLs using the `https` scheme and e-mail addresses.

IMPORTANT NOTE TO READERS: The terminology definitions in this section are a normative portion of this specification, imposing requirements upon implementations. All the capitalized words in the text of this specification, such as \"Identifier\", reference these defined terms. Whenever the reader encounters them, their definitions found in this section must be followed.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.  OpenID Provider Issuer Discovery

OpenID Provider Issuer discovery is the process of determining the location of the OpenID Provider.

Issuer discovery is OPTIONAL; if a Relying Party knows the OP\'s Issuer location through an out-of-band mechanism, it can skip this step and proceed to [Section 4 (Obtaining OpenID Provider Configuration Information)](#ProviderConfig).

The following information is needed to perform issuer discovery using [WebFinger (Jones, P., Salgueiro, G., Jones, M., and J. Smarr, "WebFinger," September 2013.)](#RFC7033) \[RFC7033\]:

> resource
> :   Identifier for the target End-User that is the subject of the discovery request.
>
> host
> :   Server where a WebFinger service is hosted.
>
> rel
> :   URI identifying the type of service whose location is being requested.

OpenID Connect uses the following discoverable `rel` value in [WebFinger (Jones, P., Salgueiro, G., Jones, M., and J. Smarr, "WebFinger," September 2013.)](#RFC7033) \[RFC7033\]:

  Rel Type                URI
  ----------------------- --------------------------------------------
  OpenID Connect Issuer   http://openid.net/specs/connect/1.0/issuer

\

To start discovery of OpenID endpoints, the End-User supplies an Identifier to the Relying Party. Any Web input form MUST employ Cross-Site Request Forgery (CSRF) prevention [\[OWASP.CSRF\] (OWASP, "Cross-Site Request Forgery Prevention Cheat Sheet," .)](#OWASP.CSRF).

The RP applies normalization rules to the Identifier to determine the Resource and Host. Then it makes an HTTP `GET` request to the Host\'s [WebFinger (Jones, P., Salgueiro, G., Jones, M., and J. Smarr, "WebFinger," September 2013.)](#RFC7033) \[RFC7033\] endpoint with the `resource` parameter to obtain the location of the requested service. Use of the `rel` parameter in the request with a value of `http://openid.net/specs/connect/1.0/issuer` is also RECOMMENDED to narrow the response to the specific link relation type needed.

All WebFinger communication MUST utilize TLS in the manner described in [Section 7.1 (TLS Requirements)](#TLSRequirements). The WebFinger endpoint SHOULD support the use of [Cross-Origin Resource Sharing (CORS) (Opera Software ASA, "Cross-Origin Resource Sharing," July 2010.)](#CORS) \[CORS\] and/or other methods as appropriate to enable JavaScript Clients and other Browser-Based Clients to access it.

The Issuer location MUST be returned in the WebFinger response as the value of the `href` member of a `links` array element with `rel` member value `http://openid.net/specs/connect/1.0/issuer`. (Per Section 7 of [WebFinger (Jones, P., Salgueiro, G., Jones, M., and J. Smarr, "WebFinger," September 2013.)](#RFC7033) \[RFC7033\], obtaining the WebFinger response may first involve following some redirects.)

The returned Issuer location MUST be a URI [RFC 3986 (Berners-Lee, T., Fielding, R., and L. Masinter, "Uniform Resource Identifier (URI): Generic Syntax," January 2005.)](#RFC3986) \[RFC3986\] with a scheme component that MUST be `https`, a host component, and optionally, port and path components and no query or fragment components. Note that since the Host and Resource values determined from the user input Identifier, as described in [Section 2.1 (Identifier Normalization)](#IdentifierNormalization), are used as input to a WebFinger request, which can return an Issuer value using a completely different scheme, host, port, and path, no relationship can be assumed between the user input Identifier string and the resulting Issuer location.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.1.  Identifier Normalization

The purpose of Identifier normalization is to determine normalized Resource and Host values from the user input Identifier. These are then used as WebFinger request parameters to discover the Issuer location.

The user input Identifier SHOULD be a URL or URI relative reference defined in [RFC 3986 (Berners-Lee, T., Fielding, R., and L. Masinter, "Uniform Resource Identifier (URI): Generic Syntax," January 2005.)](#RFC3986) \[RFC3986\]. The user input Identifier MUST include the authority component.

NOTE: A URI relative reference includes a string that looks like an e-mail address in the form of `userinfo@host`. This is a valid authority component of a URI but excludes various possible extra strings allowed in `addr-spec` syntax of [RFC 5322 (Resnick, P., Ed., "Internet Message Format," October 2008.)](#RFC5322) \[RFC5322\].

The Identifier normalization rules MAY be extended by additional specifications to enable other identifier types such as telephone numbers or [XRIs (Reed, D. and D. McAlpin, "Extensible Resource Identifier (XRI) Syntax V2.0," November 2005.)](#XRI_Syntax_2.0) \[XRI_Syntax_2.0\] to also be used.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.1.1.  User Input Identifier Types

A user input Identifier can be categorized into the following types, which require different normalization processes:

1.  User input Identifiers starting with the [XRI (Reed, D. and D. McAlpin, "Extensible Resource Identifier (XRI) Syntax V2.0," November 2005.)](#XRI_Syntax_2.0) \[XRI_Syntax_2.0\] global context symbols (\'=\',\'@\', and \'!\') are RESERVED. Processing of these identifiers is out of scope for this specification.
2.  All other user input Identifiers MUST be treated as a URI in one of the forms `scheme "://" authority path-abempty [ "?" query ] [ "#" fragment ]` or `authority path-abempty [ "?" query ] [ "#" fragment ]` or `scheme ":" path-rootless`, per [RFC 3986 (Berners-Lee, T., Fielding, R., and L. Masinter, "Uniform Resource Identifier (URI): Generic Syntax," January 2005.)](#RFC3986) \[RFC3986\].

NOTE: The user input Identifier MAY be in the form of `userinfo@host`. For the End-User, this would normally be perceived as being an e-mail address. However, it is also a valid userpart \"@\" host section of an `acct` URI [\[RFC7565\] (Saint-Andre, P., "The \'acct\' URI Scheme," May 2015.)](#RFC7565), and this specification treats it such as to exclude various extra strings allowed in `addr-spec` of [RFC 5322 (Resnick, P., Ed., "Internet Message Format," October 2008.)](#RFC5322) \[RFC5322\].

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.1.2.  Normalization Steps

A string of any other type is interpreted as a URI in one of the forms `scheme "://" authority path-abempty [ "?" query ] [ "#" fragment ]` or `authority path-abempty [ "?" query ] [ "#" fragment ]` or `scheme ":" path-rootless` per [RFC 3986 (Berners-Lee, T., Fielding, R., and L. Masinter, "Uniform Resource Identifier (URI): Generic Syntax," January 2005.)](#RFC3986) \[RFC3986\] and is normalized according to the following rules:

1.  If the user input Identifier does not have an [RFC 3986 (Berners-Lee, T., Fielding, R., and L. Masinter, "Uniform Resource Identifier (URI): Generic Syntax," January 2005.)](#RFC3986) \[RFC3986\] scheme component, the string is interpreted as `[userinfo "@"] host [":" port] path-abempty [ "?" query ] [ "#" fragment ]` per [RFC 3986 (Berners-Lee, T., Fielding, R., and L. Masinter, "Uniform Resource Identifier (URI): Generic Syntax," January 2005.)](#RFC3986) \[RFC3986\]. Examples are `example.com`, `joe@example.com`, `example.com/joe`, and `example.com:8080`.
2.  If the userinfo and host components are present and all of the scheme, path, query, port, and fragment components are absent, the `acct` scheme is assumed. In this case, the normalized URI is formed by prefixing `acct:` to the string as the scheme. Per [The \'acct\' URI Scheme (Saint-Andre, P., "The \'acct\' URI Scheme," May 2015.)](#RFC7565) \[RFC7565\], if there is an at-sign character (\'@\') in the userinfo component, it needs to be percent-encoded, as described in [RFC 3986 (Berners-Lee, T., Fielding, R., and L. Masinter, "Uniform Resource Identifier (URI): Generic Syntax," January 2005.)](#RFC3986) \[RFC3986\]. Examples are `joe@example.com` and `Jane.Doe@example.com`.
3.  For all other inputs without a scheme component, the `https` scheme is assumed, and the normalized URI is formed by prefixing `https://` to the string as the scheme. Examples are `example.com`, `example.com/joe`, `example.com:8080`, and `joe@example.com:8080`.
4.  When the input contains an explicit scheme such as `acct` or `https` that matches the RFC 3986 `scheme ":" path-rootless` syntax, no input normalization is performed. Examples are `https://example.com`, `https://example.com/joe`, `https://joe@example.com:8080`, and `acct:joe@example.com`.
5.  If the resulting URI contains a fragment component, it MUST be stripped off, together with the fragment delimiter character \"#\".

The [WebFinger (Jones, P., Salgueiro, G., Jones, M., and J. Smarr, "WebFinger," September 2013.)](#RFC7033) \[RFC7033\] Resource in this case is the resulting URI, and the WebFinger Host is the authority component.

NOTE: Since the definition of `authority` in [RFC 3986 (Berners-Lee, T., Fielding, R., and L. Masinter, "Uniform Resource Identifier (URI): Generic Syntax," January 2005.)](#RFC3986) \[RFC3986\] is `[ userinfo "@" ] host [ ":" port ]`, it is legal to have a user input identifier like `userinfo@host:port`, e.g., `alice@example.com:8080`.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.2.  Non-Normative Examples

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.2.1.  User Input using E-Mail Address Syntax

To find the Issuer for the given user input in the form of an e-mail address `joe@example.com`, the WebFinger parameters are as follows:

  WebFinger Parameter   Value
  --------------------- --------------------------------------------
  resource              acct:joe@example.com
  host                  example.com
  rel                   http://openid.net/specs/connect/1.0/issuer

\

Note that in this case, the `acct:` scheme [\[RFC7565\] (Saint-Andre, P., "The \'acct\' URI Scheme," May 2015.)](#RFC7565) is prepended to the Identifier.

The RP would make the following WebFinger request to discover the Issuer location (with line wraps within lines for display purposes only):

      GET /.well-known/webfinger
        ?resource=acct%3Ajoe%40example.com
        &rel=http%3A%2F%2Fopenid.net%2Fspecs%2Fconnect%2F1.0%2Fissuer
        HTTP/1.1
      Host: example.com

      HTTP/1.1 200 OK
      Content-Type: application/jrd+json

      {
       "subject": "acct:joe@example.com",
       "links":
        [
         {
          "rel": "http://openid.net/specs/connect/1.0/issuer",
          "href": "https://server.example.com"
         }
        ]
      }

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.2.2.  User Input using URL Syntax

To find the Issuer for the given URL, `https://example.com/joe`, the WebFinger parameters are as follows:

  WebFinger Parameter   Value
  --------------------- --------------------------------------------
  resource              https://example.com/joe
  host                  example.com
  rel                   http://openid.net/specs/connect/1.0/issuer

\

The RP would make the following WebFinger request to discover the Issuer location (with line wraps within lines for display purposes only):

      GET /.well-known/webfinger
        ?resource=https%3A%2F%2Fexample.com%2Fjoe
        &rel=http%3A%2F%2Fopenid.net%2Fspecs%2Fconnect%2F1.0%2Fissuer
        HTTP/1.1
      Host: example.com

      HTTP/1.1 200 OK
      Content-Type: application/jrd+json

      {
       "subject": "https://example.com/joe",
       "links":
        [
         {
          "rel": "http://openid.net/specs/connect/1.0/issuer",
          "href": "https://server.example.com"
         }
        ]
      }

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.2.3.  User Input using Hostname and Port Syntax

If the user input is in the form of `host:port`, e.g., example.com:8080, then it is assumed as the authority component of the URL.

To find the Issuer for the given hostname, `example.com:8080`, the WebFinger parameters are as follows:

  WebFinger Parameter   Value
  --------------------- --------------------------------------------
  resource              https://example.com:8080/
  host                  example.com:8080
  rel                   http://openid.net/specs/connect/1.0/issuer

\

The RP would make the following WebFinger request to discover the Issuer location (with line wraps within lines for display purposes only):

      GET /.well-known/webfinger
        ?resource=https%3A%2F%2Fexample.com%3A8080%2F
        &rel=http%3A%2F%2Fopenid.net%2Fspecs%2Fconnect%2F1.0%2Fissuer
        HTTP/1.1
      Host: example.com:8080

      HTTP/1.1 200 OK
      Content-Type: application/jrd+json

      {
       "subject": "https://example.com:8080/",
       "links":
        [
         {
          "rel": "http://openid.net/specs/connect/1.0/issuer",
          "href": "https://server.example.com"
         }
        ]
      }

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.2.4.  User Input using \"acct\" URI Syntax

To find the Issuer for the given user input in the form of an account URI `acct:juliet%40capulet.example@shopping.example.com`, the WebFinger parameters are as follows:

  WebFinger Parameter   Value
  --------------------- ----------------------------------------------------
  resource              acct:juliet%40capulet.example@shopping.example.com
  host                  shopping.example.com
  rel                   http://openid.net/specs/connect/1.0/issuer

\

The RP would make the following WebFinger request to discover the Issuer location (with line wraps within lines for display purposes only):

      GET /.well-known/webfinger
        ?resource=acct%3Ajuliet%2540capulet.example%40shopping.example.com
        &rel=http%3A%2F%2Fopenid.net%2Fspecs%2Fconnect%2F1.0%2Fissuer
        HTTP/1.1
      Host: shopping.example.com

      HTTP/1.1 200 OK
      Content-Type: application/jrd+json

      {
       "subject": "acct:juliet%40capulet.example@shopping.example.com",
       "links":
        [
         {
          "rel": "http://openid.net/specs/connect/1.0/issuer",
          "href": "https://server.example.com"
         }
        ]
      }

NOTE: It is common for sites to use e-mail addresses as local identifiers for accounts at those sites, even though the domain in the e-mail address is not one controlled by the site. For instance, the site `example.org` might have a local account named `joe@example.com`. This specification uses `acct:` URIs as defined by [\[RFC7565\] (Saint-Andre, P., "The \'acct\' URI Scheme," May 2015.)](#RFC7565) to represent such accounts during WebFinger discovery. Such an example is `acct:joe%40example.com@example.org`. End-Users MAY input values like `joe@example.com@example.org` to initiate discovery on such accounts.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 3.  OpenID Provider Metadata

OpenID Providers have metadata describing their configuration. These OpenID Provider Metadata values are used by OpenID Connect:

> issuer
> :   REQUIRED. URL using the `https` scheme with no query or fragment components that the OP asserts as its Issuer Identifier. If Issuer discovery is supported (see [Section 2 (OpenID Provider Issuer Discovery)](#IssuerDiscovery)), this value MUST be identical to the issuer value returned by WebFinger. This also MUST be identical to the `iss` Claim value in ID Tokens issued from this Issuer.
>
> authorization_endpoint
> :   REQUIRED. URL of the OP\'s OAuth 2.0 Authorization Endpoint [\[OpenID.Core\] (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core). This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components.
>
> token_endpoint
> :   URL of the OP\'s OAuth 2.0 Token Endpoint [\[OpenID.Core\] (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core). This is REQUIRED unless only the Implicit Flow is used. This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components.
>
> userinfo_endpoint
> :   RECOMMENDED. URL of the OP\'s UserInfo Endpoint [\[OpenID.Core\] (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core). This URL MUST use the `https` scheme and MAY contain port, path, and query parameter components.
>
> jwks_uri
> :   REQUIRED. URL of the OP\'s JWK Set [\[JWK\] (Jones, M., "JSON Web Key (JWK)," May 2015.)](#JWK) document, which MUST use the `https` scheme. This contains the signing key(s) the RP uses to validate signatures from the OP. The JWK Set MAY also contain the Server\'s encryption key(s), which are used by RPs to encrypt requests to the Server. When both signing and encryption keys are made available, a `use` (public key use) parameter value is REQUIRED for all keys in the referenced JWK Set to indicate each key\'s intended usage. Although some algorithms allow the same key to be used for both signatures and encryption, doing so is NOT RECOMMENDED, as it is less secure. The JWK `x5c` parameter MAY be used to provide X.509 representations of keys provided. When used, the bare key values MUST still be present and MUST match those in the certificate. The JWK Set MUST NOT contain private or symmetric key values.
>
> registration_endpoint
> :   RECOMMENDED. URL of the OP\'s Dynamic Client Registration Endpoint [\[OpenID.Registration\] (Sakimura, N., Bradley, J., and M. Jones, "OpenID Connect Dynamic Client Registration 1.0," December 2023.)](#OpenID.Registration), which MUST use the `https` scheme.
>
> scopes_supported
> :   RECOMMENDED. JSON array containing a list of the [OAuth 2.0 (Hardt, D., Ed., "The OAuth 2.0 Authorization Framework," October 2012.)](#RFC6749) \[RFC6749\] scope values that this server supports. The server MUST support the `openid` scope value. Servers MAY choose not to advertise some supported scope values even when this parameter is used, although those defined in [\[OpenID.Core\] (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core) SHOULD be listed, if supported.
>
> response_types_supported
> :   REQUIRED. JSON array containing a list of the OAuth 2.0 `response_type` values that this OP supports. Dynamic OpenID Providers MUST support the `code`, `id_token`, and the `id_token token` Response Type values.
>
> response_modes_supported
> :   OPTIONAL. JSON array containing a list of the OAuth 2.0 `response_mode` values that this OP supports, as specified in [OAuth 2.0 Multiple Response Type Encoding Practices (de Medeiros, B., Ed., Scurtescu, M., Tarjan, P., and M. Jones, "OAuth 2.0 Multiple Response Type Encoding Practices," February 2014.)](#OAuth.Responses) \[OAuth.Responses\]. If omitted, the default for Dynamic OpenID Providers is `["query", "fragment"]`.
>
> grant_types_supported
> :   OPTIONAL. JSON array containing a list of the OAuth 2.0 Grant Type values that this OP supports. Dynamic OpenID Providers MUST support the `authorization_code` and `implicit` Grant Type values and MAY support other Grant Types. If omitted, the default value is `["authorization_code", "implicit"]`.
>
> acr_values_supported
> :   OPTIONAL. JSON array containing a list of the Authentication Context Class References that this OP supports.
>
> subject_types_supported
> :   REQUIRED. JSON array containing a list of the Subject Identifier types that this OP supports. Valid types include `pairwise` and `public`.
>
> id_token_signing_alg_values_supported
> :   REQUIRED. JSON array containing a list of the JWS signing algorithms (`alg` values) supported by the OP for the ID Token to encode the Claims in a JWT [\[JWT\] (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Token (JWT)," May 2015.)](#JWT). The algorithm `RS256` MUST be included. The value `none` MAY be supported but MUST NOT be used unless the Response Type used returns no ID Token from the Authorization Endpoint (such as when using the Authorization Code Flow).
>
> id_token_encryption_alg_values_supported
> :   OPTIONAL. JSON array containing a list of the JWE encryption algorithms (`alg` values) supported by the OP for the ID Token to encode the Claims in a JWT [\[JWT\] (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Token (JWT)," May 2015.)](#JWT).
>
> id_token_encryption_enc_values_supported
> :   OPTIONAL. JSON array containing a list of the JWE encryption algorithms (`enc` values) supported by the OP for the ID Token to encode the Claims in a JWT [\[JWT\] (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Token (JWT)," May 2015.)](#JWT).
>
> userinfo_signing_alg_values_supported
> :   OPTIONAL. JSON array containing a list of the JWS [\[JWS\] (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Signature (JWS)," May 2015.)](#JWS) signing algorithms (`alg` values) [\[JWA\] (Jones, M., "JSON Web Algorithms (JWA)," May 2015.)](#JWA) supported by the UserInfo Endpoint to encode the Claims in a JWT [\[JWT\] (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Token (JWT)," May 2015.)](#JWT). The value `none` MAY be included.
>
> userinfo_encryption_alg_values_supported
> :   OPTIONAL. JSON array containing a list of the JWE [\[JWE\] (Jones, M. and J. Hildebrand, "JSON Web Encryption (JWE)," May 2015.)](#JWE) encryption algorithms (`alg` values) [\[JWA\] (Jones, M., "JSON Web Algorithms (JWA)," May 2015.)](#JWA) supported by the UserInfo Endpoint to encode the Claims in a JWT [\[JWT\] (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Token (JWT)," May 2015.)](#JWT).
>
> userinfo_encryption_enc_values_supported
> :   OPTIONAL. JSON array containing a list of the JWE encryption algorithms (`enc` values) [\[JWA\] (Jones, M., "JSON Web Algorithms (JWA)," May 2015.)](#JWA) supported by the UserInfo Endpoint to encode the Claims in a JWT [\[JWT\] (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Token (JWT)," May 2015.)](#JWT).
>
> request_object_signing_alg_values_supported
> :   OPTIONAL. JSON array containing a list of the JWS signing algorithms (`alg` values) supported by the OP for Request Objects, which are described in Section 6.1 of [OpenID Connect Core 1.0 (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core) \[OpenID.Core\]. These algorithms are used both when the Request Object is passed by value (using the `request` parameter) and when it is passed by reference (using the `request_uri` parameter). Servers SHOULD support `none` and `RS256`.
>
> request_object_encryption_alg_values_supported
> :   OPTIONAL. JSON array containing a list of the JWE encryption algorithms (`alg` values) supported by the OP for Request Objects. These algorithms are used both when the Request Object is passed by value and when it is passed by reference.
>
> request_object_encryption_enc_values_supported
> :   OPTIONAL. JSON array containing a list of the JWE encryption algorithms (`enc` values) supported by the OP for Request Objects. These algorithms are used both when the Request Object is passed by value and when it is passed by reference.
>
> token_endpoint_auth_methods_supported
> :   OPTIONAL. JSON array containing a list of Client Authentication methods supported by this Token Endpoint. The options are `client_secret_post`, `client_secret_basic`, `client_secret_jwt`, and `private_key_jwt`, as described in Section 9 of [OpenID Connect Core 1.0 (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core) \[OpenID.Core\]. Other authentication methods MAY be defined by extensions. If omitted, the default is `client_secret_basic` \-- the HTTP Basic Authentication Scheme specified in Section 2.3.1 of [OAuth 2.0 (Hardt, D., Ed., "The OAuth 2.0 Authorization Framework," October 2012.)](#RFC6749) \[RFC6749\].
>
> token_endpoint_auth_signing_alg_values_supported
> :   OPTIONAL. JSON array containing a list of the JWS signing algorithms (`alg` values) supported by the Token Endpoint for the signature on the JWT [\[JWT\] (Jones, M., Bradley, J., and N. Sakimura, "JSON Web Token (JWT)," May 2015.)](#JWT) used to authenticate the Client at the Token Endpoint for the `private_key_jwt` and `client_secret_jwt` authentication methods. Servers SHOULD support `RS256`. The value `none` MUST NOT be used.
>
> display_values_supported
> :   OPTIONAL. JSON array containing a list of the `display` parameter values that the OpenID Provider supports. These values are described in Section 3.1.2.1 of [OpenID Connect Core 1.0 (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core) \[OpenID.Core\].
>
> claim_types_supported
> :   OPTIONAL. JSON array containing a list of the Claim Types that the OpenID Provider supports. These Claim Types are described in Section 5.6 of [OpenID Connect Core 1.0 (Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "OpenID Connect Core 1.0," December 2023.)](#OpenID.Core) \[OpenID.Core\]. Values defined by this specification are `normal`, `aggregated`, and `distributed`. If omitted, the implementation supports only `normal` Claims.
>
> claims_supported
> :   RECOMMENDED. JSON array containing a list of the Claim Names of the Claims that the OpenID Provider MAY be able to supply values for. Note that for privacy or other reasons, this might not be an exhaustive list.
>
> service_documentation
> :   OPTIONAL. URL of a page containing human-readable information that developers might want or need to know when using the OpenID Provider. In particular, if the OpenID Provider does not support Dynamic Client Registration, then information on how to register Clients needs to be provided in this documentation.
>
> claims_locales_supported
> :   OPTIONAL. Languages and scripts supported for values in Claims being returned, represented as a JSON array of [BCP47 (Phillips, A., Ed. and M. Davis, Ed., "Tags for Identifying Languages," September 2009.)](#RFC5646) \[RFC5646\] language tag values. Not all languages and scripts are necessarily supported for all Claim values.
>
> ui_locales_supported
> :   OPTIONAL. Languages and scripts supported for the user interface, represented as a JSON array of [BCP47 (Phillips, A., Ed. and M. Davis, Ed., "Tags for Identifying Languages," September 2009.)](#RFC5646) \[RFC5646\] language tag values.
>
> claims_parameter_supported
> :   OPTIONAL. Boolean value specifying whether the OP supports use of the `claims` parameter, with `true` indicating support. If omitted, the default value is `false`.
>
> request_parameter_supported
> :   OPTIONAL. Boolean value specifying whether the OP supports use of the `request` parameter, with `true` indicating support. If omitted, the default value is `false`.
>
> request_uri_parameter_supported
> :   OPTIONAL. Boolean value specifying whether the OP supports use of the `request_uri` parameter, with `true` indicating support. If omitted, the default value is `true`.
>
> require_request_uri_registration
> :   OPTIONAL. Boolean value specifying whether the OP requires any `request_uri` values used to be pre-registered using the `request_uris` registration parameter. Pre-registration is REQUIRED when the value is `true`. If omitted, the default value is `false`.
>
> op_policy_uri
> :   OPTIONAL. URL that the OpenID Provider provides to the person registering the Client to read about the OP\'s requirements on how the Relying Party can use the data provided by the OP. The registration process SHOULD display this URL to the person registering the Client if it is given.
>
> op_tos_uri
> :   OPTIONAL. URL that the OpenID Provider provides to the person registering the Client to read about the OpenID Provider\'s terms of service. The registration process SHOULD display this URL to the person registering the Client if it is given.

The Token Endpoint, UserInfo Endpoint, `jwks_uri` endpoint, Dynamic Client Registration Endpoint, and any other endpoints directly accessed by Clients SHOULD support the use of [Cross-Origin Resource Sharing (CORS) (Opera Software ASA, "Cross-Origin Resource Sharing," July 2010.)](#CORS) \[CORS\] and/or other methods as appropriate to enable JavaScript Clients and other Browser-Based Clients to access them. The use of CORS at the Authorization Endpoint is NOT RECOMMENDED as it is redirected to by the client and not directly accessed.

Additional OpenID Provider Metadata parameters MAY also be used. Some are defined by other specifications, such as [OpenID Connect Session Management 1.0 (de Medeiros, B., Agarwal, N., Sakimura, N., Bradley, J., and M. Jones, "OpenID Connect Session Management 1.0," September 2022.)](#OpenID.Session) \[OpenID.Session\].

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 4.  Obtaining OpenID Provider Configuration Information

Using the Issuer location discovered as described in [Section 2 (OpenID Provider Issuer Discovery)](#IssuerDiscovery) or by other means, the OpenID Provider\'s configuration information can be retrieved.

OpenID Providers supporting Discovery MUST make a JSON document available at the path formed by concatenating the string `/.well-known/openid-configuration` to the Issuer. The syntax and semantics of `.well-known` are defined in [RFC 5785 (Nottingham, M. and E. Hammer-Lahav, "Defining Well-Known Uniform Resource Identifiers (URIs)," April 2010.)](#RFC5785) \[RFC5785\] and apply to the Issuer value when it contains no path component. `openid-configuration` MUST point to a JSON document compliant with this specification and MUST be returned using the `application/json` content type. The `openid-configuration` endpoint SHOULD support the use of [Cross-Origin Resource Sharing (CORS) (Opera Software ASA, "Cross-Origin Resource Sharing," July 2010.)](#CORS) \[CORS\] and/or other methods as appropriate to enable JavaScript Clients and other Browser-Based Clients to access it.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 4.1.  OpenID Provider Configuration Request

An OpenID Provider\'s configuration information MUST be retrieved using an HTTP `GET` request at the previously specified path.

The RP would make the following request to the Issuer `https://example.com` to obtain its Configuration information, since the Issuer contains no path component:

      GET /.well-known/openid-configuration HTTP/1.1
      Host: example.com

If the Issuer value contains a path component, any terminating `/` MUST be removed before appending `/.well-known/openid-configuration`. The RP would make the following request to the Issuer `https://example.com/issuer1` to obtain its configuration information, since the Issuer contains a path component:

      GET /issuer1/.well-known/openid-configuration HTTP/1.1
      Host: example.com

Using path components enables supporting multiple issuers per host. This is required in some multi-tenant hosting configurations. This use of `.well-known` is for supporting multiple issuers per host; unlike its use in [RFC 5785 (Nottingham, M. and E. Hammer-Lahav, "Defining Well-Known Uniform Resource Identifiers (URIs)," April 2010.)](#RFC5785) \[RFC5785\], it does not provide general information about the host.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 4.2.  OpenID Provider Configuration Response

The response is a set of Claims about the OpenID Provider\'s configuration, including all necessary endpoints and public key location information. A successful response MUST use the 200 OK HTTP status code and return a JSON object using the `application/json` content type that contains a set of Claims as its members that are a subset of the Metadata values defined in [Section 3 (OpenID Provider Metadata)](#ProviderMetadata). Other Claims MAY also be returned.

Claims that return multiple values are represented as JSON arrays. Claims with zero elements MUST be omitted from the response.

An error response uses the applicable HTTP status code value.

The following is a non-normative example response:

      HTTP/1.1 200 OK
      Content-Type: application/json

      {
       "issuer":
         "https://server.example.com",
       "authorization_endpoint":
         "https://server.example.com/connect/authorize",
       "token_endpoint":
         "https://server.example.com/connect/token",
       "token_endpoint_auth_methods_supported":
         ["client_secret_basic", "private_key_jwt"],
       "token_endpoint_auth_signing_alg_values_supported":
         ["RS256", "ES256"],
       "userinfo_endpoint":
         "https://server.example.com/connect/userinfo",
       "check_session_iframe":
         "https://server.example.com/connect/check_session",
       "end_session_endpoint":
         "https://server.example.com/connect/end_session",
       "jwks_uri":
         "https://server.example.com/jwks.json",
       "registration_endpoint":
         "https://server.example.com/connect/register",
       "scopes_supported":
         ["openid", "profile", "email", "address",
          "phone", "offline_access"],
       "response_types_supported":
         ["code", "code id_token", "id_token", "id_token token"],
       "acr_values_supported":
         ["urn:mace:incommon:iap:silver",
          "urn:mace:incommon:iap:bronze"],
       "subject_types_supported":
         ["public", "pairwise"],
       "userinfo_signing_alg_values_supported":
         ["RS256", "ES256", "HS256"],
       "userinfo_encryption_alg_values_supported":
         ["RSA-OAEP-256", "A128KW"],
       "userinfo_encryption_enc_values_supported":
         ["A128CBC-HS256", "A128GCM"],
       "id_token_signing_alg_values_supported":
         ["RS256", "ES256", "HS256"],
       "id_token_encryption_alg_values_supported":
         ["RSA-OAEP-256", "A128KW"],
       "id_token_encryption_enc_values_supported":
         ["A128CBC-HS256", "A128GCM"],
       "request_object_signing_alg_values_supported":
         ["none", "RS256", "ES256"],
       "display_values_supported":
         ["page", "popup"],
       "claim_types_supported":
         ["normal", "distributed"],
       "claims_supported":
         ["sub", "iss", "auth_time", "acr",
          "name", "given_name", "family_name", "nickname",
          "profile", "picture", "website",
          "email", "email_verified", "locale", "zoneinfo",
          "http://example.info/claims/groups"],
       "claims_parameter_supported":
         true,
       "service_documentation":
         "http://server.example.com/connect/service_documentation.html",
       "ui_locales_supported":
         ["en-US", "en-GB", "en-CA", "fr-FR", "fr-CA"]
      }

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 4.3.  OpenID Provider Configuration Validation

If any of the validation procedures defined in this specification fail, any operations requiring the information that failed to correctly validate MUST be aborted and the information that failed to validate MUST NOT be used.

The `issuer` value returned MUST be identical to the Issuer URL that was used as the prefix to `/.well-known/openid-configuration` to retrieve the configuration information. This MUST also be identical to the `iss` Claim value in ID Tokens issued from this Issuer.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 5.  String Operations

Processing some OpenID Connect messages requires comparing values in the messages to known values. For example, the member names in the provider configuration response might be compared to specific member names such as `issuer`. Comparing Unicode [\[UNICODE\] (The Unicode Consortium, "The Unicode Standard," .)](#UNICODE) strings, however, has significant security implications.

Therefore, comparisons between JSON strings and other Unicode strings MUST be performed as specified below:

1.  Remove any JSON applied escaping to produce an array of Unicode code points.
2.  Unicode Normalization [\[USA15\] (Whistler, K., "Unicode Normalization Forms," August 2023.)](#USA15) MUST NOT be applied at any point to either the JSON string or to the string it is to be compared against.
3.  Comparisons between the two strings MUST be performed as a Unicode code point to code point equality comparison.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 6.  Implementation Considerations

This specification defines features used by both Relying Parties and OpenID Providers that choose to implement Discovery. All of these Relying Parties and OpenID Providers MUST implement the features that are listed in this specification as being \"REQUIRED\" or are described with a \"MUST\". No other implementation considerations for implementations of Discovery are defined by this specification.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 6.1.  Compatibility Notes

NOTE: Potential compatibility issues that were previously described in the original version of this specification have since been addressed.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 7.  Security Considerations

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 7.1.  TLS Requirements

Implementations MUST support TLS. Which version(s) ought to be implemented will vary over time and depend on the widespread deployment and known security vulnerabilities at the time of implementation. Implementations SHOULD follow the guidance in BCP 195 [\[RFC8996\] (Moriarty, K. and S. Farrell, "Deprecating TLS 1.0 and TLS 1.1," March 2021.)](#RFC8996) [\[RFC9325\] (Sheffer, Y., Saint-Andre, P., and T. Fossati, "Recommendations for Secure Use of Transport Layer Security (TLS) and Datagram Transport Layer Security (DTLS)," November 2022.)](#RFC9325), which provides recommendations and requirements for improving the security of deployed services that use TLS.

To protect against information disclosure and tampering, confidentiality protection MUST be applied using TLS with a ciphersuite that provides confidentiality and integrity protection.

Whenever TLS is used, a TLS server certificate check MUST be performed, per [RFC 6125 (Saint-Andre, P. and J. Hodges, "Representation and Verification of Domain-Based Application Service Identity within Internet Public Key Infrastructure Using X.509 (PKIX) Certificates in the Context of Transport Layer Security (TLS)," March 2011.)](#RFC6125) \[RFC6125\].

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 7.2.  Impersonation Attacks

TLS certificate checking MUST be performed by the RP, as described in [Section 7.1 (TLS Requirements)](#TLSRequirements), when making an OpenID Provider Configuration Request. Checking that the server certificate is valid for the Issuer URL prevents man-in-middle and DNS-based attacks. These attacks could cause an RP to be tricked into using an attacker\'s keys and endpoints, which would enable impersonation of the legitimate Issuer. If an attacker can accomplish this, they can access the accounts of any existing users at the affected RP that can be logged into using the OP that they are impersonating.

An attacker may also attempt to impersonate an OpenID Provider by publishing a Discovery document that contains an `issuer` Claim using the Issuer URL of the OP being impersonated, but with its own endpoints and signing keys. This would enable it to issue ID Tokens as that OP, if accepted by the RP. To prevent this, RPs MUST ensure that the Issuer URL they are using for the Configuration Request exactly matches the value of the `issuer` Claim in the OP Metadata document received by the RP and that this also exactly matches the `iss` Claim value in ID Tokens that are supposed to be from that Issuer.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 8.  IANA Considerations

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 8.1.  Well-Known URI Registry

This specification registers the well-known URI defined in [Section 4 (Obtaining OpenID Provider Configuration Information)](#ProviderConfig) in the IANA \"Well-Known URIs\" registry [\[IANA.well‑known\] (IANA, "Well-Known URIs," .)](#IANA.well-known) established by [RFC 5785 (Nottingham, M. and E. Hammer-Lahav, "Defining Well-Known Uniform Resource Identifiers (URIs)," April 2010.)](#RFC5785) \[RFC5785\].

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 8.1.1.  Registry Contents

-   URI suffix: `openid-configuration`
-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification document: [Section 4 (Obtaining OpenID Provider Configuration Information)](#ProviderConfig) of this specification
-   Related information: (none)

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 8.2.  OAuth Authorization Server Metadata Registry

This specification registers the following metadata names in the IANA \"OAuth Authorization Server Metadata\" registry [\[IANA.OAuth.Parameters\] (IANA, "OAuth Parameters," .)](#IANA.OAuth.Parameters) established by [\[RFC8414\] (Jones, M., Sakimura, N., and J. Bradley, "OAuth 2.0 Authorization Server Metadata," June 2018.)](#RFC8414).

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 8.2.1.  Registry Contents

-   Metadata Name: `userinfo_endpoint`
-   Metadata Description: URL of the OP\'s UserInfo Endpoint
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `acr_values_supported`
-   Metadata Description: JSON array containing a list of the Authentication Context Class References that this OP supports
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `subject_types_supported`
-   Metadata Description: JSON array containing a list of the Subject Identifier types that this OP supports
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `id_token_signing_alg_values_supported`
-   Metadata Description: JSON array containing a list of the JWS \"alg\" values supported by the OP for the ID Token
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `id_token_encryption_alg_values_supported`
-   Metadata Description: JSON array containing a list of the JWE \"alg\" values supported by the OP for the ID Token
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `id_token_encryption_enc_values_supported`
-   Metadata Description: JSON array containing a list of the JWE \"enc\" values supported by the OP for the ID Token
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `userinfo_signing_alg_values_supported`
-   Metadata Description: JSON array containing a list of the JWS \"alg\" values supported by the UserInfo Endpoint
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `userinfo_encryption_alg_values_supported`
-   Metadata Description: JSON array containing a list of the JWE \"alg\" values supported by the UserInfo Endpoint
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `userinfo_encryption_enc_values_supported`
-   Metadata Description: JSON array containing a list of the JWE \"enc\" values supported by the UserInfo Endpoint
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `request_object_signing_alg_values_supported`
-   Metadata Description: JSON array containing a list of the JWS \"alg\" values supported by the OP for Request Objects
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `request_object_encryption_alg_values_supported`
-   Metadata Description: JSON array containing a list of the JWE \"alg\" values supported by the OP for Request Objects
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `request_object_encryption_enc_values_supported`
-   Metadata Description: JSON array containing a list of the JWE \"enc\" values supported by the OP for Request Objects
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `display_values_supported`
-   Metadata Description: JSON array containing a list of the \"display\" parameter values that the OpenID Provider supports
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `claim_types_supported`
-   Metadata Description: JSON array containing a list of the Claim Types that the OpenID Provider supports
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `claims_supported`
-   Metadata Description: JSON array containing a list of the Claim Names of the Claims that the OpenID Provider MAY be able to supply values for
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `claims_locales_supported`
-   Metadata Description: Languages and scripts supported for values in Claims being returned, represented as a JSON array of BCP 47 language tag values
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `claims_parameter_supported`
-   Metadata Description: Boolean value specifying whether the OP supports use of the \"claims\" parameter
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `request_parameter_supported`
-   Metadata Description: Boolean value specifying whether the OP supports use of the \"request\" parameter
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `request_uri_parameter_supported`
-   Metadata Description: Boolean value specifying whether the OP supports use of the \"request_uri\" parameter
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

&nbsp;

-   Metadata Name: `require_request_uri_registration`
-   Metadata Description: Boolean value specifying whether the OP requires any \"request_uri\" values used to be pre-registered
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): [Section 3 (OpenID Provider Metadata)](#ProviderMetadata) of this specification

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 9.  References

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 9.1. Normative References

  ------------------------- ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  \[CORS\]                  Opera Software ASA, "[Cross-Origin Resource Sharing](http://www.w3.org/TR/access-control/)," July 2010.
  \[IANA.well-known\]       IANA, "[Well-Known URIs](https://www.iana.org/assignments/well-known-uris)."
  \[JWA\]                   Jones, M., "[JSON Web Algorithms (JWA)](https://www.rfc-editor.org/info/rfc7518)," RFC 7518, DOI 10.17487/RFC7518, May 2015.
  \[JWE\]                   Jones, M. and J. Hildebrand, "[JSON Web Encryption (JWE)](https://www.rfc-editor.org/info/rfc7516)," RFC 7516, DOI 10.17487/RFC7516, May 2015.
  \[JWK\]                   Jones, M., "[JSON Web Key (JWK)](https://www.rfc-editor.org/info/rfc7517)," RFC 7517, DOI 10.17487/RFC7517, May 2015.
  \[JWS\]                   Jones, M., Bradley, J., and N. Sakimura, "[JSON Web Signature (JWS)](https://www.rfc-editor.org/info/rfc7515)," RFC 7515, DOI 10.17487/RFC7515, May 2015.
  \[JWT\]                   Jones, M., Bradley, J., and N. Sakimura, "[JSON Web Token (JWT)](https://www.rfc-editor.org/info/rfc7519)," RFC 7519, DOI 10.17487/RFC7519, May 2015.
  \[OAuth.Responses\]       de Medeiros, B., Ed., Scurtescu, M., Tarjan, P., and M. Jones, "[OAuth 2.0 Multiple Response Type Encoding Practices](https://openid.net/specs/oauth-v2-multiple-response-types-1_0.html)," February 2014.
  \[OpenID.Core\]           Sakimura, N., Bradley, J., Jones, M., de Medeiros, B., and C. Mortimore, "[OpenID Connect Core 1.0](https://openid.net/specs/openid-connect-core-1_0.html)," December 2023.
  \[OpenID.Registration\]   Sakimura, N., Bradley, J., and M. Jones, "[OpenID Connect Dynamic Client Registration 1.0](https://openid.net/specs/openid-connect-registration-1_0.html)," December 2023.
  \[RFC2119\]               Bradner, S., "[Key words for use in RFCs to Indicate Requirement Levels](https://www.rfc-editor.org/info/rfc2119)," BCP 14, RFC 2119, DOI 10.17487/RFC2119, March 1997.
  \[RFC3986\]               Berners-Lee, T., Fielding, R., and L. Masinter, "[Uniform Resource Identifier (URI): Generic Syntax](https://www.rfc-editor.org/info/rfc3986)," STD 66, RFC 3986, DOI 10.17487/RFC3986, January 2005.
  \[RFC5322\]               Resnick, P., Ed., "[Internet Message Format](https://www.rfc-editor.org/info/rfc5322)," RFC 5322, DOI 10.17487/RFC5322, October 2008.
  \[RFC5646\]               Phillips, A., Ed. and M. Davis, Ed., "[Tags for Identifying Languages](https://www.rfc-editor.org/info/rfc5646)," BCP 47, RFC 5646, DOI 10.17487/RFC5646, September 2009.
  \[RFC5785\]               Nottingham, M. and E. Hammer-Lahav, "[Defining Well-Known Uniform Resource Identifiers (URIs)](https://www.rfc-editor.org/info/rfc5785)," RFC 5785, DOI 10.17487/RFC5785, April 2010.
  \[RFC6125\]               Saint-Andre, P. and J. Hodges, "[Representation and Verification of Domain-Based Application Service Identity within Internet Public Key Infrastructure Using X.509 (PKIX) Certificates in the Context of Transport Layer Security (TLS)](https://www.rfc-editor.org/info/rfc6125)," RFC 6125, DOI 10.17487/RFC6125, March 2011.
  \[RFC6749\]               Hardt, D., Ed., "[The OAuth 2.0 Authorization Framework](https://www.rfc-editor.org/info/rfc6749)," RFC 6749, DOI 10.17487/RFC6749, October 2012.
  \[RFC7033\]               Jones, P., Salgueiro, G., Jones, M., and J. Smarr, "[WebFinger](https://www.rfc-editor.org/info/rfc7033)," RFC 7033, DOI 10.17487/RFC7033, September 2013.
  \[RFC7565\]               Saint-Andre, P., "[The \'acct\' URI Scheme](https://www.rfc-editor.org/info/rfc7565)," RFC 7565, DOI 10.17487/RFC7565, May 2015.
  \[RFC8259\]               Bray, T., Ed., "[The JavaScript Object Notation (JSON) Data Interchange Format](https://www.rfc-editor.org/info/rfc8259)," STD 90, RFC 8259, DOI 10.17487/RFC8259, December 2017.
  \[RFC8996\]               Moriarty, K. and S. Farrell, "[Deprecating TLS 1.0 and TLS 1.1](https://www.rfc-editor.org/info/rfc8996)," BCP 195, RFC 8996, DOI 10.17487/RFC8996, March 2021.
  \[RFC9325\]               Sheffer, Y., Saint-Andre, P., and T. Fossati, "[Recommendations for Secure Use of Transport Layer Security (TLS) and Datagram Transport Layer Security (DTLS)](https://www.rfc-editor.org/info/rfc9325)," BCP 195, RFC 9325, DOI 10.17487/RFC9325, November 2022.
  \[UNICODE\]               The Unicode Consortium, "[The Unicode Standard](http://www.unicode.org/versions/latest/)."
  \[USA15\]                 Whistler, K., "[Unicode Normalization Forms](https://www.unicode.org/reports/tr15/)," Unicode Standard Annex 15, August 2023.
  ------------------------- ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 9.2. Informative References

  ------------------------------ ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  \[IANA.OAuth.Parameters\]      IANA, "[OAuth Parameters](https://www.iana.org/assignments/oauth-parameters)."
  \[OWASP.CSRF\]                 OWASP, "[Cross-Site Request Forgery Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)."
  \[OpenID.Discovery.Errata1\]   Sakimura, N., Bradley, J., Jones, M., and E. Jay, "[OpenID Connect Discovery 1.0 incorporating errata set 1](https://openid.net/specs/openid-connect-discovery-1_0-errata1.html)," November 2014.
  \[OpenID.Discovery.Final\]     Sakimura, N., Bradley, J., Jones, M., and E. Jay, "[OpenID Connect Discovery 1.0 (final)](https://openid.net/specs/openid-connect-discovery-1_0-final.html)," February 2014.
  \[OpenID.Session\]             de Medeiros, B., Agarwal, N., Sakimura, N., Bradley, J., and M. Jones, "[OpenID Connect Session Management 1.0](https://openid.net/specs/openid-connect-session-1_0.html)," September 2022.
  \[RFC8414\]                    Jones, M., Sakimura, N., and J. Bradley, "[OAuth 2.0 Authorization Server Metadata](https://www.rfc-editor.org/info/rfc8414)," RFC 8414, DOI 10.17487/RFC8414, June 2018.
  \[XRI_Syntax_2.0\]             Reed, D. and D. McAlpin, "[Extensible Resource Identifier (XRI) Syntax V2.0](https://www.oasis-open.org/committees/download.php/15377/xri-syntax-V2.0-cs.pdf)," November 2005.
  ------------------------------ ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### Appendix A.  Acknowledgements

The OpenID Community would like to thank the following people for their contributions to this specification:

> Andrew Arnott (andarno@microsoft.com), Microsoft
>
> Dirk Balfanz (balfanz@google.com), Google
>
> Casper Biering (cb@peercraft.com), Peercraft
>
> John Bradley (ve7jtb@ve7jtb.com), Yubico (was at Ping Identity)
>
> Johnny Bufu (johnny.bufu@gmail.com), independent (was at Janrain)
>
> Brian Campbell (bcampbell@pingidentity.com), Ping Identity
>
> Blaine Cook (romeda@gmail.com), independent
>
> Breno de Medeiros (breno@google.com), Google
>
> Pamela Dingle (Pamela.Dingle@microsoft.com), Microsoft (was at Ping Identity)
>
> Vladimir Dzhuvinov (vladimir@connect2id.com), Connect2id (was at Nimbus Directory Services)
>
> George Fletcher (gffletch@aol.com), Capital One (was at AOL)
>
> Dick Hardt (dick.hardt@gmail.com), independent
>
> Roland Hedberg (roland@catalogix.se), independent (was at University of Umeå)
>
> Edmund Jay (ejay@mgi1.com), Illumila
>
> Michael B. Jones (michael_b_jones@hotmail.com), Self-Issued Consulting (was at Microsoft)
>
> Torsten Lodderstedt (torsten@lodderstedt.net), independent (was at Deutsche Telekom)
>
> Nov Matake (nov@matake.jp), independent
>
> Chuck Mortimore (charliemortimore@gmail.com), Disney (was at Salesforce)
>
> Anthony Nadalin (nadalin@prodigy.net), independent (was at Microsoft)
>
> Axel Nennker (axel.nennker@telekom.de), Deutsche Telekom
>
> John Panzer (jpanzer@google.com), Google
>
> Justin Richer (justin@bspk.io), Bespoke Engineering (was at MITRE)
>
> Nat Sakimura (nat@nat.consulting), NAT.Consulting (was at NRI)
>
> Owen Shepherd (owen.shepherd@e43.eu), independent
>
> Andreas Åkre Solberg (Andreas.Solberg@sikt.no), Sikt (was at UNINET)
>
> Kick Willemse (k.willemse@evidos.nl), Evidos B.V.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### Appendix B.  Notices

Copyright (c) 2023 The OpenID Foundation.

The OpenID Foundation (OIDF) grants to any Contributor, developer, implementer, or other interested party a non-exclusive, royalty free, worldwide copyright license to reproduce, prepare derivative works from, distribute, perform and display, this Implementers Draft or Final Specification solely for the purposes of (i) developing specifications, and (ii) implementing Implementers Drafts and Final Specifications based on such documents, provided that attribution be made to the OIDF as the source of the material, but that such attribution does not indicate an endorsement by the OIDF.

The technology described in this specification was made available from contributions from various sources, including members of the OpenID Foundation and others. Although the OpenID Foundation has taken steps to help ensure that the technology is available for distribution, it takes no position regarding the validity or scope of any intellectual property or other rights that might be claimed to pertain to the implementation or use of the technology described in this specification or the extent to which any license under such rights might or might not be available; neither does it represent that it has made any independent effort to identify any such rights. The OpenID Foundation and the contributors to this specification make no (and hereby expressly disclaim any) warranties (express, implied, or otherwise), including implied warranties of merchantability, non-infringement, fitness for a particular purpose, or title, related to this specification, and the entire risk as to implementing this specification is assumed by the implementer. The OpenID Intellectual Property Rights policy requires contributors to offer a patent promise not to assert certain patent claims against other contributors and against implementers. The OpenID Foundation invites any interested party to bring to its attention any copyrights, patents, patent applications, or other proprietary rights that may cover technology that may be required to practice this specification.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### Authors\' Addresses

  --------- -------------------------------
            Nat Sakimura
            NAT.Consulting
  Email:    <nat@nat.consulting>
  URI:      <https://nat.sakimura.org/>
             
            John Bradley
            Yubico
  Email:    <ve7jtb@ve7jtb.com>
  URI:      <http://www.thread-safe.com/>
             
            Michael B. Jones
            Self-Issued Consulting
  Email:    <michael_b_jones@hotmail.com>
  URI:      <https://self-issued.info/>
             
            Edmund Jay
            Illumila
  Email:    <ejay@mgi1.com>
  --------- -------------------------------
