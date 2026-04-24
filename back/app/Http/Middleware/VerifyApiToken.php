<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->readToken($request);
        $expected = env('API_TOKEN');

        if (empty($expected) || $token === null || ! hash_equals($expected, $token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }

    private function readToken(Request $request): ?string
    {
        if ($bearer = $request->bearerToken()) {
            return $bearer;
        }

        $header = $request->header('X-Api-Token');
        if ($header !== null && $header !== '') {
            return $header;
        }

        return null;
    }
}
