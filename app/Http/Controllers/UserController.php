<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserVerify;
use App\Models\PersonalInfo;
use App\Models\User;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users/show",
     *     tags={"Users"},
     *     summary="Finds Pets by status",
     *     description="Multiple status values can be provided with comma separated string",
     *     operationId="show",
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
    public function show(Request $request){
        $language = $request->header('language');
        $model = Auth::user();
        if(isset($model->device_type) && isset($model->device_id)){
            $device_types = json_decode($model->device_type);
            $device_id = json_decode($model->device_id);
            $i = -1;
            foreach ($device_types as $device_type){
                $i++;
                $device[] = ['type'=>$device_type??'', 'id'=>$device_id[$i]??''];
            }
        }
        if(isset($model->personalInfo)){
            $first_name = $model->personalInfo->first_name?$model->personalInfo->first_name.' ':'';
            $last_name = $model->personalInfo->last_name?strtoupper($model->personalInfo->last_name[0].'. '):'';
            $middle_name = $model->personalInfo->middle_name?strtoupper($model->personalInfo->middle_name[0].'.'):'';
           
            if(isset($model->personalInfo->avatar)){
                $avatar = storage_path('app/public/avatar/'.$model->personalInfo->avatar);
                if(file_exists($avatar)){
                    $model->personalInfo->avatar = asset('storage/avatar/'.$model->personalInfo->avatar);
                }
            }
            $list = [
                'device'=>$device??[],
                'img'=>$model->personalInfo->avatar,
                'full_name'=>$first_name.''.strtoupper($last_name).''.strtoupper($middle_name),
                'birth_date'=>$model->personalInfo->birth_date,
                'gender'=>$model->personalInfo->gender,
                'phone_number'=>$model->personalInfo->phone_number,
                'rating'=>$model->rating,
            ];
            return $this->success('Success', 201, $list);
        }else{
            return $this->error(translate_api('No personal info', $language), 201, $device??null);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/users/update",
     *     tags={"Users"},
     *     summary="Update user",
     *     operationId="update",
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
     *                     property="first_name",
     *                     description="write your firstname",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     description="write your lastname",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="middle_name",
     *                     description="write your middlename",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="birth_date",
     *                     description="write your birth date format data(1999-01-21)" ,
     *                     type="date",
     *                 ),
     *                 @OA\Property(
     *                     property="gender",
     *                     description="write your gender",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     description="write your email",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     description="Enter your photo",
     *                     type="file",
     *                 ),
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function update(Request $request){
        $language = $request->header('language');
        $model = Auth::user();
        if(isset($model->personalInfo->id)){
            $personal_info = $model->personalInfo;
        }else{
            $personal_info = new PersonalInfo();
        }if(!isset($request->first_name)){
            return $this->error(translate_api('first name is not entered', $language), 400);
        }
        if(!isset($request->last_name)){
            return $this->error(translate_api('last name is not entered', $language), 400);
        }
        if(!isset($request->middle_name)){
            return $this->error(translate_api('middle name is not entered', $language), 400);
        }
        if(!isset($request->birth_date)){
            return $this->error(translate_api('birth date is not entered', $language), 400);
        }
        if(!isset($request->gender)){
            return $this->error(translate_api('gender is not entered', $language), 400);
        }
        if(!isset($request->email)){
            return $this->error(translate_api('email is not entered', $language), 400);
        }
        $personal_info->first_name = $request->first_name;
        $personal_info->last_name = $request->last_name;
        $personal_info->middle_name = $request->middle_name;
        $personal_info->birth_date = $request->birth_date;
        $personal_info->gender = $request->gender;
        $personal_info->email = $request->email;
        $letters = range('a', 'z');
        $user_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
        $user_random = implode("", $user_random_array);
        $user_img = $request->file('avatar');
        if(isset($user_img)){
            if(isset($personal_info->avatar) && $personal_info->avatar != ''){
                $avatar = storage_path('app/public/avatar/'.$personal_info->avatar??'no');
                if(file_exists($avatar)){
                    unlink($avatar);
                }
            }
            $image_name = $user_random . '' . date('Y-m-dh-i-s') . '.' . $user_img->extension();
            $user_img->storeAs('public/avatar/', $image_name);
            $personal_info->avatar = $image_name;
        }
        $personal_info->save();
        return $this->success(translate_api('Success', $language), 201);
    }
    /**
     * @OA\Post(
     *     path="/api/users/delete",
     *     tags={"Users"},
     *     summary="Delete user",
     *     operationId="delete",
     *     @OA\Response(
     *         response=405,
     *         description="Invalid input"
     *     ),
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object"
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function delete(Request $request){
        $language = $request->header('language');
        $model = Auth::user();
        if(isset($model->personalInfo)){
            $model->personalInfo->delete();
        }
        $user_verify = UserVerify::where('user_id', $model->id)->first();
        if(isset($user_verify->id)){
            $user_verify->delete();
        }
        $model->delete();
        return $this->success('Success', 201);
    }

    public function feedback(Request $request)
    {
        if (!isset($request['name']) || $request['name'] == '')
            return $this->error('name parameter is missing', 400);

        if (!isset($request['phone']) || $request['phone'] == '')
            return $this->error('phone parameter is missing', 400);

        if (!isset($request['message']) || $request['message'] == '')
            return $this->error('message parameter is missing', 400);

        $newPersonalInfo = new PersonalInfo();
        $newPersonalInfo->last_name = $request['name'];
        $newPersonalInfo->phone_number = $request['phone'];
        if (!$newPersonalInfo->save())
            return $this->error('PersonalInfo is not saved', 400);

        $newUser = new User();
        $newUser->about_me = $request['message'];
        $newUser->personal_info_id = $newPersonalInfo->id;
        $newUser->balance = 0;
        $newUser->personal_account = rand(100000, 999999);
        $newUser->rating = 4.5;
        if (!$newUser->save())
            return $this->error('User is not saved', 400);

        return $this->success('success', 200);
    }
}
