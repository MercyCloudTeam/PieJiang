<?php

namespace App\Http\Controllers;

use App\Models\Proxy;
use App\Models\ProxyGroup;
use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class ProxyController extends Controller
{
    public function show(Request $request)
    {

    }

    public function configure(Request $request)
    {

    }

    public function importRules()
    {

    }

    public function clashConfig()
    {
//        $this->importRules();
        $general = Yaml::parseFile(storage_path('app/GeneralClashConfig.yml'));

        //generate rules
        $groups = ProxyGroup::all()->load('rules');
        $proxiesList = Proxy::all()->pluck('name')->toArray();

        $proxiesList = array_merge($proxiesList, ['DIRECT', 'REJECT']);

        $rules = [];
        $proxyGroups = [];
        $proxyGroups[] = [
            'name' => 'Proxy',
            'type' => 'select',
            'proxies' => $proxiesList
        ];

        $proxiesList[] = 'Proxy';
        $proxyGroups[] = [
            'name' => 'ByPass',
            'type' => 'select',
            'proxies' => $proxiesList
        ];

        foreach ($groups as $group) {
            $proxyGroups[] = [
                'name' => $group->name,
                'type' => $group->type,
                'proxies' => $proxiesList,
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
//                ]
//                $rules[] = $rule->type . ',' . $rule->content . ',' . $group->name;
            }
        }
        $rules[] = 'MATCH,ByPass';

//        dd($rules,$proxyGroups);

//        $general['proxies'] = Proxy::all()->toArray();
        $general['proxy-groups'] = $proxyGroups;
        $general['rules'] = $rules;

        //out yaml
        $yaml = Yaml::dump($general, 10, 2);
        return response($yaml)->header('Content-Type', 'text/plain');
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
            "inbounds" => [

            ],
            "outbounds" => [
                [
                    "protocol" => "freedom",
                    "settings" => [
                        "domainStrategy" => "UseIP"
                    ],
                    "tag" => "direct"
                ],
                [
                    "protocol" => "blackhole",
                    "settings" => [],
                    "tag" => "block"
                ]
            ],
        ];
    }
}
