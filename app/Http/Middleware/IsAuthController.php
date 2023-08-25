<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAuthController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        date_default_timezone_set("Asia/Tashkent");
        if(isset(Auth::user()->deleted_at)){
            return response()->json('Your account had been deleted', 401);
        }else{
            return $next($request);
        }
    }
}
