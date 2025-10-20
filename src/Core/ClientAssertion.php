<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core;

use SimpleSAML\OpenID\Exceptions\ClientAssertionException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class ClientAssertion extends ParsedJws
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getAudience(...),
            $this->getJwtId(...),
            $this->getExpirationTime(...),
            $this->getIssuedAt(...),
            $this->validateIssuerAndSubject(...),
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     */
    protected function validateIssuerAndSubject(): void
    {
        if ($this->getIssuer() !== $this->getSubject()) {
            throw new ClientAssertionException(
                'Issuer claim is expected to be the same as Subject claim',
            );
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     * @return non-empty-string
     */
    public function getIssuer(): string
    {
        return parent::getIssuer() ?? throw new ClientAssertionException('No Issuer claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     * @return non-empty-string
     */
    public function getSubject(): string
    {
        return parent::getSubject() ?? throw new ClientAssertionException('No Subject claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     * @return string[]
     */
    public function getAudience(): array
    {
        return parent::getAudience() ?? throw new ClientAssertionException('No Audience claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     */
    public function getJwtId(): string
    {
        return parent::getJwtId() ?? throw new ClientAssertionException('No JWT ID claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     */
    public function getExpirationTime(): int
    {
        return parent::getExpirationTime() ?? throw new ClientAssertionException('No Expiration Time claim found.');
    }
}
