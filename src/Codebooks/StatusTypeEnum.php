<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * Status Type values for a Token Status List entry.
 *
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list#name-status-types-values
 *
 * Value 0x03 and values 0x0C to 0x0F are permanently reserved as application specific, so they are deliberately
 * absent here; all other unlisted values are reserved for future registration in the Status Types registry.
 *
 * Note that the processing rules for the Referenced Token supersede its status in the list: a token evaluated as
 * expired through its own claims stays expired even when the list reports it as Valid.
 *
 * @see \SimpleSAML\Test\OpenID\Codebooks\StatusTypeEnumTest
 */
enum StatusTypeEnum: int
{
    /** The status of the Referenced Token is valid, correct or legal. */
    case Valid = 0x00;

    /** The status of the Referenced Token is revoked, annulled, taken back, recalled or cancelled. */
    case Invalid = 0x01;

    /**
     * The status of the Referenced Token is temporarily invalid, hanging, debarred from privilege. This status
     * is usually temporary.
     */
    case Suspended = 0x02;


    /**
     * The smallest number of bits per entry a Status List needs in order to be able to represent this status.
     * A list configured with fewer bits than this can never carry the status.
     */
    public function requiredBits(): int
    {
        return match ($this) {
            self::Valid, self::Invalid => 1,
            self::Suspended => 2,
        };
    }
}
