<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiGenericToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-API-TOKEN');
        if ($token !== env('GENERIC_API_TOKEN')) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid API Token'
            ], 401);
        }
        return $next($request);
    }
}
