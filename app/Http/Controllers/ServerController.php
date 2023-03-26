<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ServerController extends Controller
{
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
            'domain' => Str::random(12) . ".network.vg",
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
