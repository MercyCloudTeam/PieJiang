<?php

namespace App\Http\Controllers;

use App\Models\Access;
use App\Models\Proxy;
use App\Models\ProxyGroup;
use App\Models\Rule;
use App\Models\Server;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\Yaml\Yaml;

class ProxyController extends Controller
{

    public array $types = [
        'http',
        'socks5',
        'trojan',
        'vless',
        'vmess',
        'ss',
    ];

    public function storeAccess(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'proxy_id' => 'required|exists:proxies,id',
            'server_id' => 'required|exists:servers,id',
            'port' => 'required|integer|min:1|max:65535',
            'type' => 'required|in:' . implode(',', $this->types),
        ]);

        $config = [];
        switch ($request->type) {
            case 'trojan':
                $config = [
                    'password' => Str::random(16),
                    'udp' => true,
                ];
                break;
            case 'vmess':
                $config = [
                    'uuid' => Str::uuid(),
                    'alterId' => 0,
                    'udp' => true,
                    'network' => 'ws',
                    'cipher' => 'auto',
                ];
                break;
            case 'vless':
                $config = [
                    'uuid' => Str::uuid(),
                    'udp' => true,
                ];
                break;
            case 'socks5':
                $config = [
                    'username' => Str::random(16),
                    'password' => Str::random(16),
                    'udp' => true,
                ];
                break;
            case 'ss':
                $config = [
                    'method' => 'chacha20-ietf-poly1305',
                    'password' => Str::random(16),
                ];
                break;

        }

        Access::updateOrCreate([
            'proxy_id' => $request->proxy_id,
            'server_id' => $request->server_id,
            'type' => $request->type,
        ], [
            'name' => $request->name,
            'port' => $request->port,
            'config' => $config,
        ]);

        return Redirect::route('access.index');
    }

    public function index()
    {
        return Inertia::render('Proxy/Index', [
            'proxies' => Proxy::all()->load('server'),
            'proxyGroups' => ProxyGroup::all(),
            'servers' => Server::all(),
        ]);
    }

    public function access()
    {
        return Inertia::render('Access/Index', [
            'proxies' => Proxy::all()->load('server'),
            'servers' => Server::all(),
            'accesses' => Access::all()->load('server', 'proxy'),
            'types' => $this->types,

        ]);
    }

    public function makeXrayOutboundConfig(Proxy|Access $proxy, array $addons = [])
    {
        $nameTag = trim($proxy->name . '-' . $proxy->type . '-' . 'outbound');
        switch ($proxy->type) {
            case 'trojan':
                $result = [
                    'protocol' => 'trojan',
                    'tag' => $nameTag,
                    'settings' => [
                        'servers' => [
                            [
                                'address' => $proxy->server->ip,
                                'port' => $proxy->port,
                                'password' => $proxy->config['password'],
                                'udp' => $proxy->config['udp'] ?? false,
                            ]
                        ]
                    ],
                    'streamSettings' => [
                        'security' => 'tls',
                        'network' => 'tcp',
                    ],
                ];
                break;
            case 'ss':
                $result = [
                    'protocol' => 'shadowsocks',
                    'tag' => $nameTag,
                    'settings' => [
                        'servers' => [
                            [
                                'address' => $proxy->server->ip,
                                'port' => $proxy->port,
                                'method' => $proxy->config['method'],
                                'password' => $proxy->config['password'],
                                'uot' => $proxy->config['uot'] ?? false,
                            ]
                        ]
                    ],
                ];
                break;
            case 'vmess':
                $result = [
                    'protocol' => 'vmess',
                    'tag' => $nameTag,
                    'settings' => [
                        'vnext' => [
                            [
                                'address' => $proxy->server->ip,
                                'port' => $proxy->port,
                                'users' => [
                                    [
                                        'id' => $proxy->config['uuid'],
                                        'alterId' => $proxy->config['alterId'],
                                        'security' => 'auto',
                                        'level' => $proxy->config['level'] ?? 0,
                                    ]
                                ]
                            ]
                        ]
                    ],
                ];

                if ($proxy->config['network'] === 'ws') {
                    $result['streamSettings'] = [
                        'network' => 'ws',
                    ];
                }
                break;
            case 'socks':
                $result = [
                    'protocol' => 'socks',
                    'tag' => $nameTag,
                    'settings' => [
                        'servers' => [
                            [
                                'address' => $proxy->server->ip,
                                'port' => $proxy->port,
                                'users' => [
                                    [
                                        'user' => $proxy->config['username'],
                                        'pass' => $proxy->config['password'],
                                    ]
                                ]
                            ]
                        ]
                    ],
                ];
                break;
                case 'vless':
                $result = [
                    'protocol' => 'vless',
                    'tag' => $nameTag,
                    'settings' => [
                        'vnext' => [
                            [
                                'address' => $proxy->server->ip,
                                'port' => $proxy->port,
                                'users' => [
                                    [
                                        'id' => $proxy->config['uuid'],
                                        'security' => 'none',
                                        'flow'=>$proxy->config['flow'] ?? '',
                                    ]
                                ]
                            ]
                        ]
                    ],
                ];
                    if (isset($proxy->config['security']) && $proxy->config['security'] == 'reality') {
                        $result = array($result, [
                            'streamSettings' => [
                                'network' => 'grpc',
                                'security' => 'reality',
                                'realitySettings' => [
                                    'show' => false,
                                    'fingerprint' => "chrome",
                                    'serverNames' => $proxy->config['serverName'][0],
                                    'publicKey' => $proxy->config['pubKey'],
                                    "shortIds" => "",
                                    'spiderX'=>$proxy->config['spiderX'] ?? "",
                                ],
                                'grpcSettings' => [
                                    'serviceName' => 'grpc',
                                    'multiMode' => true
                                ],
                            ],
                        ]);
                    }
                    break;
                break;
        }

        if (empty($result)) {
            return [];
        } else {
            return array_merge($result, $addons);
        }
    }

    public function makeXrayInboundConfig(Proxy|Access $proxy, array $addons = [])
    {
        $nameTag = trim($proxy->name . '-' . $proxy->type . '-' . 'inbound');
        switch ($proxy->type) {
            case 'trojan':
                $result = [
                    //Trojan
                    'port' => $proxy->port,
                    'protocol' => 'trojan',
                    'tag' => $nameTag,
//            'domain'=>Str::random().'',
                    'settings' => [
                        'clients' => [
                            [
                                'password' => $proxy->config['password'],
                                'email' => $proxy->config['email'],
                            ]
                        ],
                    ],
                    'streamSettings' => [
                        'network' => 'tcp',
                        'security' => 'tls',
                        'tlsSettings' => [
                            'alpn' => ['http/1.1'],
                            'certificates' => $proxy->config['certificates'],
                        ],
                    ],
                ];
                break;
            case 'ss':
                $result = [
                    //Shadowsocks-2022
                    'port' => $proxy->port,
                    'tag' => $nameTag,
                    'protocol' => 'shadowsocks',
                    'settings' => [
                        'method' => $proxy->config['method'],
                        'password' => $proxy->config['password'],
                        'udp' => $proxy->config['udp']
                    ],
                    'sniffing' => [
                        'enabled' => true,
                        'destOverride' => ['http', 'tls']
                    ],
                ];
                break;
            case 'vmess':
                $result = [
                    //vmess
                    'port' => $proxy->port,
                    'tag' => $nameTag,
                    'protocol' => 'vmess',
                    'settings' => [
                        'clients' => [
                            ['id' => $proxy->config['uuid'], 'alterId' => $proxy->config['alterId']]
                        ],
                    ],
                    'streamSettings' => [
                        'network' => 'ws',
                        'security' => 'none',
                    ],
                ];
                break;

            case 'vless':
                $result = [
                    //VLESS
                    'port' => $proxy->port,
                    'tag' => $nameTag,
                    'protocol' => 'vless',
                    'settings' => [
                        'clients' => [
                            [
                                'id' => $proxy->config['uuid'],
                                // 'encryption' => 'none',
                                'flow' => $proxy->config['flow'] ?? '',
                            ]
                        ],
                        'decryption' => 'none',
                    ],
                ];

                if (isset($proxy->config['security']) && $proxy->config['security'] == 'reality') {
                    $result = array_merge($result, [
                        'streamSettings' => [
                            'network' => 'grpc',
                            'security' => 'reality',
                            'realitySettings' => [
                                'show' => false,
                                'dest' => "{$proxy->config['serverName'][0]}:443",
                                'xver' => 0,
                                'serverNames' => $proxy->config['serverName'],
                                'privateKey' => $proxy->config['privateKey'],
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
                    ]);
                }
                break;
        }

        if (empty($result)) {
            return [];
        } else {
            return array_merge($result, $addons);
        }
    }

    /**
     * Generate Xray Server config
     * @param Server $server
     * @return \Illuminate\Http\JsonResponse
     * @throws \SodiumException
     */
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
        $vmess = Proxy::where([
            ['server_id', '=', $server->id],
            ['type', '=', 'vmess'],
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
        $base['inbounds'][] = $this->makeXrayInboundConfig($ss);

        if (empty($trojan)) {
            $name = "[$server->country]" . $server->name;
            $trojan = Proxy::create([
                'name' => $name,
                'type' => 'trojan',
                'port' => 3000,
                'server_id' => $server->id,
                'config' => [
                    'password' => Str::random(16),
                    'email' => "$server->id@proxy.piejiang.com",
                    'certificates' => [
                        [
                            'certificateFile' => '/ssl/cert.pem',
                            'keyFile' => '/ssl/cert.key',
                        ]
                    ],
                ]
            ]);
        }
        $base['inbounds'][] = $this->makeXrayInboundConfig($trojan);

        if (empty($vmess)) {
            $name = "[$server->country]" . $server->name;
            $vmess = Proxy::create([
                'name' => $name,
                'type' => 'vmess',
                'port' => mt_rand(10000, 65535),
                'server_id' => $server->id,
                'config' => [
                    'uuid' => Str::uuid(),
                    'alterId' => 0,
                ]
            ]);
        }
        $base['inbounds'][] = $this->makeXrayInboundConfig($vmess);

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
                    'security' => 'reality',

                    'spiderX' => '/',
                    'serverName' => ['www.lovelive-anime.jp', 'lovelive-anime.jp'],
                ]
            ]);
        }
        $base['inbounds'][] = $this->makeXrayInboundConfig($vless);

        // $base['policy']['levels']['0']= [
        //             'handshake' => 3,
        //             'connIdle' => 200,
        // ];

        return response()->json($base, 200, [], 448);
    }


    public function generateXrayAccessConfig(Server $server)
    {
        $access = Access::where('server_id', $server->id)->get();
        $base = $this->xrayBase();
        $base['outbounds'] = [
            [
                'protocol' => 'freedom',
                'settings' => [
                    'domainStrategy' => 'UseIP',
                ],
                'tag' => 'direct',
            ],
            [
                'protocol' => 'blackhole',
                'tag' => 'block',
            ]
        ];
        $base['routing'] = [
            "domainStrategy" => "IPIfNonMatch",
            "rules" => [
                [
                    "type" => "field",
                    "outboundTag" => "direct",
                    "domain" => ["geosite:cn"]
                ],
            ]
        ];

        $base['inbounds'] = [];
        foreach ($access as $item) {
            $proxy = $item->proxy;
            $base['outbounds'][] = $this->makeXrayOutboundConfig($proxy);
//            dd($proxy);
            //out in
            $base['inbounds'][] = $this->makeXrayInboundConfig($item);

            $inboundNameTag = trim($item->name . '-' . $item->type . '-' . 'inbound');
            $outboundNameTag = trim($proxy->name . '-' . $proxy->type . '-' . 'outbound');
            $base['routing']['rules'][] = [
                "type" => "field",
                "inboundTag" => [$inboundNameTag],
                "outboundTag" => $outboundNameTag,
            ];
        }


        return response()->json($base, 200, [], 448);
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

    public function getProxies()
    {
        $result = [];
        $proxies = Proxy::all();
        foreach ($proxies as $proxy) {
            switch ($proxy->type) {
                case 'trojan':
                    $result[] = [
                        'name' => $proxy->display_name,
                        'type' => 'trojan',
                        'server' => $proxy->server->ip,
                        'port' => $proxy->port,
                        'password' => $proxy->config['password'],
                        'sni' => $proxy->config['sni'] ?? $proxy->server->domain,
                        'skip-cert-verify' => $proxy->config['skip-cert-verify'] ?? false,
                        'udp' => $proxy->config['udp'] ?? false,
                    ];
                    break;
                case 'http':
                    $result[] = [
                        'name' => $proxy->display_name,
                        'type' => 'http',
                        'server' => $proxy->server->ip,
                        'port' => $proxy->port,
                        'username' => $proxy->config['username'],
                        'password' => $proxy->config['password'],
                        'tls' => $proxy->config['tls'],
                    ];
                    break;
                case 'sock5':
                    $result[] = [
                        'name' => $proxy->display_name,
                        'type' => 'socks5',
                        'server' => $proxy->server->ip,
                        'port' => $proxy->port,
                        'username' => $proxy->config['username'],
                        'password' => $proxy->config['password'],
                        'udp' => $proxy->config['udp'],
                    ];
                    break;
                case 'ss':
                    if ($proxy->config['method'] == '2022-blake3-aes-128-gcm') {
                        break;
                    }
                    $result[] = [
                        'name' => $proxy->display_name,
                        'type' => 'ss',
                        'server' => $proxy->server->ip,
                        'port' => $proxy->port,
                        'cipher' => $proxy->config['method'],
                        'password' => $proxy->config['password'],
                        'udp' => $proxy->config['udp'] ?? false,
                    ];
                    break;
                case 'vmess':
                    $temp = [
                        'name' => $proxy->display_name,
                        'type' => 'vmess',
                        'server' => $proxy->server->ip,
                        'port' => $proxy->port,
                        'uuid' => $proxy->config['uuid'],
                        'alterId' => $proxy->config['alterId'],
                        'cipher' => $proxy->config['cipher'] ?? "auto",
                        'skip-cert-verify' => $proxy->config['skip-cert-verify'] ?? true,
                    ];
                    if (!empty($proxy->config['network']) && $proxy->config['network'] == 'ws') {
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
//        $proxiesNameList = Proxy::all()->pluck('display_name')->toArray();
        $proxies = $this->getProxies();

//        dd($proxies);
        foreach ($proxies as $proxy) {
            $proxiesNameList[] = $proxy['name'];
        }
        $user = User::where('token', $request->token)->first();
        $buildTimeName = "BuildTime: " . date('Ymd H:i:s');
        $userInfoName = "User: $user->name";
        $proxies[] = [
            "name" => $buildTimeName,
            "type" => "socks5",
            "port" => '10086',
            "server" => "127.0.0.1",
            "username" => $user->name,
            "password" => $request->token,
        ];
        $proxies[] = [
            "name" => $userInfoName,
            "type" => "socks5",
            "port" => '10010',
            "server" => "127.0.0.1",
            "username" => $user->name,
            "password" => $request->token,
        ];

        $general['proxies'] = $proxies;

        $proxiesNameList = array_merge($proxiesNameList, ['DIRECT', 'REJECT']);
        $rules = [];
        $proxyGroups = [];
        $proxyGroups[] = [
            'name' => 'Proxy',
            'type' => 'select',
            'proxies' => $proxiesNameList
        ];
        $proxyGroups[] = [
            'name' => env('APP_NAME') . " Info",
            'type' => 'select',
            'proxies' => [
                "Proxy",
                $buildTimeName,
                $userInfoName,
            ]
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
        if ($request->download) {
            return response($yaml)->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . env('APP_NAME') . ' Proxy"');
        } else {
            return response($yaml)->header('Content-Type', 'text/plain');
        }
    }

}
