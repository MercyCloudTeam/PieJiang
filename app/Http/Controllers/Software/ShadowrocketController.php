<?php

namespace App\Http\Controllers\Software;

use App\Http\Controllers\Controller;
use App\Models\Proxy;
use App\Models\User;
use Illuminate\Http\Request;

class ShadowrocketController extends Controller
{

    public function shadowrocketConfig(Request $request)
    {
        $user = User::where('token', $request->token)->first();
        $proxies = Proxy::all();
        $result = [
            "REMARKS=" . env('APP_NAME'),
            "STATUS=USER-$user->name"
        ];
        foreach ($proxies as $proxy) {
            switch ($proxy->type) {
                case 'ss':
                    $url = "{$proxy->config['method']}:{$proxy->config['password']}@{$proxy->ip}:{$proxy->port}";
                    $result[] = "ss://".base64_encode($url);
                    break;
                case 'vmess':
                    $url = "{$proxy->config['uuid']}@{$proxy->ip}:{$proxy->port}";
                    $temp = "vmess://".base64_encode($url)."?remarks={$proxy->display_name}&alterId={$proxy->config['alterId']}";
                    if ($proxy->config['network'] == 'ws'){
//                        $result[] = "obfs=websocket&obfsParam={$proxy->domain}";
                    }
                    $result[] = $temp ;
                    break;

            }
        }

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
