<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList\Factories;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\StatusList;
use Throwable;

/**
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\Factories\StatusListFactoryTest
 */
class StatusListFactory
{
    /** Fixed part of zlib's own compressBound(), being the wrapper and block framing overhead. */
    protected const ZLIB_BOUND_OVERHEAD = 13;

    /**
     * Bytes of compressed input handed to the inflater at a time.
     *
     * This is what bounds how far past the caller's maximum a hostile list can push before the size check gets a
     * chance to run: DEFLATE tops out at a ratio of 1032:1, so one chunk yields at most some 258 KiB whatever the
     * maximum happens to be. Larger chunks make that overshoot proportionally larger without being any faster --
     * measured on PHP 8.2, this is the quickest of the sizes tried as well as the tightest.
     */
    protected const INFLATE_CHUNK_BYTES = 256;


    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function build(int $bits, string $bytes, ?string $aggregationUri = null): StatusList
    {
        return new StatusList($bits, $bytes, $aggregationUri, $this->helpers);
    }


    /**
     * Builds a Status List of the given capacity with every index set to 0 (Valid).
     *
     * Capacity is rounded up to a whole number of bytes, so the resulting list may convey a status for a few more
     * indices than asked for; the specification recommends choosing a capacity divisible by 8 anyway.
     *
     * @param int $capacity Number of Referenced Tokens the list is to convey a status for.
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function forCapacity(int $capacity, int $bits, ?string $aggregationUri = null): StatusList
    {
        if ($capacity < 1) {
            throw new StatusListException(
                sprintf('Status List capacity must be at least 1, %d given.', $capacity),
            );
        }

        // Checked before the byte math below, which divides by it.
        $this->enforceAllowedBits($bits);

        return $this->build(
            $bits,
            str_repeat("\x00", intdiv($capacity * $bits + 7, 8)),
            $aggregationUri,
        );
    }


    /**
     * Builds a Status List of the given capacity from index => status pairs, leaving every index not supplied
     * at 0 (Valid).
     *
     * This is the bulk materialiser to use when reconstructing a list from stored entries: applying the immutable
     * withStatus() per entry would copy the whole byte array each time. Entries are keyed on the index they carry,
     * never on their position in the iterable, so a gap in the input leaves that index Valid rather than shifting
     * every status after it.
     *
     * @param iterable<int,int|\SimpleSAML\OpenID\Codebooks\StatusTypeEnum> $idxStatusPairs
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function fromEntries(
        iterable $idxStatusPairs,
        int $bits,
        int $capacity,
        ?string $aggregationUri = null,
    ): StatusList {
        $statusList = $this->forCapacity($capacity, $bits, $aggregationUri);

        $bytes = $statusList->getBytes();
        $listCapacity = $statusList->getCapacity();
        $entriesPerByte = intdiv(8, $bits);
        $statusMask = (1 << $bits) - 1;

        foreach ($idxStatusPairs as $idx => $status) {
            /** @phpstan-ignore function.alreadyNarrowedType (Keys can be strings at runtime, whatever is declared.) */
            if (!is_int($idx)) {
                throw new StatusListException(
                    sprintf('Status List index must be an integer, %s given.', get_debug_type($idx)),
                );
            }

            if ($idx < 0 || $idx >= $listCapacity) {
                throw new StatusListException(
                    sprintf('Index %d is out of bounds of the Status List (capacity %d).', $idx, $listCapacity),
                );
            }

            $value = $status instanceof StatusTypeEnum ? $status->value : $status;

            if ($value < 0 || $value > $statusMask) {
                throw new StatusListException(
                    sprintf(
                        'Status value %d at index %d can not be represented using %d bit(s) per Referenced Token ' .
                        '(maximum is %d).',
                        $value,
                        $idx,
                        $bits,
                        $statusMask,
                    ),
                );
            }

            $byteIndex = intdiv($idx, $entriesPerByte);
            $shift = ($idx % $entriesPerByte) * $bits;

            $bytes[$byteIndex] = chr(
                (ord($bytes[$byteIndex]) & ~($statusMask << $shift)) | ($value << $shift),
            );
        }

        return $this->build($bits, $bytes, $aggregationUri);
    }


    /**
     * Builds a Status List from the base64url-encoded compressed byte array conveyed in the `lst` member.
     *
     * Decompression is bounded by the caller: the specification sets no maximum list size, so what counts as an
     * acceptable list is a deployment decision, and an unbounded gzuncompress() on a token fetched from the network
     * is a decompression bomb waiting to happen.
     *
     * @param int $maxDecompressedBytes Largest byte array the caller is willing to decompress to. With `bits` of 1
     * this is capacity/8 bytes, with `bits` of 8 it is one byte per index.
     * @param ?int $maxCompressedBytes Largest compressed input the caller is willing to decode. Defaults to the
     * size a normally framed stream decompressing to $maxDecompressedBytes can reach. That default is a heuristic,
     * not a law: DEFLATE places no cap on how many blocks a stream may be split into, so a producer emitting a
     * flush every few bytes can stay well within $maxDecompressedBytes while exceeding it. Pass an explicit value
     * when interoperating with a producer whose framing is unknown.
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function fromEncoded(
        string $lst,
        int $bits,
        int $maxDecompressedBytes,
        ?int $maxCompressedBytes = null,
        ?string $aggregationUri = null,
    ): StatusList {
        if ($maxDecompressedBytes < 1) {
            throw new StatusListException(
                sprintf('Maximum decompressed size must be at least 1 byte, %d given.', $maxDecompressedBytes),
            );
        }

        $maxCompressedBytes ??= $this->maxCompressedBytesFor($maxDecompressedBytes);

        if ($maxCompressedBytes < 1) {
            throw new StatusListException(
                sprintf('Maximum compressed size must be at least 1 byte, %d given.', $maxCompressedBytes),
            );
        }

        // Unpadded base64url only. Padding, the standard base64 alphabet and embedded whitespace are all rejected
        // rather than normalised, so that a list only ever has the one encoding the specification defines.
        // Anchored with \z rather than $, which would also match immediately before a trailing newline.
        if (preg_match('/^[A-Za-z0-9_-]+\z/', $lst) !== 1) {
            throw new StatusListException('Status List is not encoded as unpadded base64url.');
        }

        // Bound the input before decoding it, so an oversized list costs a strlen() rather than an allocation.
        $maxEncodedLength = intdiv($maxCompressedBytes + 2, 3) * 4;

        if (strlen($lst) > $maxEncodedLength) {
            throw new StatusListException(
                sprintf(
                    'Encoded Status List is %d characters long, which exceeds the maximum of %d.',
                    strlen($lst),
                    $maxEncodedLength,
                ),
            );
        }

        try {
            $compressed = $this->helpers->base64Url()->decode($lst);
        } catch (Throwable $throwable) {
            // @codeCoverageIgnoreStart
            // Not reachable while the alphabet check above stands, and kept so that a decoding failure surfaces
            // as this library's own exception rather than as whatever the helper happens to throw.
            throw new StatusListException(
                'Unable to base64url-decode the Status List.',
                (int)$throwable->getCode(),
                $throwable,
            );
            // @codeCoverageIgnoreEnd
        }

        // The alphabet check above does not catch a final quantum whose unused bits are not zero: base64_decode()
        // ignores them, so two different strings would decode to the same bytes. Re-encoding and comparing is what
        // holds the encoding to the single canonical form promised above.
        if ($this->helpers->base64Url()->encode($compressed) !== $lst) {
            throw new StatusListException(
                'Status List is not canonically encoded as unpadded base64url.',
            );
        }

        if (strlen($compressed) > $maxCompressedBytes) {
            throw new StatusListException(
                sprintf(
                    'Compressed Status List is %d bytes long, which exceeds the maximum of %d. An unusually ' .
                    'framed but otherwise acceptable list can exceed the derived default, in which case pass an ' .
                    'explicit maximum compressed size.',
                    strlen($compressed),
                    $maxCompressedBytes,
                ),
            );
        }

        return $this->build($bits, $this->inflate($compressed, $maxDecompressedBytes), $aggregationUri);
    }


    /**
     * Decompresses the Status List, enforcing both the caller's size bound and that the input is exactly one
     * complete ZLIB stream and nothing else.
     *
     * Done as one streaming pass rather than gzuncompress() followed by a separate check. The stream has to be
     * inflated to learn where it ends, and inflating twice would hold two full copies of a list that the caller
     * has already sized its bound around -- so a maximum picked to fit one decoded list could exhaust memory on
     * the second copy, which is precisely the outcome the bound exists to prevent.
     *
     * Trailing bytes matter because a stream stops where it stops and says nothing about what follows: padding,
     * or a second stream entirely, would otherwise decode to the same statuses as the clean list. That is the
     * same encoding ambiguity the base64url canonicality check exists to rule out.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    protected function inflate(string $compressed, int $maxDecompressedBytes): string
    {
        $context = inflate_init(ZLIB_ENCODING_DEFLATE);

        if ($context === false) {
            // @codeCoverageIgnoreStart
            throw new StatusListException('Unable to initialise decompression of the Status List.');
            // @codeCoverageIgnoreEnd
        }

        $bytes = '';
        $offset = 0;
        $compressedLength = strlen($compressed);

        while ($offset < $compressedLength) {
            // Silenced deliberately: inflate_add() raises a warning for a malformed stream, which is reported
            // through the exception below instead.
            $chunk = @inflate_add(
                $context,
                substr($compressed, $offset, self::INFLATE_CHUNK_BYTES),
                ZLIB_NO_FLUSH,
            );

            if ($chunk === false) {
                throw new StatusListException('Unable to decompress the Status List.');
            }

            $bytes .= $chunk;
            $offset += self::INFLATE_CHUNK_BYTES;

            if (strlen($bytes) > $maxDecompressedBytes) {
                throw new StatusListException(
                    sprintf('Status List decompresses to more than %d bytes.', $maxDecompressedBytes),
                );
            }

            if (inflate_get_status($context) === ZLIB_STREAM_END) {
                break;
            }
        }

        // A stream that never ended is a truncated one. inflate_add() reports no error for it, since as far as it
        // knows more input was simply still to come.
        if (inflate_get_status($context) !== ZLIB_STREAM_END) {
            throw new StatusListException('Status List does not hold a complete ZLIB stream.');
        }

        $readLength = inflate_get_read_len($context);

        if ($readLength !== $compressedLength) {
            throw new StatusListException(
                sprintf(
                    'Compressed Status List carries %d trailing byte(s) after the end of its ZLIB stream.',
                    $compressedLength - $readLength,
                ),
            );
        }

        return $bytes;
    }


    /**
     * Builds a Status List from the members of a `status_list` claim.
     *
     * @param array<string,mixed> $claimData
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function fromClaimData(
        array $claimData,
        int $maxDecompressedBytes,
        ?int $maxCompressedBytes = null,
    ): StatusList {
        $bits = $claimData[ClaimsEnum::Bits->value] ?? null;

        // A JSON Integer, so a numeric string is not acceptable here.
        if (!is_int($bits)) {
            throw new StatusListException(
                sprintf('Status List bits claim must be an integer, %s given.', get_debug_type($bits)),
            );
        }

        $lst = $this->helpers->type()->ensureNonEmptyString(
            $claimData[ClaimsEnum::Lst->value] ?? null,
            ClaimsEnum::Lst->value,
        );

        $aggregationUri = $claimData[ClaimsEnum::AggregationUri->value] ?? null;

        if ($aggregationUri !== null) {
            $aggregationUri = $this->helpers->type()->ensureNonEmptyString(
                $aggregationUri,
                ClaimsEnum::AggregationUri->value,
            );
        }

        return $this->fromEncoded($lst, $bits, $maxDecompressedBytes, $maxCompressedBytes, $aggregationUri);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     */
    protected function enforceAllowedBits(int $bits): void
    {
        if (!StatusList::isAllowedBits($bits)) {
            throw new StatusListException(
                sprintf(
                    'Invalid number of bits per Referenced Token (%d), expected one of: %s.',
                    $bits,
                    implode(', ', StatusList::ALLOWED_BITS),
                ),
            );
        }
    }


    /**
     * Largest a ZLIB stream decompressing to the given number of bytes is expected to be. DEFLATE falls back to
     * stored blocks for incompressible input, which is where a stream ends up longer than what it encodes.
     *
     * This is zlib's own compressBound() calculation rather than a hand-derived one, so that the bound is never
     * tighter than what the compressor on the other side is entitled to emit -- including the compressor in this
     * very library, whose output has to survive a round trip through fromEncoded().
     */
    protected function maxCompressedBytesFor(int $decompressedBytes): int
    {
        return $decompressedBytes
        + ($decompressedBytes >> 12)
        + ($decompressedBytes >> 14)
        + ($decompressedBytes >> 25)
        + self::ZLIB_BOUND_OVERHEAD;
    }
}
