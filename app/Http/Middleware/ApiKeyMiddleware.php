<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = (string) $request->header('X-API-Key', '');
        $validApiKey = config('app.api_key') ?? env('API_KEY');

        if (!$validApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key not configured on server'
            ], 500);
        }

        if ($apiKey === '' || !hash_equals((string) $validApiKey, $apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing API key'
            ], 401);
        }

        return $next($request);
    }
}
