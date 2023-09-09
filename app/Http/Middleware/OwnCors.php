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
        header('Content-Type: application/json');
            header('Access-Control-Allow-Methods: GET POST PUT DELETE');
            header('Access-Control-Allow-Ceredentials: true');
            header('Access-Control-Allow-Headers: Authorization, Accept, Content-Type');

        return $next($request);
    }
}
