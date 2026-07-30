<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;

/**
 * Status List data structure, holding the status of many Referenced Tokens, one per index.
 *
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list#name-status-list
 *
 * Statuses are packed into a byte array, least significant bit upward: with a `bits` of 2, index 0 occupies bits
 * 0-1 of byte 0, index 1 bits 2-3, index 4 bits 0-1 of byte 1, and so on. Instances are immutable; use
 * \SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory to build them from a capacity, from entries or
 * from an encoded list.
 *
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\StatusListTest
 */
class StatusList implements JsonSerializable
{
    /**
     * Numbers of bits per Referenced Token the specification allows. The limitation keeps every operation within
     * a single byte.
     */
    public const ALLOWED_BITS = [1, 2, 4, 8];

    /** The specification RECOMMENDS the highest compression level available. */
    public const COMPRESSION_LEVEL = 9;


    /** Number of statuses stored in each byte of the array. */
    protected readonly int $entriesPerByte;

    /** Bitmask covering a single status, so (1 << bits) - 1. Doubles as the largest representable status value. */
    protected readonly int $statusMask;

    protected readonly int $capacity;


    /**
     * @param int $bits Number of bits per Referenced Token, one of self::ALLOWED_BITS.
     * @param string $bytes Uncompressed status byte array. Its length fixes the capacity of the list, so a list
     * always holds a whole number of bytes worth of entries (8, 4, 2 or 1 per byte, depending on $bits).
     * @param ?string $aggregationUri URI to retrieve the Status List Aggregation for this type of Referenced Token
     * or Issuer, if the Status Issuer publishes one.
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function __construct(
        protected readonly int $bits,
        protected readonly string $bytes,
        protected readonly ?string $aggregationUri = null,
        protected readonly Helpers $helpers = new Helpers(),
    ) {
        if (!self::isAllowedBits($this->bits)) {
            throw new StatusListException(
                sprintf(
                    'Invalid number of bits per Referenced Token (%d), expected one of: %s.',
                    $this->bits,
                    implode(', ', self::ALLOWED_BITS),
                ),
            );
        }

        if ($this->bytes === '') {
            throw new StatusListException('Status List byte array must convey the status of at least one index.');
        }

        if ($this->aggregationUri !== null) {
            $this->helpers->type()->enforceUri($this->aggregationUri, ClaimsEnum::AggregationUri->value);
        }

        $this->entriesPerByte = intdiv(8, $this->bits);
        $this->statusMask = (1 << $this->bits) - 1;
        $this->capacity = strlen($this->bytes) * $this->entriesPerByte;
    }


    public static function isAllowedBits(int $bits): bool
    {
        return in_array($bits, self::ALLOWED_BITS, true);
    }


    public function getBits(): int
    {
        return $this->bits;
    }


    /**
     * Uncompressed status byte array.
     */
    public function getBytes(): string
    {
        return $this->bytes;
    }


    /**
     * Number of indices this list conveys a status for, so one greater than the largest valid index.
     */
    public function getCapacity(): int
    {
        return $this->capacity;
    }


    public function getAggregationUri(): ?string
    {
        return $this->aggregationUri;
    }


    /**
     * Raw status value stored at the given index.
     *
     * An index outside the list is not a status of Valid: no statement about the Referenced Token can be made,
     * and the specification requires the Relying Party to reject it, so this throws instead of returning a value.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    public function get(int $idx): int
    {
        $this->enforceIndexInRange($idx);

        return (ord($this->bytes[intdiv($idx, $this->entriesPerByte)]) >> $this->shiftFor($idx)) & $this->statusMask;
    }


    /**
     * Status Type stored at the given index, or null if the value is not one registered in this library (an
     * application specific or a not yet registered value). Callers which treat any non-Valid value as a rejection
     * should use get() and compare numerically instead.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    public function getStatusType(int $idx): ?StatusTypeEnum
    {
        return StatusTypeEnum::tryFrom($this->get($idx));
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function withStatus(int $idx, int|StatusTypeEnum $status): self
    {
        $this->enforceIndexInRange($idx);
        $value = $this->enforceStatusFits($status);

        $bytes = $this->bytes;
        $byteIndex = intdiv($idx, $this->entriesPerByte);
        $shift = $this->shiftFor($idx);

        $bytes[$byteIndex] = chr(
            (ord($bytes[$byteIndex]) & ~($this->statusMask << $shift)) | ($value << $shift),
        );

        return new self($this->bits, $bytes, $this->aggregationUri, $this->helpers);
    }


    /**
     * Compressed byte array in its base64url-encoded form, as conveyed in the `lst` member.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    public function toEncoded(): string
    {
        $compressed = gzcompress($this->bytes, self::COMPRESSION_LEVEL);

        if ($compressed === false) {
            // @codeCoverageIgnoreStart
            // Not reachable through this class: the compression level is fixed and valid, and the byte array is
            // never empty, so the only remaining cause would be an allocation failure.
            throw new StatusListException('Unable to compress the Status List byte array.');
            // @codeCoverageIgnoreEnd
        }

        return $this->helpers->base64Url()->encode($compressed);
    }


    /**
     * @return array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    public function jsonSerialize(): array
    {
        $data = [
            ClaimsEnum::Bits->value => $this->bits,
            ClaimsEnum::Lst->value => $this->toEncoded(),
        ];

        if ($this->aggregationUri !== null) {
            $data[ClaimsEnum::AggregationUri->value] = $this->aggregationUri;
        }

        return $data;
    }


    /**
     * Number of positions the status at the given index is shifted by within its byte.
     */
    protected function shiftFor(int $idx): int
    {
        return ($idx % $this->entriesPerByte) * $this->bits;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    protected function enforceIndexInRange(int $idx): void
    {
        if ($idx < 0 || $idx >= $this->capacity) {
            throw new StatusListException(
                sprintf('Index %d is out of bounds of the Status List (capacity %d).', $idx, $this->capacity),
            );
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    protected function enforceStatusFits(int|StatusTypeEnum $status): int
    {
        $value = $status instanceof StatusTypeEnum ? $status->value : $status;

        if ($value < 0 || $value > $this->statusMask) {
            throw new StatusListException(
                sprintf(
                    'Status value %d can not be represented using %d bit(s) per Referenced Token (maximum is %d).',
                    $value,
                    $this->bits,
                    $this->statusMask,
                ),
            );
        }

        return $value;
    }
}
