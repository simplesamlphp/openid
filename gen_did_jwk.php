<?php
require 'vendor/autoload.php';
use SimpleSAML\OpenID\Helpers;
$helpers = new Helpers();
$jwk = ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => '11-O_J6_K8_mu2_5_K8_mu2_5_K8_mu2_5'];
$jsonJwk = json_encode($jwk);
$encodedJwk = $helpers->base64Url()->encode($jsonJwk);
echo "did:jwk:" . $encodedJwk . "\n";
