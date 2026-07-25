<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Utils;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use OverflowException;
use Psr\Http\Message\StreamInterface;

/**
 * Sink stream that refuses to accept more than a given number of bytes.
 *
 * Used as the Guzzle "sink" request option so that an oversized response is aborted while it is still being
 * transferred. Checking the size after the response has been returned would be too late: by then the whole body
 * has already been pulled into memory or temporary storage, which is the cost being defended against.
 *
 * The sink option is honoured by both the cURL and the stream handler, so unlike the "stream" request option it
 * bounds the transfer without influencing which handler Guzzle picks.
 *
 * @see \SimpleSAML\Test\OpenID\Utils\SizeLimitedStreamTest
 */
class SizeLimitedStream implements StreamInterface
{
    use StreamDecoratorTrait;


    /**
     * Declared rather than left to the trait, which only documents it: assigning an undeclared property is
     * deprecated as of PHP 8.2.
     */
    protected StreamInterface $stream;

    protected int $writtenBytes = 0;

    protected bool $hasExceededLimit = false;

    protected int $maxSizeBytes;


    public function __construct(int $maxSizeBytes)
    {
        $this->maxSizeBytes = max(1, $maxSizeBytes);
        $this->stream = $this->buildBackingStream();
    }


    /**
     * Backing storage for a single response body. Matches what the handlers use for a sink of their own.
     */
    protected function buildBackingStream(): StreamInterface
    {
        return Utils::streamFor(Utils::tryFopen('php://temp', 'r+'));
    }


    /**
     * Begin accounting for a new response body, discarding whatever the previous one wrote.
     *
     * One sink is reused for every hop of a redirect chain, so without this the hops would be counted as a
     * single body and a shorter final response would inherit the tail of a longer earlier one.
     */
    public function startNewResponse(): void
    {
        if ($this->writtenBytes === 0) {
            return;
        }

        $this->stream = $this->buildBackingStream();
        $this->writtenBytes = 0;
    }


    public function write(string $string): int
    {
        // Handlers that rewind the sink between hops (the cURL and stream handlers both do) announce a new
        // body this way. Handlers driven by an explicit new-response signal are covered by startNewResponse().
        if ($this->writtenBytes > 0 && $this->stream->tell() === 0) {
            $this->startNewResponse();
        }

        $this->writtenBytes += strlen($string);

        if ($this->writtenBytes > $this->maxSizeBytes) {
            // Remembered separately, since the handlers wrap this exception into their own transfer failure.
            $this->hasExceededLimit = true;

            throw new OverflowException(
                sprintf('Response body size exceeded the limit of %s bytes.', $this->maxSizeBytes),
            );
        }

        return $this->stream->write($string);
    }


    /**
     * Whether the limit was ever crossed during this transfer. Deliberately not reset per redirect hop, so
     * that the caller inspecting an aborted transfer always learns the real cause.
     */
    public function hasExceededLimit(): bool
    {
        return $this->hasExceededLimit;
    }


    public function getMaxSizeBytes(): int
    {
        return $this->maxSizeBytes;
    }
}
