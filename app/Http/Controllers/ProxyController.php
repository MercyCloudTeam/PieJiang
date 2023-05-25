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
use Illuminate\Http\Resources\Json\JsonResource;
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
        'ss2022'
    ];

    public function destroyAccess(Access $access)
    {
        $access->delete();
        return Redirect::route('access.index');
    }


    public function portUpdate(Proxy $proxy, Request $request)
    {
        $this->validate($request, [
            'port' => 'required|integer',
        ]);
        $proxy->update([
            'port' => $request->port,
        ]);

        return new JsonResource($proxy);

    }

    public function update(Proxy $proxy, Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'type' => 'required|in:' . implode(',', $this->types),
            'config' => 'required|json',
            'port' => 'required|integer|min:1|max:65535',
            'domain' => 'nullable|string',
        ]);
        $proxy->update([
            'name' => $request->name,
            'type' => $request->type,
            'port' => $request->port,
            'domain' => $request->domain,
            'config' => json_decode($request->config, true),
        ]);

        return Redirect::route('proxies.index');

    }


    public function destroy(Proxy $proxy)
    {
        $proxy->delete();
        return Redirect::route('proxies.index');
    }

    public function initialProxy(Server $server,string $type)
    {
        switch ($type) {
            case 'trojan':
                $name = "[$server->country]" . $server->name;
                return Proxy::create([
                    'name' => $name,
                    'listen' => $server->ip,
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
                        'listen' => $server->ip,
                    ]
                ]);
            case 'ss':
                $name = "[$server->country]" . $server->name;
                return Proxy::create([
                    'name' => $name,
                    'type' => 'ss',
                    'port' => mt_rand(10000, 65535),
                    'server_id' => $server->id,
                    'config' => [
                        'password' => Str::random(16),
                        'method' => 'chacha20-poly1305',
                        'udp' => true,
                        'listen' => $server->ip,
                    ]
                ]);
            case 'ss2022':
                $name = "[$server->country]" . $server->name;
                // openssl rand -base64 16
                return Proxy::create([
                    'name' => $name,
                    'type' => 'ss2022',
                    'port' => mt_rand(10000, 65535),
                    'server_id' => $server->id,
                    'config' => [
                        'password' => base64_encode(Str::random(16)),
                        'method' => '2022-blake3-aes-128-gcm',
                        'udp' => true,
                        'listen' => $server->ip,
                    ]
                ]);
            case 'vmess':
                $name = "[$server->country]" . $server->name;
                return Proxy::create([
                    'name' => $name,
                    'listen' => $server->ip,
                    'type' => 'vmess',
                    'port' => mt_rand(10000, 65535),
                    'server_id' => $server->id,
                    'config' => [
                        'uuid' => Str::uuid(),
                        'alterId' => 0,
                        'listen' => $server->ip,
                        'network' => 'ws',

                    ]
                ]);
            case 'vless':
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
                return Proxy::create([
                    'name' => $name,
                    'type' => 'vless',
                    'port' => 443,
                    'server_id' => $server->id,
                    'listen' => $server->ip,
                    'config' => [
                        'uuid' => Str::uuid(),
                        //x25519 key
                        'privateKey' => $privateKey,
                        'pubKey' => $pubKey,
                        'security' => 'reality',

                        'spiderX' => '/',
                        'serverName' => ['www.lovelive-anime.jp', 'lovelive-anime.jp'],

                        'listen' => $server->ip,
                    ]
                ]);
            default:
                return false;
        }
    }

    public function initialProxyConfig($type)
    {
        $config = [];

        switch ($type) {
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
        return $config;
    }

    public function storeAccess(Request $request)
    {
        $this->validate($request, [
            'name' => 'nullable|string',
            'proxy_id' => 'required|exists:proxies,id',
            'server_id' => 'required|exists:servers,id',
            'port' => 'required|integer|min:1|max:65535',
            'type' => 'required|in:' . implode(',', $this->types),
        ]);

        $server = Server::find($request->server_id);
        $proxy = Proxy::find($request->proxy_id);
        if (empty($request->name)) {
            $request->name = "[{$server->country}->{$proxy->server->country}]{$server->name}->{$proxy->server->name}";
        }

        $config = $this->initialProxyConfig($request->type);

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

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'nullable|string',
            'port' => 'required|integer|min:1|max:65535',
            'type' => 'required|in:' . implode(',', $this->types),
        ]);

        $server = Server::find($request->server_id);
        $proxy = Proxy::find($request->proxy_id);
        if (empty($request->name)) {
            $request->name = "[{$server->country}]{$proxy->server->name}";
        }

        $config = $this->initialProxyConfig($request->type);

        Proxy::updateOrCreate([
            'proxy_id' => $request->proxy_id,
            'server_id' => $request->server_id,
            'type' => $request->type,
        ], [
            'name' => $request->name,
            'port' => $request->port,
            'config' => $config,
        ]);

        return Redirect::route('proxy.index');
    }

    public function index()
    {
        return Inertia::render('Proxy/Index', [
            'proxies' => Proxy::all()->load('server'),
            'proxyGroups' => ProxyGroup::all(),
            'servers' => Server::all(),
            'types' => $this->types,
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


}
