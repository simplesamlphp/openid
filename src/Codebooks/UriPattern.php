<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * Patterns that decide whether an untrusted value is a URI this library will carry.
 *
 * Both end in $D rather than $. Without the D, PCRE lets $ match immediately before a trailing newline, so a
 * value ending in one passes validation and keeps the newline. These patterns guard `iss` on ID and Logout
 * Tokens, the `uri` of a Status Reference and the `aggregation_uri` of a Status List - both of which get
 * fetched - and the `id` of an issued credential, so a newline surviving here reaches a request, a log line
 * and a credential.
 */
enum UriPattern: string
{
    case HttpNoQueryNoFragment = '/^http(s?):\/\/[^\s\/$.?#][^\s?#]*$/Di';

    case Uri = '/^[a-zA-Z][a-zA-Z0-9+.-]*:\S*$/Di';
}
