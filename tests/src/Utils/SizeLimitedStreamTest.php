<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Utils;

use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Utils\SizeLimitedStream;

#[CoversClass(SizeLimitedStream::class)]
final class SizeLimitedStreamTest extends TestCase
{
    protected function sut(int $maxSizeBytes = 10): SizeLimitedStream
    {
        return new SizeLimitedStream($maxSizeBytes);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(SizeLimitedStream::class, $this->sut());
    }


    public function testCanWriteUpToLimit(): void
    {
        $sut = $this->sut(10);

        $this->assertSame(10, $sut->write(str_repeat('a', 10)));
        $this->assertFalse($sut->hasExceededLimit());
        $this->assertSame('aaaaaaaaaa', (string)$sut);
    }


    public function testThrowsWhenWritePassesLimit(): void
    {
        $sut = $this->sut(10);

        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('exceeded the limit of 10 bytes');

        $sut->write(str_repeat('a', 11));
    }


    public function testLimitIsEnforcedAcrossSeparateWrites(): void
    {
        $sut = $this->sut(10);

        $sut->write('aaaaa');
        $sut->write('bbbbb');

        try {
            $sut->write('c');
            $this->fail('Expected an OverflowException.');
        } catch (OverflowException) {
            $this->assertTrue($sut->hasExceededLimit());
        }
    }


    public function testRemembersThatLimitWasExceeded(): void
    {
        $sut = $this->sut(1);
        $this->assertFalse($sut->hasExceededLimit());

        try {
            $sut->write('too long');
        } catch (OverflowException) {
            // Expected, the flag is what matters here.
        }

        $this->assertTrue($sut->hasExceededLimit());
    }


    public function testMaxSizeBytesIsClamped(): void
    {
        $this->assertSame(1, $this->sut(0)->getMaxSizeBytes());
        $this->assertSame(1, $this->sut(-10)->getMaxSizeBytes());
    }


    public function testDecoratesBackingStream(): void
    {
        $sut = $this->sut(1024);
        $sut->write('existing');
        $sut->rewind();

        $this->assertTrue($sut->isSeekable());
        $this->assertSame('existing', $sut->getContents());
    }


    public function testStartsCleanBodyForEachRedirectHop(): void
    {
        // Mimics how a handler drives a reused sink across a redirect: write, rewind, write the next response.
        $sut = $this->sut(1024);

        $sut->write('a-long-redirect-body');
        $sut->rewind();
        $sut->write('JWT');
        $sut->rewind();

        // Nothing of the longer first body may survive into the second one.
        $this->assertSame('JWT', $sut->getContents());
    }


    public function testStartNewResponseResetsAccountingWithoutRewind(): void
    {
        // Covers handlers that reuse the sink without rewinding it, such as Guzzle's MockHandler.
        $sut = $this->sut(10);

        $sut->write(str_repeat('a', 8));
        $sut->startNewResponse();
        $sut->write(str_repeat('b', 8));
        $sut->rewind();

        $this->assertFalse($sut->hasExceededLimit());
        $this->assertSame(str_repeat('b', 8), $sut->getContents());
    }


    public function testStartNewResponseIsNoOpBeforeAnyWrite(): void
    {
        $sut = $this->sut(10);
        $sut->startNewResponse();
        $sut->write('abc');
        $sut->rewind();

        $this->assertSame('abc', $sut->getContents());
    }


    public function testCountsEachRedirectHopSeparately(): void
    {
        $sut = $this->sut(10);

        $sut->write(str_repeat('a', 8));
        $sut->rewind();

        // Individually valid hops must not add up into a false limit breach.
        $sut->write(str_repeat('b', 8));
        $sut->rewind();

        $this->assertFalse($sut->hasExceededLimit());
        $this->assertSame(str_repeat('b', 8), $sut->getContents());
    }
}
