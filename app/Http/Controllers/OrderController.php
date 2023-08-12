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
    public function searchTaxi(Request $request)
    {
        $language = $request->header('language');

        $date=Carbon::parse($request->start_date)->format('Y-m-d');
        $tomorrow=Carbon::parse($date)->addDays(1)->format('Y-m-d');
            
        $list=[]; 
        $orders = DB::table('yy_orders')
            ->where('status_id', Constants::ORDERED)
            ->where('from_id', $request->from_id)
            ->where('to_id', $request->to_id)
            ->select('start_date','driver_id','price','booking_place','car_id','seats')
            ->where('start_date','>=',$date)
            ->where('start_date','<',$tomorrow)
            ->get();

        $order_count=count($orders);
        $total_trips=Order::where('driver_id',auth()->id())
            ->where('status_id', Constants::COMPLETED)
            ->count();

        foreach ($orders as $order) {
            $user=User::where('id',$order->driver_id)->first();
            $personalInfo=PersonalInfo::where('id',$user->personal_info_id)->first();

            $car=DB::table('yy_cars as dt1')
                ->join('yy_car_lists as dt2', 'dt2.id', '=', 'dt1.car_list_id')
                ->where('dt1.id',$order->car_id)
                ->select(DB::raw('DATE(dt1.production_date) as production_date'),'dt2.name','dt1.color_list_id as color_id')
                ->first();

            $color=table_translate($car,'color',$language);
            $car_information=[
                'name'=>$car->name,
                'color'=>$color,
                'production_date'=>$car->production_date
            ];

            $data=[
                'order_count'=>$order_count,
                'start_date'=>$order->start_date ,
                'avatar'=>$personalInfo->avatar,
                'rating'=>4,
                'price'=>$order->price,
                'name'=>$personalInfo->first_name .' '. $personalInfo->last_name .' '. $personalInfo->middle_name,
                'count_pleace'=>$order->booking_place,
                'seats'=>$order->seats, // obshi joylar soni
                'car_information'=>$car_information
            ];

            array_push($list,$data);
        }       

        $language=$request->header('language');
        $message=translate_api('success',$language);

        return $this->success($message, 200, $list);
    }

    public function show(Request $request)
    {
        if (!$request->id)
            return $this->error('id parameter is missing', 400);

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

            return $this->success('success', 200, $arr);
        } else {
            return $this->success('This kind of order not found', 204);
        }
    }

    public function create(OrderRequest $request)
    {
        $data = $request->validated();
        // $token = $request->header()['token'];
        // $driver = User::where('token', $token)->first();

        // if (!isset($driver)) {
        //     return [
        //         "status" => false,
        //         "message" => "Token not found"
        //     ];
        // }

        $driver_id = auth()->user()->id;
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

        return $this->success('success', 200);
    }

    public function history(Request $request)
    {
        // if (!$this->validateByToken($request))
        //     return $this->error('The owner of the token you sent was not identified', 400);
     
        if ($request->page)
            $page = $request->page;
        else
            return $this->error('page parameter is missing', 400);
        
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
                $arr[$n]['options'] = json_decode($value->options) ?? [];

                $n++;
            }

            return $this->success('success', 200, $arr);
        } else {
            return $this->success('Order table is empty', 204);
        }

        // return response()->json([
        //     'data' => $arr,
        //     'status' => true,
        //     'message' => "success"
        // ], 200);
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

            return $this->success('success', 200, $arr);
        } else {
            return $this->success('Order table is empty', 204);
        }
    }

    public function booking(Request $request)
    {
        if (!$request['order_id'])
            return $this->error('order_id parameter is missing', 400);

        $order_id = $request['order_id'];

        if (!$request['order_detail_id'])
            return $this->error('order_detail_id parameter is missing', 400);
        
        $order_detail_id = $request['order_detail_id'];

        $order = Order::find($order_id);
        $orderDetail = OrderDetail::find($order_detail_id);

        if (!$order)
            return $this->success('Order not found', 204);

        if (!$orderDetail)
            return $this->success('Order Detail not found', 204);
        else
            if ($orderDetail->order_id != null)
                return $this->error('This order detail is booked already', 400);

        $orderDetail->order_id = $order->id;
        $saveOrderDetail = $orderDetail->save();

        $order->booking_place = ($order->booking_place > 0) ? $order->booking_place + 1 : 1;
        $saveOrder = $order->save();

        if ($saveOrderDetail && $saveOrder)
            return $this->success('success', 200);
    }

    public function getOptions()
    {
        $options = Options::select('id', 'name', 'icon')->get();

        $data = [];
        if (isset($options) && count($options) > 0) {
            foreach ($options as $option) {
                $data[] = [
                    'id' => $option->id,
                    'name' => $option->name,
                    'icon' => "http://admin.easygo.uz/storage/option/$option->icon",
                ];
            }

            return $this->success('success', 200, $data);
        } else {
            return $this->success('Options table is empty', 204);
        }
    }

    public function searchHistory()
    {
        // if ($request->page)
        //     $page = $request->page;
        // else
        //     return $this->error('page parameter is missing', 400);
        
        // $model = Order::where('driver_id',auth()->id())->select('')->orderBy('id', 'desc')->limit(5)->get();
        $model = DB::table('yy_orders as yyo')
            ->leftJoin('yy_cities as yyF', 'yyF.id', '=', 'yyo.from_id')
            ->leftJoin('yy_cities as yyT', 'yyT.id', '=', 'yyo.to_id')
            ->where('yyo.driver_id',auth()->id())
            ->select('yyo.id', 'yyF.name as from', 'yyF.id as from_id', DB::raw('67.098776 as from_lng'), DB::raw('41.098776 as from_lat'), 'yyT.name as to', 'yyT.id as to_id', DB::raw('67.098776 as to_lng'), DB::raw('41.098776 as to_lat'))
            ->get()
            ->toArray();

        return $this->success('success', 200, $model);
    }

    // public function index()
    // {
    //     $model = Order::orderBy('start_date', 'asc')->get();

    //     $arr = [];
    //     if (isset($model) && count($model) > 0) {
    //         $n = 0;
    //         foreach ($model as $key => $value) {
    //             $arrCars = [];
    //             if (isset($value->car)) {
    //                 $arrCarImg = [];
    //                 if (!empty($value->car->images)) {
    //                     $ci = 0;
    //                     foreach (json_decode($value->car->images) as $valueCI) {
    //                         $arrCarImg[$ci] = asset('storage/cars/' . $valueCI);
    //                         $ci++;
    //                     }
    //                 }
    //                 $arrCars['id'] = $value->car->id;
    //                 $arrCars['car_list_name'] = $value->car->car->name;
    //                 $arrCars['car_color'] = ($value->car->color) ? ['name' => $value->car->color->name, 'code' => $value->car->color->code] : [];
    //                 $arrCars['production_date'] = date('d.m.Y', strtotime($value->car->production_date));
    //                 $arrCars['car_class'] = ($value->car->class) ? $value->car->class->name : '';
    //                 $arrCars['car_reg_certificate'] = $value->car->reg_certificate;
    //                 $arrCars['car_reg_certificate_img'] = $value->car->reg_certificate_image;
    //                 $arrCars['images'] = $arrCarImg;
    //             }

    //             $arr[$n]['id'] = $value->id;
    //             $arr[$n]['start_date'] = date('d.m.Y H:i', strtotime($value->start_date));
    //             $arr[$n]['price'] = (double)$value->price;
    //             $arr[$n]['from'] = ($value->from) ? $value->from->name : '';
    //             $arr[$n]['from_lng'] = 69.287645;
    //             $arr[$n]['from_lat'] = 41.339596;
    //             $arr[$n]['to'] = ($value->to) ? $value->to->name : '';
    //             $arr[$n]['to_lng'] = 69.287645;
    //             $arr[$n]['to_lat'] = 41.339596;
    //             $arr[$n]['seats_count'] = $value->seats ?? 0;
    //             $arr[$n]['driver_full_name'] = (isset($value->driver) && isset($value->driver->personalInfo)) ? $value->driver->personalInfo->last_name . ' ' . $value->driver->personalInfo->first_name . ' ' . $value->driver->personalInfo->middle_name : '';
    //             $arr[$n]['driver_img'] = (isset($value->driver) && isset($value->driver->personalInfo)) ? asset('storage/avatar/' . $value->driver->personalInfo->avatar) : '';
    //             $arr[$n]['driver_rating'] = (isset($value->driver)) ? $value->driver->rating : 0;
    //             $arr[$n]['car_information'] = $arrCars;
    //             $arr[$n]['options'] = json_decode($value->options) ?? [];
    //             $n++;
    //         }
    //     }

    //     return response()->json([
    //         'data' => $arr,
    //         'status' => true,
    //         'message' => "success"
    //     ], 200);

    //     // return $this->success(
    //     //     'success',
    //     //     200,
    //     //     $arr
    //     // );

    //     // return $this->error(
    //     //     'There are some problems',
    //     //     400,
    //     //     []
    //     // );
    // }
}
