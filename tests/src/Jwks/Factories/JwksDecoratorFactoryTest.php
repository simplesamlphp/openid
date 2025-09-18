<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jwks\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jwks;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jwks\JwksDecorator;

#[CoversClass(JwksDecoratorFactory::class)]
#[UsesClass(JwksDecorator::class)]
#[UsesClass(JwksDecoratorFactory::class)]
final class JwksDecoratorFactoryTest extends TestCase
{
    protected array $jwksArraySample = [
        'keys' => [
            [
                'alg' => 'RS256',
                'use' => 'sig',
                'kty' => 'RSA',
                // phpcs:ignore
                'n' => 'oZX-hYGAdxl0Pe0CXl917OYmxzhaUg0Gpt6rejdWve5wuCwL5Ox0GHiYCYOlBSs0JMJhHxg-7msjwMsMs0E3OpR_Pz4qzMmSVpYa8tGAYr9H6kXWJ5BuJ7SUNBQRAycm58sgwE-kC5K9ZFmzZVfU52uKML16g4QHq95DKQK2q7wXlsaLDR8aNiith67xcP4udi83wJvGaTcEG-fb--OGj3EQ0_DOxdp6ZekVehlS3kf8EVCXXD9lR6QkxRMitdRdXZ_sL4p1PEfJQpPHv23TOxA-TGNGGCShnRIkAAxPlJxvCfqbpAaKQoQb6J3nNK468sUiJDfUaClGlzO5bz4wrQ',
                'e' => 'AQAB',
                'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
            ],
        ],
    ];


    protected function setUp(): void
    {
    }


    protected function sut(): JwksDecoratorFactory
    {
        return new JwksDecoratorFactory();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwksDecoratorFactory::class, $this->sut());
    }


    public function testCanBuildFromKeyData(): void
    {
        $this->assertInstanceOf(Jwks\JwksDecorator::class, $this->sut()->fromKeySetData($this->jwksArraySample));
    }
}
