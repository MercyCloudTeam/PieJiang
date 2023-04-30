<?php

namespace App\Http\Controllers;

use App\Models\Access;
use App\Models\Proxy;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ServerController extends Controller
{

    public function statusUpdate(Request $request, Server $server)
    {
//        $server->update([
//            'status' => $request->status,
//        ]);
//        return redirect()->route('servers.index');
        Log::info('statusUpdate', [$request->all()]);
    }

    public function update(Request $request, Server $server)
    {
        $this->validate($request, [
            'domain' => 'required|string|max:255',
            'ip' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'config' => 'nullable|json',
        ]);

        $server->update([
            'domain' => $request->domain,
            'ip' => $request->ip,
            'name' => $request->name,
            'country' => $request->country,
            'config' => json_decode($request->config, true),
        ]);
        Proxy::where('server_id', $server->id)->update(
            ['name'=> "[$request->country]" . $request->name]
        );
        return redirect()->route('servers.index');
    }

    public function destroy(Server $server)
    {
        //delete proxy
//        $server->proxies->delete();
        Proxy::where('server_id', $server->id)->delete();
        //delete access
        Access::where('server_id', $server->id)->delete();
        //delete server
        $server->delete();

        return redirect()->route('servers.index');
    }

    public function destroyCert(Server $server)
    {
        $server->update(['config' => null]);
        // $server->config['cert'] = null;
        // $server->config['key'] = null;
        // $server->save();
        return response()->json(['message' => 'success']);
    }

    public function certKey(Server $server, Request $request)
    {
        if (empty($server->config['key'])) {
            $certController = new CertController();
            $certController->serverCert($server);
            $server = $server->fresh();
        }
        if ($request->download) {
            return response()->make($server->config['key'], 200)->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename=' . $server->domain . '.key"');
        }
        return response()->make($server->config['key'], 200)->header('Content-Type', 'text/plain');
    }

    public function cert(Server $server, Request $request)
    {
        if (empty($server->config['cert'])) {
            $certController = new CertController();
            $certController->serverCert($server);
            $server = $server->fresh();
        }
        if ($request->download) {
            return response()->make($server->config['cert'], 200)->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $server->domain . '.pem"');
        }
        return response()->make($server->config['cert'], 200)->header('Content-Type', 'text/plain');
    }

    public function bindBash(Request $request, Server $server)
    {
        $bash = [
            '#!/bin/bash',
            'curl "' . route('api.dns.rand.domain', ['token' => $request->token, 'download' => true, 'ip' => $server->ip]) . '" > /etc/bind/' . env('RAND_DOMAIN') . '.zone',
            // 'echo "Include \\"/etc/bind/db.'.env('RAND_DOMAIN').'\\";" > /etc/bind/named.conf.local',
            //build named.conf.local
            'echo "zone \\"' . env('RAND_DOMAIN') . '\\" {" > /etc/bind/named.conf.local',
            'echo "    type master;" >> /etc/bind/named.conf.local',
            'echo "    file \\"/etc/bind/' . env('RAND_DOMAIN') . '.zone\\";" >> /etc/bind/named.conf.local',
            'echo "};" >> /etc/bind/named.conf.local',

            'sleep 1',
            'service bind9 restart',
            'sleep 5',
            'service bind9 status',

            //save script
            'mkdir -p /scripts',
            'echo "#!/bin/bash" > /scripts/bind9-config-updare.sh',
            'echo "curl \\"' . route('api.dns.rand.domain', ['token' => $request->token, 'download' => true, 'ip' => $server->ip]) . '\\" > /etc/bind/' . env('RAND_DOMAIN') . '.zone" >> /scripts/bind9-config-updare.sh',
            'echo "sleep 1" >> /scripts/bind9-config-updare.sh',
            'echo "service bind9 restart" >> /scripts/bind9-config-updare.sh',

        ];
        $bash = implode(PHP_EOL, $bash);

        if ($request->download) {
            return response()->make($bash, 200)->header('Content-Type', 'text/plain')->header('Content-Disposition', 'attachment; filename="bind.sh"');
        } else {
            return response()->make($bash, 200)->header('Content-Type', 'text/plain');
        }
    }


    public function bash(Server $server, Request $request)
    {
        $bash = [
            '#!/bin/bash',
            'mkdir -p /ssl',
            'curl "' . route('api.server.cert.key', ['server' => $server->id, 'token' => $server->token, 'download' => true]) . '" > /ssl/cert.key',
            'curl "' . route('api.server.cert', ['server' => $server->id, 'token' => $server->token, 'download' => true]) . '" > /ssl/cert.pem',
            'chmod -R 777 /ssl',
            'echo -n "Server or Access? [s/a]:"',
            'read mode',
            'if [ "$mode" = "s" ]; then',
            'curl "' . route('api.server.xray.config', ['server' => $server->id, 'token' => $server->token, 'download' => true]) . '" >  /usr/local/etc/xray/config.json',
            'elif [ "$mode" = "a" ]; then',
            'curl "' . route('api.server.xray.config.access', ['server' => $server->id, 'token' => $server->token, 'download' => true]) . '" >  /usr/local/etc/xray/config.json',
            'fi',
            'sleep 1',
            'service xray restart',
            'sleep 5',
            'service xray status',

            //save script
            'mkdir -p /scripts',
            'echo "#!/bin/bash" > /scripts/xray-config-updare.sh',
            'echo "curl \\"' . route('api.server.xray.config', ['server' => $server->id, 'token' => $server->token, 'download' => true]) . '\\" >  /usr/local/etc/xray/config.json" >> /scripts/xray-config-updare.sh',
            'echo "sleep 1" >> /scripts/xray-config-updare.sh',
            'echo "service xray restart" >> /scripts/xray-config-updare.sh',
            'chmod +x /scripts/xray-config-updare.sh',

            'echo "Done! PieJiang Love You!"'
        ];
        //is server or access

        //bash to file
        $bash = implode(PHP_EOL, $bash);

        if ($request->download) {
            return response()->make($bash, 200)->header('Content-Type', 'text/plain')->header('Content-Disposition', 'attachment; filename="install.sh"');
        } else {
            return response()->make($bash, 200)->header('Content-Type', 'text/plain');
        }
    }

    public function index()
    {
        return Inertia::render('Server/Index', [
            'servers' => Server::all(),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid signature',
            ], 401);
        }

        try {
            $request->validate([
                'ip' => 'nullable|ip',
                'name' => 'nullable|string',
                'location' => 'nullable|string',
                'country' => 'nullable|string',
                'plain' => 'boolean|nullable',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid request',
            ], 400);
        }

        $ip = $request->get('ip') ?? $request->ip();
        $server = Server::updateOrCreate([
            'ip' => $ip,
        ], [
            'name' => $request->get('name'),
            'location' => $request->get('location'),
            'country' => $request->get('country'),
            'status' => "UP",
            'domain' => Str::random(12) . "." . env('RAND_DOMAIN'),
            'token' => Str::random(32),
        ]);

        if ($request->get('plain')) {
            return response()->make(
                "Server Token:" . $server->token . PHP_EOL .
                "Xray Config:" . route('api.server.xray.config', ['server' => $server->id, 'token' => $server->token]) . PHP_EOL
                , 200)->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'message' => 'Server registered successfully',
            'data' => $server->toArray(),
        ], 200);
    }

    public function registerUrl(Request $request)
    {
        $request->validate([
            'ip' => 'nullable|ip',
            'name' => 'nullable|string',
            'location' => 'nullable|string',
            'country' => 'nullable|string',
            'plain' => 'boolean|nullable',
        ]);
        return response()->json([
            'message' => 'Server register url',
            'data' => [
                'json' => URL::temporarySignedRoute(
                    'api.server.register',
                    now()->addMinutes(30)
                ),
                'plain' => URL::temporarySignedRoute(
                    'api.server.register',
                    now()->addMinutes(30),
                    ['plain' => true]
                ),
                'params' => URL::temporarySignedRoute(
                    'api.server.register',
                    now()->addMinutes(30),
                    [
                        'ip' => $request->get('ip'),
                        'name' => $request->get('name'),
                        'location' => $request->get('location'),
                        'country' => $request->get('country'),
                        'plain' => $request->get('plain') ?? false,
                    ]
                ),
//                'url' => URL::temporarySignedRoute('api.server.register', now()->addMinutes(5)),
            ],
        ], 200);
    }
}
