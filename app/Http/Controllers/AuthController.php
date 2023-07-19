<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\PersonalInfo;
use App\Models\User;
use http\Env\Response;
use App\Models\UserVerify;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login with your phone",
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
     *                     property="phone",
     *                     description="write your phone",
     *                     type="integer",
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function Login(Request $request){

        date_default_timezone_set("Asia/Tashkent");
        $fields = $request->validate([
            'phone'=>'required|string'
        ]);
        $user_verify = UserVerify::where('phone_number', (int)$fields['phone'])->first();
        $random = rand(100000, 999999);
        if(!isset($user_verify->id)){
            $user_verify = new UserVerify();
            $user_verify->phone_number = (int)$request->phone;
            $user_verify->status_id = 1;
            $message = 'Success. Phone number registered';
        }else{
            $message = 'Success';
        }
        $user_verify->verify_code = $random;
        $user_verify->save();
        $response = [
            'Status'=>true,
            'Message'=>$message,
            'Verify_code'=>$random
        ];
        Log::info(['token'=>$random]);
        return response()->json($response);
    }
//    /**
//     * @OA\Post(
//     *     path="/api/login",
//     *     tags={"Auth"},
//     *     summary="Login with your email",
//     *     operationId="Login",
//     *     @OA\Response(
//     *         response=405,
//     *         description="Invalid input"
//     *     ),
//     *     @OA\RequestBody(
//     *         description="Input data format",
//     *         @OA\MediaType(
//     *             mediaType="application/x-www-form-urlencoded",
//     *             @OA\Schema(
//     *                 type="object",
//     *                 @OA\Property(
//     *                     property="email",
//     *                     description="write your email",
//     *                     type="string",
//     *                 ),
//     *                 @OA\Property(
//     *                     property="password",
//     *                     description="write your password",
//     *                     type="string"
//     *                 )
//     *             )
//     *         )
//     *     )
//     * )
//     */
   public function Logins(Request $request){
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
     *     path="/api/verify",
     *     tags={"Auth"},
     *     summary="Confirm with your phone and with your verify code",
     *     operationId="loginToken",
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
     *                     property="phone_number",
     *                     description="write your phone",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="verify_code",
     *                     description="write your verify code",
     *                     type="integer",
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function loginToken(Request $request){
        $fields = $request->validate([
             'phone_number'=>'required',
            'verify_code'=>'required'
        ]);
        $random = rand(1000000, 9999999);
        $model = UserVerify::where('phone_number',(int)$fields['phone_number'])->first();
        if(isset($model->id)){
            if($model->verify_code == $fields['verify_code']){
                if(!isset($model->user->id)){
                    $new_user = new User();
                    $old_user = User::order_by('created_at', 'desc')->first();
                    if(isset($old_user) && isset($old_user->personal_account)){
                        $new_user->personal_account = $old_user->personal_account+1;
                    }else{
                        $new_user->personal_account = 1000000;
                    }
                    $new_user->save();
                    $model->user_id = $new_user->id;
                    $model->save();
                    $model->user->email = $model->phone_number;
                    $model->user->password = Hash::make($model->verify_code);
                }
                $token = $model->user->createToken('myapptoken')->plainTextToken;
                $model->user->token = $token;
                $model->user->save();
                $message = 'Success';
                $status = true;
            }else{
                $message = 'Failed your token didn\'t match';
                $status = false;
                $token = 'no token';
            }
        }else{
            $message = 'Failed your token didn\'t match';
            $status = false;
            $token = 'no token';
        }
        $response = [
            'Status'=>$status,
            'Message'=>$message,
            'token' => $token
        ];
        return response()->json($response);
    }


    /**
     * @OA\Post(
     *     path="/api/set-name-surname",
     *     tags={"Auth"},
     *     summary="Set name surname",
     *     operationId="Set_name_surname",
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
     *                     property="firstname",
     *                     description="write your firstname",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="lastname",
     *                     description="write your lastname",
     *                     type="string",
     *                 )
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */

    public function Set_name_surname(Request $request) {
        $auth_user = Auth::user();
        $personal_info = new PersonalInfo();
        $personal_info->first_name = $request->firstname;
        $personal_info->last_name = $request->lastname;
        $personal_info->save();
        $auth_user->personal_info_id = $personal_info->id;
        $auth_user->save();
        $data = [
            'id'=>$auth_user->id,
            'first_name'=>$auth_user->personalInfo->first_name,
            'last_name'=>$auth_user->personalInfo->last_name,
            'email'=>$auth_user->token,
            'created_at'=>$auth_user->created_at,
        ];
        $response = [
            'status'=>true,
            'message'=>'Success',
            'user'=>$data
        ];
        return response()->json($response, 201);
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


    /**
     * @OA\Get(
     *     path="/api/verify-get",
     *     tags={"Phone"},
     *     summary="Finds Pets by status",
     *     description="Multiple status values can be provided with comma separated string",
     *     operationId="loginToken_get",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status values that needed to be considered for filter",
     *         required=true,
     *         explode=true,
     *         @OA\Schema(
     *             default="available",
     *             type="string",
     *             enum={"available", "pending", "sold"},
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid status value"
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function loginToken_get(){
        return response()->json('good');
    }
}
