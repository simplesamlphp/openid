<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\SdJwt;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Codebooks\SdJwtDisclosureType;
use SimpleSAML\OpenID\Exceptions\SdJwtException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\SdJwt\Disclosure;

#[CoversClass(Disclosure::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers\Base64Url::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers\Json::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers\Type::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers\Hash::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(HashAlgorithmsEnum::class)]
final class DisclosureTest extends TestCase
{
    protected Helpers $helpers;

    protected string $salt;

    protected string $value;

    protected string $name;

    protected array $path;

    protected HashAlgorithmsEnum $selectiveDisclosureAlgorithm;


    protected function setUp(): void
    {
        $this->helpers = new Helpers();
        $this->salt = 'salt';
        $this->value = 'value';
        $this->name = 'name';
        $this->path = ['path'];
        $this->selectiveDisclosureAlgorithm = HashAlgorithmsEnum::SHA_256;
    }


    protected function sut(
        ?Helpers $helpers = null,
        ?string $salt = null,
        mixed $value = null,
        false|null|string $name = null,
        ?array $path = null,
        ?HashAlgorithmsEnum $selectiveDisclosureAlgorithm = null,
    ): Disclosure {
        $helpers ??= $this->helpers;
        $salt ??= $this->salt;
        $value ??= $this->value;
        $name = $name === false ? null : $name ?? $this->name;
        $path ??= $this->path;
        $selectiveDisclosureAlgorithm ??= $this->selectiveDisclosureAlgorithm;

        return new Disclosure(
            $helpers,
            $salt,
            $value,
            $name,
            $path,
            $selectiveDisclosureAlgorithm,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Disclosure::class, $this->sut());
    }


    public function testThrowsForInvalidName(): void
    {
        $this->expectException(SdJwtException::class);
        $this->expectExceptionMessage('forbidden name');

        $this->sut(
            name: ClaimsEnum::_Sd->value,
        );
    }


    public function testThrowsForEmptyNameAndPath(): void
    {
        $this->expectException(SdJwtException::class);
        $this->expectExceptionMessage('name and path');

        $this->sut(
            name: false,
            path: [],
        );
    }


    public function testCanGetCommonProperties(): void
    {
        $sut = $this->sut();

        $this->assertSame($this->salt, $sut->getSalt());
        $this->assertSame($this->value, $sut->getValue());
        $this->assertSame($this->name, $sut->getName());
        $this->assertSame($this->path, $sut->getPath());
    }


    public function testHasProperSerialization(): void
    {
        $this->assertSame(
            [$this->salt, $this->name, $this->value],
            $this->sut()->jsonSerialize(),
        );

        $this->assertSame(
            [$this->salt, $this->value],
            $this->sut(name: false)->jsonSerialize(),
        );
    }


    public function testHasProperType(): void
    {
        $this->assertSame(
            SdJwtDisclosureType::ObjectProperty,
            $this->sut()->getType(),
        );

        $this->assertSame(
            SdJwtDisclosureType::ArrayElement,
            $this->sut(name: false)->getType(),
        );
    }


    public function testCanGetEncoded(): void
    {
        $this->assertNotEmpty($this->sut()->getEncoded());
    }


    public function testCanGetDigest(): void
    {
        $this->assertNotEmpty($this->sut()->getDigest());
    }


    public function testCanGetDigestRepresentation(): void
    {
        $this->assertNotEmpty($this->sut(
            name: false,
        )->getDigestRepresentation());

        $this->assertNotEmpty($this->sut()->getDigestRepresentation());
    }
}
