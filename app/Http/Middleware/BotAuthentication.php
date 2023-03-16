<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BotAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-He4rt-Authorization');

        if (! $apiKey) {
            return response()->json(['error' => 'Chave não encontrada'], Response::HTTP_UNAUTHORIZED);
        }

        if ($apiKey !== config('he4rt.server_key')) {
            return response()->json(['error' => 'Chave incorreta'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
