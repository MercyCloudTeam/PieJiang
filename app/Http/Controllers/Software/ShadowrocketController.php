<?php

namespace App\Http\Controllers\Software;

use App\Http\Controllers\Controller;
use App\Models\Access;
use App\Models\Proxy;
use App\Models\User;
use Illuminate\Http\Request;

class ShadowrocketController extends Controller
{

    public function makeUrl($proxy)
    {
        $result = '';
        $name = urlencode($proxy->display_name);
        switch ($proxy->type) {
//            case 'ss':
//                $url = "{$proxy->config['method']}:{$proxy->config['password']}@{$proxy->ip}:{$proxy->port}";
//                $result = "ss://".base64_encode($url);
//                break;
            case 'vmess':
                $url = "{$proxy->config['uuid']}@{$proxy->ip}:{$proxy->port}";
                $temp = "vmess://" . base64_encode($url) . "?remarks={$name}&alterId={$proxy->config['alterId']}";
                if ($proxy->config['network'] == 'ws') {
                    $temp .= "&obfs=websocket&obfsParam={$proxy->domain}";
                }
                $result = $temp;
                break;
//            case 'trojan':
//                $url = "{$proxy->config['password']}@{$proxy->ip}:{$proxy->port}";
//                $temp = "trojan://" . base64_encode($url) . "?remarks={$name}";
//                if ($proxy->config['network'] == 'ws') {
//                    $temp .= "&obfs=websocket&obfsParam={$proxy->domain}";
//                }
//                $result = $temp;
        }

        return $result;
    }

    public function shadowrocketConfig(Request $request)
    {
        $user = User::where('token', $request->token)->first();
        $proxies = Proxy::all();
        $access = Access::all();
        $result = [
            "REMARKS=" . env('APP_NAME'),
            "STATUS=USER-$user->name"
        ];
        foreach ($proxies as $proxy) {
            $result[] = $this->makeUrl($proxy);
        }

        foreach ($access as $item) {
            $result[] = $this->makeUrl($item);
        }

        //remove empty lines
        $result = array_filter($result);

        dd($result);
        $resp = implode("\n", $result);
        $resp = base64_encode($resp);
        if ($request->download) {
            return response($resp)->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . env('APP_NAME') . ' Proxy"');
        } else {
            return response($resp)->header('Content-Type', 'text/plain');
        }
    }

}
