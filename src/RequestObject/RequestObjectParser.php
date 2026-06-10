<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\RequestObject;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Exceptions\RequestObjectException;

class RequestObjectParser
{
    public function __construct(
        protected readonly RequestObjectFactories $requestObjectFactories,
        protected readonly RequestUriFetcher $requestUriFetcher,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    public function fromToken(string $token): RequestObjectBag
    {
        $requestObjectBag = new RequestObjectBag();

        foreach ($this->requestObjectFactories->getAll() as $factory) {
            try {
                $requestObject = $factory->fromToken($token);
                $requestObjectBag->add($requestObject);
            } catch (RequestObjectException) {
                // Ignore and try the next factory
                $this->logger?->debug(
                    sprintf('Failed to parse request object using factory %s. Skipping.', $factory::class),
                );
            }
        }

        return $requestObjectBag;
    }


    public function fromRequestUri(string $requestUri, int $timeout = 5, int $maxSizeBytes = 102400): RequestObjectBag
    {
        return $this->fromToken(
            $this->requestUriFetcher->fetch(
                $requestUri,
                $timeout,
                $maxSizeBytes,
            ),
        );
    }
}
