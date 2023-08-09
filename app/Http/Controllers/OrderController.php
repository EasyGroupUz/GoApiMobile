<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Options;
use App\Models\OrderDetail;
use App\Models\PersonalInfo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Constants;


use App\Http\Requests\OrderRequest;

class OrderController extends Controller
{

    public function index()
    {
        $model = Order::orderBy('start_date', 'asc')->get();

        $arr = [];
        if (isset($model) && count($model) > 0) {
            $n = 0;
            foreach ($model as $key => $value) {
                $arrCars = [];
                if (isset($value->car)) {
                    $arrCarImg = [];
                    if (!empty($value->car->images)) {
                        $ci = 0;
                        foreach (json_decode($value->car->images) as $valueCI) {
                            $arrCarImg[$ci] = asset('storage/cars/' . $valueCI);
                            $ci++;
                        }
                    }
                    $arrCars['id'] = $value->car->id;
                    $arrCars['car_list_name'] = $value->car->car->name;
                    $arrCars['car_color'] = ($value->car->color) ? ['name' => $value->car->color->name, 'code' => $value->car->color->code] : [];
                    $arrCars['production_date'] = date('d.m.Y', strtotime($value->car->production_date));
                    $arrCars['car_class'] = ($value->car->class) ? $value->car->class->name : '';
                    $arrCars['car_reg_certificate'] = $value->car->reg_certificate;
                    $arrCars['car_reg_certificate_img'] = $value->car->reg_certificate_image;
                    $arrCars['images'] = $arrCarImg;
                }

                $arr[$n]['id'] = $value->id;
                $arr[$n]['start_date'] = date('d.m.Y H:i', strtotime($value->start_date));
                $arr[$n]['price'] = (double)$value->price;
                $arr[$n]['from'] = ($value->from) ? $value->from->name : '';
                $arr[$n]['from_lng'] = 69.287645;
                $arr[$n]['from_lat'] = 41.339596;
                $arr[$n]['to'] = ($value->to) ? $value->to->name : '';
                $arr[$n]['to_lng'] = 69.287645;
                $arr[$n]['to_lat'] = 41.339596;
                $arr[$n]['seats_count'] = $value->seats ?? 0;
                $arr[$n]['driver_full_name'] = (isset($value->driver) && isset($value->driver->personalInfo)) ? $value->driver->personalInfo->last_name . ' ' . $value->driver->personalInfo->first_name . ' ' . $value->driver->personalInfo->middle_name : '';
                $arr[$n]['driver_img'] = (isset($value->driver) && isset($value->driver->personalInfo)) ? asset('storage/avatar/' . $value->driver->personalInfo->avatar) : '';
                $arr[$n]['driver_rating'] = (isset($value->driver)) ? $value->driver->rating : 0;
                $arr[$n]['car_information'] = $arrCars;
                $arr[$n]['options'] = $value->options ?? [];
                $n++;
            }
        }

        return response()->json([
            'data' => $arr,
            'status' => true,
            'message' => "success"
        ], 200);

        // return $this->success(
        //     'success',
        //     $arr
        // );

        // return $this->error(
        //     'There are some problems',
        //     []
        // );
    }

    public function searchTaxi(Request $request)
    {
        // dd($request->all());
        // $request = $request->validate([
        //     'from_id'=>'required',
        //     'to_id'=>'required',
        //     'date'=>'required'
        // ]);


            $date=Carbon::parse($request->date)->format('Y-m-d');
            $tomorrow=Carbon::parse($date)->addDays(1)->format('Y-m-d');
            // dd($tomorrow);
            $list=[]; 
                $orders = DB::table('yy_orders')
                ->where('status_id', Constants::ORDERED)
                ->where('from_id', $request->from_id)
                ->where('to_id', $request->to_id)
                ->select(DB::raw('DATE(start_date) as start_date'),'driver_id','price','booking_place')
                ->where('start_date','>',$date)
                ->where('start_date','<',$tomorrow)
                // ->orderBy('start_date', 'asc')
                ->get();
                // dd($orders);
                $total_trips=Order::where('driver_id',auth()->id())
                    ->where('status_id', Constants::COMPLETED)
                    ->count();
                    // dd($total_trips);

                foreach ($orders as $order) {
                    // dd($order);
                    // dd(User::where('id',$order->driver_id)->first()->personal_info_id);
                    $personalInfo=PersonalInfo::where('id',User::where('id',$order->driver_id)->first()->personal_info_id)->first();
                    // dd($personalInfo);
                    $data=[
                        'start_date'=>$order->start_date ,
                        'avatar'=>$personalInfo->avatar,
                        'rating'=>4,
                        'price'=>$order->price,
                        'name'=>$personalInfo->first_name .' '. $personalInfo->last_name .' '. $personalInfo->middle_name,
                        'total_trips'=>$total_trips,
                        'count_pleace'=>$order->booking_place,
                    ];
                    // dd($data);
                    array_push($list,$data);
                }       
        // dd($aa);
        $language=$request->header('language');
        $message=translate_api('success',$language);
        // dd($message);
        // dd()
        return response()->json([
            'data' => $list,
            'status' => true,
            'message' => $message,

        ], 200);
    }

    public function show(Request $request)
    {
        $id = $request->id;

        $order = Order::find($id);
        $arr = [];

        if (isset($order)) {
            $arrDriverInformation = [];
            if ($order->driver) {
                $driver_info = $order->driver;

                $d_full_name = '';
                $d_phone_number = '';
                $d_img = '';
                
                if ($driver_info->personalInfo) {
                    $d_personal_info = $driver_info->personalInfo;

                    $d_full_name = $d_personal_info->last_name . ' ' . $d_personal_info->first_name . ' ' . $d_personal_info->middle_name;
                    $d_phone_number = $d_personal_info->phone_number;
                    $d_img = asset('storage/avatar/' . $d_personal_info->avatar);
                }

                $arrComments = [];
                if ($driver_info->commentScores) {
                    $arrDriverComments = $driver_info->commentScores;
                    $c = 0;
                    foreach ($arrDriverComments as $key => $value) {
                        $arrComments[$c]['text'] = $value->text;
                        $arrComments[$c]['date'] = date('d.m.Y H:i', strtotime($value->date));
                        $arrComments[$c]['score'] = $value->score;

                        $c++;
                    }
                }

                $arrDriverInformation['id'] = $driver_info->id;
                $arrDriverInformation['full_name'] = $d_full_name;
                $arrDriverInformation['phone_number'] = $d_phone_number;
                $arrDriverInformation['img'] = $d_img;
                $arrDriverInformation['rating'] = $driver_info->rating;
                $arrDriverInformation['type'] = $driver_info->type ?? 0;
                $arrDriverInformation['count_comments'] = count($arrComments);
                $arrDriverInformation['comments'] = $arrComments;
            }

            $arrCarInfo = [];
            if ($order->car) {
                $arr_orde_car = $order->car;

                $arrCarImg = [];
                if (!empty($arr_orde_car->images)) {
                    $ci = 0;
                    foreach (json_decode($arr_orde_car->images) as $valueCI) {
                        $arrCarImg[$ci] = asset('storage/cars/' . $valueCI);
                        $ci++;
                    }
                }

                $arrCarInfo['id'] = $arr_orde_car->id;
                $arrCarInfo['name'] = $arr_orde_car->car->name ?? '';
                $arrCarInfo['color'] = ($arr_orde_car->color) ? ['name' => $arr_orde_car->color->name, 'code' => $arr_orde_car->color->code] : [];
                $arrCarInfo['production_date'] = date('d.m.Y', strtotime($arr_orde_car->production_date));
                $arrCarInfo['class'] = $arr_orde_car->class->name ?? '';
                $arrCarInfo['reg_certificate'] = $arr_orde_car->reg_certificate;
                $arrCarInfo['reg_certificate_img'] = $arr_orde_car->reg_certificate_image;
                $arrCarInfo['images'] = $arrCarImg;
            }

            $arrClients = [];
            if ($order->orderDetails && count($order->orderDetails) > 0) {
                $oo = 0;
                foreach ($order->orderDetails as $key => $value) {
                    $order_details_client = $value->client;
                    
                    $c_last_name = '';
                    $c_first_name = '';
                    $c_middle_name = '';
                    $c_phone_number = '';
                    $c_img = '';
                    $c_gender = '';
                    
                    if ($order_details_client->personalInfo) {
                        $c_personal_info = $order_details_client->personalInfo;

                        $c_last_name = $c_personal_info->last_name;
                        $c_first_name = $c_personal_info->first_name;
                        $c_middle_name = $c_personal_info->middle_name;
                        $c_phone_number = $c_personal_info->phone_number;
                        $c_img = $d_personal_info->avatar;
                        $c_gender = $d_personal_info->gender;
                    }

                    $arrClients[$oo]['id'] = $order_details_client->id;
                    $arrClients[$oo]['last_name'] = $c_last_name;
                    $arrClients[$oo]['first_name'] = $c_first_name;
                    $arrClients[$oo]['middle_name'] = $c_middle_name;
                    $arrClients[$oo]['phone_number'] = $c_phone_number;
                    $arrClients[$oo]['avatar'] = $c_img;
                    $arrClients[$oo]['gender'] = $c_gender;
                    $arrClients[$oo]['balance'] = $order_details_client->balance ?? 0;
                    $arrClients[$oo]['about_me'] = $order_details_client->about_me;
                    
                    $oo++;
                }
            }

            $arr['id'] = $order->id;
            $arr['start_date'] = date('d.m.Y H:i', strtotime($order->start_date));
            $arr['from'] = ($order->from) ? $order->from->name : '';
            $arr['from_lng'] = 69.287645;
            $arr['from_lat'] = 41.339596;
            $arr['to'] = ($order->to) ? $order->to->name : '';
            $arr['to_lng'] = 69.287645;
            $arr['to_lat'] = 41.339596;
            $arr['seats_count'] = $order->seats;
            $arr['price'] = $order->price;
            $arr['price_type'] = $order->price_type;
            $arr['driver_information'] = $arrDriverInformation;
            $arr['car_information'] = $arrCarInfo;
            $arr['clients_list'] = $arrClients;
            $arr['options'] = json_decode($order->options) ?? [];
        }

        return response()->json([
            'data' => $arr,
            'status' => true,
            'message' => "success"
        ], 200);
    }

    public function create(OrderRequest $request)
    {
        $data = $request->validated();
        $token = $request->header()['token'];
        $driver = User::where('token', $token)->first();

        if (!isset($driver)) {
            return [
                "status" => false,
                "message" => "Token not found"
            ];
        }
        $driver_id = $driver->id;
        $data['driver_id'] = $driver_id;

        $order = new Order();
        $order->create($data);
        
        if ($data['back_date']) {
            $from_id = $data['to_id'];
            $to_id = $data['from_id'];

            $data['start_date'] = $data['back_date'];
            $data['from_id'] = $from_id;
            $data['to_id'] = $to_id;

            $order = new Order();
            $order->create($data);
        }

        return response()->json([
            "status" => true,
            "message" => "success"
        ], 200);
    }

    public function history(Request $request)
    {
        $page = $request->page;
        
        $model = Order::orderBy('id', 'asc')->offset($page - 1)->limit(15)->get();

        $arr = [];
        if (isset($model) && count($model) > 0) {
            $n = 0;
            foreach ($model as $key => $value) {
                $clientArr = [];
                // dd($value->orderDetails[0]->client);
                if ($value->orderDetails) {
                    $i = 0;
                    foreach ($value->orderDetails as $keyOD => $valueOD) {
                        if (isset($valueOD->client) && isset($valueOD->client->personalInfo)) {
                            $clientArr[$i]['clients_full_name'] = $valueOD->client->personalInfo->last_name . ' ' . $valueOD->client->personalInfo->first_name . ' ' . $valueOD->client->personalInfo->middle_name;
                            $clientArr[$i]['client_img'] = $valueOD->client->personalInfo->avatar;
                            $clientArr[$i]['client_rating'] = 4.3;
                        }

                        $i++;
                    }
                }

                $arrDriverInfo = [];
                if ($value->driver) {
                    $valDriver = $value->driver;
    
                    $d_full_name = '';
                    $d_phone_number = '';
                    $d_img = '';
                    if ($valDriver->personalInfo) {
                        $driverPersonalInfo = $valDriver->personalInfo;

                        $d_full_name = $driverPersonalInfo->last_name . ' ' . $driverPersonalInfo->first_name . ' ' . $driverPersonalInfo->middle_name;
                        $d_phone_number = $driverPersonalInfo->phone_number;
                        $d_img = asset('storage/avatar/' . $driverPersonalInfo->avatar);
                    }
                    $arrDriverInfo['full_name'] = $d_full_name;
                    $arrDriverInfo['phone_number'] = $d_phone_number;
                    $arrDriverInfo['img'] = $d_img;
                    $arrDriverInfo['rating'] = $valDriver->rating;
                }

                $arr[$n]['id'] = $value->id;
                $arr[$n]['start_date'] = date('d.m.Y H:i', strtotime($value->start_date));
                $arr[$n]['price'] = (double)$value->price;
                $arr[$n]['from'] = ($value->from) ? $value->from->name : '';
                $arr[$n]['to'] = ($value->to) ? $value->to->name : '';
                $arr[$n]['seats_count'] = $value->seats ?? 0;
                $arr[$n]['booking_count'] = ($value->orderDetails) ? count($value->orderDetails) : 0;
                $arr[$n]['clients_list'] = $clientArr;
                $arr[$n]['driver'] = $arrDriverInfo;
                $arr[$n]['options'] = $value->options ?? [];

                $n++;
            }
        }

        return response()->json([
            'data' => $arr,
            'status' => true,
            'message' => "success"
        ], 200);
    }

    public function expired()
    {
        $model = Order::where('start_date', '<', date('Y-m-d H:i:s'))->orderBy('start_date', 'asc')->get();

        $arr = [];
        if (isset($model) && count($model) > 0) {
            $n = 0;
            foreach ($model as $key => $value) {

                $arrDriverInfo = [];
                if ($value->driver) {
                    $valDriver = $value->driver;
    
                    $d_full_name = '';
                    $d_phone_number = '';
                    $d_img = '';
                    if ($valDriver->personalInfo) {
                        $driverPersonalInfo = $valDriver->personalInfo;

                        $d_full_name = $driverPersonalInfo->last_name . ' ' . $driverPersonalInfo->first_name . ' ' . $driverPersonalInfo->middle_name;
                        $d_phone_number = $driverPersonalInfo->phone_number;
                        $d_img = asset('storage/avatar/' . $driverPersonalInfo->avatar);
                    }
                    $arrDriverInfo['full_name'] = $d_full_name;
                    $arrDriverInfo['phone_number'] = $d_phone_number;
                    $arrDriverInfo['img'] = $d_img;
                    $arrDriverInfo['rating'] = $valDriver->rating;
                }

                $arr[$n]['id'] = $value->id;
                $arr[$n]['start_date'] = date('d.m.Y H:i', strtotime($value->start_date));
                $arr[$n]['price'] = $value->price;
                $arr[$n]['from'] = ($value->from) ? $value->from->name : '';
                $arr[$n]['from_lng'] = 69.287645;
                $arr[$n]['from_lat'] = 41.339596;
                $arr[$n]['to'] = ($value->to) ? $value->to->name : '';  
                $arr[$n]['to_lng'] = 69.287645;
                $arr[$n]['to_lat'] = 41.339596;
                $arr[$n]['seats_count'] = $value->seats;
                // $arr[$n]['booking_count'] = $value->/*seats*/;
                $arr[$n]['driver_information'] = $arrDriverInfo;
                $arr[$n]['options'] = json_decode($value->options) ?? [];
                
                $n++;
            }
        }

        return response()->json([
            'data' => $arr,
            'status' => true,
            'message' => 'success'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function booking(Request $request)
    {
        // $token = $request->header()['token'];
        $order_id = $request['order_id'];
        $order_detail_id = $request['order_detail_id'];

        $order = Order::find($order_id);
        $orderDetail = OrderDetail::find($order_detail_id);

        $status = true;
        $message = 'Success';
        if (!$order) {
            $status = false;
            $message = 'Order not found';
        }

        if (!$orderDetail) {
            $status = false;
            $message = 'Order Detail not found';
        }

        if ($status) {
            $orderDetail->order_id = $order->id;
            $saveOrderDetail = $orderDetail->save();

            if ($saveOrderDetail) {
                $status = true;
                $message = 'success';
            }
        }

        return response()->json([
            "status" => $status,
            "message" => $message
        ], 200);
    }

    public function getOptions(){
        $options = Options::select('id', 'name', 'icon')->get();
        $status = false;
        $message = 'no data';
        foreach($options as $option){
            $data[] = [
                'id'=>$option->id,
                'name'=>$option->name,
                'icon'=>"http://admin.easygo.uz/storage/option/$option->icon",
            ];
            $status = true;
            $message = 'success';
        }
        $response = [
          'data'=>$data,
          'status'=>$status,
          'message'=>$message,
        ];
        return response()->json($response);
    }
}
