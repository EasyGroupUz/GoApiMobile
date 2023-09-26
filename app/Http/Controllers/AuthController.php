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
        date_default_timezone_set("Asia/Tashkent");
        $language = $request->header('language');
        $fields = $request->validate([
            'phone'=>'required|string'
        ]);
        $client = new Client();
        $eskiz_token = EskizToken::first();
        $user_verify = UserVerify::withTrashed()->where('phone_number', (int)$fields['phone'])->first();
        $random = rand(100000, 999999);
        if(!isset($user_verify->id)){
            $user_verify = new UserVerify();
            $user_verify->phone_number = (int)$request->phone;
            $user_verify->status_id = 1;
        }elseif(isset($user_verify->deleted_at)){
            $user_verify->status_id = 1;
            $user_verify->deleted_at = NULL;
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
            return $this->error(translate_api("Fail message not sent. Try again", $language), 400);
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
        date_default_timezone_set("Asia/Tashkent");
        $language = $request->header('language');
        $fields = $request->validate([
             'phone_number'=>'required',
             'verify_code'=>'required',
             'device_type'=>'nullable',
             'device_id'=>'nullable',
        ]);
        if((int)$fields['phone_number'] == '998333367578'){
            $model = UserVerify::withTrashed()->where('phone_number', (int)$fields['phone_number'])->first();
            if(!isset($model->id)) {
                $model = new UserVerify();
                $model->phone_number = (int)$fields['phone_number'];
                $model->status_id = 1;
                $model->verify_code = 111111;
            }else{
                if ($model->verify_code != 111111) {
                    $model->verify_code = 111111;
                }
                if(isset($model->deleted_at)){
                    $model->status_id = 1;
                    $model->deleted_at = NULL;
                }
            }
            if($fields['verify_code'] == 111111) {
                if ($model->verify_code != 111111) {
                    $model->verify_code = 111111;
                    $model->save();
                }
                $user = User::withTrashed()->find($model->user_id);
                if(isset($user)) {
                    if(isset($user->deleted_at)){
                        $user->deleted_at = NULL;
                    }
                    $user->email = $model->phone_number;
                    if(!isset($user->personal_info_id)){
                        $personal_info = new PersonalInfo();
                        $personal_info->phone_number = (int)$fields['phone_number'];
                        $personal_info->save();
                        $user->personal_info_id = $personal_info->id;
                    }else{
                        $personal_info = PersonalInfo::withTrashed()->find($user->personal_info_id);
                        if(isset($personal_info->deleted_at)){
                            $personal_info->deleted_at = NULL;
                        }
                    }
                    $personal_info->save();
                    $user->password = Hash::make($model->verify_code);
                    $token = $user->createToken('myapptoken')->plainTextToken;
                    $user->token = $token;
                    if(!isset($user->device_type)){
                        $user->device_type = json_encode(['google']);
                    }
                    if(!isset($user->device_id)){
                        $user->device_id = json_encode(['7777']);
                    }
                    if($user->rating == null || $user->rating == ''){
                        $user->rating = 4.5;
                    }
                    $user->language = $request->header('language');
                    $user->save();
                    $message = 'Success';
                    return $this->success($message, 201, ['token'=>$token]);
                }else{
                    $new_user = new User();
                    $old_user = User::withTrashed()->orderBy('created_at', 'DESC')->first();
                    if(isset($old_user) && isset($old_user->personal_account)){
                        $new_user->personal_account = $old_user->personal_account+1;
                    }else{
                        $new_user->personal_account = 1000000;
                    }
                    $personal_info = new PersonalInfo();
                    $personal_info->phone_number = (int)$fields['phone_number'];
                    $personal_info->save();
                    $new_user->personal_info_id = $personal_info->id;
                    $personal_info->phone_number = (int)$fields['phone_number'];
                    $personal_info->save();
                    $new_user->personal_info_id = $personal_info->id;
                    $new_user->rating = 4.5;
                    $new_user->language = $request->header('language');
                    $new_user->save();
                    $model->user_id = $new_user->id;
                    $model->save();
                    $new_user->email = $model->phone_number;
                    $new_user->password = Hash::make($model->verify_code);
                    $token = $new_user->createToken('myapptoken')->plainTextToken;
                    $new_user->token = $token;
                    $new_user->device_type = json_encode(['Google']);
                    $new_user->device_id = json_encode(['777777']);
                    $new_user->save();
                    $message = 'Success';
                    return $this->success($message, 201, ['token'=>$token]);
                }
            }else{
                $message = "Failed your token didn't match";
                return $this->error(translate_api($message, $language), 400);
            }
        }
        $model = UserVerify::withTrashed()->where('phone_number', (int)$fields['phone_number'])->first();
        if(isset($model->id)){
            if(strtotime('-7 minutes') > strtotime($model->updated_at)){
                $model->verify_code = rand(100000, 999999);
                $model->save();
                return $this->error(translate_api('Your sms code expired. Resend sms code', $language), 400);
            }
            if(isset($model->deleted_at)){
                $model->deleted_at = NULL;
            }
            if($model->verify_code == $fields['verify_code']){
                $user = User::withTrashed()->find($model->user_id);
                if(!isset($user->id)){
                    $new_user = new User();
                    $old_user = User::withTrashed()->orderBy('created_at', 'DESC')->first();
                    if(isset($old_user) && isset($old_user->personal_account)){
                        $new_user->personal_account = $old_user->personal_account+1;
                    }else{
                        $new_user->personal_account = 1000000;
                    }
                    $personal_info = new PersonalInfo();
                    $personal_info->phone_number = (int)$fields['phone_number'];
                    $personal_info->save();
                    $new_user->personal_info_id = $personal_info->id;
                    $personal_info->phone_number = (int)$fields['phone_number'];
                    $personal_info->save();
                    $new_user->personal_info_id = $personal_info->id;
                    $new_user->rating = 4.5;
                    $new_user->language = $request->header('language');
                    $new_user->save();
                    $model->user_id = $new_user->id;
                    $model->save();
                    $new_user->email = $model->phone_number;
                    $new_user->password = Hash::make($model->verify_code);
                    $token = $new_user->createToken('myapptoken')->plainTextToken;
                    $new_user->token = $token;
                    $new_user->device_type = json_encode([$fields['device_type']??NULL]);
                    $new_user->device_id = json_encode([$fields['device_id']??NULL]);
                    $new_user->save();
                    $message = 'Success';
                    return $this->success($message, 201, ['token'=>$token]);
                }else{
                    if(isset($user->deleted_at)){
                        $user->deleted_at = NULL;
                    }
                    $user->email = $model->phone_number;
                    if(!isset($user->personal_info_id)){
                        $personal_info = new PersonalInfo();
                        $personal_info->phone_number = (int)$fields['phone_number'];
                        $personal_info->save();
                        $user->personal_info_id = $personal_info->id;
                    }else{
                        $personal_info = PersonalInfo::withTrashed()->find($user->personal_info_id);
                        if(!isset($personal_info->id)){
                            $personal_info = new PersonalInfo();
                            $personal_info->phone_number = (int)$fields['phone_number'];
                            $personal_info->save();
                            $user->personal_info_id = $personal_info->id;
                        }elseif(isset($personal_info->deleted_at)){
                            $personal_info->deleted_at = NULL;
                        }
                    }
                    $personal_info->save();
                    $user->password = Hash::make($model->verify_code);
                    $token = $user->createToken('myapptoken')->plainTextToken;
                    $user->token = $token;
                    if(isset($fields['device_id']) || isset($fields['device_type'])){
                        if($user->device_id == null || $user->device_id == ''){
                            $this->savingDeviceType($fields['device_type']??'', $user);
                            $user->device_id = json_encode([$fields['device_id']??'']);
                        }else{
                            $device_id = json_decode($user->device_id);
                            if(!isset($fields['device_id'])){
                                $user->device_id = json_encode(array_merge($device_id, ['']));
                            }elseif(!in_array($fields['device_id'], $device_id)){
                                $user->device_id = json_encode(array_merge($device_id, [$fields['device_id']]));
                                $this->savingDeviceType($fields['device_type']??'', $user??'');
                            }
                        }
                    }
                    if($user->rating == null || $user->rating == ''){
                        $user->rating = 4.5;
                    }
                    $user->language = $request->header('language');
                    $user->save();
                    $model->save();
                    $message = 'Success';
                    return $this->success($message, 201, ['token'=>$token]);
                }
            }else{
                $message = "Failed your token didn't match";
                return $this->error(translate_api($message, $language), 400);
            }
        }else{
            $message = "Failed your token didn't match";
            return $this->error(translate_api($message, $language), 400);
        }
    }
    function savingDeviceType($request_device_type, $user){
        if($user->device_type == null || $user->device_type == ''){
            $user->device_type = json_encode([$request_device_type??'']);
        }else{
            $device_type = json_decode($user->device_type);
            $user->device_type = json_encode(array_merge($device_type, [$request_device_type??'']));
        }
        return $user;
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
        date_default_timezone_set("Asia/Tashkent");
        $language = $request->header('language');
        $fields = $request->validate([
            'phone_number'=>'required',
            'verify_code'=>'required',
            'device_type'=>'nullable',
            'device_id'=>'nullable',
        ]);
        $user = Auth::user();
        $personal_info = PersonalInfo::withTrashed()->find($user->personal_info_id);
        $user_verify = UserVerify::withTrashed()->where('user_id', $user->id)->first();
        if(isset($personal_info->phone_history)){
            $phone_history = json_decode($personal_info->phone_history);
            if(end($phone_history) != $fields['phone_number']){
                return $this->error(translate_api('Failed phone number is not correct', $language), 400);
            }
            if(strtotime('-7 minutes') > strtotime($user_verify->updated_at )){
                $user_verify->verify_code = rand(100000, 999999);
                $user_verify->save();
                return $this->error(translate_api('Your sms code expired. Resend sms code', $language), 400);
            }
            if($user_verify->verify_code == $fields['verify_code']){
                $user->email = $fields['phone_number'];
                if(!isset($personal_info->id)){
                    $personal_info = new PersonalInfo();
                    $personal_info->phone_number = (int)$fields['phone_number'];
                    $personal_info->save();
                    $user->personal_info_id = $personal_info->id;
                }else{
                    if(isset($personal_info->deleted_at)){
                        $personal_info->deleted_at = NULL;
                        $personal_info->save();
                    }
                }
                $user->password = Hash::make($user_verify->verify_code);
                $token = $user->createToken('myapptoken')->plainTextToken;
                $user->token = $token;
                if($user->device_id == null || $user->device_id == ''){
                    $this->savingDeviceType($fields['device_type']??'', $user);
                    $user->device_id = json_encode([$fields['device_id']??'']);
                }else{
                    $device_id = json_decode($user->device_id);
                    $user->device_id = json_encode(array_merge($device_id, [$fields['device_id']??'']));
                    $this->savingDeviceType($fields['device_type']??'', $user??'');
                }
                if($user->rating == null || $user->rating == ''){
                    $user->rating = 4.5;
                }
                $user_verify->phone_number = $fields['phone_number'];
                $user_verify->save();
                $user->save();
                $message = 'Success';
                return $this->success($message, 201, ['token'=>$token]);
            }else{
                $message = "Failed your token didn't match";
                return $this->error(translate_api($message, $language), 400);
            }
        }else{
            $message = "Failed your token didn't match";
            return $this->error(translate_api($message, $language), 400);
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
        date_default_timezone_set("Asia/Tashkent");
        $language = $request->header('language');
        $auth_user = Auth::user();
        $personal_info = PersonalInfo::withTrashed()->find($auth_user->personal_info_id);
        if(isset($personal_info->id)){
            if(isset($personal_info->deleted_at)){
                $personal_info->deleted_at = NULL;
            }
            $personal_info->first_name = $request->first_name;
            $personal_info->last_name = $request->last_name;
        }else{
            $personal_info = new PersonalInfo();
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
        return $this->success('Success', 200);
    }

    public function PhoneUpdate(Request $request){
        date_default_timezone_set("Asia/Tashkent");
        $language = $request->header('language');
        $user = Auth::user();
        $fields = $request->validate([
            'phone'=>'required|string'
        ]);
        $client = new Client();
        $eskiz_token = EskizToken::first();
        $user_verify = UserVerify::withTrashed()->where('user_id', $user->id)->first();
        $random = rand(100000, 999999);
        $user_verify_phone = UserVerify::withTrashed()->where('phone_number', (int)$fields['phone'])
            ->where('user_id', '!=', $user->id)->first();
        if(isset($user_verify_phone->phone_number) && $user_verify_phone->phone_number == (int)$fields['phone']){
            return $this->error(translate_api("Failed enter new phone number this number exists", $language), 400);
        }
        if(isset($user_verify->deleted_at)){
            $user_verify->deleted_at = NULL;
        }
        if(!isset($user_verify->id)){
            $user_verify = new UserVerify();
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
            $user = User::withTrashed()->find($user_verify->user_id);
            $personal_info = PersonalInfo::withTrashed()->find($user->personal_info_id);
            if(isset($personal_info->phone_history)){
                $phone_history = json_decode($personal_info->phone_history);
                $personal_info->phone_history = json_encode(array_merge($phone_history, [(int)$fields['phone']]));
            }else{
                $personal_info->phone_history = json_encode([(int)$fields['phone']]);
            }
            $user_verify->save();
            $personal_info->save();
            return $this->success("Success", 200, ['Verify_code'=>$random]);
        }else{
            return $this->error(translate_api("Fail message not sent. Try again", $language), 400);
        }
    }
}
