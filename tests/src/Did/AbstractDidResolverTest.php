<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Did\AbstractDidResolver;
use SimpleSAML\OpenID\Did\DidDocument;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Exceptions\DidException;

#[CoversClass(AbstractDidResolver::class)]
#[UsesClass(DidUrl::class)]
#[UsesClass(DidDocument::class)]
final class AbstractDidResolverTest extends TestCase
{
    protected function sut(string $methodName = 'example'): AbstractDidResolver
    {
        return new class ($methodName) extends AbstractDidResolver {
            public function __construct(
                protected readonly string $methodName,
            ) {
            }


            public function methodName(): string
            {
                return $this->methodName;
            }


            /**
             * Exposes requireBareDid() through the interface, so the shared check is exercised the way a
             * real resolver reaches it.
             *
             * @throws \SimpleSAML\OpenID\Exceptions\DidException
             */
            public function resolveDocument(string $did, ?float $deadlineTimestamp = null): DidDocument
            {
                return new DidDocument($this->requireBareDid($did)->getDid(), []);
            }
        };
    }


    public function testSupportsItsOwnMethod(): void
    {
        $this->assertTrue($this->sut()->supports('did:example:123'));
    }


    public function testDoesNotSupportAnotherMethod(): void
    {
        $this->assertFalse($this->sut()->supports('did:web:example.org'));
    }


    /**
     * A value that is not a DID URL at all is simply not supported. Asking is how a caller finds out, so it
     * is not an error.
     */
    #[DataProvider('unparsableValueProvider')]
    public function testDoesNotSupportAnUnparsableValue(string $value): void
    {
        $this->assertFalse($this->sut()->supports($value));
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function unparsableValueProvider(): Iterator
    {
        yield 'empty' => [''];
        yield 'not a DID' => ['https://example.org'];
        yield 'no method specific id' => ['did:example:'];
        yield 'relative DID URL' => ['#key-1'];
        yield 'trailing newline' => ["did:example:123\n"];
    }


    public function testResolvesABareDidOfItsOwnMethod(): void
    {
        $this->assertSame('did:example:123', $this->sut()->resolveDocument('did:example:123')->getId());
    }


    public function testRefusesADidOfAnotherMethod(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('is not one this resolver handles');

        $this->sut()->resolveDocument('did:web:example.org');
    }


    #[DataProvider('nonBareDidProvider')]
    public function testRefusesADidUrlThatIsNotBare(string $didUrl): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('bare DID');

        $this->sut()->resolveDocument($didUrl);
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function nonBareDidProvider(): Iterator
    {
        yield 'fragment' => ['did:example:123#key-1'];
        yield 'path' => ['did:example:123/path'];
        yield 'query' => ['did:example:123?a=b'];
    }
}
