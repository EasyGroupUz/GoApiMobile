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
    public function __construct(){
        date_default_timezone_set("Asia/Tashkent");
    }
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
        $model = UserVerify::where('phone_number',(int)$fields['phone_number'])->first();
        if(isset($model->id)){
            if($model->verify_code == $fields['verify_code']){
                if(!isset($model->user->id)){
                    $new_user = new User();
                    $old_user = User::orderBy('created_at', 'DESC')->first();
                    if(isset($old_user) && isset($old_user->personal_account)){
                        $new_user->personal_account = $old_user->personal_account+1;
                    }else{
                        $new_user->personal_account = 1000000;
                    }
                    $personal_info = new PersonalInfo();
                    $personal_info->phone_number = (int)$fields['phone_number'];
                    $personal_info->save();
                    $new_user->personal_info_id = $personal_info->id;
                    $new_user->save();
                    $model->user_id = $new_user->id;
                    $model->save();
                    $new_user->email = $model->phone_number;
                    $new_user->password = Hash::make($model->verify_code);
                    $token = $new_user->createToken('myapptoken')->plainTextToken;
                    $new_user->token = $token;
                    $new_user->save();
                    $message = 'Success';
                    $status = true;
                }else{
                    $model->user->email = $model->phone_number;
                    if(!isset($model->user->personalInfo)){
                        if(isset($model->user->personalInfo->phone_number)){
                            $personal_info = $model->user->personalInfo;
                            $personal_info->phone_number = (int)$fields['phone_number'];
                        }else{
                            $personal_info = new PersonalInfo();
                            $personal_info->phone_number = (int)$fields['phone_number'];
                        }
                        $personal_info->save();
                        $model->user->personal_info_id = $personal_info->id;
                    }
                    $model->user->password = Hash::make($model->verify_code);
                    $token = $model->user->createToken('myapptoken')->plainTextToken;
                    $model->user->token = $token;
                    $model->user->save();
                    $message = 'Success';
                    $status = true;
                }
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
     *                     description="write your first_name",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="lastname",
     *                     description="write your last_name",
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
        if(!isset($auth_user->personalInfo)){
            $personal_info = new PersonalInfo();
            $personal_info->first_name = $request->first_name;
            $personal_info->last_name = $request->last_name;
        }else{
            $personal_info = $auth_user->personalInfo;
            $personal_info->first_name = $request->first_name;
            $personal_info->last_name = $request->last_name;
        }
        $personal_info->save();
        $auth_user->personal_info_id = $personal_info->id;
        $auth_user->save();

        $response = [
            'status'=>true,
            'message'=>'Success',
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
