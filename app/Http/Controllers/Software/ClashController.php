<?php

namespace App\Http\Controllers\Software;

use App\Http\Controllers\Controller;
use App\Models\Access;
use App\Models\Proxy;
use App\Models\ProxyGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\Yaml\Yaml;

class ClashController extends Controller
{

    public function processProxiesList($list)
    {
        $result = [];
        foreach ($list as $proxy) {
            switch ($proxy->type) {
                case 'trojan':
                    $result[] = [
                        'name' => $proxy->display_name,
                        'type' => 'trojan',
                        'server' => $proxy->server->ip,
                        'port' => $proxy->port,
                        'password' => $proxy->config['password'],
                        'sni' => $proxy->config['sni'] ?? $proxy->server->domain,
                        'skip-cert-verify' => $proxy->config['skip-cert-verify'] ?? true,
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
                case 'socks5':
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
                    if ($proxy->config['method'] == 'chacha20-poly1305') {
                        $method = 'chacha20-ietf-poly1305';
                    }
                    $result[] = [
                        'name' => $proxy->display_name,
                        'type' => 'ss',
                        'server' => $proxy->server->ip,
                        'port' => $proxy->port,
                        'cipher' => $method ?? $proxy->config['method'],
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
                    // dd($proxy->config);
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

    public function getProxies()
    {
        $result = [];
        $proxies = Proxy::all();
        //get access
        $access = Access::all();

        $proxiesList = $this->processProxiesList($proxies);
        $accessList = $this->processProxiesList($access);
//        dd($accessList);

        return array_merge($proxiesList, $accessList);
    }

    public function clashConfig(Request $request)
    {

        $general = Yaml::parseFile(storage_path('app/GeneralClashConfig.yml'));
        //generate rules
        $groups = ProxyGroup::all()->load('rules');
//        $proxiesNameList = Proxy::all()->pluck('display_name')->toArray();
        $proxies = $this->getProxies();


//        dd($proxies);
        foreach ($proxies as $k => $proxy) {
            //filter proxies
            if ($request->filter != ''){
                $filterList = explode(',', $request->filter);
                foreach ($filterList as $filterType){
                    if ($proxy['type'] == $filterType){
                        unset($proxies[$k]);
                    }
                }
            }
            if (!empty($proxies[$k])){
                $proxiesNameList[] = $proxy['name'];
            }
        }
        $proxies = array_values($proxies);
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

        $proxiesNameList = array_merge(['DIRECT', 'REJECT'], $proxiesNameList);
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

        // $proxiesNameList[] = 'Proxy';
        $proxyGroups[] = [
            'name' => 'ByPass',
            'type' => 'select',
            'proxies' => array_merge($proxiesNameList, ['Proxy'])
        ];
        array_unshift($proxiesNameList, "Proxy");


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
