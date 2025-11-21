## OpenID for Verifiable Credential Issuance (OpenID4VCI) Tools

To use it, create an instance of the `\SimpleSAML\OpenID\VerifiableCredentials`
class.

```php

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Jwk;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\VerifiableCredentials;

// Prepare supported JWS serializers.
$jwsSerializerBag = new JwsSerializerBag(
    JwsSerializerEnum::Compact,
);

// Prepare supported signature algorithms.
$supportedAlgorithms = new SupportedAlgorithms(
    new SignatureAlgorithmBag(
        SignatureAlgorithmEnum::RS256,
        SignatureAlgorithmEnum::RS384,
        SignatureAlgorithmEnum::RS512,
        SignatureAlgorithmEnum::ES256,
        SignatureAlgorithmEnum::ES384,
        SignatureAlgorithmEnum::ES512,
        SignatureAlgorithmEnum::PS256,
        SignatureAlgorithmEnum::PS384,
        SignatureAlgorithmEnum::PS512,
    ),
);

// Choose the leeway time used when validate timestamps like `exp`, `iat`, etc.
$timestampValidationLeeway = new DateInterval('PT1M');

$verifiableCredentialTools = new VerifiableCredentials(
    $jwsSerializerBag,
    $supportedAlgorithms,
    $timestampValidationLeeway,
);

// You can also use the JWK Tools to create a JWK decorator from a private key file.
$jwkTools = new Jwk();
```

You can now use the `$verifiableCredentialTools` instance to create and verify
verifiable credentials.

### Creating SD-JWT VCs

The following example shows how to create a SD-JWT VC.

```php

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

/** @var \SimpleSAML\OpenID\VerifiableCredentials $verifiableCredentialTools */
/** @var \SimpleSAML\OpenID\Jwk $jwkTools */

// Use any logic necessary to prepare data to be disclosed.
$disclosedData = [
    'name' => 'John',
    // ...
];

// Prepare a disclosure bag.
$disclosureBag = $verifiableCredentialTools->disclosureBagFactory()->build();

// Add disclosures to the bag.
foreach ($disclosedData as $key => $value) {
    $disclosure = $verifiableCredentialTools->disclosureFactory()->build(
        value: $value,
        name: $key,
        path: [], // Or set a path as ['path', 'to', 'value']
        saltBlacklist: $disclosureBag->salts(), // To prevent salt collisions.
    );

    $disclosureBag->add($disclosure);
}

$issuedAt = new \DateTimeImmutable();

// Use any logic necessary to prepare basic JWT payload data.
$jwtPayload = [
    ClaimsEnum::Iss->value => 'https://example.com/issuer',
    ClaimsEnum::Iat->value => $issuedAt->getTimestamp(),
    ClaimsEnum::Nbf->value => $issuedAt->getTimestamp(),
    ClaimsEnum::Sub->value => 'subject-id',
    ClaimsEnum::Jti->value => 'vc-id',
    ClaimsEnum::Vct->value => 'https://credentials.example.com/identity_credential',
    // ...
];

// Use any logic necessary to prepare SD JWT header data.
$jwtHeader = [
    //...
];

// Prepare a signing key decorator. Check other methods on `jwkDecoratorFactory`
// for alternative ways to create a key decorator. 
$signingKey = $jwkTools->jwkDecoratorFactory()->fromPkcs1Or8KeyFile(
    '/path/to/private/key.pem',
);

// Set the signature algorithm to use.
$signatureAlgorithm = SignatureAlgorithmEnum::ES256;

$verifiableCredential = $verifiableCredentialTools->sdJwtVcFactory()->fromData(
    $signingKey,
    $signatureAlgorithm,
    $jwtPayload,
    $jwtHeader,
    $disclosureBag,
);

// Get the credential token string.
$token = $verifiableCredential->getToken();
```
