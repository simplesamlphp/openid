<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Exceptions\DidException;

/**
 * The two checks every DID method resolver makes before it does anything else: that the identifier names
 * this method, and that it is a bare DID rather than a DID URL pointing inside a document.
 *
 * Shared so that the answer to "is this mine" is derived from {@see DidResolverInterface::methodName()} in
 * one place. A resolver that answered it independently could accept an identifier it is not registered for,
 * or refuse one it is.
 *
 * @see \SimpleSAML\Test\OpenID\Did\AbstractDidResolverTest
 */
abstract class AbstractDidResolver implements DidResolverInterface
{
    public function supports(string $did): bool
    {
        try {
            return (new DidUrl($did))->getMethod() === $this->methodName();
        } catch (DidException) {
            return false;
        }
    }


    /**
     * Parse an identifier that is expected to be a bare DID of this resolver's method.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function requireBareDid(string $did): DidUrl
    {
        $didUrl = new DidUrl($did);

        if ($didUrl->getMethod() !== $this->methodName()) {
            throw new DidException(
                sprintf('DID method "%s" is not one this resolver handles.', $didUrl->getMethod()),
            );
        }

        if (!$didUrl->isBareDid()) {
            throw new DidException('A DID document can only be resolved for a bare DID.');
        }

        return $didUrl;
    }
}
