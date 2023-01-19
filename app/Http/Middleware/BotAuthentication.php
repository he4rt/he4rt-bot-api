<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BotAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('Authorization');

        if (!$apiKey) {
            return response()->json(['error' => 'Chave não encontrada'], 401);
        }

        if ($apiKey !== config('he4rt.server_key')) {
            return response()->json(['error' => 'Chave incorreta'], 401);
        }
        return $next($request);
    }
}
