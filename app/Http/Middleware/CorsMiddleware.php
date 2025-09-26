<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');
        
        // Debug logging
        \Log::info('CORS Middleware Hit', [
            'method' => $request->method(),
            'origin' => $origin,
            'url' => $request->fullUrl(),
            'is_options' => $request->isMethod('OPTIONS')
        ]);
       
        $allowedOrigins = [
            'https://motorssooq.com',
            'https://www.motorssooq.com',
            'https://dashboard.motorssooq.com',
            'http://dashboard.motorssooq.com',
            'http://localhost:3000',
            'http://localhost:3001',
            'https://motors.azsystems.tech',
            'https://ipapi.co',
            'http://motors.azsystems.tech'
        ];

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
            
            \Log::info('Handling OPTIONS request', [
                'origin' => $origin,
                'allowed' => in_array($origin, $allowedOrigins)
            ]);
            
            if ($origin && in_array($origin, $allowedOrigins)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, X-API-Key');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Max-Age', '86400');
                
                \Log::info('CORS headers added to OPTIONS response');
            }
            
            return $response;
        }
        
        // Process the actual request
        $response = $next($request);
        
        // Set CORS headers for actual requests
        if ($origin && in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, X-API-Key');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            
            \Log::info('CORS headers added to actual response');
        }
       
        return $response;
    }
}