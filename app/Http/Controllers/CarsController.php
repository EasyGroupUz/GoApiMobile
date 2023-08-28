<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cars;
use App\Models\CarList;
use App\Models\Driver;
use App\Models\Status;
use App\Models\ColorList;
use App\Models\ClassList;
use App\Models\CarTypes;
use App\Http\Requests\CarsRequest;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class CarsController extends Controller
{
    public function myTaxi(Request $request)
    {
        $language = $request->header('language');
        $cars = DB::table('yy_drivers as dt1')
            ->leftJoin('yy_cars as dt2', 'dt2.driver_id', '=', 'dt1.id')
            ->leftJoin('yy_car_lists as dt3', 'dt3.id', '=', 'dt2.car_list_id')
            ->leftJoin('yy_color_lists as dt4', 'dt4.id', '=', 'dt2.color_list_id')
            ->where('dt1.user_id', auth()->id())->where('dt2.deleted_at', NULL)
            ->select('dt2.id', 'dt2.images', 'dt2.reg_certificate_image', 'dt2.reg_certificate','dt2.production_date', 'dt3.name as car_name', 'dt4.name as color_name', 'dt4.name as color_code', 'dt4.id as color_id', 'dt1.created_at', 'dt1.updated_at')
            ->get()->toArray();
        $car_array = null;
        foreach ($cars as $car){
            $images_array = json_decode($car->images);
            if(gettype($images_array) == 'string'){
                $str_images = str_replace('[', '', $images_array);
                $str_images_1 = str_replace(']', '', $str_images);
                $images_array = str_replace("\"", '', $str_images_1);
                $images_array = explode(',', $images_array);
            }
            if(isset($images_array) && count($images_array)>0){
                foreach($images_array as $images){
                    $images_[] = asset("storage/cars/$images");
                }
            }
            $color_table = table_translate($car, 'color', $language);
            $car_array[] = [
                'id'=>$car->id,
                'images'=>$images_??[],
                'reg_certificate'=>$car->reg_certificate,
                'reg_certificate_image'=>asset("storage/certificate/$car->reg_certificate_image"),
                'production_date'=>$car->production_date,
                'car_name'=>$car->car_name,
                'color'=>$color_table->color_translation_name??$car->color_name,
                'color_code'=>$color_table->color_code??$car->color_code,
                'created_at'=>$car->created_at,
                'updated_at'=>$car->updated_at,
            ];
            $images_ = [];
        }
        if($car_array != null){
            return $this->success('Success', 200, $car_array);
        }else{
            return $this->success(translate_api('No my cars', $language), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/car/list",
     *     tags={"Users"},
     *     summary="Finds Pets by status",
     *     description="Multiple status values can be provided with comma separated string",
     *     operationId="information",
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

    public function information(Request $request){
        $language = $request->header('language');
        $car_types = CarTypes::select('id', 'name')->get();
        $class_lists = table_translate('', 'class_list', $language);
        $color_lists = table_translate('', 'color_list', $language);
        foreach ($car_types as $car_type){
            $model = $car_type->name;
            foreach($car_type->carList as $car_list){
                $list[] = [
                    "id" => $car_list->id,
                    "status_id" => $car_list->status_id,
                    "car_type_id" => $car_list->car_type_id,
                    "name" => $car_list->name,
                    "default_seats" => $car_list->default_seats,
                ];
            }
            $carList[] = [
                'id'=>$car_type->id,
                'model'=>$model??'',
                'list' => $list,
            ];
            $list = [];
        }

        if(count($class_lists)>0 && count($color_lists)>0 && count($carList)>0){
            return $this->success(translate_api('Success', $language), 200, [
                "class_list"=>$class_lists??[],
                "color_list"=>$color_lists??[],
                "car_list"=>$carList??[],
            ]);
        }elseif(count($class_lists) == 0){
            return $this->error(translate_api('No car class', $language), 400);
        }elseif(count($color_lists) == 0){
            return $this->error(translate_api('No car color', $language), 400);
        }elseif(count($carList) == 0){
            return $this->error(translate_api('No car list', $language), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/car/create",
     *     tags={"Users"},
     *     summary="",
     *     operationId="create",
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
     *                     property="class_id",
     *                     description="write your class(select option)",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="model_id",
     *                     description="write your model(select option)",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="color_id",
     *                     description="write your color(select option)",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="production_date",
     *                     description="write your production date",
     *                     type="date",
     *                 ),
     *                 @OA\Property(
     *                     property="state_number",
     *                     description="write your state number",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="wheel_side",
     *                     description="write your wheel side(select option)",
     *                     type="integer",
     *                 )
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */

    public function store(Request $request) {
        $language = $request->header('language');
        if(!isset($request->production_date)){
            return $this->error('Car production date is not entered', 400);
        }
        if(!isset($request->wheel_side)){
            return $this->error('Car wheel side is not entered', 400);
        }
        $car_list = CarList::find($request->model_id);
        if(!isset($car_list)){
            return $this->error(translate_api('Car list is not exist', $language), 400);
        }
        $color_list = ColorList::find($request->color_id);
        if(!isset($color_list)){
            return $this->error(translate_api('Color is not exist', $language), 400);
        }
        $color_list = ClassList::find($request->class_id);
        if(!isset($color_list)){
            return $this->error(translate_api('Class list is not exist', $language), 400);
        }
        $user = Auth::user();
        $cars = new Cars();
        $cars->status_id = 1;
        $cars->car_list_id = $request->model_id;
        $cars->reg_certificate =  $request->state_number;
        $cars->color_list_id =  $request->color_id;
        $cars->class_list_id =  $request->class_id;
        $cars->production_date =  $request->production_date;
        $cars->wheel_side =  $request->wheel_side;

        $letters = range('a', 'z');
        if(isset($request->reg_certificate_image)){
            $certificate_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
            $certificate_random = implode("", $certificate_random_array);
            $certificate_img = $request->file('reg_certificate_image');
            if(isset($certificate_img)) {
                $image_name = $certificate_random . '' . date('Y-m-dh-i-s') . '.' . $certificate_img->extension();
                $certificate_img->storeAs('public/certificate/', $image_name);
                $cars->reg_certificate_image = $image_name;
            }
        }
        $images = $request->file('images');
        if(isset($images)){
            foreach ($images as $image){
                $images_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
                $images_random = implode("", $images_random_array);
                $image_name = $images_random . '' . date('Y-m-dh-i-s') . '.' . $image->extension();
                $image->storeAs('public/cars/', $image_name);
                $images_array[] = $image_name;
            }
            $cars->images = json_encode($images_array);
        }
        $is_driver = Driver::where('user_id', $user->id)->first();

        if(!isset($is_driver)){
            $driver = new Driver();
            $driver->user_id = $user->id;
            $driver->status_id = 1;
            $driver->save();
        }else{
            $driver = $is_driver;
        }
        $cars->driver_id = $driver->id;
        $cars->save();
        return $this->success(translate_api('Success', $language), 201);
    }


    public function update(Request $request, $id) {
        $language = $request->header('language');
        $user = Auth::user();
        $cars = Cars::find($id);
        if(isset($request->production_date)){
            $cars->production_date = $request->production_date;
        }
        if(isset($request->wheel_side)){
            $cars->wheel_side = $request->wheel_side;
        }
        if(isset($request->model_id)){
            $car_list = CarList::find($request->model_id);
            if(!isset($car_list)){
                return $this->error(translate_api('Car list is not exist', $language), 400);
            }
            $cars->car_list_id = $request->model_id;
        }

        if(isset($request->color_id)){
            $color_list = ColorList::find($request->color_id);
            if(!isset($color_list)){
                return $this->error(translate_api('Color is not exist', $language), 400);
            }
            $cars->color_list_id = $request->color_id;
        }
        if(isset($request->class_id)){
            $class_list = ClassList::find($request->class_id);
            if(!isset($class_list)){
                return $this->error(translate_api('Class list is not exist', $language), 400);
            }
            $cars->class_list_id = $request->class_id;
        }
        if(isset($request->class_id)){
            $cars->reg_certificate = $request->state_number;
        }
        $letters = range('a', 'z');
        if(isset($field['reg_certificate_image'])){
            $sms_avatar = storage_path('app/public/certificate/'.$cars->reg_certificate_image);
            if(file_exists($sms_avatar)){
                unlink($sms_avatar);
            }
            $certificate_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
            $certificate_random = implode("", $certificate_random_array);
            $certificate_img = $request->file('reg_certificate_image');
            if(isset($certificate_img)) {
                $image_name = $certificate_random . '' . date('Y-m-dh-i-s') . '.' . $certificate_img->extension();
                $certificate_img->storeAs('public/certificate/', $image_name);
                $cars->reg_certificate_image = $image_name;
            }
        }
        $images = $request->file('images');

        if(isset($images)){
            $model_images = json_decode($cars->images);
            foreach ($model_images as $model_image){
                $sms_image = storage_path('app/public/cars/'.$model_image);
                if(file_exists($sms_image)){
                    unlink($sms_image);
                }
            }
            foreach ($images as $image){
                $images_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
                $images_random = implode("", $images_random_array);
                $image_name = $images_random . '' . date('Y-m-dh-i-s') . '.' . $image->extension();
                $image->storeAs('public/cars/', $image_name);
                $images_array[] = $image_name;
            }
            $cars->images = json_encode($images_array);
        }
        $is_driver = Driver::where('user_id', $user->id)->first();

        if(!isset($is_driver)){
            $driver = new Driver();
            $driver->user_id = $user->id;
            $driver->status_id = 1;
            $driver->save();
        }else{
            $driver = $is_driver;
        }
        $cars->driver_id = $driver->id;
        $cars->save();
        return $this->success(translate_api('Success', $language), 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $language = $request->header('language');
        $model= Cars::find($request->id);
        // if(isset($model->id)){
        //     if(isset($model->reg_certificate_image)){
        //         $sms_avatar = storage_path('app/public/cars/'.$model->reg_certificate_image);
        //         if(file_exists($sms_avatar)){
        //             unlink($sms_avatar);
        //         }
        //     }
        //     if(isset($model->images)){
        //         $images_array = json_decode($model->images);
        //         if(gettype($images_array) == 'string'){
        //             $str_images = str_replace('[', '', $images_array);
        //             $str_images_1 = str_replace(']', '', $str_images);
        //             $images_array = str_replace("\"", '', $str_images_1);
        //             $images_array = explode(',', $images_array);
        //         }
        //         if(isset($images_array) && count($images_array)>0){
        //             foreach ($images_array as $image){
        //                 $sms_image = storage_path('app/public/cars/'.$image);
        //                 if(file_exists($sms_image)){
        //                     unlink($sms_image);
        //                 }
        //             }
        //         }
        //     }
        // }else{
        //     return $this->error(translate_api('Failed car not found', $language), 400);
        // }
        if(!isset($model)) {
            return $this->error(translate_api('Failed car not found', $language), 400);
        }
        $model->delete();
        return $this->success(translate_api('Success', $language), 201);
    }
}
