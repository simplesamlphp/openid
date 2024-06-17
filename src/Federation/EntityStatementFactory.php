<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Jws\JwsParser;

class EntityStatementFactory
{
    public function __construct(
        protected readonly JwsParser $jwsParser = new JwsParser(),
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): EntityStatement
    {
        return new EntityStatement($token, $this->jwsParser);
    }
}
