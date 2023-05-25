<?php

namespace App\Http\Controllers\Software;

use App\Http\Controllers\Controller;
use App\Models\Access;
use App\Models\Proxy;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class XrayController extends Controller
{
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

        $proxiesType = [
            'ss', 'ss2022', 'vless', 'vmess', 'trojan'
        ];
        $proxies = Proxy::where('server_id', $server->id)->get();
        foreach ($proxies as $item) {
            if (!in_array($item->type, $proxiesType)) {
                continue;
            }
            $base['inbounds'][] = $this->makeXrayInboundConfig($item);
        }

        if (!empty($server->config['cloudflare-warp']) && $server->config['cloudflare-warp']) {
            $base['outbounds'][] = [
                'tag' => 'cloudflare-warp-socks',
                'protocol' => 'socks',
                'settings' => [
                    'servers' => [
                        [
                            'address' => '127.0.0.1',
                            'port' => 40000,
                        ]
                    ]
                ]
            ];

            $inboundTags = [];
            foreach ($proxies as $item) {
                if (!in_array($item->type, $proxiesType)) {
                    continue;
                }
                $inboundTags[] = trim($item->name . '-' . $item->type . '-' . 'inbound');
            }
            $base['routing']['rules'][] = [
                'type' => 'field',
                'inboundTag' => $inboundTags,
                'outboundTag' => 'cloudflare-warp-socks',
            ];
        }

        return response()->json($base, 200, [], 448);
    }


    public function generateXrayAccessConfig(Server $server)
    {
        $access = Access::where('server_id', $server->id)->get();
        $proxies = Proxy::where('server_id', $server->id)->get();
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

        foreach ($proxies as $item) {
            $base['inbounds'][] = $this->makeXrayInboundConfig($item);
        }

        foreach ($access as $item) {
            $proxy = $item->proxy;
            // dd( $this->makeXrayOutboundConfig($proxy) );
            $base['outbounds'][] = $this->makeXrayOutboundConfig($proxy);
//            dd($proxy);
            //out in
            $base['inbounds'][] = $this->makeXrayInboundConfig($item);

            $inboundNameTag = trim($item->name . '-' . $item->type . '-' . 'inbound'.'-'.$proxy->port);
            $outboundNameTag = trim($proxy->name . '-' . $proxy->type . '-' . 'outbound'.'-'.$proxy->port);

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

    public function makeXrayInboundConfig(Proxy|Access $proxy, array $addons = [])
    {
        $nameTag = trim($proxy->name . '-' . $proxy->type . '-' . 'inbound'.'-'.$proxy->port);
        switch ($proxy->type) {
            case 'trojan':
                $result = [
                    //Trojan
                    'port' => $proxy->port,
                    'protocol' => 'trojan',
                    'listen' => $proxy->config['listen'] ?? '0.0.0.0',
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
            case 'ss2022':
                $result = [
                    'port' => $proxy->port,
                    'listen' => $proxy->config['listen'] ?? '0.0.0.0',
                    'tag' => $nameTag,
                    'protocol' => 'shadowsocks',
                    'settings' => [
                        'method' => $proxy->config['method'],
                        'password' => $proxy->config['password'],
                        'udp' => $proxy->config['udp'] ?? false,
                    ],
                    'sniffing' => [
                        'enabled' => true,
                        'destOverride' => ['http', 'tls']
                    ],
                ];
                break;
            case 'vmess':
                $temp = [
                    //vmess
                    'port' => $proxy->port,
                    'listen' => $proxy->config['listen'] ?? '0.0.0.0',
                    'tag' => $nameTag,
                    'protocol' => 'vmess',
                    'settings' => [
                        'clients' => [
                            ['id' => $proxy->config['uuid'], 'alterId' => $proxy->config['alterId']]
                        ],
                    ],

                ];

                if ($proxy->config['network'] === 'ws') {
                    $result['streamSettings'] = [
                        'network' => 'ws',
                        'security' => 'none',
                    ];
                }
                $result = array_merge($temp, $result);
                break;

            case 'socks5':
                $result = [
                    //socks5
                    'port' => $proxy->port,
                    'listen' => $proxy->config['listen'] ?? '0.0.0.0',
                    'tag' => $nameTag,
                    'protocol' => 'socks',
                    'settings' => [
                        'auth' => 'password',
                        'accounts' => [
                            [
                                'user' => $proxy->config['username'] ?? '',
                                'pass' => $proxy->config['password'] ?? '',
                            ]
                        ],
                        'udp' => $proxy->config['udp'] ?? false,
                    ],
                ];
                break;
            case 'vless':
                $result = [
                    //VLESS
                    'port' => $proxy->port,
                    'listen' => $proxy->config['listen'] ?? '0.0.0.0',
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

    public function makeXrayOutboundConfig(Proxy|Access $proxy, array $addons = [])
    {
        $nameTag = trim($proxy->name . '-' . $proxy->type . '-' . 'outbound'.'-'.$proxy->port);
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
            case 'ss2022':
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
                                        'encryption' => 'none',
                                        'flow' => $proxy->config['flow'] ?? '',
                                    ]
                                ]
                            ]
                        ]
                    ],
                ];
                if (isset($proxy->config['security']) && $proxy->config['security'] == 'reality') {
                    $result['streamSettings'] = [
                        'network' => 'grpc',
                        'security' => 'reality',
                        'realitySettings' => [
                            'show' => false,
                            'fingerprint' => "chrome",
                            'serverName' => $proxy->config['serverName'][0],
                            'publicKey' => $proxy->config['pubKey'],
                            "shortId" => "",
                            'spiderX' => $proxy->config['spiderX'] ?? "",
                        ],
                        'grpcSettings' => [
                            'serviceName' => 'grpc',
                            'multiMode' => true
                        ],
                    ];
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


}
