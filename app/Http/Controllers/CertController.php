<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CertController extends Controller
{
    private $privateKey;

    public function __construct()
    {
//        Loading private key from file
        $this->privateKey = openssl_pkey_get_private(file_get_contents(storage_path('app/certs/private.key')));
    }

    public function getCsr()
    {
        $csr = openssl_csr_new([
            'countryName' => 'RU',
            'stateOrProvinceName' => 'Moscow',
            'localityName' => 'Moscow',
            'organizationName' => 'Test',
            'organizationalUnitName' => 'Test',
            'commonName' => 'Test',
            'emailAddress' => ''
        ], $this->privateKey, [
            'digest_alg' => 'sha256',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

    }

    public function signSSL()
    {
        $csr = openssl_csr_new([
            'countryName' => 'RU',
            'stateOrProvinceName' => 'Moscow',
            'localityName' => 'Moscow',
            'organizationName' => 'Test',
            'organizationalUnitName' => 'Test',
            'commonName' => 'Test',
            'emailAddress' => ''
        ], $this->privateKey, [
            'digest_alg' => 'sha256',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $x509 = openssl_csr_sign($csr, null, $this->privateKey, 365, [
            'digest_alg' => 'sha256',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_x509_export($x509, $cert);

        return $cert;
    }
}
