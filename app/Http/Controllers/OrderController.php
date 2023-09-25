<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Options;
use App\Models\Offer;
use App\Models\DirectionHistory;
use App\Models\OrderDetail;
use App\Models\Cars;
use App\Models\PersonalInfo;
use App\Models\Chat;
use App\Models\City;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Constants;

use App\Http\Requests\OrderRequest;

class OrderController extends Controller
{
    public function searchTaxi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_id' => 'required|integer',
            'to_id' => 'required|integer',
            'start_date' => 'required',
            'seats_count' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $newOrderDetail = $this->createOrderDetail($request->all());

        $language = $request->header('language');

        $date=Carbon::parse($request->start_date)->format('Y-m-d');
        $tomorrow=Carbon::parse($date)->addDays(1)->format('Y-m-d');

        $list=[]; 
        // $orders = DB::table('yy_orders')
        //     ->where('status_id', Constants::ORDERED)
        //     ->where('from_id', $request->from_id)
        //     ->where('to_id', $request->to_id)
        //     ->select('start_date','driver_id','price','booking_place','car_id','seats')
        //     ->where('start_date','>=',$date)
        //     ->where('start_date','<',$tomorrow)
        //     ->get();

        $citiesFrom = City::where('parent_id', $request->from_id)->get();
        $arrFromIds = array();
        if (!empty($citiesFrom) && count($citiesFrom) > 0) {
            foreach ($citiesFrom as $cityFrom) {
                $arrFromIds[] = $cityFrom->id;
            }
        }
        $arrFromIds[] = $request->from_id;

        $citiesTo = City::where('parent_id', $request->to_id)->get();
        $arrToIds = array();
        if (!empty($citiesTo) && count($citiesTo) > 0) {
            foreach ($citiesTo as $cityTo) {
                $arrToIds[] = $cityTo->id;
            }
        }
        $arrToIds[] = $request->to_id;

        $orders = Order::where('status_id', Constants::ORDERED)
            ->whereIn('from_id', $arrFromIds)
            ->whereIn('to_id', $arrToIds)
            ->where('start_date', '>=', $date)
            ->where('start_date', '<=', $tomorrow)
            ->where('start_date', '>=', date('Y-m-d H:i:s'))
            ->where('driver_id', '!=', auth()->id())
            ->get();

        $order_count = count($orders);
        $total_trips = Order::where('driver_id',auth()->id())
            ->where('status_id', Constants::COMPLETED)
            ->count();

        foreach ($orders as $order) {
            $user = User::where('id', $order->driver_id)->first();

            $personalInfo = [];
            if ($user)
                $personalInfo = PersonalInfo::where('id',$user->personal_info_id)->first();

            $car = DB::table('yy_cars as dt1')
                ->join('yy_car_lists as dt2', 'dt2.id', '=', 'dt1.car_list_id')
                ->where('dt1.id',$order->car_id)
                ->select(DB::raw('DATE(dt1.production_date) as production_date'),'dt2.name','dt1.color_list_id as color_id')
                ->first();

            $color = '';
            if ($car)
                $color = table_translate($car,'color',$language);

            $car_information = [
                'name' => $car->name ?? '',
                'color' => $color,
                'production_date' => date('Y', strtotime($car->production_date)) ?? ''
            ];

            $distance = $this->getDistanceAndKm((($order->from) ? $order->from->lng : ''), (($order->from) ? $order->from->lat : ''), (($order->to) ? $order->to->lng : ''), (($order->to) ? $order->to->lat : ''));

            $driver_info = $order->driver;
            $data = [
                'id' => $order->id,
                'order_detail_id' => $newOrderDetail->id,
                'order_count' => $order_count,
                'start_date' => date('d.m.Y H:i', strtotime($order->start_date)),
                'isYour' => ($order->driver_id == auth()->id()) ? true : false,
                // 'avatar' => $personalInfo->avatar ?? '',
                'avatar' => ($personalInfo && $personalInfo->avatar) ? asset('storage/avatar/' . $personalInfo->avatar) : NULL,
                'rating' => $driver_info->rating,
                'price' => $order->price,
                'name' => ($personalInfo) ? $personalInfo->first_name .' '. $personalInfo->last_name .' '. $personalInfo->middle_name : '', 
                'driver' => [
                    'id' => $driver_info->id,
                    'full_name' => $driver_info->personalInfo->last_name . ' ' . $driver_info->personalInfo->first_name . ' ' . $driver_info->personalInfo->middle_name,
                    'phone_number' => $driver_info->personalInfo->phone_number,
                    'img' => ($driver_info->personalInfo->avatar) ? asset('storage/avatar/' . $driver_info->personalInfo->avatar) : '',
                    'rating' => $driver_info->rating,
                    'doc_status' => ($driver_info->driver) ? (int)$driver_info->driver->doc_status : NULL
                ],
                'options' => json_decode($order->options) ?? [],
                'count_pleace' => $order->booking_place,
                'seats' => $order->seats, // obshi joylar soni
                'is_full' => ($order->seats <= $order->booking_place) ? true : false,
                'car_information' => $car_information,

                'from' => ($order->from) ? $order->from->name : '',
                'from_lng' => ($order->from) ? $order->from->lng : '',
                'from_lat' => ($order->from) ? $order->from->lat : '',
                'to' => ($order->to) ? $order->to->name : '',
                'to_lng' => ($order->to) ? $order->to->lng : '',
                'to_lat' => ($order->to) ? $order->to->lat : '',

                'distance_km' => $distance['km'],
                'distance' => $distance['time'],
                'arrived_date' => date('d.m.Y H:i', strtotime($order->start_date. ' +' . $distance['time'])),
            ];

            array_push($list,$data);
        }       

        $language=$request->header('language');
        $message=translate_api('success',$language);

        return $this->success($message, 200, $list);
    }

    public function createOrderDetail($data)
    {
        $newOrderDetail = OrderDetail::create([
            'client_id' => auth()->id(),
            'status_id' => Constants::ACTIVE,
            'from_id' => $data['from_id'],
            'to_id' => $data['to_id'],
            'seats_count' => $data['seats_count'],
            'start_date' => date('Y-m-d', strtotime($data['start_date']))
        ]);

        return $newOrderDetail;
    }

    /* ========================= Order search-taxi start ========================= */
        // public function searchTaxi(Request $request)
        // {
        //     $language = $request->header('language');
        //     $date = Carbon::parse($request->start_date)->format('Y-m-d');
        //     $tomorrow = Carbon::parse($date)->addDays(1)->format('Y-m-d');
            
        //     $orders = Order::all();
        //     // $orders = Order::where('status_id', Constants::ORDERED)
        //     //     ->where('from_id', $request->from_id)
        //     //     ->where('to_id', $request->to_id)
        //     //     ->where('start_date', '>=', $date)
        //     //     ->where('start_date', '<', $tomorrow)
        //     //     ->get();


        //     $order_count = $orders->count();
        //     $total_trips = Order::where('driver_id', auth()->id())
        //         // ->where('status_id', Constants::COMPLETED)
        //         ->count();

        //     $list = [];
        //     foreach ($orders as $order) {
        //         $user = User::find($order->driver_id);
        //         $personalInfo = $user->personalInfo ?? null;

        //         $car = Cars::find($order->car_id);
        //         $car_information = [
        //             'name' => optional($car->carList)->name ?? '',
        //             'color' => table_translate($car, 'color', $language),
        //             'production_date' => optional($car->production_date)->format('d.m.Y') ?? '',
        //         ];

        //         $distance = $this->getDistanceAndKm(
        //             optional($order->from)->lng,
        //             optional($order->from)->lat,
        //             optional($order->to)->lng,
        //             optional($order->to)->lat
        //         );

        //         $data = [
        //             'id' => $order->id,
        //             'order_count' => $order_count,
        //             'start_date' => date('d.m.Y H:i', strtotime($order->start_date)),
        //             'isYour' => ($order->driver_id == auth()->id()),
        //             'avatar' => (optional($personalInfo)->avatar) ? asset('storage/avatar/' . optional($personalInfo)->avatar) : NULL,
        //             'rating' => $user->rating,
        //             'price' => $order->price,
        //             'name' => optional($personalInfo)->full_name,
        //             'count_pleace' => $order->booking_place,
        //             'seats' => $order->seats,
        //             'car_information' => $car_information,
        //             'from' => optional($order->from)->name ?? '',
        //             'from_lng' => optional($order->from)->lng ?? '',
        //             'from_lat' => optional($order->from)->lat ?? '',
        //             'to' => optional($order->to)->name ?? '',
        //             'to_lng' => optional($order->to)->lng ?? '',
        //             'to_lat' => optional($order->to)->lat ?? '',
        //             'distance_km' => $distance['km'],
        //             'distance' => $distance['time'],
        //             'arrived_date' => date('d.m.Y H:i', strtotime($order->start_date. ' +' . $distance['time'])),
        //         ];

        //         $list[] = $data;
        //     }

        //     $message = translate_api('success', $language);
        //     return $this->success($message, 200, $list);
        // }
    /* ========================= Order search-taxi end ========================= */




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
                    $d_img = ($d_personal_info->avatar) ? asset('storage/avatar/' . $d_personal_info->avatar) : '';
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
                $arrDriverInformation['created_at'] = date('d.m.Y H:i', strtotime($driver_info->created_at));
                $arrDriverInformation['doc_status'] = ($driver_info->driver) ? (int)$driver_info->driver->doc_status : NULL;
                $arrDriverInformation['type'] = $driver_info->type ?? 0;
                $arrDriverInformation['count_comments'] = count($arrComments);
                // $arrDriverInformation['comments'] = $arrComments;
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
                $arrCarInfo['production_date'] = date('Y', strtotime($arr_orde_car->production_date));
                $arrCarInfo['class'] = $arr_orde_car->class->name ?? '';
                $arrCarInfo['reg_certificate'] = $arr_orde_car->reg_certificate;
                // $arrCarInfo['reg_certificate_img'] = $arr_orde_car->reg_certificate_image;
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
                        $c_img = ($c_personal_info->avatar) ? asset('storage/avatar/' . $c_personal_info->avatar) : '';
                        $c_gender = $c_personal_info->gender;
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

            $distance = $this->getDistanceAndKm((($order->from) ? $order->from->lng : ''), (($order->from) ? $order->from->lat : ''), (($order->to) ? $order->to->lng : ''), (($order->to) ? $order->to->lat : ''));
            $chat_id = $this->getChatId($order->id, auth()->id());

            $orderDetailId = null;
            $orderDetail=null;
            if ($order->driver_id != auth()->id()) {
                $start_date_formatted = date('Y-m-d', strtotime($order->start_date));
                $orderDetail = OrderDetail::where('from_id', $order->from_id)
                    ->where('to_id', $order->to_id)
                    ->where('client_id', auth()->id())
                    ->whereRaw('TO_CHAR(start_date, ?) = ?', ['YYYY-MM-DD', $start_date_formatted])
                    ->latest()
                    ->first();

                if ($orderDetail) {
                    $orderDetailId = $orderDetail->id;
                } else {
                    $orderDetailId = null;
                }
            }
           
            $offer_status = Constants::NOT_OFFER;
            // $offer_id = NULL;

            if ($orderDetail != null) {
                $offer = Offer::where('order_detail_id', $orderDetail->id)->where('order_id', $order->id)->where('status', '!=', Constants::CANCEL)->first();

                if ($offer) {
                    // $offer_id = $offer->id;
                    if ($offer->status == Constants::NEW) {
                        $offer_status = Constants::NEW_OFFER;
                    } elseif ($offer->status == Constants::ACCEPT) {
                        $offer_status = Constants::ACCEPT_OFFER;
                    } else{
                        $offer_status = Constants::NOT_OFFER;
                    }
                }
            }

            $arr['id'] = $order->id;
            $arr['order_detail_id'] = $orderDetailId;
            $arr['start_date'] = date('d.m.Y H:i', strtotime($order->start_date));
            $arr['isYour'] = ($order->driver_id == auth()->id()) ? true : false;
            $arr['from'] = ($order->from) ? $order->from->name : '';
            $arr['from_lng'] = ($order->from) ? $order->from->lng : '';
            $arr['from_lat'] = ($order->from) ? $order->from->lat : '';
            $arr['to'] = ($order->to) ? $order->to->name : '';
            $arr['to_lng'] = ($order->to) ? $order->to->lng : '';
            $arr['to_lat'] = ($order->to) ? $order->to->lat : '';
            $arr['distance_km'] = $distance['km'];
            $arr['distance'] = $distance['time'];
            $arr['arrived_date'] = date('d.m.Y H:i', strtotime($arr['start_date'] . ' +' . $distance['time']));
            $arr['seats_count'] = $order->seats;
            $arr['price'] = $order->price;
            $arr['price_type'] = $order->price_type;
            $arr['status'] = ($order->status) ? $order->status->name : '';
            $arr['offer_status'] = $offer_status;
            // $arr['offer_id'] = $offer_id;
            $arr['driver_information'] = $arrDriverInformation;
            $arr['car_information'] = (empty($arrCarInfo)) ? NULL : $arrCarInfo;
            $arr['clients_list'] = $arrClients;
            $arr['options'] = json_decode($order->options) ?? [];
            $arr['chat_id'] = $chat_id;

            return $this->success('success', 200, $arr);
        } else {
            return $this->success('This kind of order not found', 204);
        }
    }

    private function getChatId($order_id, $auth_id)
    {
        $chat = Chat::where('user_from_id', $auth_id)->where('order_id', $order_id)->first();

        return $chat->id ?? NULL;
    }


    /* ========================= Order show start ========================= */
        // public function show(Request $request)
        // {
        //     // Check if the 'id' parameter is present in the request
        //     if (!$request->has('id')) {
        //         return $this->error('id parameter is missing', 400);
        //     }

        //     $orderId = $request->id;
        //     $order = Order::find($orderId);

        //     // Check if the order exists
        //     if (!$order) {
        //         return $this->error('This kind of order not found', 204);
        //     }

        //     // Generate and return the order details
        //     $orderDetails = $this->generateOrderDetails($order);
        //     return $this->success('success', 200, $orderDetails);
        // }

        // // Function to generate order details
        // private function generateOrderDetails(Order $order)
        // {
        //     // Generate driver information, car information, clients list, and distance information
        //     $driverInformation = $this->generateDriverInformation($order->driver);
        //     $carInformation = $this->generateCarInformation($order->car);
        //     $clientsList = $this->generateClientsList($order->orderDetails);
        //     $distanceInfo = $this->getDistanceAndKm((($order->from) ? $order->from->lng : ''), (($order->from) ? $order->from->lat : ''), (($order->to) ? $order->to->lng : ''), (($order->to) ? $order->to->lat : ''));

        //     // Build the order details array
        //     $orderDetails = [
        //         'id' => $order->id,
        //         'start_date' => date('d.m.Y H:i', strtotime($order->start_date)),
        //         'isYour' => $order->driver_id == auth()->id(),
        //         'from' => optional($order->from)->name,
        //         'from_lng' => optional($order->from)->lng,
        //         'from_lat' => optional($order->from)->lat,
        //         'to' => optional($order->to)->name,
        //         'to_lng' => optional($order->to)->lng,
        //         'to_lat' => optional($order->to)->lat,
        //         'distance_km' => $distanceInfo['km'],
        //         'distance' => $distanceInfo['time'],
        //         'arrived_date' => date('d.m.Y H:i', strtotime($order->start_date. ' +' . $distanceInfo['time'])),
        //         'seats_count' => $order->seats,
        //         'price' => $order->price,
        //         'price_type' => $order->price_type,
        //         'driver_information' => $driverInformation,
        //         'car_information' => $carInformation,
        //         'clients_list' => $clientsList,
        //         'options' => json_decode($order->options) ?? []
        //     ];

        //     return $orderDetails;
        // }

        // /**
        //  * Generate driver information.
        //  *
        //  * @param object $driver The driver object
        //  * @return array|null The generated driver information array, or null if no driver provided
        //  */
        // private function generateDriverInformation($driver)
        // {
        //     if ($driver) {
        //         // Initialize variables to store driver details
        //         $d_full_name = '';
        //         $d_phone_number = '';
        //         $d_img = '';
                
        //         // Check if the driver has personal information
        //         if ($driver->personalInfo) {
        //             // Retrieve personal information of the driver
        //             $d_personal_info = $driver->personalInfo;

        //             // Build full name using first name, middle name, and last name
        //             $d_full_name = $d_personal_info->last_name . ' ' . $d_personal_info->first_name . ' ' . $d_personal_info->middle_name;
        //             $d_phone_number = $d_personal_info->phone_number;
        //             // Build image URL using the avatar path
        //             $d_img = asset('storage/avatar/' . $d_personal_info->avatar);

        //             // Generate driver comments array
        //             $arrComments = $this->generateDriverComments($driver->commentScores);
        //         }

        //         // Build driver information array
        //         $arrDriverInformation = [
        //             'id' => $driver->id,
        //             'full_name' => $d_full_name,
        //             'phone_number' => $d_phone_number,
        //             'img' => $d_img,
        //             'rating' => $driver->rating,
        //             'type' => $driver->type ?? 0,
        //             'count_comments' => count($arrComments ?? []),
        //             'comments' => $arrComments ?? [],
        //         ];

        //         return $arrDriverInformation;
        //     }

        //     return null;
        // }

        // /**
        //  * Generate driver comments array.
        //  *
        //  * @param array|null $commentScores The array of comment scores
        //  * @return array The array of driver comments
        //  */
        // private function generateDriverComments($commentScores) 
        // {
        //     $arrComments = [];

        //     if ($commentScores) {
        //         // Iterate through comment scores and build comments array
        //         foreach ($commentScores as $value) {
        //             $arrComments[] = [
        //                 'text' => $value->text,
        //                 'date' => date('d.m.Y H:i', strtotime($value->date)),
        //                 'score' => $value->score,
        //             ];
        //         }
        //     }

        //     return $arrComments;
        // }

        // /**
        //  * Generate car information array.
        //  *
        //  * @param object $car The car object
        //  * @return array The generated car information array
        //  */
        // private function generateCarInformation($car)
        // {
        //     $arrCarInfo = [];

        //     // Check if the car object is provided
        //     if ($car) {
        //         // Initialize an array to store car images
        //         $arrCarImg = [];
                
        //         // Check if the car has images
        //         if (!empty($car->images)) {
        //             $ci = 0;
                    
        //             // Iterate through car images and generate image URLs
        //             foreach (json_decode($car->images) as $valueCI) {
        //                 $arrCarImg[$ci] = asset('storage/cars/' . $valueCI);
        //                 $ci++;
        //             }
        //         }

        //         // Build car information array
        //         $arrCarInfo = [
        //             'id' => $car->id,
        //             'name' => $car->car->name ?? '',
        //             'color' => ($car->color) ? ['name' => $car->color->name, 'code' => $car->color->code] : [],
        //             'production_date' => date('d.m.Y', strtotime($car->production_date)),
        //             'class' => $car->class->name ?? '',
        //             'reg_certificate' => $car->reg_certificate,
        //             'reg_certificate_img' => $car->reg_certificate_image,
        //             'images' => $arrCarImg,
        //         ];
        //     }

        //     return $arrCarInfo;
        // }

        // /**
        //  * Generate an array of client information from the given order details.
        //  *
        //  * @param array $orderDetails The order details array
        //  * @return array The generated array of client information
        //  */
        // private function generateClientsList($orderDetails)
        // {
        //     $arrClients = [];

        //     // Iterate through each order detail to extract client information
        //     foreach ($orderDetails as $orderDetail) {
        //         $order_details_client = $orderDetail->client;
                
        //         // Retrieve the client's personal information, if available
        //         $c_personal_info = $order_details_client->personalInfo ?? null;

        //         // Build an array of client information
        //         $arrClients[] = [
        //             'id' => $order_details_client->id,
        //             'last_name' => optional($c_personal_info)->last_name ?? '',
        //             'first_name' => optional($c_personal_info)->first_name ?? '',
        //             'middle_name' => optional($c_personal_info)->middle_name ?? '',
        //             'phone_number' => optional($c_personal_info)->phone_number ?? '',
        //             'avatar' => optional($c_personal_info)->avatar ?? '',
        //             'gender' => optional($c_personal_info)->gender ?? '',
        //             'balance' => $order_details_client->balance ?? 0,
        //             'about_me' => $order_details_client->about_me ?? '',
        //         ];
        //     }

        //     return $arrClients;
        // }
    /* ========================= Order show end ========================= */


    public function create(OrderRequest $request)
    {
        $data = $request->validated();

        $driver_id = auth()->user()->id;
        $data['driver_id'] = $driver_id;
        $data['status_id'] = Constants::ORDERED;

        $order = new Order();
        $order->create($data);
        
        if (isset($data['back_date'])) {
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

    /* ========================= Order create start ========================= */
        // public function create(Request $request)
        // {
        //     $validator = Validator::make($request->all(), [
        //         'from_id' => 'required|integer',
        //         'to_id' => 'required|integer',
        //         'start_date' => 'required|date_format:Y-m-d H:i:s',
        //         'car_id' => 'required|integer',
        //         'seats' => 'required|integer',
        //         'options' => 'nullable|max:1000',
        //         'price' => 'nullable|numeric',
        //         'price_type' => 'nullable|integer',
        //         'tarif_id' => 'nullable|integer',
        //     ]);

        //     if ($validator->fails()) {
        //         return $this->error($validator->errors()->first(), 400);
        //     }

        //     $data = $request->all();
        //     $driver_id = auth()->user()->id;
        //     $data['driver_id'] = $driver_id;

        //     $this->createOrder($data);

        //     if (isset($data['back_date'])) {
        //         $reversedData = $this->reverseOrderData($data);
        //         $this->createOrder($reversedData);
        //     }

        //     return $this->success('success', 200);
        // }

        // private function createOrder($data)
        // {
        //     $order = new Order();
        //     $order->create($data);
        // }

        // private function reverseOrderData($data)
        // {
        //     return [
        //         'start_date' => $data['back_date'],
        //         'from_id' => $data['to_id'],
        //         'to_id' => $data['from_id'],
        //         // Add other necessary fields here
        //     ];
        // }
    /* ========================= Order create end ========================= */


    public function edit(Request $request)
    {
        if (!isset($request->id))
            return $this->error('id parameter is missing', 400);

        $car = Cars::find($request->car_id); 
        if (!isset($car))
            return $this->error('car_id parameter is not correct. Car not found', 400);

        $id = $request->id;
        $order = Order::find($id);

        if (!isset($order))
            return $this->error('id parameter is not correct. Order not found', 400);

        $order->car_id = $request->car_id;
        $order->seats = $request->seats;
        $order->options = $request->options;
        $order->price = $request->price;
        $order->price_type = $request->price_type;
        $order->save();

        return $this->success('success', 200);
    }


    /* ========================= Order edit start ========================= */
        // public function edit(Request $request)
        // {
        //     $validator = Validator::make($request->all(), [
        //         'from_id' => 'required|integer',
        //         'to_id' => 'required|integer',
        //         'start_date' => 'required|date_format:Y-m-d H:i:s',
        //         'car_id' => 'required|integer',
        //         'seats' => 'required|integer',
        //         'options' => 'nullable|max:1000',
        //         'price' => 'nullable|numeric',
        //         'price_type' => 'nullable|integer',
        //         'tarif_id' => 'nullable|integer',
        //     ]);

        //     $id = $request->input('id');
        //     $carId = $request->input('car_id');
            
        //     if (!$id) {
        //         return $this->error('id parameter is missing', 400);
        //     }

        //     $car = Cars::find($carId); 
        //     if (!$car) {
        //         return $this->error('car_id parameter is not correct. Car not found', 400);
        //     }

        //     $order = Order::find($id);
        //     if (!$order) {
        //         return $this->error('id parameter is not correct. Order not found', 400);
        //     }

        //     $this->updateOrder($order, $request);

        //     return $this->success('success', 200);
        // }

        // private function updateOrder(Order $order, Request $request)
        // {
        //     $order->car_id = $request->input('car_id');
        //     $order->seats = $request->input('seats');
        //     $order->options = $request->input('options');
        //     $order->price = $request->input('price');
        //     $order->price_type = $request->input('price_type');
        //     $order->save();
        // }
    /* ========================= Order edit end ========================= */


    public function delete(Request $request)
    {
        if (!isset($request->id))
            return $this->error('id parameter is missing', 400);

        $id = $request->id;

        $order = Order::find($id);
        if (!isset($order))
            return $this->error('id parameter is not correct. Order not found', 400);

        $order->delete();

        return $this->success('success', 200);
    }

    /* ========================= Order delete start ========================= */
        // public function delete(Request $request)
        // {
        //     $id = $request->input('id');

        //     if (!$id) {
        //         return $this->error('id parameter is missing', 400);
        //     }

        //     $order = Order::find($id);

        //     if (!$order) {
        //         return $this->error('Order not found with the given ID', 400);
        //     }

        //     $order->delete();

        //     return $this->success('Order deleted successfully', 200);
        // }
    /* ========================= Order delete end ========================= */


    public function cancel(Request $request)
    {
        $language = $request->header('language');

        if (!isset($request->id))
            return $this->error(translate_api('id parameter is missing', $language), 400);

        $id = $request->id;

        $order = Order::find($id);
        if (!isset($order))
            return $this->error(translate_api('id parameter is not correct. Order not found', $language), 400);
        
        if ($order->status_id == Constants::CANCEL_ORDER)
            return $this->error(translate_api('This order has already been canceled', $language), 400);

        $order->status_id = Constants::CANCEL_ORDER;
        $order->save();

        $offers = $this->findOffers($id);
        $order_id = $order->id;
        $this->cancelOffers($offers, $language, $order_id);

        return $this->success(translate_api('success', $language), 200);
    }

    private function findOffers($id)
    {
        $offers = Offer::where('order_id', $id)->where('status', '!=', Constants::CANCEL)->get();

        return $offers;
    }

    private function cancelOffers($offers, $language, $order_id = 0)
    {
        if (!empty($offers)) {
            foreach ($offers as $offer) {
                $offer->status = Constants::CANCEL;
                $offer->cancel_type = Constants::ORDER;
                $offer->cancel_date = date('Y-m-d H:i:s');
                $offer->save();

                $offersOrderDetail = $offer->orderDetail;
                $device = ($offersOrderDetail->client) ? json_decode($offersOrderDetail->client->device_id) : [];
                $title = translate_api('Your request has been denied', $language);
                $message = translate_api('Route', $language) . ': ' . (($offersOrderDetail && $offersOrderDetail->from) ? $offersOrderDetail->from->name : '') . ' - ' . (($offersOrderDetail && $offersOrderDetail->to) ? $offersOrderDetail->to->name : '');
                $user_id = ($offersOrderDetail->client) ? $offersOrderDetail->client->id : 0;
                $entity_id = $order_id;
    
                $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
            }
        }
    }


    public function history(Request $request)
    {
        if ($request->page)
            $page = $request->page;
        else
            return $this->error('page parameter is missing', 400);
        
        $limitData = 10;
        $model = Order::where('driver_id', auth()->id())->orderBy('id', 'asc')->offset(($page - 1) * $limitData)->limit($limitData)->get();

        $arr = [];
        if (isset($model) && count($model) > 0) {
            $n = 0;
            foreach ($model as $key => $value) {
                $clientArr = [];
                if ($value->orderDetails) {
                    $i = 0;
                    foreach ($value->orderDetails as $keyOD => $valueOD) {
                        if (isset($valueOD->client) && isset($valueOD->client->personalInfo)) {
                            $clientArr[$i]['clients_full_name'] = $valueOD->client->personalInfo->last_name . ' ' . $valueOD->client->personalInfo->first_name . ' ' . $valueOD->client->personalInfo->middle_name;
                            $clientArr[$i]['client_img'] = asset('storage/avatar/' . $valueOD->client->personalInfo->avatar);
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
                    $arrDriverInfo['doc_status'] = ($valDriver->driver) ? (int)$valDriver->driver->doc_status : NULL;
                }

                $arrCar = [];
                if ($value->car) {
                    $valCar = $value->car;
                    
                    $arrCarImg = [];
                    if (!empty($valCar->images)) {
                        $ci = 0;
                        foreach (json_decode($valCar->images) as $valueCI) {
                            $arrCarImg[$ci] = asset('storage/cars/' . $valueCI);
                            $ci++;
                        }
                    }

                    $arrCar['id'] = $valCar->id;
                    $arrCar['name'] = $valCar->car->name ?? '';
                    $arrCar['color'] = ($valCar->color) ? ['name' => $valCar->color->name, 'code' => $valCar->color->code] : [];
                    $arrCar['production_date'] = date('Y', strtotime($valCar->production_date));
                    $arrCar['class'] = $valCar->class->name ?? '';
                    $arrCar['reg_certificate'] = $valCar->reg_certificate;
                    $arrCar['reg_certificate_img'] = asset('storage/cars/' . $valCar->reg_certificate_image);
                    $arrCar['images'] = $arrCarImg;
                }

                $distance = $this->getDistanceAndKm((($value->from) ? $value->from->lng : ''), (($value->from) ? $value->from->lat : ''), (($value->to) ? $value->to->lng : ''), (($value->to) ? $value->to->lat : ''));

                $arr[$n]['id'] = $value->id;
                $arr[$n]['start_date'] = date('d.m.Y H:i', strtotime($value->start_date));
                $arr[$n]['price'] = (double)$value->price;
                $arr[$n]['isYour'] = ($value->driver_id == auth()->id()) ? true : false;
                // $arr[$n]['from'] = ($value->from) ? $value->from->name : '';
                // $arr[$n]['to'] = ($value->to) ? $value->to->name : '';
                $arr[$n]['seats_count'] = $value->seats ?? 0;
                $arr[$n]['booking_count'] = ($value->orderDetails) ? count($value->orderDetails) : 0;
                $arr[$n]['clients_list'] = $clientArr;
                $arr[$n]['driver'] = $arrDriverInfo;
                $arr[$n]['car'] = (empty($arrCar)) ? NULL : $arrCar;
                $arr[$n]['options'] = json_decode($value->options) ?? [];

                $arr[$n]['from'] = ($value->from) ? $value->from->name : '';
                $arr[$n]['from_lng'] = ($value->from) ? $value->from->lng : '';
                $arr[$n]['from_lat'] = ($value->from) ? $value->from->lat : '';
                $arr[$n]['to'] = ($value->to) ? $value->to->name : '';
                $arr[$n]['to_lng'] = ($value->to) ? $value->to->lng : '';
                $arr[$n]['to_lat'] = ($value->to) ? $value->to->lat : '';
                
                $arr[$n]['distance_km'] = $distance['km'];
                $arr[$n]['distance'] = $distance['time'];
                $arr[$n]['arrived_date'] = date('d.m.Y H:i', strtotime($value->start_date. ' +' . $distance['time']));
                
                $arr[$n]['status'] = ($value->status) ? $value->status->name : '';

                $n++;
            }

            return $this->success('success', 200, $arr);
        } else {
            return $this->success('Order table is empty', 200, $arr);
        }
    }

    /* ========================= Order hostory start ========================= */
        // public function history(Request $request)
        // {
        //     // Check if 'page' parameter exists in the request
        //     if (!$request->has('page')) {
        //         return $this->error('page parameter is missing', 400);
        //     }

        //     // Retrieve the 'page' parameter from the request
        //     $page = $request->input('page');

        //     // Retrieve a list of orders with pagination and ordering
        //     $model = Order::orderBy('id', 'asc')
        //         ->offset(($page - 1) * 15)
        //         ->limit(15)
        //         ->get();

        //     $arr = [];

        //     // Check if there are orders in the result
        //     if ($model->isNotEmpty()) {
        //         foreach ($model as $key => $value) {
        //             // Initialize an array to store client information
        //             $clientArr = [];

        //             if ($value->orderDetails) {
        //                 foreach ($value->orderDetails as $keyOD => $valueOD) {
        //                     if (isset($valueOD->client) && isset($valueOD->client->personalInfo)) {
        //                         // Create an array with client information
        //                         $clientArr[] = [
        //                             'clients_full_name' => "{$valueOD->client->personalInfo->last_name} {$valueOD->client->personalInfo->first_name} {$valueOD->client->personalInfo->middle_name}",
        //                             'client_img' => asset('storage/avatar/' . $valueOD->client->personalInfo->avatar),
        //                             'client_rating' => 4.3,
        //                         ];
        //                     }
        //                 }
        //             }

        //             // Initialize an array to store driver information
        //             $arrDriverInfo = [];

        //             if ($value->driver) {
        //                 $valDriver = $value->driver;

        //                 $d_full_name = '';
        //                 $d_phone_number = '';
        //                 $d_img = '';

        //                 if ($valDriver->personalInfo) {
        //                     $driverPersonalInfo = $valDriver->personalInfo;

        //                     $d_full_name = "{$driverPersonalInfo->last_name} {$driverPersonalInfo->first_name} {$driverPersonalInfo->middle_name}";
        //                     $d_phone_number = $driverPersonalInfo->phone_number;
        //                     $d_img = asset('storage/avatar/' . $driverPersonalInfo->avatar);
        //                 }

        //                 // Create an array with driver information
        //                 $arrDriverInfo = [
        //                     'full_name' => $d_full_name,
        //                     'phone_number' => $d_phone_number,
        //                     'img' => $d_img,
        //                     'rating' => $valDriver->rating,
        //                 ];
        //             }

        //             // Initialize an array to store car information
        //             $arrCar = [];

        //             if ($value->car) {
        //                 $valCar = $value->car;

        //                 $arrCarImg = [];

        //                 if (!empty($valCar->images)) {
        //                     foreach (json_decode($valCar->images) as $valueCI) {
        //                         $arrCarImg[] = asset('storage/cars/' . $valueCI);
        //                     }
        //                 }

        //                 // Create an array with car information
        //                 $arrCar = [
        //                     'id' => $valCar->id,
        //                     'name' => $valCar->car->name ?? '',
        //                     'color' => ($valCar->color) ? ['name' => $valCar->color->name, 'code' => $valCar->color->code] : [],
        //                     'production_date' => date('d.m.Y', strtotime($valCar->production_date)),
        //                     'class' => $valCar->class->name ?? '',
        //                     'reg_certificate' => $valCar->reg_certificate,
        //                     'reg_certificate_img' => asset('storage/cars/' . $valCar->reg_certificate_image),
        //                     'images' => $arrCarImg,
        //                 ];
        //             }

        //             $distance = $this->getDistanceAndKm(
        //                 ($value->from) ? $value->from->lng : '',
        //                 ($value->from) ? $value->from->lat : '',
        //                 ($value->to) ? $value->to->lng : '',
        //                 ($value->to) ? $value->to->lat : ''
        //             );

        //             // Create an array with order information
        //             $arr[] = [
        //                 'id' => $value->id,
        //                 'start_date' => date('d.m.Y H:i', strtotime($value->start_date)),
        //                 'price' => (double)$value->price,
        //                 'isYour' => ($value->driver_id == auth()->id()),
        //                 'seats_count' => $value->seats ?? 0,
        //                 'booking_count' => ($value->orderDetails) ? count($value->orderDetails) : 0,
        //                 'clients_list' => $clientArr,
        //                 'driver' => $arrDriverInfo,
        //                 'car' => (empty($arrCar)) ? NULL : $arrCar,
        //                 'options' => json_decode($value->options) ?? [],
        //                 'from' => ($value->from) ? $value->from->name : '',
        //                 'from_lng' => ($value->from) ? $value->from->lng : '',
        //                 'from_lat' => ($value->from) ? $value->from->lat : '',
        //                 'to' => ($value->to) ? $value->to->name : '',
        //                 'to_lng' => ($value->to) ? $value->to->lng : '',
        //                 'to_lat' => ($value->to) ? $value->to->lat : '',
        //                 'distance_km' => $distance['km'],
        //                 'distance' => $distance['time'],
        //                 'arrived_date' => date('d.m.Y H:i', strtotime("{$value->start_date} + {$distance['time']}")),
        //                 'status' => ($value->status) ? $value->status->name : '',
        //             ];
        //         }

        //         return $this->success('success', 200, $arr);
        //     } else {
        //         return $this->success('Order table is empty', 204);
        //     }
        // }
    /* ========================= Order hostory end ========================= */




    public function expired()
    {
        // $model = Order::where('start_date', '<', date('Y-m-d H:i:s'))->orderBy('start_date', 'asc')->get();
        $model = Order::orderBy('start_date', 'asc')->get();

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
                    $arrDriverInfo['doc_status'] = ($valDriver->driver) ? (int)$valDriver->driver->doc_status : NULL;
                }

                $distance = $this->getDistanceAndKm((($value->from) ? $value->from->lng : ''), (($value->from) ? $value->from->lat : ''), (($value->to) ? $value->to->lng : ''), (($value->to) ? $value->to->lat : ''));


                $arr[$n]['id'] = $value->id;
                $arr[$n]['start_date'] = date('d.m.Y H:i', strtotime($value->start_date));
                $arr[$n]['price'] = $value->price;
                $arr[$n]['isYour'] = ($value->driver_id == auth()->id()) ? true : false;
                $arr[$n]['from'] = ($value->from) ? $value->from->name : '';
                $arr[$n]['from_lng'] = ($value->from) ? $value->from->lng : '';
                $arr[$n]['from_lat'] = ($value->from) ? $value->from->lat : '';
                $arr[$n]['to'] = ($value->to) ? $value->to->name : '';  
                $arr[$n]['to_lng'] = ($value->to) ? $value->to->lng : '';
                $arr[$n]['to_lat'] = ($value->to) ? $value->to->lat : '';
                $arr[$n]['distance_km'] = $distance['km'];
                $arr[$n]['distance'] = $distance['time'];
                $arr[$n]['arrived_date'] = date('d.m.Y H:i', strtotime($arr[$n]['start_date']. ' +' . $distance['time']));
                $arr[$n]['seats_count'] = $value->seats;
                // $arr[$n]['booking_count'] = $value->/*seats*/;
                $arr[$n]['driver_information'] = $arrDriverInfo;
                $arr[$n]['options'] = json_decode($value->options) ?? [];
                
                $n++;
            }

            return $this->success('success', 200, $arr);
        } else {
            return $this->success('Order table is empty', 200, $arr);
        }
    }

    /* ========================= ========================= */
        // public function expired()
        // {
        //     $orders = Order::orderBy('start_date', 'asc')->get();
        //     $result = [];
        
        //     foreach ($orders as $order) {
        //         $driverInfo = $this->getDriverInfo($order->driver);
        //         $distance = $this->getDistanceAndKm((($order->from) ? $order->from->lng : ''), (($order->from) ? $order->from->lat : ''), (($order->to) ? $order->to->lng : ''), (($order->to) ? $order->to->lat : ''));
        
        //         $result[] = [
        //             'id' => $order->id,
        //             'start_date' => date('d.m.Y H:i', strtotime($order->start_date)),
        //             'price' => $order->price,
        //             'isYour' => $order->driver_id == auth()->id(),
        //             'from' => $order->from ? $order->from->name : '',
        //             'from_lng' => $order->from ? $order->from->lng : '',
        //             'from_lat' => $order->from ? $order->from->lat : '',
        //             'to' => $order->to ? $order->to->name : '',
        //             'to_lng' => $order->to ? $order->to->lng : '',
        //             'to_lat' => $order->to ? $order->to->lat : '',
        //             'distance_km' => $distance['km'],
        //             'distance' => $distance['time'],
        //             'arrived_date' => date('d.m.Y H:i', strtotime($order->start_date . ' +' . $distance['time'])),
        //             'seats_count' => $order->seats,
        //             'driver_information' => $driverInfo,
        //             'options' => json_decode($order->options) ?? [],
        //         ];
        //     }
        
        //     if (empty($result)) {
        //         return $this->success('Order table is empty', 200, $result);
        //     }
        
        //     return $this->success('success', 200, $result);
        // }
        
        // private function getDriverInfo($driver)
        // {
        //     $driverInfo = [];
        
        //     if ($driver && $driver->personalInfo) {
        //         $driverPersonalInfo = $driver->personalInfo;
        //         $driverInfo = [
        //             'full_name' => $driverPersonalInfo->last_name . ' ' . $driverPersonalInfo->first_name . ' ' . $driverPersonalInfo->middle_name,
        //             'phone_number' => $driverPersonalInfo->phone_number,
        //             'img' => asset('storage/avatar/' . $driverPersonalInfo->avatar),
        //             'rating' => $driver->rating,
        //             'doc_status' => true, // Assuming $valDriver->driver is always truthy
        //         ];
        //     }
        
        //     return $driverInfo;
        // }
    /* ========================= ========================= */



    public function booking(Request $request)
    {
        $language = $request->header('language');

        if (!$request['order_id'])
            return $this->error(translate_api('order_id parameter is missing', $language), 400);

        $order_id = $request['order_id'];

        if (!$request['order_detail_id'])
            return $this->error(translate_api('order_detail_id parameter is missing', $language), 400);
        
        $order_detail_id = $request['order_detail_id'];

        $order = Order::find($order_id);
        $orderDetail = OrderDetail::find($order_detail_id);

        if (!$order)
            return $this->success(translate_api('Order not found', $language), 204);

        if (!$orderDetail)
            return $this->success(translate_api('Order Detail not found', $language), 204);
     
        $options = json_decode($order->options);
        
        $seats_count = ($order->seats) - ($order->booking_place);
        if ($request['offer_id'] != '') {
            if ($offer = Offer::where('id', $request['offer_id'])->first()) {
                if ($offer->status !== Constants::ACCEPT) {
                    if (($order->booking_place + $offer->seats) <= $order->seats && $offer->cancel_type !== Constants::ORDER_DETAIL) {
                        $offer->update([
                            'status' => Constants::ACCEPT,
                            'accepted' => Constants::OFFER_ACCEPTED
                        ]);
                     
                        $orderDetail->order_id = $order->id;
                        $saveOrderDetail = $orderDetail->save();
    
                        $order->booking_place = ($order->booking_place > 0) ? ($order->booking_place + $offer->seats ): $offer->seats;
                        $saveOrder = $order->save();
    
                        if ($order->booking_place == $order->seats) {
                            $cancel_offers = Offer::where('order_id', $order->id)->where('order_detail_id', '!=', $orderDetail->id)->where('status', Constants::NEW)->get();

                            if (!empty($cancel_offers)) {
                                foreach ($cancel_offers as $value) {
                                    $offersOrderDetail = $value->orderDetail;
                                    $value->update(['status' => Constants::CANCEL]);

                                    $device = ($offersOrderDetail->client) ? json_decode($offersOrderDetail->client->device_id) : [];
                                    $title = translate_api('Your request has been denied', $language);
                                    $message = translate_api('Route', $language) . ': ' . (($offersOrderDetail && $offersOrderDetail->from) ? $offersOrderDetail->from->name : '') . ' - ' . (($offersOrderDetail && $offersOrderDetail->to) ? $offersOrderDetail->to->name : '');
                                    $user_id = ($offersOrderDetail->client) ? $offersOrderDetail->client->id : 0;
                                    $entity_id = $order->id;
    
                                    $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
                                }
                            }
                        }    
    
                        $device = ($orderDetail->client) ? json_decode($orderDetail->client->device_id) : [];
                        $title = translate_api('Your request has been accepted', $language);
                        $message = translate_api('Route', $language) . ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
                        $user_id = ($orderDetail->client) ? $orderDetail->client->id : 0;
                        $entity_id = $order->id;
    
                        $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
                        
                        $id = auth()->id();
                        $data = $this->getOffer($id , $language);
                       
                        return $this->success(translate_api('Offer accepted', $language), 200, $data);
                        
                    } elseif ($offer->cancel_type == Constants::ORDER_DETAIL) {
                        // return $this->success(translate_api('Sorry this offer canceled', $language), 200);
                        return $this->error(translate_api('Unfortunately, you will not be able to reserve a place on this booking as you have canceled your previous booking', $language), 400);

                    } else {
                        return $this->error(translate_api('Sorry we only have ', $language) . $seats_count . translate_api(' Spaces available', $language), 400);

                        // return $this->success(translate_api('Sorry we only have', $language) . $seats_count . translate_api('Spaces available', $language), 200);
                    }
                } else {
                    return $this->error(translate_api('Sorry, this booking has been cancelled or accepted', $language), 400);

                    // return $this->success(translate_api('Sorry, this booking has been cancelled or accepted', $language), 200);
                }
            }
        } elseif ($options->quick_booking == 1) {
            if (($order->booking_place + $request['seats']) <= $order->seats) {

                $old_offer = Offer::where('order_id',$order->id)->where('order_detail_id',$orderDetail->id)->first();

                if ($old_offer) {
                    if ($old_offer->accepted == Constants::NOT_ACCEPTED && $old_offer->status==Constants::CANCEL) {
                        $old_offer->update([
                            'status' => Constants::ACCEPT,
                            'seats' =>$request['seats'],
                            'accepted' => Constants::OFFER_ACCEPTED
                        ]);
    
    
                        $device = ($order->driver) ? json_decode($order->driver->device_id) : [];
                        $title = translate_api('Your request has been accepted', $language);
                        $message = translate_api('Route', $language) . ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
                        $user_id = ($order->driver) ? $order->driver->id : 0;
                        $entity_id = $order->id;
        
                        $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
    
                        return $this->success(translate_api('offer created', $language), 204);  
    
                    }
                    if ($old_offer->accepted == Constants::OFFER_ACCEPTED && $old_offer->status==Constants::CANCEL && $old_offer->cancel_type==Constants::ORDER_DETAIL) {

                        // return $this->success(translate_api('You cannot reserve a seat on this trip because you have already cancelled', $language), 204);           
                        return $this->error(translate_api('Sorry, you cannot make another offer for this order', $language), 400);

                    }
                }
                // dd($old_offer);
               


                $id = auth()->id();
                $create_type = ($id == $orderDetail->client_id) ? 0 : 1;
                
                $offer = new Offer();
                $offer->order_id = $order->id;
                $offer->seats = $request['seats'];
                $offer->order_detail_id = $orderDetail->id;
                $offer->create_type = $create_type;
                $offer->status = Constants::ACCEPT;
                $offer->save();

                $order->booking_place = ($order->booking_place > 0) ? ($order->booking_place + $offer->seats): $offer->seats;
                $saveOrder = $order->save();
            } else {
                // return $this->success(translate_api('sorry we only have', $language). $seats_count . translate_api('spaces available', $language), 200);
                return $this->error(translate_api('Sorry we only have ', $language) . $seats_count . translate_api(' Spaces available', $language), 400);
            }

            $orderDetail->order_id = $order->id;
            $saveOrderDetail = $orderDetail->save();

            $device = ($order->driver) ? json_decode($order->driver->device_id) : [];
            $title = translate_api('Your request has been accepted', $language);
            $message = translate_api('Route', $language) . ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
            $user_id = ($order->driver) ? $order->driver->id : 0;
            $entity_id = $order->id;
    
            $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);

            $id = auth()->id();
            $data = $this->getOffer($id , $language);

            return $this->success(translate_api('offer created', $language), 204 , $data);  
        } else {
            // return $this->success(translate_api('Offer not found', $language), 204);
            return $this->error(translate_api('Offer not found', $language), 400);

        }
    }

    public function bookingCancel(Request $request)
    {
        // agar offer id berilsa order_id and order_detail_id kemidi
        // agar order_id and order_detail_id berilsa offer_id kemidi
        $language = $request->header('language');

        if ($request->offer_id) {

            $first_offer = Offer::where('id', $request['offer_id'])->first();
            if ($first_offer->status != Constants::NEW) {
                // return $this->success('This is offer sttus not new', 204);
                return $this->error(translate_api('This is offer sttus not new', $language), 400);

            }
        }
        else {
            
            $first_offer = Offer::where('order_id', $request['order_id'])->where('order_detail_id', $request['order_detail_id'])->first();
            
        }
        
        
        
        if ($first_offer) {



            $order_detail_id = $first_offer->order_detail_id;

            $order = Order::where('id',$first_offer->order_id)->first();
            $orderDetail = OrderDetail::find($order_detail_id);

            if (!$order)
                // return $this->success('Order not found', 204);
                return $this->error(translate_api('Order not found', $language), 400);


            if (!$orderDetail)
                // return $this->success('Order detail not found', 204);
                return $this->error(translate_api('Order detail not found', $language), 400);


            $orderDetail->order_id = null;
            $saveOrderDetail = $orderDetail->save();

            $timezone = 'Asia/Tashkent';
            $date_time = Carbon::now($timezone)->format('Y-m-d H:i:s');
            $id = auth()->id();
            if ($id == $orderDetail->client_id) {
                $cancel_type = 0;
            } else {
                $cancel_type = 1;
            } 


            // dd($order);
            $order->booking_place = ($order->booking_place > 0) ? ($order->booking_place - $first_offer->seats) : 0;
            $saveOrder = $order->save();

            $old_offer_status = $first_offer->status;

            $offer = [
                'cancel_type' => $cancel_type,
                'cancel_date' => $date_time,
                'status' => Constants::CANCEL
                // 'price' => $order->price
            ];

            $cancel_offer = $first_offer->update($offer);

            $title = '';
            $user_id = ($order->driver) ? $order->driver->id : 0;
            $device = ($order->driver) ? json_decode($order->driver->device_id) : [];
            if ($old_offer_status == Constants::NEW) {
                $title = translate_api('Your request has been denied', $language);
                if ($id == $order->driver_id) {
                    $user_id = $orderDetail->client->id;
                    $device = ($orderDetail->client) ? json_decode($orderDetail->client->device_id) : [];
                } else {
                    $user_id = $order->driver->id;
                    $device = ($order->driver) ? json_decode($order->driver->device_id) : [];
                }
            } else {
                if ($id == $order->driver_id) {
                    $user_id = ($orderDetail->client) ? $orderDetail->client->id : 0;
                    $title = translate_api('Passenger canceled the booking', $language);
                    $device = ($orderDetail->client) ? json_decode($orderDetail->client->device_id) : [];
                } else {
                    $title = translate_api('Your order has been cancelled', $language);
                    $device = ($order->driver) ? json_decode($order->driver->device_id) : [];
                }
            }
             
            $message = translate_api('Route', $language) . ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
            $entity_id = $order->id;
    
            $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
        } else {
            // return $this->success('Offer not found', 204);
            return $this->error(translate_api('Offer not found', $language), 400);

        }

        $id=auth()->id();
        $data=$this->getOffer($id , $language);

        return $this->success('offer cancelled', 200, $data);
    }


    public function getOptions(Request $request)
    {
        $language = $request->header('language');
        $options = table_translate('', 'option', $language);
       // $options = Options::select('id', 'name', 'icon')->get();

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
            return $this->success('Options table is empty', 200, $data);
        }
    }

    public function priceDestinations(Request $request)
    {
        if (!$request['from_id'])
            return $this->error('from_id parameter is missing', 400);

        $cityFrom = DB::table('yy_cities')->find($request['from_id']);
        if (!$cityFrom)
            return $this->error('from_id parameter is not correct. cities from not found', 400);

        if (!$request['to_id'])
            return $this->error('to_id parameter is missing', 400);

        $cityTo = DB::table('yy_cities')->find($request['to_id']);
        if (!$cityTo)
            return $this->error('to_id parameter is not correct. cities to not found', 400);

        $distance = $this->getDistanceAndKm($cityFrom->lng, $cityFrom->lat, $cityTo->lng, $cityTo->lat);

        $minPrice = round((int)($distance['distance_value'] / 1000 * Constants::MIN_DESTINATION_PRICE) / 1000) * 1000;
        $maxPrice = round((int)($distance['distance_value'] / 1000 * Constants::MAX_DESTINATION_PRICE) / 1000) * 1000;

        return $this->success('success', 200, ['min_price' => $minPrice, 'max_price' => $maxPrice]);
    }


    public function getOffer($id , $language){




                            
            $offers = DB::table('yy_offers as dt1')
            ->Leftjoin('yy_order_details as dt2', 'dt2.id', '=', 'dt1.order_detail_id')
            ->Leftjoin('yy_orders as dt3', 'dt3.id', '=', 'dt1.order_id')
            ->Leftjoin('yy_statuses as dt4', 'dt4.type_id', '=', 'dt1.status')
            ->Leftjoin('yy_users as dt5', 'dt5.id', '=', 'dt2.client_id')
            ->Leftjoin('yy_personal_infos as dt6', 'dt6.id', '=', 'dt5.personal_info_id')
            ->where('dt3.driver_id', $id)
            ->orWhere('dt2.client_id', $id)
            ->select('dt1.id as offer_id','dt1.order_id','dt1.status as status_id', 'dt1.order_detail_id','dt2.from_id' ,'dt2.to_id',DB::raw('DATE(dt2.start_date) as start_date'),'dt2.client_id as client_id','dt2.seats_count as seats_count','dt4.name as status','dt5.rating','dt6.first_name','dt6.middle_name','dt6.last_name','dt6.avatar')
            ->get();
            // ->toArray();
            // dd($offers);


            $data=[];
            foreach ($offers as $key => $offer) {
                // dd($offer);
                $from_to_name=table_translate($offer,'city',$language);


                if(isset($offer->avatar)){
                    $avatar = storage_path('app/public/avatar/'.$offer->avatar);
                    if(file_exists($avatar)){
                        $offer->avatar = asset('storage/avatar/'.$offer->avatar);
                    }
                    else {
                        $offer->avatar=null;
                    }
                }
                if ($offer->status_id == Constants::NEW) {
                    

                    if ($offer->client_id==$id) {
                        $is_your=true;
                    }else {
                        $is_your=false;
                    }
                    $list=[
                        'offer_id'=>$offer->offer_id,
                        'order_id'=>$offer->order_id,
                        'order_detail_id'=>$offer->order_detail_id,
                        'start_date'=>$offer->start_date,
                        'status'=>$offer->status,
                        'rating'=>$offer->rating,
                        'from_name' => $from_to_name['from_name'],
                        'to_name' => $from_to_name['to_name'],
                        'full_name'=> $offer->first_name. '.' .$offer->last_name[0],
                        'avatar'=>$offer->avatar,
                        'seats_count'=>$offer->seats_count,
                        'is_your'=>$is_your
                    ];
                    array_push($data , $list);

                }

            }

            return $data;



    }

}
