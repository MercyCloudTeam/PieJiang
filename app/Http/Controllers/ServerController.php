<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ServerController extends Controller
{
    private function getPrivateKey()
    {
        $path = storage_path('app/private.key');
        if (!file_exists($path)) {
            $private_key = openssl_pkey_new(array(
                "private_key_type" => OPENSSL_KEYTYPE_EC,
                "curve_name" => 'prime256v1',
            ));
            openssl_pkey_export_to_file($private_key, $path);
            //generate CA Certificates
            $dn = array(
                "countryName" => env('COUNTRYNAME', 'US'),
//                "stateOrProvinceName" => "California",
//                "localityName" => "San Francisco",
                "organizationName" => env('APP_NAME', 'PieJiang'),
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
        $caPrivateKey = openssl_pkey_get_private($this->getPrivateKey());
        $privateKey = openssl_pkey_new(array(
            "private_key_type" => OPENSSL_KEYTYPE_EC,
            "curve_name" => 'prime256v1',
        ));
        $ca = file_get_contents(storage_path('app/ca.crt'));

        $subject = array(
            "commonName" => $server->domain,
            "countryName" => env('COUNTRYNAME', 'US'),
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

    public function register(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid signature',
            ], 401);
        }

        try {
            $request->validate([
                'ip' => 'nullable|ip',
                'name' => 'nullable|string',
                'location' => 'nullable|string',
                'country' => 'nullable|string',
                'plain' => 'boolean|nullable',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid request',
            ], 400);
        }

        $ip = $request->get('ip') ?? $request->ip();
        $server = Server::updateOrCreate([
            'ip' => $ip,
        ], [
            'name' => $request->get('name'),
            'location' => $request->get('location'),
            'country' => $request->get('country'),
            'status' => "UP",
            'domain' => Str::random(12) . ".network.vg",
            'token' => Str::random(32),
        ]);

        if ($request->get('plain')) {
            return response()->make(
                "Server Token:" . $server->token . PHP_EOL .
                "Xray Config:" . route('api.server.xray.config', ['server' => $server->id, 'token' => $server->token]) . PHP_EOL
                , 200)->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'message' => 'Server registered successfully',
            'data' => $server->toArray(),
        ], 200);
    }

    public function registerUrl(Request $request)
    {
        $request->validate([
            'ip' => 'nullable|ip',
            'name' => 'nullable|string',
            'location' => 'nullable|string',
            'country' => 'nullable|string',
            'plain' => 'boolean|nullable',
        ]);
        return response()->json([
            'message' => 'Server register url',
            'data' => [
                'json' => URL::temporarySignedRoute(
                    'api.server.register',
                    now()->addMinutes(30)
                ),
                'plain' => URL::temporarySignedRoute(
                    'api.server.register',
                    now()->addMinutes(30),
                    ['plain' => true]
                ),
                'params' => URL::temporarySignedRoute(
                    'api.server.register',
                    now()->addMinutes(30),
                    [
                        'ip' => $request->get('ip'),
                        'name' => $request->get('name'),
                        'location' => $request->get('location'),
                        'country' => $request->get('country'),
                        'plain' => $request->get('plain') ?? false,
                    ]
                ),
//                'url' => URL::temporarySignedRoute('api.server.register', now()->addMinutes(5)),
            ],
        ], 200);
    }
}
