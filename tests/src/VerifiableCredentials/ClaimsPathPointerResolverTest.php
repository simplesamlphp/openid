<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\ClaimsPathPointerException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\VerifiableCredentials\ClaimsPathPointerResolver;

#[CoversClass(ClaimsPathPointerResolver::class)]
#[UsesClass(Helpers\Arr::class)]
final class ClaimsPathPointerResolverTest extends TestCase
{
    protected MockObject $helpersMock;

    // From https://openid.net/specs/openid-4-verifiable-credential-issuance-1_0.html#name-claims-path-pointer-example
    protected array $jsonDataSample = [
        'name' => 'Arthur Dent',
        'address' => [
            'street_address' => '42 Market Street',
            'locality' => 'Milliways',
            'postal_code' => '12345',
        ],
        'degrees' => [
            [
                'type' => 'Bachelor of Science',
                'university' => 'University of Betelgeuse',
            ],
            [
                'type' => 'Master of Science',
                'university' => 'University of Betelgeuse',
            ],
        ],
        'nationalities' => ['British', 'Betelgeusian'],
    ];


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(ClaimsPathPointerResolver::class, $this->sut());
    }


    protected function sut(
        ?Helpers $helpers = null,
    ): ClaimsPathPointerResolver {
        $helpers ??= $this->helpersMock;

        return new ClaimsPathPointerResolver($helpers);
    }


    protected function setUp(): void
    {
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->helpersMock->method('arr')->willReturn(new Helpers\Arr());
    }


    public function testCanResolveForJsonBased(): void
    {
        $sut = $this->sut();

        $this->assertSame(
            ['Arthur Dent'],
            $sut->forJsonBased($this->jsonDataSample, ['name']),
        );

        $this->assertSame(
            [
                [
                    'street_address' => '42 Market Street',
                    'locality' => 'Milliways',
                    'postal_code' => '12345',
                ],
            ],
            $sut->forJsonBased($this->jsonDataSample, ['address']),
        );

        $this->assertSame(
            ['42 Market Street'],
            $sut->forJsonBased($this->jsonDataSample, ['address', 'street_address']),
        );

        $this->assertSame(
            ['Bachelor of Science', 'Master of Science'],
            $sut->forJsonBased($this->jsonDataSample, ['degrees', null, 'type']),
        );

        $this->assertSame(
            ['Betelgeusian'],
            $sut->forJsonBased($this->jsonDataSample, ['nationalities', 1]),
        );
    }


    public function testThrowsForInvalidPathComponent(): void
    {
        $this->expectException(ClaimsPathPointerException::class);
        $this->expectExceptionMessage('Path component');

        $this->sut()->forJsonBased($this->jsonDataSample, [false]);
    }


    public function testThrowsForNonObjectInStringPathComponent(): void
    {
        $this->expectException(ClaimsPathPointerException::class);
        $this->expectExceptionMessage('Expected object');

        $this->sut()->forJsonBased($this->jsonDataSample, ['nationalities', 'invalid']);
    }


    public function testThrowsForNonArrayInNullPathComponent(): void
    {
        $this->expectException(ClaimsPathPointerException::class);
        $this->expectExceptionMessage('Expected array');

        $this->sut()->forJsonBased($this->jsonDataSample, ['address', null]);
    }


    public function testThrowsForNonArrayInIntegerPathComponent(): void
    {
        $this->expectException(ClaimsPathPointerException::class);
        $this->expectExceptionMessage('Expected array');

        $this->sut()->forJsonBased($this->jsonDataSample, ['address', 0]);
    }


    public function testThrowsForEmptySelection(): void
    {
        $this->expectException(ClaimsPathPointerException::class);
        $this->expectExceptionMessage('No elements');

        $this->sut()->forJsonBased($this->jsonDataSample, ['invalid']);
    }
}
