<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        if(isset($request->phone_number) && isset($request->Bearer_token)){
            $model = UserVerify::where('phone_number', $request->phone_number)->first();
            if(isset($model->id)){
                if($model->verify_code == $request->verify_code){
                    return $next($request);
                }else{
                    $message = 'Token error. re-enter the token';
                }
            }else{
                $message = 'Enter your phone to authentificate';
            }
        }
        $response = [
            'Status'=>false,
            'Message'=>$message
        ];
        return response()->json($response);
    }
}
