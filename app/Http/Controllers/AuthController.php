<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use App\Models\PersonalInfo;
use App\Models\User;
use GuzzleHttp\Client;
use http\Env\Response;
use App\Models\UserVerify;
use App\Models\EskizToken;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
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
        $language = $request->header('language');
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
        }
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
            $eskizToken->expire_date = strtotime('+28 days');
            $eskizToken->save();
        }elseif(strtotime('now') > (int)$eskiz_token->expire_date){
            $guzzle_request = new GuzzleRequest('POST', 'https://notify.eskiz.uz/api/auth/login');
            $res = $client->sendAsync($guzzle_request, $token_options)->wait();
            $res_array = json_decode($res->getBody());
            $eskizToken = EskizToken::first();
            $eskizToken->token = $res_array->data->token;
            $eskizToken->expire_date = strtotime('+28 days');
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
                    'contents' => translate_api('GoEasy - Sizni bir martalik tasdiqlash kodingiz', $language).': '.$random
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
            $user_verify->verify_code = $random;
            $user_verify->save();
            return $this->success("Success", 200, ['Verify_code'=>$random]);
        }else{
            return $this->error(translate_api("Fail message not sent. Try again", $language), 400, ['Verify_code'=>$random]);
        }
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
        $language = $request->header('language');
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
                    $new_user->rating = 4.5;
                    $new_user->save();
                    $model->user_id = $new_user->id;
                    $model->save();
                    $new_user->email = $model->phone_number;
                    $new_user->password = Hash::make($model->verify_code);
                    $token = $new_user->createToken('myapptoken')->plainTextToken;
                    $new_user->token = $token;
                    $new_user->device_type = json_encode([$fields['device_type']]);
                    $new_user->device_id = json_encode([$fields['device_id']]);
                    $new_user->save();
                    $message = 'Success';
                    return $this->success($message, 201, ['token'=>$token]);
                }else{
                    $model->user->email = $model->phone_number;
                    if(!isset($model->user->personalInfo)){
                        $personal_info = new PersonalInfo();
                        $personal_info->phone_number = (int)$fields['phone_number'];
                        $personal_info->save();
                        $model->user->personal_info_id = $personal_info->id;
                    }
                    $model->user->password = Hash::make($model->verify_code);
                    $token = $model->user->createToken('myapptoken')->plainTextToken;
                    $model->user->token = $token;
                    if($fields['device_id'] != null && $fields['device_id'] != ''){
                        if($model->user->device_id == null || $model->user->device_id == ''){
                            if($model->user->device_type == null || $model->user->device_type == ''){
                                if($fields['device_type'] != null && $fields['device_type'] != ''){
                                    $model->user->device_type = json_encode([$fields['device_type']]);
                                }
                            }else{
                                $device_type = json_decode($model->user->device_type);
                                if($fields['device_type'] != null && $fields['device_type'] != ''){
                                    $model->user->device_type = json_encode(array_merge($device_type, [$fields['device_type']]));
                                }
                            }
                            $model->user->device_id = json_encode([$fields['device_id']]);
                        }else{
                            $device_id = json_decode($model->user->device_id);
                            if(!in_array($fields['device_id'], $device_id)){
                                $model->user->device_id = json_encode(array_merge($device_id, [$fields['device_id']]));
                                if($model->user->device_type == null || $model->user->device_type == ''){
                                    if($fields['device_type'] != null && $fields['device_type'] != ''){
                                        $model->user->device_type = json_encode([$fields['device_type']]);
                                    }
                                }else{
                                    $device_type = json_decode($model->user->device_type);
                                    if($fields['device_type'] != null && $fields['device_type'] != ''){
                                        $model->user->device_type = json_encode(array_merge($device_type, [$fields['device_type']]));
                                    }
                                }
                            }
                        }
                    }
                    if($model->user->rating == null || $model->user->rating == ''){
                        $model->user->rating = 4.5;
                    }
                    $model->user->save();
                    $message = 'Success';
                    return $this->success($message, 201, ['token'=>$token]);
                }
            }else{

                $message = 'Failed your token didn\'t match';
                $token = 'no token';
                return $this->error(translate_api($message, $language), 400, ['token'=>$token]);
            }
        }else{
            $message = 'Failed your token didn\'t match';
            $token = 'no token';
            return $this->error(translate_api($message, $language), 400, ['token'=>$token]);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/phone-update/verify",
     *     tags={"Auth"},
     *     summary="Confirm with your phone and with your verify code",
     *     operationId="resetLoginToken",
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
    public function resetLoginToken(Request $request){
        $language = $request->header('language');
        $fields = $request->validate([
            'phone_number'=>'required',
            'verify_code'=>'required',
            'device_type'=>'nullable',
            'device_id'=>'nullable',
        ]);
        $user = Auth::user();
        if(isset($user->personalInfo->phone_history)){
            $phone_history = json_decode($user->personalInfo->phone_history);
            if(end($phone_history) != $fields['phone_number']){
                return $this->error(translate_api('Failed phone number is not correct', $language), 400);
            }
            if($user->userVerify->verify_code == $fields['verify_code']){
                $user->email = $fields['phone_number'];
                if(!isset($user->personalInfo)){
                    $personal_info = new PersonalInfo();
                    $personal_info->phone_number = (int)$fields['phone_number'];
                    $personal_info->save();
                    $user->personal_info_id = $personal_info->id;
                }
                $user->password = Hash::make($user->userVerify->verify_code);
                $token = $user->createToken('myapptoken')->plainTextToken;
                $user->token = $token;
                if($fields['device_id'] != null && $fields['device_id'] != ''){
                    if($user->device_id == null || $user->device_id == ''){
                        if($user->device_type == null || $user->device_type == ''){
                            if($fields['device_type'] != null && $fields['device_type'] != ''){
                                $user->device_type = json_encode([$fields['device_type']]);
                            }
                        }else{
                            $device_type = json_decode($user->device_type);
                            if($fields['device_type'] != null && $fields['device_type'] != ''){
                                $user->device_type = json_encode(array_merge($device_type, [$fields['device_type']]));
                            }
                        }
                        $user->device_id = json_encode([$fields['device_id']]);
                    }else{
                        $device_id = json_decode($user->device_id);
                        if(!in_array($fields['device_id'], $device_id)){
                            $user->device_id = json_encode(array_merge($device_id, [$fields['device_id']]));
                            if($user->device_type == null || $user->device_type == ''){
                                if($fields['device_type'] != null && $fields['device_type'] != ''){
                                    $user->device_type = json_encode([$fields['device_type']]);
                                }
                            }else{
                                $device_type = json_decode($user->device_type);
                                if($fields['device_type'] != null && $fields['device_type'] != ''){
                                    $user->device_type = json_encode(array_merge($device_type, [$fields['device_type']]));
                                }
                            }
                        }
                    }
                }
                if($user->rating == null || $user->rating == ''){
                    $user->rating = 4.5;
                }
                $user->userVerify->phone_number = $fields['phone_number'];
                $user->userVerify->save();
                $user->save();
                $message = 'Success';
                return $this->success($message, 201, ['token'=>$token]);
            }else{
                $message = 'Failed your token didn\'t match';
                $token = 'no token';
                return $this->error(translate_api($message, $language), 400, ['token'=>$token]);
            }
        }else{
            $message = 'Failed your token didn\'t match';
            $token = 'no token';
            return $this->error(translate_api($message, $language), 400, ['token'=>$token]);
        }
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
        $language = $request->header('language');
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
        return $this->success('Success', 201);
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
        return $this->success('Logged out', 200);
    }

    public function PhoneUpdate(Request $request){
        $language = $request->header('language');
        $user = Auth::user();
        $fields = $request->validate([
            'phone'=>'required|string'
        ]);
        $client = new Client();
        $eskiz_token = EskizToken::first();
        $user_verify = UserVerify::where('user_id', $user->id)->first();
        $random = rand(100000, 999999);
        if($user_verify->phone_number == (int)$fields['phone']){
            return $this->error(translate_api("Failed enter new phone number ", $language), 400, ['Verify_code'=>$random]);
        }
        $user_verify_phone = UserVerify::where('phone_number', (int)$fields['phone'])->first();
        if(isset($user_verify_phone->phone_number) && $user_verify_phone->phone_number == (int)$fields['phone']){
            return $this->error(translate_api("Failed enter new phone number this number exists", $language), 400, ['Verify_code'=>$random]);
        }
        if(isset($user_verify_phone->phone_number) && $user_verify->phone_number == (int)$fields['phone']){
            return $this->error(translate_api("Failed enter new phone number ", $language), 400, ['Verify_code'=>$random]);
        }
        $user_verify->phone_number = (int)$fields['phone'];
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
            $eskizToken->expire_date = strtotime('+28 days');
            $eskizToken->save();
        }elseif(strtotime('now') > (int)$eskiz_token->expire_date){
            $guzzle_request = new GuzzleRequest('POST', 'https://notify.eskiz.uz/api/auth/login');
            $res = $client->sendAsync($guzzle_request, $token_options)->wait();
            $res_array = json_decode($res->getBody());
            $eskizToken = EskizToken::first();
            $eskizToken->token = $res_array->data->token;
            $eskizToken->expire_date = strtotime('+28 days');
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
                    'contents' => translate_api('GoEasy - Sizni bir martalik tasdiqlash kodingiz', $language).': '.$random
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
            $user_verify->verify_code = $random;
            if(isset($user_verify->user->personalInfo->phone_history)){
                $phone_history = json_decode($user_verify->user->personalInfo->phone_history);
                $user_verify->user->personalInfo->phone_history = json_encode(array_merge($phone_history, [(int)$fields['phone']]));
            }else{
                $user_verify->user->personalInfo->phone_history = json_encode([(int)$fields['phone']]);
            }
            $user_verify->user->personalInfo->save();
            return $this->success("Success", 200, ['Verify_code'=>$random]);
        }else{
            return $this->error(translate_api("Fail message not sent. Try again", $language), 400, ['Verify_code'=>$random]);
        }
    }

}
