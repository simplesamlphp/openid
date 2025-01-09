<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

abstract class AbstractJwsFetcher
{
    public function __construct(
        protected readonly ArtifactFetcher $artifactFetcher,
        protected readonly DateIntervalDecorator $maxCacheDuration,
        protected readonly Helpers $helpers,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    abstract protected function buildJwsInstance(string $token): ParsedJws;
    abstract protected function getExpectedContentTypeHttpHeader(): ?string;
}
