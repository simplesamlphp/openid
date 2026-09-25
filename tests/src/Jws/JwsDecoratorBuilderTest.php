<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws;

use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Exceptions\JwsParseException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(JwsDecoratorBuilder::class)]
#[UsesClass(JwsDecorator::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
final class JwsDecoratorBuilderTest extends TestCase
{
    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $jwsBuilderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Helpers
     */
    protected \PHPUnit\Framework\MockObject\Stub $helpersMock;


    protected function setUp(): void
    {
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->jwsBuilderMock = $this->createMock(JWSBuilder::class);
        $this->helpersMock = $this->createStub(Helpers::class);
    }


    protected function sut(
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?JWSBuilder $jwsBuilder = null,
        ?Helpers $helpers = null,
    ): JwsDecoratorBuilder {
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $jwsBuilder ??= $this->jwsBuilderMock;
        $helpers ??= $this->helpersMock;

        return new JwsDecoratorBuilder(
            $jwsSerializerManagerDecorator,
            $jwsBuilder,
            $helpers,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsDecoratorBuilder::class, $this->sut());
    }


    public function testCanParseToken(): void
    {
        $this->jwsSerializerManagerDecoratorMock->expects($this->once())->method('unserialize')
            ->willReturn($this->createStub(\SimpleSAML\OpenID\Jws\JwsDecorator::class));

        $this->assertInstanceOf(JwsDecorator::class, $this->sut()->fromToken('token'));
    }


    public function testThrowsOnTokenParseError(): void
    {
        $this->jwsSerializerManagerDecoratorMock->expects($this->once())->method('unserialize')
            ->willThrowException(new \Exception('Error'));

        $this->expectException(JwsParseException::class);
        $this->expectExceptionMessage('parse');

        $this->sut()->fromToken('token');
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function valueWhichIsNotACompactJwsProvider(): \Iterator
    {
        yield 'no dots' => ['not-a-jws'];
        yield 'two parts' => ['eyJhbGciOiJSUzI1NiJ9.e30'];
        yield 'four parts' => ['eyJhbGciOiJSUzI1NiJ9.e30.c2ln.c2ln'];
        yield 'header not base64url' => ['!!!.e30.c2ln'];
        yield 'header not JSON' => ['bm90LWpzb24.e30.c2ln'];
        yield 'header a JSON string' => ['InN0cmluZyI.e30.c2ln'];
    }


    #[DataProvider('valueWhichIsNotACompactJwsProvider')]
    public function testThrowsParseExceptionOnAValueWhichIsNotAJws(string $token): void
    {
        $this->expectException(JwsParseException::class);

        $this->sut(
            jwsSerializerManagerDecorator: new JwsSerializerManagerDecorator(
                new JWSSerializerManager([new CompactSerializer()]),
            ),
        )->fromToken($token);
    }


    public function testParsesACompactJwsWithRealSerializer(): void
    {
        $sut = $this->sut(
            jwsSerializerManagerDecorator: new JwsSerializerManagerDecorator(
                new JWSSerializerManager([new CompactSerializer()]),
            ),
        );

        $this->assertInstanceOf(JwsDecorator::class, $sut->fromToken('eyJhbGciOiJSUzI1NiJ9.e30.c2ln'));
    }


    public function testCanBuildFromData(): void
    {
        $this->assertInstanceOf(
            JwsDecorator::class,
            $this->sut()->fromData(
                $this->createStub(\SimpleSAML\OpenID\Jwk\JwkDecorator::class),
                SignatureAlgorithmEnum::RS256,
                [],
                [],
            ),
        );
    }


    public function testBuildFromDataThrowsJwsException(): void
    {
        $this->jwsBuilderMock->expects($this->once())->method('create')
            ->willThrowException(new \Exception('Error'));

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('build');

        $this->sut()->fromData(
            $this->createStub(\SimpleSAML\OpenID\Jwk\JwkDecorator::class),
            SignatureAlgorithmEnum::RS256,
            [],
            [],
        );
    }
}
