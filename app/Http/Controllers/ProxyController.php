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
            'domain' => 'nullable|string',
        ]);
        $proxy->update([
            'name' => $request->name,
            'type' => $request->type,
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
