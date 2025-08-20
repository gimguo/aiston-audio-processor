<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-API-Token');
        
        if (!$token) {
            return response()->json([
                'error' => 'Authentication token is required',
                'message' => 'Please provide a valid API token in Authorization header or X-API-Token header'
            ], 401);
        }
        
        $validTokens = config('auth.api_tokens', []);
        
        if (!in_array($token, $validTokens)) {
            return response()->json([
                'error' => 'Invalid authentication token',
                'message' => 'The provided API token is not valid'
            ], 401);
        }
        
        return $next($request);
    }
}
