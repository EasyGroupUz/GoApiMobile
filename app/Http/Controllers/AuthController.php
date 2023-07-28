<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\PersonalInfo;
use App\Models\User;
use GuzzleHttp\Client;
use http\Env\Response;
use App\Models\UserVerify;
use App\Models\EskizToken;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        $fields = $request->validate([
            'phone'=>'required|string'
        ]);
        $client = new Client();
        $eskiz_token = EskizToken::first();
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
        $status = true;
        $token_options = [
            'multipart' => [
                [
                    'name' => 'email',
                    'contents' => 'easysolutiongroupuz@gmail.com'
                ],
                [
                    'name' => 'password',
                    'contents' => '4TYvyjOof4CmOUk5CisHHUzzQ5Mcn1mirx0VBuQV'
                ]
            ]
        ];
        if(!isset($eskiz_token->expire_date)){
            $guzzle_request = new GuzzleRequest('POST', 'https://notify.eskiz.uz/api/auth/login');
            $res = $client->sendAsync($guzzle_request, $token_options)->wait();
            $res_array = json_decode($res->getBody());
            $eskizToken = new EskizToken();
            $eskizToken->token = $res_array->data->token;
            $eskizToken->expire_date = strtotime('+29 days 23 hours');
            $eskizToken->save();
        }elseif(strtotime('now') > (int)$eskiz_token->expire_date){
            $guzzle_request = new GuzzleRequest('POST', 'https://notify.eskiz.uz/api/auth/login');
            $res = $client->sendAsync($guzzle_request, $token_options)->wait();
            $res_array = json_decode($res->getBody());
            $eskizToken = EskizToken::first();
            $eskizToken->token = $res_array->data->token;
            $eskizToken->expire_date = strtotime('+29 days 23 hours');
            $eskizToken->save();
        }
        $eskiz_token = '';
        $eskiz_token = EskizToken::first();
        $options = [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => "Bearer $eskiz_token->token",
            ],
            'multipart' => [
                [
                    'name' => 'mobile_phone',
                    'contents' => $request->phone
                ],
                [
                    'name' => 'message',
                    'contents' => "GoEasy - Sizni bir martalik tasdiqlash kodingiz: $random"
                ],
                [
                    'name' => 'from',
                    'contents' => '4546'
                ],
            ]
        ];
        $guzzle_request = new GuzzleRequest('POST', 'https://notify.eskiz.uz/api/message/sms/send');
        $res = $client->sendAsync($guzzle_request, $options)->wait();
        $result = $res->getBody();
        $result = json_decode($result);
        if(isset($result)){
            $status = true;
            $message = "Success";
        }else{
            $status = false;
            $message = translate("Fail message not sent. Try again");
        }
        $user_verify->verify_code = $random;
        $user_verify->save();
        $response = [
            "data"=>[
                'Verify_code'=>$random,
            ],
            'status'=>$status,
            'message'=>$message,
        ];
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
     *                 ),
     *                 @OA\Property(
     *                     property="device_type",
     *                     description="write your device type",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="device_id",
     *                     description="write your device id",
     *                     type="string",
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function loginToken(Request $request){
        $fields = $request->validate([
             'phone_number'=>'required',
             'verify_code'=>'required',
             'device_type'=>'nullable',
             'device_id'=>'nullable',
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
                    $new_user->device_type = $fields['device_type'];
                    $new_user->device_id = $fields['device_id'];
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

                    if($model->user->device_type == null || $model->user->device_type == ''){
                        $model->user->device_type = $fields['device_type'];
                    }
                    if($model->user->device_id == null || $model->user->device_id == ''){
                        $model->user->device_id = $fields['device_id'];
                    }
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
            'data'=>[
                'token' => $token,
            ],
            'status'=>$status,
            'message'=>$message
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

}
