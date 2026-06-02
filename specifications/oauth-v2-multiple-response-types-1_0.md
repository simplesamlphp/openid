---
description: OAuth 2.0 Multiple Response Type Encoding Practices
generator: "xml2rfc v1.36 (http://xml.resource.org/)"
lang: en
title: "Final: OAuth 2.0 Multiple Response Type Encoding Practices"
---

  ---------------
  [ TOC ](#toc)
  ---------------

+-----------------------------------------------------------------------+
|   ------- ---------------------                                       |
|   Final   B. de Medeiros, Ed.                                         |
|           M. Scurtescu                                                |
|           Google                                                      |
|           P. Tarjan                                                   |
|           Facebook                                                    |
|           M. Jones                                                    |
|           Microsoft                                                   |
|           February 25, 2014                                           |
|   ------- ---------------------                                       |
+-----------------------------------------------------------------------+

#  OAuth 2.0 Multiple Response Type Encoding Practices

### Abstract

This specification provides guidance on the proper encoding of responses to OAuth 2.0 Authorization Requests in which the request uses a Response Type value that includes space characters. Furthermore, this specification registers several new Response Type values in the OAuth Authorization Endpoint Response Types registry.

This specification also defines a Response Mode Authorization Request parameter that informs the Authorization Server of the mechanism to be used for returning Authorization Response parameters from the Authorization Endpoint.

\

------------------------------------------------------------------------

### Table of Contents

[1.](#Introduction)  Introduction\
    [1.1.](#rnc)  Requirements Notation and Conventions\
    [1.2.](#Terminology)  Terminology\
[2.](#ResponseTypesAndModes)  Response Types and Response Modes\
    [2.1.](#ResponseModes)  Response Modes\
    [2.2.](#MultiValueResponseTypes)  Multiple-Valued Response Types\
[3.](#id_token)  ID Token Response Type\
[4.](#none)  None Response Type\
[5.](#Combinations)  Definitions of Multiple-Valued Response Type Combinations\
[6.](#IANA)  IANA Considerations\
    [6.1.](#OAuthResponseTypesReg)  OAuth Authorization Endpoint Response Types Registration\
        [6.1.1.](#RegistryContents)  Registry Contents\
    [6.2.](#OAuthParametersRegistry)  OAuth Parameters Registration\
        [6.2.1.](#ParametersContents)  Registry Contents\
[7.](#Security)  Security Considerations\
[8.](#rfc.references1)  References\
    [8.1.](#rfc.references1)  Normative References\
    [8.2.](#rfc.references2)  Informative References\
[Appendix A.](#FragmentExample)  Example using Multiple-Valued Response Type\
[Appendix B.](#Acknowledgements)  Acknowledgements\
[Appendix C.](#Notices)  Notices\
[§](#rfc.authors)  Authors\' Addresses\

\
\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 1.  Introduction

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 1.1.  Requirements Notation and Conventions

The key words \"MUST\", \"MUST NOT\", \"REQUIRED\", \"SHALL\", \"SHALL NOT\", \"SHOULD\", \"SHOULD NOT\", \"RECOMMENDED\", \"MAY\", and \"OPTIONAL\" in this document are to be interpreted as described in [RFC 2119 (Bradner, S., "Key words for use in RFCs to Indicate Requirement Levels," March 1997.)](#RFC2119) \[RFC2119\].

In the .txt version of this document, values are quoted to indicate that they are to be taken literally. When using these values in protocol messages, the quotes MUST NOT be used as part of the value. In the HTML version of this document, values to be taken literally are indicated by the use of `this fixed-width font`.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 1.2.  Terminology

This specification uses the terms \"Access Token\", \"Authorization Code\", \"Authorization Endpoint\", \"Authorization Grant\", \"Authorization Server\", \"Client\", \"Client Identifier\", \"Client Secret\", \"Protected Resource\", \"Redirection URI\", \"Refresh Token\", \"Resource Owner\", \"Resource Server\", \"Response Type\", and \"Token Endpoint\" defined by [OAuth 2.0 (Hardt, D., "The OAuth 2.0 Authorization Framework," October 2012.)](#RFC6749) \[RFC6749\] and the term \"User Agent\" defined by [RFC 2616 (Fielding, R., Gettys, J., Mogul, J., Frystyk, H., Masinter, L., Leach, P., and T. Berners-Lee, "Hypertext Transfer Protocol \-- HTTP/1.1," June 1999.)](#RFC2616) \[RFC2616\]. This specification also defines the following terms:

> Multiple-Valued Response Types
> :   The OAuth 2.0 specification allows for registration of space-separated `response_type` parameter values. If a Response Type contains one of more space characters (%20), it is compared as a space-delimited list of values in which the order of values does not matter.
>
> Response Mode
> :   The Response Mode determines how the Authorization Server returns result parameters from the Authorization Endpoint. Non-default modes are specified using the `response_mode` request parameter. If `response_mode` is not present in a request, the default Response Mode mechanism specified by the Response Type is used.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.  Response Types and Response Modes

The Response Type request parameter `response_type` informs the Authorization Server of the desired authorization processing flow, including what parameters are returned from the endpoints used. The Response Mode request parameter `response_mode` informs the Authorization Server of the mechanism to be used for returning Authorization Response parameters from the Authorization Endpoint. Each Response Type value also defines a default Response Mode mechanism to be used, if no Response Mode is specified using the request parameter.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.1.  Response Modes

This specification defines the following OAuth Authorization Request parameter:

> response_mode
> :   OPTIONAL. Informs the Authorization Server of the mechanism to be used for returning Authorization Response parameters from the Authorization Endpoint. This use of this parameter is NOT RECOMMENDED with a value that specifies the same Response Mode as the default Response Mode for the Response Type used.

This specification defines the following Response Modes, which are described with their `response_mode` parameter values:

> query
> :   In this mode, Authorization Response parameters are encoded in the query string added to the `redirect_uri` when redirecting back to the Client.
>
> fragment
> :   In this mode, Authorization Response parameters are encoded in the fragment added to the `redirect_uri` when redirecting back to the Client.

For purposes of this specification, the default Response Mode for the OAuth 2.0 `code` Response Type is the `query` encoding. For purposes of this specification, the default Response Mode for the OAuth 2.0 `token` Response Type is the `fragment` encoding.

See [OAuth 2.0 Form Post Response Mode (Jones, M. and B. Campbell, "OAuth 2.0 Form Post Response Mode," February 2014.)](#OAuth.Post) \[OAuth.Post\] for an example of a specification that defines an additional Response Mode. Note that it is expected that additional Response Modes may be defined by other specifications in the future, including possibly ones utilizing the HTML5 postMessage API and Cross Origin Resource Sharing (CORS).

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 2.2.  Multiple-Valued Response Types

When a multiple-valued Response Type is defined, it is RECOMMENDED that the following encoding rules be applied for the issued response from the Authorization Endpoint.

All parameters returned from the Authorization Endpoint SHOULD use the same Response Mode. This recommendation applies to both success and error responses.

Rationale: This significantly simplifies Client parameter processing. It also can have positive performance benefits, as described below.

For instance, if a response includes fragment encoded parts, a User Agent Client component must be involved to complete processing of the response. If a new query parameter is added to the Client URI, it will cause the User Agent to re-fetch the Client URI, causing discontinuity of operation of the User Agent based Client components. If only fragment encoding is used, the User Agent will simply reactivate the Client component, which can then process the fragment and also convey any parameters to a Client host as necessary, e.g., via XmlHttpRequest. Therefore, full fragment encoding always results in lower latency for response processing.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 3.  ID Token Response Type

This section registers a new Response Type, the `id_token`, in accordance with the stipulations in the OAuth 2.0 specification, Section 8.4. The intended purpose of the `id_token` is that it MUST provide an assertion of the identity of the Resource Owner as understood by the Authorization Server. The assertion MUST specify a targeted audience, e.g. the requesting Client. However, the specific semantics of the assertion and how it can be validated are not specified in this document.

> id_token
> :   When supplied as the `response_type` parameter in an OAuth 2.0 Authorization Request, a successful response MUST include the parameter `id_token`. The Authorization Server SHOULD NOT return an OAuth 2.0 Authorization Code, Access Token, or Access Token Type in a successful response to the grant request. If a `redirect_uri` is supplied, the User Agent SHOULD be redirected there after granting or denying access. The request MAY include a `state` parameter, and if so, the Authorization Server MUST echo its value as a response parameter when issuing either a successful response or an error response. The default Response Mode for this Response Type is the fragment encoding and the query encoding MUST NOT be used. Both successful and error responses SHOULD be returned using the supplied Response Mode, or if none is supplied, using the default Response Mode.

Returning the `id_token` in a fragment reduces the likelihood that the `id_token` leaks during transport and mitigates the associated risks to the privacy of the user (Resource Owner).

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 4.  None Response Type

This section registers the Response Type `none`, in accordance with the stipulations in the OAuth 2.0 specification, Section 8.4. The intended purpose is to enable use cases where a party requests the Authorization Server to register a grant of access to a Protected Resource on behalf of a Client but requires no access credentials to be returned to the Client at that time. The means by which the Client eventually obtains the access credentials is left unspecified here.

One scenario is where a user wishes to purchase an application from a market, and desires to authorize application installation and grant the application access to Protected Resources in a single step. However, since the user is not presently interacting with the (not yet active) application, it is not appropriate to return access credentials simultaneously in the authorization step.

> none
> :   When supplied as the `response_type` parameter in an OAuth 2.0 Authorization Request, the Authorization Server SHOULD NOT return an OAuth 2.0 Authorization Code, Access Token, Access Token Type, or ID Token in a successful response to the grant request. If a `redirect_uri` is supplied, the User Agent SHOULD be redirected there after granting or denying access. The request MAY include a `state` parameter, and if so, the Authorization Server MUST echo its value as a response parameter when issuing either a successful response or an error response. The default Response Mode for this Response Type is the query encoding. Both successful and error responses SHOULD be returned using the supplied Response Mode, or if none is supplied, using the default Response Mode.

The Response Type `none` SHOULD NOT be combined with other Response Types.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 5.  Definitions of Multiple-Valued Response Type Combinations

This section defines combinations of the values `code`, `token`, and `id_token`, which are each individually registered Response Types.

> code token
> :   When supplied as the value for the `response_type` parameter, a successful response MUST include an Access Token, an Access Token Type, and an Authorization Code. The default Response Mode for this Response Type is the fragment encoding and the query encoding MUST NOT be used. Both successful and error responses SHOULD be returned using the supplied Response Mode, or if none is supplied, using the default Response Mode.
>
> code id_token
> :   When supplied as the value for the `response_type` parameter, a successful response MUST include both an Authorization Code and an `id_token`. The default Response Mode for this Response Type is the fragment encoding and the query encoding MUST NOT be used. Both successful and error responses SHOULD be returned using the supplied Response Mode, or if none is supplied, using the default Response Mode.
>
> id_token token
> :   When supplied as the value for the `response_type` parameter, a successful response MUST include an Access Token, an Access Token Type, and an `id_token`. The default Response Mode for this Response Type is the fragment encoding and the query encoding MUST NOT be used. Both successful and error responses SHOULD be returned using the supplied Response Mode, or if none is supplied, using the default Response Mode.
>
> code id_token token
> :   When supplied as the value for the `response_type` parameter, a successful response MUST include an Authorization Code, an `id_token`, an Access Token, and an Access Token Type. The default Response Mode for this Response Type is the fragment encoding and the query encoding MUST NOT be used. Both successful and error responses SHOULD be returned using the supplied Response Mode, or if none is supplied, using the default Response Mode.

For all these Response Types, the request MAY include a `state` parameter, and if so, the Authorization Server MUST echo its value as a response parameter when issuing either a successful response or an error response.

A non-normative request/response example as issued/received by the User Agent (with extra line breaks for display purposes only) is:

      GET /authorize?
        response_type=id_token%20token
        &client_id=s6BhdRkqt3
        &redirect_uri=https%3A%2F%2Fclient.example.org%2Fcb
        &state=af0ifjsldkj HTTP/1.1
      Host: server.example.com

      HTTP/1.1 302 Found
      Location: https://client.example.org/cb#
      access_token=SlAV32hkKG
      &token_type=bearer
      &id_token=eyJ0 ... NiJ9.eyJ1c ... I6IjIifX0.DeWt4Qu ... ZXso
      &expires_in=3600
      &state=af0ifjsldkj

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 6.  IANA Considerations

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 6.1.  OAuth Authorization Endpoint Response Types Registration

This specification registers the `response_type` values defined by this specification in the IANA OAuth Authorization Endpoint Response Types registry defined in [RFC 6749 (Hardt, D., "The OAuth 2.0 Authorization Framework," October 2012.)](#RFC6749) \[RFC6749\].

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 6.1.1.  Registry Contents

-   Response Type Name: `id_token`
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html

&nbsp;

-   Response Type Name: `none`
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html

&nbsp;

-   Response Type Name: `code token`
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html

&nbsp;

-   Response Type Name: `code id_token`
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html

&nbsp;

-   Response Type Name: `id_token token`
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html

&nbsp;

-   Response Type Name: `code id_token token`
-   Change Controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification Document(s): http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 6.2.  OAuth Parameters Registration

This specification registers the following parameter in the IANA OAuth Parameters registry defined in [RFC 6749 (Hardt, D., "The OAuth 2.0 Authorization Framework," October 2012.)](#RFC6749) \[RFC6749\].

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 6.2.1.  Registry Contents

-   Parameter name: `response_mode`
-   Parameter usage location: Authorization Request
-   Change controller: OpenID Foundation Artifact Binding Working Group - openid-specs-ab@lists.openid.net
-   Specification document(s): [Section 2.1 (Response Modes)](#ResponseModes) of this document
-   Related information: None

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 7.  Security Considerations

There are security implications to encoding response values in the query string. The HTTP Referer header includes query parameters, and so any values encoded in query parameters will leak to third parties. Thus, while it is safe to encode an Authorization Code as a query parameter when using a Confidential Client (because it can\'t be used without the Client Secret, which third parties won\'t have), more sensitive information such as Access Tokens and ID Tokens MUST NOT be encoded in the query string. In no case should a set of Authorization Response parameters whose default Response Mode is the fragment encoding be encoded using the query encoding.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 8.  References

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 8.1. Normative References

  ------------- -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  \[RFC2119\]   [Bradner, S.](mailto:sob@harvard.edu), "[Key words for use in RFCs to Indicate Requirement Levels](http://tools.ietf.org/html/rfc2119)," BCP 14, RFC 2119, March 1997 ([TXT](http://www.rfc-editor.org/rfc/rfc2119.txt), [HTML](http://xml.resource.org/public/rfc/html/rfc2119.html), [XML](http://xml.resource.org/public/rfc/xml/rfc2119.xml)).
  \[RFC2616\]   [Fielding, R.](mailto:fielding@ics.uci.edu), [Gettys, J.](mailto:jg@w3.org), [Mogul, J.](mailto:mogul@wrl.dec.com), [Frystyk, H.](mailto:frystyk@w3.org), [Masinter, L.](mailto:masinter@parc.xerox.com), [Leach, P.](mailto:paulle@microsoft.com), and [T. Berners-Lee](mailto:timbl@w3.org), "[Hypertext Transfer Protocol \-- HTTP/1.1](http://tools.ietf.org/html/rfc2616)," RFC 2616, June 1999 ([TXT](http://www.rfc-editor.org/rfc/rfc2616.txt), [PS](http://www.rfc-editor.org/rfc/rfc2616.ps), [PDF](http://www.rfc-editor.org/rfc/rfc2616.pdf), [HTML](http://xml.resource.org/public/rfc/html/rfc2616.html), [XML](http://xml.resource.org/public/rfc/xml/rfc2616.xml)).
  \[RFC6749\]   Hardt, D., "[The OAuth 2.0 Authorization Framework](http://tools.ietf.org/html/rfc6749)," RFC 6749, October 2012 ([TXT](http://www.rfc-editor.org/rfc/rfc6749.txt)).
  ------------- -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### 8.2. Informative References

  ---------------- -----------------------------------------------------------------------------------------------------------------------------------------------------
  \[OAuth.Post\]   Jones, M. and B. Campbell, "[OAuth 2.0 Form Post Response Mode](http://openid.net/specs/oauth-v2-form-post-response-mode-1_0.html)," February 2014.
  ---------------- -----------------------------------------------------------------------------------------------------------------------------------------------------

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### Appendix A.  Example using Multiple-Valued Response Type

The following is non-normative example of an Authorization Request using the Multiple-Valued Response Type `code token` and a resulting Authorization Response. The response uses the `fragment` Response Mode, which is the default for this Response Type. Line wraps within values are for display purposes only.

      GET /authorize?
        response_type=code%20token
        &client_id=s6BhdRkqt3
        &redirect_uri=https%3A%2F%2Fclient.example.org%2Fcb
        &state=af0ifjsldkj HTTP/1.1
      Host: server.example.com

      HTTP/1.1 302 Found
      Location: https://client.example.org/cb#
        access_token=2YotnFZFEjr1zCsicMWpAA
        &token_type=Bearer
        &code=SplxlOBeZQQYbYS6WxSbIA
        &state=af0ifjsldkj
        &expires_in=3600

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### Appendix B.  Acknowledgements

The OpenID Community would like to thank the following people for their contributions to this specification:

> Naveen Agarwal (naa@google.com), Google
>
> John Bradley (ve7jtb@ve7jtb.com), Ping Identity
>
> Brian Campbell (bcampbell@pingidentity.com), Ping Identity
>
> George Fletcher (george.fletcher@corp.aol.com), AOL
>
> Michael B. Jones (mbj@microsoft.com), Microsoft
>
> Torsten Lodderstedt (t.lodderstedt@telekom.de), Deutsche Telekom
>
> Breno de Medeiros (breno@google.com), Google
>
> Nat Sakimura (n-sakimura@nri.co.jp), Nomura Research Institute, Ltd.
>
> David Recordon (dr@fb.com), Facebook
>
> Marius Scurtescu (mscurtescu@google.com), Google
>
> Paul Tarjan (pt@fb.com), Facebook

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### Appendix C.  Notices

Copyright (c) 2014 The OpenID Foundation.

The OpenID Foundation (OIDF) grants to any Contributor, developer, implementer, or other interested party a non-exclusive, royalty free, worldwide copyright license to reproduce, prepare derivative works from, distribute, perform and display, this Implementers Draft or Final Specification solely for the purposes of (i) developing specifications, and (ii) implementing Implementers Drafts and Final Specifications based on such documents, provided that attribution be made to the OIDF as the source of the material, but that such attribution does not indicate an endorsement by the OIDF.

The technology described in this specification was made available from contributions from various sources, including members of the OpenID Foundation and others. Although the OpenID Foundation has taken steps to help ensure that the technology is available for distribution, it takes no position regarding the validity or scope of any intellectual property or other rights that might be claimed to pertain to the implementation or use of the technology described in this specification or the extent to which any license under such rights might or might not be available; neither does it represent that it has made any independent effort to identify any such rights. The OpenID Foundation and the contributors to this specification make no (and hereby expressly disclaim any) warranties (express, implied, or otherwise), including implied warranties of merchantability, non-infringement, fitness for a particular purpose, or title, related to this specification, and the entire risk as to implementing this specification is assumed by the implementer. The OpenID Intellectual Property Rights policy requires contributors to offer a patent promise not to assert certain patent claims against other contributors and against implementers. The OpenID Foundation invites any interested party to bring to its attention any copyrights, patents, patent applications, or other proprietary rights that may cover technology that may be required to practice this specification.

\

------------------------------------------------------------------------

  ---------------
  [ TOC ](#toc)
  ---------------

### Authors\' Addresses

  --------- -----------------------------------------------
            Breno de Medeiros (editor)
            Google
  Email:    <breno@google.com>
  URI:      <http://stackoverflow.com/users/311376/breno>
             
            Marius Scurtescu
            Google
  Email:    <mscurtescu@google.com>
  URI:      <https://twitter.com/mscurtescu>
             
            Paul Tarjan
            Facebook
  Email:    <pt@fb.com>
             
            Michael B. Jones
            Microsoft
  Email:    <mbj@microsoft.com>
  URI:      <http://self-issued.info/>
  --------- -----------------------------------------------
