<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Exceptions;

/**
 * The value could not be read as a JWS in any serialization the library is set up for. For the compact
 * serialization: not three dot-separated parts, a part that does not decode as base64url, or a protected header
 * that does not decode as JSON.
 *
 * A JwsException, so whatever catches that still catches this. It is thrown by parsing alone, before anything
 * is read out of the token, which lets a caller tell "not a JWS at all" apart from "a JWS which fails a check"
 * (an expired one, one with a malformed claim), which is a plain JwsException or an InvalidValueException.
 */
class JwsParseException extends JwsException
{
}
