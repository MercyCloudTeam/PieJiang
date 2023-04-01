<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class CertController extends Controller
{

    private $privateKey;

    public function __construct()
    {
        $this->privateKey = openssl_pkey_get_private($this->getPrivateKey(), env('CERT_PASSPHRASE', null));
    }

    private function getPrivateKey()
    {
        $path = storage_path('app/private.key');
        if (!file_exists($path)) {
            //passphrase
            $passphrase = env('CERT_PASSPHRASE', null);

            $private_key = openssl_pkey_new(array(
                "private_key_type" => OPENSSL_KEYTYPE_EC,
                "curve_name" => 'prime256v1',
            ));

            openssl_pkey_export_to_file($private_key, $path, $passphrase);
            //generate CA Certificates
            $dn = array(
                "commonName" => env('CERT_COMMONNAME', 'PieJiang'),
                "countryName" => env('CERT_COUNTRYNAME', 'CN'),
                "localityName" => env('CERT_LOCALITYNAME', 'California'),
                "organizationName" => env('CERT_ORGNAME', 'PieJiang'),
                "organizationalUnitName"=>env('CERT_ORGUNIT', 'PieJiang'),
                "stateOrProvinceName"=>env('CERT_STATE', 'California'),
                "emailAddress"=>env('CERT_EMAIL', ''),

                //san all root ca
            );


            $csr = openssl_csr_new($dn, $private_key);
            $sscert = openssl_csr_sign($csr, null, $private_key, 36500);
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
            // Subject Alternative Name:
            "subjectAltName" => "DNS:".$server->domain.",IP:".$server->ip,

        ));
        $ca = file_get_contents(storage_path('app/ca.crt'));

        //domain & ip
        $subject = array(
            //ip
            "commonName" => $server->domain,
            "countryName" => env('CERT_COUNTRYNAME', 'US'),
            "localityName" => env('CERT_LOCALITYNAME', 'California'),
            "organizationName" => env('CERT_ORGNAME', 'PieJiang'),
            "organizationalUnitName"=>env('CERT_ORGUNIT', 'PieJiang'),
            "emailAddress"=>env('CERT_EMAIL', ''),
        );
        //SubjectAlternativeName
        //Generate OpenSSL config file domain openssl.cnf
        $subjectAltName = "DNS:".$server->domain.",IP:".$server->ip;
        $cnf = view('config.openssl', [
            'domains'=>[$server->domain],
            'ips'=>[$server->ip],
            'subjectAltName'=>$subjectAltName,
        ])->__toString();
        // dd(storage_path('app/openssl/'.$server->domain.'.cnf'));
        file_put_contents(storage_path('app/openssl/'.$server->domain.'.cnf'), $cnf);

        $csr = openssl_csr_new($subject, $privateKey,[
            "config" => storage_path('app/openssl/'.$server->domain.'.cnf'),
            "digest_alg" => "sha384",
            'req_extensions' => 'v3_req',
            // "private_key_bits" => 2048,
            // "private_key_type" => OPENSSL_KEYTYPE_RSA,
            // "x509_extensions" => "v3_req",
            // "req_extensions" => "v3_req",
        ]);
        $x509 = openssl_csr_sign($csr, $ca, $caPrivateKey, days: 365, options: array(
            'config' => storage_path('app/openssl/'.$server->domain.'.cnf'),
            'digest_alg' => 'sha384',
            'req_extensions' => 'v3_req',
        ));
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
