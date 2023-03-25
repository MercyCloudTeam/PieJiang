<?php

namespace App\Http\Controllers;

use App\Models\Proxy;
use App\Models\ProxyGroup;
use App\Models\Rule;
use App\Models\Server;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class ProxyController extends Controller
{

    public function generateXrayServerConfig(Server $server)
    {
        $base = $this->xrayBase();

        $base['routing'] = [
            'domainStrategy' => 'AsIs',
            'rules' => [
                [
                    'type' => 'field',
                    'ip' => [
                        'geoip:private',
                    ],
                    'outboundTag' => 'block',
                ],
                [
                    'type' => 'field',
                    'ip' => [
                        'geoip:cn',
                    ],
                    'outboundTag' => 'block',
                ]
            ],
        ];
        $base['outbounds'] = [
            [
                'protocol' => 'freedom',
//                'settings' => [
//                    'domainStrategy' => 'UseIP',
//                ],
                'tag' => 'direct',
            ],
            [
                'protocol' => 'blackhole',
                'tag' => 'block',
            ],
        ];
        $ss = Proxy::where([
            ['server_id', '=', $server->id],
            ['type', '=', 'ss'],
        ])->first();
        $vless = Proxy::where([
            ['server_id', '=', $server->id],
            ['type', '=', 'vless'],
        ])->first();
        $trojan = Proxy::where([
            ['server_id', '=', $server->id],
            ['type', '=', 'trojan'],
        ])->first();

        if (empty($ss)) {
            $name = "[$server->country]" . $server->name;
            $ss = Proxy::create([
                'name' => $name,
                'type' => 'ss',
                'port' => mt_rand(10000, 65535),
                'server_id' => $server->id,
                'config' => [
                    'password' => Str::random(16),
                    'method' => '2022-blake3-aes-128-gcm',
                    'udp' => true,
                ]
            ]);
        }
        $base['inbounds'][] = [
            //Shadowsocks-2022
            'port' => $ss->port,
            'protocol' => 'shadowsocks',
            'settings' => [
                'method' => $ss->config['method'],
                'password' => $ss->config['password'],
                'udp' => $ss->config['udp']
            ],
            'sniffing' => [
                'enabled' => true,
                'destOverride' => ['http', 'tls']
            ],
        ];

//        if (empty($trojan)) {
//            $name = "[$server->country]" . $server->name;
//            //Generate SSL Certificates openssl
////            $privkey = openssl_pkey_new([
////                "digest_alg" => "sha256",
////                "private_key_bits" => 2048,
////                "private_key_type" => OPENSSL_KEYTYPE_RSA,
////            ]);
////            $csr = openssl_csr_new([
////                    "countryName" => "CN",
////                    "stateOrProvinceName" => "Guangdong",
////                    "localityName" => "Shenzhen",
////                    "organizationName" => "ProxyPie",
////                    "organizationalUnitName" => "ProxyPie",
////                    "commonName" => "123"]
////                , $privkey, [
////                    "digest_alg" => "sha256",
////                    "private_key_bits" => 2048,
////                    "private_key_type" => OPENSSL_KEYTYPE_RSA,
////                ]);
////
////            $sscert = openssl_csr_sign($csr, null, $privkey, 365);
////            openssl_x509_export($sscert, $certout);
////            openssl_pkey_export($privkey, $pkeyout);
////            Storage::disk('local')->put("ssl/certs/ssl-cert.pem", $certout);
////            Storage::disk('local')->put("ssl/private/ssl-cert.key", $pkeyout);
//
//            $trojan = Proxy::create([
//                'name' => $name,
//                'type' => 'trojan',
//                'port' => 3000,
//                'server_id' => $server->id,
//                'config' => [
//                    'password' => Str::random(16),
//                    'email' => "$server->id@proxy.piejiang.com",
//                    'certificates' => [
//                        [
//                            'certificateFile' => '/ssl/certs/ssl-cert.pem',
//                            'keyFile' => '/ssl/private/ssl-cert.key',
//                        ]
//                    ],
//                ]
//            ]);
//        }
//        $base['inbounds'][] = [
//            //Trojan
//            'port' => $trojan->port,
//            'protocol' => 'trojan',
//            'settings' => [
//                'clients' => [
//                    [
//                        'password' => $trojan->config['password'],
//                        'email' => $trojan->config['email'],
//                    ]
//                ],
//            ],
//            'streamSettings' => [
//                'network' => 'tcp',
//                'security' => 'tls',
//                'tlsSettings' => [
//                    'alpn' => ['http/1.1'],
//                    'certificates' => $trojan->config['certificates'],
//                ],
//            ],
//        ];

        if (empty($vless)) {
            $name = "[$server->country]" . $server->name;
            //x25519 Private key
            $ed25519 = sodium_crypto_sign_keypair();
            $ed25519PrivKey = sodium_crypto_sign_secretkey($ed25519);
            $ed25519PubKey = sodium_crypto_sign_publickey($ed25519);
            $curve25519PrivKey = sodium_crypto_sign_ed25519_sk_to_curve25519($ed25519PrivKey);
            $curve25519PubKey = sodium_crypto_sign_ed25519_pk_to_curve25519($ed25519PubKey);
            // RFC 4648
            $privateKey = rtrim(strtr(base64_encode($curve25519PrivKey), '+/', '-_'), '=');
            $pubKey = rtrim(strtr(base64_encode($curve25519PubKey), '+/', '-_'), '=');
            $vless = Proxy::create([
                'name' => $name,
                'type' => 'vless',
                'port' => 443,
                'server_id' => $server->id,
                'config' => [
                    'uuid' => Str::uuid(),
                    //x25519 key
                    'privateKey' => $privateKey,
                    'pubKey' => $pubKey,

                    'spiderX' => '/',
                    'serverName' => ['www.lovelive-anime.jp', 'lovelive-anime.jp'],
                ]
            ]);
        }
        $base['inbounds'][] = [
            //VLESS
            'port' => $vless->port,
            'protocol' => 'vless',
            'settings' => [
                'clients' => [
                    [
                        'id' => $vless->config['uuid'],
                        'flow' => '',
                    ]
                ],
                'decryption' => 'none',
            ],
            'streamSettings' => [
                'network' => 'grpc',
                'security' => 'reality',
                'realitySettings' => [
                    'show' => false,
                    'dest' => "{$vless->config['serverName'][0]}:443",
                    'xver' => 0,
                    'serverNames' => $vless->config['serverName'],
                    'privateKey' => $vless->config['privateKey'],
                    "shortIds" => [
                        ""
                    ],
                ],
                'grpcSettings' => [
                    'serviceName' => 'grpc'
                ],
            ],
            'sniffing' => [
                'enabled' => true,
                'destOverride' => ['http', 'tls']
            ],
        ];

        $base['policy'] = [
            'levels' => [
                '0' => [
                    'handshake' => 3,
                    'connIdle' => 200,
                ],
            ],
        ];

        return response()->json($base);
    }

    public function getProxies()
    {
        $result = [];
        $proxies = Proxy::all();
        foreach ($proxies as $proxy) {
            $result[] = [
                'name' => $proxy->name,
            ];
            switch ($proxy->type) {
                case 'trojan':
                    $result[] = [
                        'type' => 'trojan',
                        'server' => $proxy->in ?? $proxy->config['server'],
                        'port' => $proxy->port,
                        'password' => $proxy->config['password'],
                        'sni' => $proxy->config['sni'],
                        'skip-cert-verify' => $proxy->config['skip-cert-verify'],
                        'udp' => $proxy->config['udp'],
                    ];
                    break;
                case 'http':
                    $result[] = [
                        'type' => 'http',
                        'server' => $proxy->config['server'],
                        'port' => $proxy->port,
                        'username' => $proxy->config['username'],
                        'password' => $proxy->config['password'],
                        'tls' => $proxy->config['tls'],
                    ];
                    break;
                case 'sock5':
                    $result[] = [
                        'type' => 'socks5',
                        'server' => $proxy->config['server'],
                        'port' => $proxy->port,
                        'username' => $proxy->config['username'],
                        'password' => $proxy->config['password'],
                        'udp' => $proxy->config['udp'],
                    ];
                    break;
                case 'ss':
                    $result[] = [
                        'type' => 'ss',
                        'server' => $proxy->config['server'],
                        'port' => $proxy->port,
                        'cipher' => $proxy->config['cipher'],
                        'password' => $proxy->config['password'],
                        'udp' => $proxy->config['udp'],
                    ];
                    break;
                case 'vmess':
                    $temp = [
                        'type' => 'vmess',
                        'server' => $proxy->config['server'],
                        'port' => $proxy->port,
                        'uuid' => $proxy->config['uuid'],
                        'alterId' => $proxy->config['alterId'],
                        'cipher' => $proxy->config['cipher'],
                        'skip-cert-verify' => $proxy->config['skip-cert-verify'],
                    ];
                    if ($proxy->config['network'] == 'ws') {
                        $temp['network'] = 'ws';
//                        $temp['ws-path'] = $proxy->config['ws-path'];
//                        $temp['ws-headers'] = [
//                            'Host' => $proxy->config['ws-headers']['Host']
//                        ];
                    }
                    $result[] = $temp;
                    break;
            }
        }
        return $result;
    }

    public function clashConfig(Request $request)
    {
        $general = Yaml::parseFile(storage_path('app/GeneralClashConfig.yml'));
        //generate rules
        $groups = ProxyGroup::all()->load('rules');
        $proxiesNameList = Proxy::all()->pluck('name')->toArray();

        $proxiesNameList = array_merge($proxiesNameList, ['DIRECT', 'REJECT']);
        $rules = [];
        $proxyGroups = [];
        $proxyGroups[] = [
            'name' => 'Proxy',
            'type' => 'select',
            'proxies' => $proxiesNameList
        ];

        $proxiesNameList[] = 'Proxy';
        $proxyGroups[] = [
            'name' => 'ByPass',
            'type' => 'select',
            'proxies' => $proxiesNameList
        ];

        foreach ($groups as $group) {
            $proxyGroups[] = [
                'name' => $group->name,
                'type' => $group->type,
                'proxies' => $proxiesNameList,
            ];
            foreach ($group->rules as $rule) {
                if ($rule->type == "USER-AGENT" || $rule->type == "URL-REGEX") {
                    continue;
                }
                $temp = [
                    $rule->type,
                    $rule->content,
                    $group->name,
                ];
                if (!$rule->resolve) {
                    $temp[] = 'no-resolve';
                }
                $rules[] = implode(',', $temp);
            }
        }
        $rules[] = 'MATCH,ByPass';
        $general['proxy-groups'] = $proxyGroups;
        $general['rules'] = $rules;

        //out yaml
        $yaml = Yaml::dump($general, 10, 2);
        return response($yaml)->header('Content-Type', 'text/plain')->header('Content-Disposition', 'attachment; filename="PieJiang Proxy"');
    }

    public function processACL4SSR($acl)
    {

        $acl = file_get_contents(storage_path('app/banAD.acl'));
        //remove # and empty line
        $acl = preg_replace('/^#.*$/m', '', $acl);
        $acl = preg_replace('/^\s*$/m', '', $acl);
        $acl = preg_replace('/\r\n/', '', $acl);
        dd($acl);
    }

    public function clashBase(): array
    {
        return [
            'port' => 7890,
            'socks-port' => 7891,
            'redir-port' => 7892,
            'mixed-port' => 7893,
            'allow-lan' => false,
            'mode' => 'Rule',
            'log-level' => 'warning',
            'ipv6' => false,
            'hosts' => null,
            'externe'
        ];
    }

    public function xrayBase(): array
    {
        return [
            "log" => [
                "loglevel" => "warning",
                "error" => "/var/log/xray/error.log",
                "access" => "/var/log/xray/access.log",
                "dnsLog" => false,
            ],
        ];
    }
}
