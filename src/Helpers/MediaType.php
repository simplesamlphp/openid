<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

/**
 * @see \SimpleSAML\Test\OpenID\Helpers\MediaTypeTest
 */
class MediaType
{
    /**
     * The media type a JWS "typ" header value names, in the form a recipient is to compare it in.
     *
     * RFC 7515 section 4.1.9 has the header carry a media type, shortened and compared under two rules. "To keep
     * messages compact in common situations, it is RECOMMENDED that producers omit an "application/" prefix of a
     * media type value in a "typ" Header Parameter when no other '/' appears in the media type value. A recipient
     * using the media type value MUST treat it as if "application/" were prepended to any "typ" value not
     * containing a '/'." And: "Per RFC 2045 [RFC2045], all media type values, subtype values, and parameter names
     * are case insensitive. However, parameter values are case sensitive unless otherwise specified for the
     * specific parameter." So "at+jwt", "AT+JWT" and "application/at+jwt" all come out as "application/at+jwt",
     * and "jwt;V=1" as "application/jwt;v=1".
     *
     * Parameter values are returned as given, a quoted one included; a value with parameters therefore never
     * equals the parameterless media type a JWT type registers, which is the outcome a recipient wants, since it
     * is a different media type. An empty value names no media type and stays empty.
     */
    public function normalizeJwtType(string $typ): string
    {
        if ($typ === '') {
            return '';
        }

        if (!str_contains($typ, '/')) {
            $typ = 'application/' . $typ;
        }

        $parametersStart = strpos($typ, ';');

        if ($parametersStart === false) {
            return strtolower($typ);
        }

        return strtolower(substr($typ, 0, $parametersStart)) .
        $this->normalizeParameterNames(substr($typ, $parametersStart));
    }


    /**
     * Whether two JWS "typ" header values name the same media type, so "at+jwt" and "application/AT+JWT" do,
     * while "at+jwt" and "at+jwt;v=1" do not.
     */
    public function areJwtTypesEqual(string $typ, string $otherTyp): bool
    {
        return $this->normalizeJwtType($typ) === $this->normalizeJwtType($otherTyp);
    }


    /**
     * Lower-cases the parameter names in a ";name=value;name=value" suffix and leaves the values as given. A
     * quoted value may itself contain ';' or '=', so quotes are tracked rather than the suffix split on ';'.
     */
    protected function normalizeParameterNames(string $parameters): string
    {
        $normalized = '';
        $inQuotes = false;
        $inName = false;
        $length = strlen($parameters);

        for ($i = 0; $i < $length; ++$i) {
            $char = $parameters[$i];

            if ($inQuotes) {
                $normalized .= $char;

                if ($char === '\\' && $i + 1 < $length) {
                    $normalized .= $parameters[++$i];
                } elseif ($char === '"') {
                    $inQuotes = false;
                }

                continue;
            }

            if ($char === '"') {
                $inQuotes = true;
                $inName = false;
            } elseif ($char === ';') {
                $inName = true;
            } elseif ($char === '=') {
                $inName = false;
            }

            $normalized .= $inName ? strtolower($char) : $char;
        }

        return $normalized;
    }
}
