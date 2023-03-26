<?php

namespace App\Http\Middleware;

use App\Models\Server;
use App\Models\User;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyServerToken
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            //only alpha string
            $token = (string)$request->get('token');
            if (!preg_match('/^[a-zA-Z0-9]+$/', $token)) {
                return response()->json([
                    'message' => 'Failed to verify token',
                ], 401);
            }
            Server::where('token', $token)->firstOrFail();
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Invalid Server Token',
            ], 401);
        }
        return $next($request);
    }
}
