<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwk\Factories;

use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use SimpleSAML\OpenID\Jwk\JwkDecorator;

class JwkDecoratorFactory
{
    /**
     * @param mixed[] $data
     */
    public function fromData(array $data): JwkDecorator
    {
        return new JwkDecorator(
            new JWK($data),
        );
    }


    /**
     * @param non-empty-string $path
     * @param array<non-empty-string,mixed> $additionalData
     */
    public function fromPkcs1Or8KeyFile(
        string $path,
        ?string $password = null,
        array $additionalData = [],
    ): JwkDecorator {
        return new JwkDecorator(
            JWKFactory::createFromKeyFile($path, $password, $additionalData),
            $additionalData,
        );
    }


    /**
     * @param non-empty-string $key
     * @param array<non-empty-string,mixed> $additionalData
     */
    public function fromPkcs1Or8Key(
        string $key,
        ?string $password = null,
        array $additionalData = [],
    ): JwkDecorator {
        return new JwkDecorator(
            JWKFactory::createFromKey($key, $password, $additionalData),
            $additionalData,
        );
    }


    /**
     * @param non-empty-string $path
     * @param array<non-empty-string,mixed> $additionalData
     */
    public function fromPkcs12CertificateFile(
        string $path,
        string $password = '',
        array $additionalData = [],
    ): JwkDecorator {
        return new JwkDecorator(
            JWKFactory::createFromPKCS12CertificateFile($path, $password, $additionalData),
            $additionalData,
        );
    }


    /**
     * @param non-empty-string $path
     * @param array<non-empty-string,mixed> $additionalData
     */
    public function fromX509CertificateFile(
        string $path,
        array $additionalData = [],
    ): JwkDecorator {
        return new JwkDecorator(
            JWKFactory::createFromCertificateFile($path, $additionalData),
            $additionalData,
        );
    }


    /**
     * @param non-empty-string $certificate
     * @param array<non-empty-string,mixed> $additionalData
     */
    public function fromX509Certificate(
        string $certificate,
        array $additionalData = [],
    ): JwkDecorator {
        return new JwkDecorator(
            JWKFactory::createFromCertificate($certificate, $additionalData),
            $additionalData,
        );
    }
}
