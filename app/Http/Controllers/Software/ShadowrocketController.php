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
            case 'ss':
                $url = "{$proxy->config['method']}:{$proxy->config['password']}@{$proxy->server->ip}:{$proxy->port}";
                $result = "ss://".base64_encode($url);
                $result .= '#'.$name;
                break;
            case 'vmess':
                $url = "auto:{$proxy->config['uuid']}@{$proxy->server->ip}:{$proxy->port}";
                $temp = "vmess://" . base64_encode($url) . "?alterId={$proxy->config['alterId']}";
//                obfsParam=hahs.due&path=/&obfs=websocket&alterId=0
//                &path=/&obfs=websocket&alterId=0
                if ($proxy->config['network'] == 'ws') {
                    $temp .= "&obfsParam={$proxy->domain}&path=/&obfs=websocket";
                }
                $result = $temp;
                $result .= '#'.$name;
                break;
            case 'trojan':
//                ?allowInsecure=1&plugin=obfs-local;obfs=websocket;obfs-host=domain.q;obfs-uri=/tls
                $url = "{$proxy->config['password']}@{$proxy->server->ip}:{$proxy->port}?peer={$proxy->domain}";
                $temp = "trojan://" . $url ;
//                $temp = "trojan://" . base64_encode($url) ;
                if (!empty($proxy->config['network']) && $proxy->config['network'] == 'ws') {
                    $temp .= "?obfs=websocket&obfsParam={$proxy->domain}";
                }
                $result = $temp;
                $result .= '#'.$name;
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
