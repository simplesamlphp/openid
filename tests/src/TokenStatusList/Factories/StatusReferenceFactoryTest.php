<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusReferenceFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusClaim;
use SimpleSAML\OpenID\TokenStatusList\StatusReference;

#[CoversClass(StatusReferenceFactory::class)]
#[UsesClass(StatusReference::class)]
#[UsesClass(StatusClaim::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Type::class)]
final class StatusReferenceFactoryTest extends TestCase
{
    protected function sut(): StatusReferenceFactory
    {
        return new StatusReferenceFactory(new Helpers());
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusReferenceFactory::class, $this->sut());
    }


    public function testCanBuild(): void
    {
        $statusReference = $this->sut()->build('https://example.com/statuslists/1', 7);

        $this->assertSame('https://example.com/statuslists/1', $statusReference->getUri());
        $this->assertSame(7, $statusReference->getIdx());
    }


    public function testCanBuildClaim(): void
    {
        $statusClaim = $this->sut()->buildClaim('https://example.com/statuslists/1', 7);

        $this->assertInstanceOf(StatusClaim::class, $statusClaim);
        $this->assertSame(7, $statusClaim->getStatusReference()->getIdx());
    }


    public function testCanBuildFromStatusListClaimData(): void
    {
        $statusReference = $this->sut()->fromStatusListClaimData(
            ['idx' => 0, 'uri' => 'https://example.com/statuslists/1'],
        );

        $this->assertSame('https://example.com/statuslists/1', $statusReference->getUri());
        $this->assertSame(0, $statusReference->getIdx());
    }


    /**
     * The example Referenced Token payload from the specification.
     */
    public function testCanBuildFromReferencedTokenPayload(): void
    {
        $statusReference = $this->sut()->fromReferencedTokenPayload([
            'iss' => 'https://example.com/issuer',
            'iat' => 1683000000,
            'status' => [
                'status_list' => [
                    'idx' => 0,
                    'uri' => 'https://example.com/statuslists/1',
                ],
            ],
        ]);

        $this->assertInstanceOf(StatusReference::class, $statusReference);
        $this->assertSame('https://example.com/statuslists/1', $statusReference->getUri());
        $this->assertSame(0, $statusReference->getIdx());
    }


    /**
     * A token without a status claim is not malformed; the Relying Party decides what to make of it.
     */
    public function testReturnsNullWhenPayloadHasNoStatusClaim(): void
    {
        $this->assertNotInstanceOf(
            StatusReference::class,
            $this->sut()->fromReferencedTokenPayload(['iss' => 'https://example.com/issuer']),
        );
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function invalidIdxProvider(): \Iterator
    {
        yield 'missing' => [null];
        yield 'numeric string' => ['0'];
        yield 'float' => [0.0];
        yield 'boolean' => [false];
        yield 'array' => [[0]];
    }


    #[DataProvider('invalidIdxProvider')]
    public function testThrowsForNonIntegerIdx(mixed $idx): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('index claim must be an integer');

        $this->sut()->fromStatusListClaimData(['idx' => $idx, 'uri' => 'https://example.com/statuslists/1']);
    }


    public function testThrowsForNegativeIdx(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('non-negative integer');

        $this->sut()->fromStatusListClaimData(['idx' => -1, 'uri' => 'https://example.com/statuslists/1']);
    }


    public function testThrowsForMissingUri(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->sut()->fromStatusListClaimData(['idx' => 0]);
    }


    public function testThrowsForNonUri(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->sut()->fromStatusListClaimData(['idx' => 0, 'uri' => 'not a uri']);
    }


    /**
     * A claim which is present but null is a malformed Referenced Token, not one that simply carries no status.
     */
    public function testThrowsForNullStatusClaim(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Status claim must be an object');

        $this->sut()->fromReferencedTokenPayload(['status' => null]);
    }


    public function testThrowsForNonObjectStatusClaim(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Status claim must be an object');

        $this->sut()->fromReferencedTokenPayload(['status' => 'revoked']);
    }


    /**
     * A status claim referencing some other mechanism is not silently treated as an absent status.
     */
    public function testThrowsForStatusClaimWithoutStatusListMember(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Status List claim must be an object');

        $this->sut()->fromReferencedTokenPayload(['status' => ['some_other_mechanism' => ['a' => 'b']]]);
    }
}
