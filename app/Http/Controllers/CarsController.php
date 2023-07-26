<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cars;
use App\Models\CarList;
use App\Models\Driver;
use App\Models\Status;
use App\Models\ColorList;
use App\Models\ClassList;
use App\Http\Requests\CarsRequest;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class CarsController extends Controller
{
    public function myTaxi(Request $request)
    {
        $cars = DB::table('yy_drivers as dt1')
            ->leftJoin('yy_cars as dt2', 'dt2.driver_id', '=', 'dt1.id')
            ->leftJoin('yy_car_lists as dt3', 'dt3.id', '=', 'dt2.car_list_id')
            ->leftJoin('yy_color_lists as dt4', 'dt4.id', '=', 'dt2.color_list_id')
            ->where('dt1.user_id', auth()->id())
            ->select('dt2.id', 'dt2.images','dt2.production_date', 'dt3.name as car_name', 'dt4.name as color')
            ->get();

        return response()->json([
            'data' => $cars,
            'status' => true,
            'message' => 'success',

        ], 200);

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

    public function information(){
        $class_list = ClassList::select('id', 'name')->get()->toArray();
        $color_list = ColorList::select('id', 'name')->get()->toArray();
        $car_lists = CarList::all();
        foreach ($car_lists as $car_list){
            $carList[] = [
                'id'=>$car_list->id,
                'model'=>$car_list->name??'',
                'list' => [
                    'id' => $car_list->type?$car_list->type->id:'',
                    'name' => $car_list->type?$car_list->type->name:'',
                ],
            ];
        }
        $response = [
            'data'=>[
                "class_list"=>$class_list??[],
                "color_list"=>$color_list??[],
                "car_list"=>$carList??[]
            ],
            'status'=>true,
            'message'=>'success',
        ];
        return response()->json($response);
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

    public function create(Request $request) {
        $user = Auth::user();
        $cars = new Cars();
        $cars->status_id = 1;
        $cars->car_list_id = $request->model_id;
        $cars->reg_certificate = $request->state_number;
        $cars->color_list_id = $request->color_id;
        $cars->class_list_id = $request->class_id;
        $cars->production_date = $request->production_date;
        $cars->wheel_side = $request->wheel_side;
        $is_driver = Driver::where('user_id', $user->id)->first();
        $car_list = CarList::find($request->model_id);
        if(!isset($car_list)){
            $response = [
                'status'=>false,
                'message'=> translate('Car list is not exist')
            ];
            return response()->json($response);
        }
        $color_list = ColorList::find($request->color_id);
        if(!isset($color_list)){
            $response = [
                'status'=>false,
                'message'=> translate('Color is not exist')
            ];
            return response()->json($response);
        }
        $color_list = ClassList::find($request->class_id);
        if(!isset($color_list)){
            $response = [
                'status'=>false,
                'message'=> translate('Class list is not exist')
            ];
            return response()->json($response);
        }
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
        $response = [
            'status'=>true,
            'message'=>'Success',
        ];
        return response()->json($response, 201);
    }
}
