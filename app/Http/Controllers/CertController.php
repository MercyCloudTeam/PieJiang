<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class CertController extends Controller
{

    private $privateKey;

    public function __construct()
    {
        $this->privateKey = openssl_pkey_get_private($this->getPrivateKey(), env('PASSPHRASE', null));
    }

    private function getPrivateKey()
    {
        $path = storage_path('app/private.key');
        if (!file_exists($path)) {
            //passphrase
            $passphrase = env('PASSPHRASE', null);

            $private_key = openssl_pkey_new(array(
                "private_key_type" => OPENSSL_KEYTYPE_EC,
                "curve_name" => 'prime256v1',
            ));

            openssl_pkey_export_to_file($private_key, $path, $passphrase);
            //generate CA Certificates
            $dn = array(
                "countryName" => env('COUNTRYNAME', 'US'),
//                "stateOrProvinceName" => "California",
//                "localityName" => "San Francisco",
                "organizationName" => env('ORGANIZATIONNAME', 'PieJiang'),
                "commonName" => env('APP_NAME', 'PieJiang'), " Root CA",
            );

            $csr = openssl_csr_new($dn, $private_key);
            $sscert = openssl_csr_sign($csr, null, $private_key, 3650);
            openssl_x509_export_to_file($sscert, storage_path('app/ca.crt'));
        }
        return file_get_contents($path);
    }

    public function serverCert(Server $server)
    {
        //Generate SSL Certificates openssl
        $caPrivateKey = $this->privateKey;
        $privateKey = openssl_pkey_new(array(
            "private_key_type" => OPENSSL_KEYTYPE_EC,
            "curve_name" => 'prime256v1',
        ));
        $ca = file_get_contents(storage_path('app/ca.crt'));

        $subject = array(
            "commonName" => $server->domain,
            "countryName" => env('COUNTRYNAME', 'CN'),
//            "stateOrProvinceName" => "California",
//            "localityName" => "San Francisco",
            "organizationName" => env('APP_NAME', 'PieJiang'),
        );

        $csr = openssl_csr_new($subject, $privateKey, array('digest_alg' => 'sha384'));
        $x509 = openssl_csr_sign($csr, $ca, $caPrivateKey, days: 365, options: array('digest_alg' => 'sha384'));
        openssl_x509_export($x509, $certout);
        openssl_pkey_export($privateKey, $pkeyout);

        openssl_csr_export($csr, $csrout);

        $server->update([
            'config' => array_merge($server->config ?? [], [
                'cert' => $certout,
                'key' => $pkeyout,
                'csr' => $csrout,
            ])
        ]);
//        print_r($certout);
        return response()->json([
            'message' => 'Server Cert',
            'data' => [
                'cert' => $certout,
                'key' => $pkeyout,
                'csr' => $csrout,
            ],
        ]);
    }

}
