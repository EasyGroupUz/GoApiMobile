<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
//        if($request->hasHeader('Access-Control-Request-Headers')) {
//            $response->header('Access-Control-Allow-Origin', $request->headers('Access-Control-Request-Headers'));
//        }
//        if($request->hasHeader('Access-Control-Request-Methods')) {
//            $response->header('Access-Control-Allow-Methods', $request->headers('Access-Control-Request-Methods'));
//        }
//        $response->header('Access-Control-Allow-Ceredentials', 'true');

        return $response;
    }
}
