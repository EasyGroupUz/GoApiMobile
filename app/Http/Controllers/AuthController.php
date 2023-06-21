<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use http\Env\Response;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class AuthController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login with your email",
     *     operationId="Login",
     *     @OA\Response(
     *         response=405,
     *         description="Invalid input"
     *     ),
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     description="write your email",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     description="write your password",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     )
     * )
     */
   public function Login(Request $request){
       $fields = $request->validate([
          'email'=>'required|string',
          'password'=>'required|string'
       ]);
       $user = User::where('email', $fields['email'])->first();
       if(!$user||!Hash::check($fields['password'], $user->password)){
           return response(['message'=>'bad creds', 401]);
       }
       $token = $user->createToken('myapptoken')->plainTextToken;
       $user->token = $token;
       $user->save();
       $data = [
         'id'=>$user->id,
         'role'=>$user->role->name,
         'company'=>$user->company->name,
         'first_name'=>$user->personalInfo->first_name,
         'last_name'=>$user->personalInfo->last_name,
         'middle_name'=>$user->personalInfo->middle_name,
         'email'=>$user->email,
         'created_at'=>$user->created_at,
       ];
       $response = [
           'status'=>true,
           'message'=>'Success',
           'token'=>$token,
           'token_expired_date' => date('Y-m-d H:i:s', strtotime('+24 hours')),
           'user'=>$data
       ];
       return response($response, 201);
   }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout",
     *     operationId="Logout",
     *     @OA\Response(
     *         response=405,
     *         description="Invalid input"
     *     ),
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function Logout() {
        auth()->user()->tokens()->delete();
        $response = [
            'status'=>true,
            'message'=>'Logged out'
        ];
        return response($response);
    }
}
