<?php

namespace App\Http\Middleware;

use Closure;

class BotAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
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
