<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use App\Models\PersonalInfo;
use App\Models\User;
use Illuminate\Http\Request;
use App\Constants;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class OrderDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     //
    // }

    // /**
    //  * Show the form for creating a new resource.
    //  */
    // public function create()
    // {
    //     //
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_id' => 'required|integer',
            'to_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d',
            'seats_count' => 'nullable|integer|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $language = $request->header('language');
        $data = $request->all();

        $order_detail = OrderDetail::create([
            'client_id' => auth()->id(),
            'status_id' => Constants::ACTIVE,
            'from_id' => $data['from_id'],
            'to_id' => $data['to_id'],
            'seats_count' => $data['seats_count'],
            'start_date' => date('Y-m-d', strtotime($data['start_date'])),
            'type' => Constants::CREATED_ORDER_DETAIL
        ]);

        $message = translate_api('success',$language);

        return $this->success($message, 200);
    }

    /* ========================= OrderCode store start ========================= */
    // public function store(Request $request)
    // {
    //     // Get the 'language' header from the request
    //     $language = $request->header('language');

    //     // Validate the request data
    //     $validatedData = $request->validate([
    //         'from_id' => 'required',
    //         'to_id' => 'required',
    //         'seats_count' => 'required',
    //         'date' => 'required|date',
    //     ]);

    //     // Create an OrderDetail instance and store it in the database
    //     $order_detail = OrderDetail::create([
    //         'client_id' => auth()->id(),
    //         'status_id' => Constants::ACTIVE,
    //         'from_id' => $validatedData['from_id'],
    //         'to_id' => $validatedData['to_id'],
    //         'seats_count' => $validatedData['seats_count'],
    //         'start_date' => Carbon::parse($validatedData['date'])->format('Y-m-d'),
    //     ]);

    //     // Define the timezone
    //     $timezone = 'Asia/Tashkent';

    //     // Get the current date
    //     $date = Carbon::now($timezone)->format('Y-m-d');

    //     // Parse the provided date from the request
    //     $came_date = Carbon::parse($validatedData['date']);

    //     // Calculate tomorrow's date
    //     $tomorrow = $came_date->copy()->addDay();

    //     // Calculate the start and end date for a date range
    //     $startDate = $came_date->copy()->subDays(3)->format('Y-m-d');
    //     $endDate = ($came_date < $date) ? $date : $came_date->copy()->addDays(3)->format('Y-m-d');

    //     if ($came_date >= $date) {
    //         // Translate city names and construct the order detail array
    //         $from_to_name = table_translate($order_detail, 'city', $language);

    //         $order_detail_arr = [
    //             'id' => $order_detail->id,
    //             'seats_count' => (int)$order_detail->seats_count,
    //             'start_date' => date('d.m.Y', strtotime($order_detail->start_date)),
    //             'from_id' => (int)$order_detail->from_id,
    //             'from_name' => $from_to_name['from_name'],
    //             'from_lng' => optional($order_detail->from)->lng ?? '',
    //             'from_lat' => optional($order_detail->from)->lat ?? '',
    //             'to_id' => (int)$order_detail->to_id,
    //             'to_name' => $from_to_name['to_name'],
    //             'to_lng' => optional($order_detail->to)->lng ?? '',
    //             'to_lat' => optional($order_detail->to)->lat ?? '',
    //         ];

    //         // Retrieve order information for the specified date
    //         $order_information = DB::table('yy_orders')
    //             ->where('status_id', Constants::ORDERED)
    //             ->where('from_id', $validatedData['from_id'])
    //             ->where('to_id', $validatedData['to_id'])
    //             ->select(DB::raw('DATE(start_date) as start_date'), 'driver_id', 'price', 'booking_place')
    //             ->where('start_date', '>=', $came_date->format('Y-m-d'))
    //             ->where('start_date', '<', $tomorrow->format('Y-m-d'))
    //             ->get();

    //         if (!empty($order_information)) {
    //             $list = [];
    //             $total_trips = DB::table('yy_orders')
    //                 ->where('driver_id', auth()->id())
    //                 ->where('status_id', Constants::COMPLETED)
    //                 ->count();

    //             foreach ($order_information as $order) {
    //                 $personalInfo = PersonalInfo::where('id', optional(User::find($order->driver_id))->personal_info_id)->first();

    //                 $data = [
    //                     'start_date' => date('d.m.Y H:i', strtotime($order->start_date)),
    //                     'avatar' => optional($personalInfo)->avatar ? asset('storage/avatar/' . $personalInfo->avatar) : null,
    //                     'rating' => (int)optional($personalInfo->driver)->rating ?? 0,
    //                     'price' => (float)$order->price,
    //                     'name' => optional($personalInfo)->full_name,
    //                     'total_trips' => $total_trips,
    //                     'count_place' => $order->booking_place,
    //                 ];

    //                 array_push($list, $data);
    //             }
    //         } else {
    //             // Retrieve order dates within the date range
    //             $order_dates = DB::table('yy_orders')
    //                 ->where('status_id', Constants::ORDERED)
    //                 ->where('from_id', $validatedData['from_id'])
    //                 ->where('to_id', $validatedData['to_id'])
    //                 ->where('start_date', '>=', $date)
    //                 ->select(DB::raw('DATE(start_date) as start_date'))
    //                 ->whereBetween('start_date', [$startDate, $endDate])
    //                 ->orderBy('start_date', 'asc')
    //                 ->distinct()
    //                 ->take(5)
    //                 ->get();

    //             $list = $order_dates->pluck('start_date')->toArray();
    //         }

    //         $message = translate_api('success', $language);

    //         return response()->json([
    //             'data' => $list,
    //             'order_detail' => $order_detail_arr,
    //             'status' => true,
    //             'message' => $message,
    //         ], 200);
    //     } else {
    //         $message = translate_api('Sorry, you must enter a date greater than or equal to today', $language);
    //         return $this->error($message, 500);
    //     }
    // }
    /* ========================= OrderCode store end ========================= */


    public function edit(Request $request)
    {
        if (!isset($request->id))
            return $this->error('id parameter is missing', 400);

        $id = $request->id;
        $model = OrderDetail::find($id);
        if (!isset($model))
            return $this->error('id parameter is not correct. OrderDetail not found', 400);

        $model->seats_type = $request->seats_type;
        $model->seats_count = $request->seats_count;
        $model->comment = $request->comment;
        $model->price = $request->price;
        $model->save();

        return $this->success('success', 200);
    }

    /* ========================= OrderCode edit start ========================= */
    // public function edit(Request $request)
    // {
    //     // Get the 'language' header from the request
    //     $language = $request->header('language');

    //     // Check if 'id' parameter is provided
    //     if (!$request->has('id')) {
    //         return $this->error(translate_api('id parameter is missing', $language), 400);
    //     }

    //     $id = $request->input('id');
        
    //     // Find the OrderDetail by its ID
    //     $model = OrderDetail::find($id);
        
    //     // Check if the OrderDetail exists
    //     if (!$model) {
    //         return $this->error(translate_api('OrderDetail not found for the given id', $language), 400);
    //     }

    //     // Update the fields based on the request data
    //     $model->seats_type = $request->input('seats_type', $model->seats_type);
    //     $model->seats_count = $request->input('seats_count', $model->seats_count);
    //     $model->comment = $request->input('comment', $model->comment);
    //     $model->price = $request->input('price', $model->price);

    //     // Save the changes to the database
    //     $model->save();

    //     // Return a success response
    //     $message = translate_api('success', $language);
    //     return $this->success($message, 200);
    // }
    /* ========================= OrderCode edit end ========================= */


    public function delete(Request $request)
    {
        if (!isset($request->id))
            return $this->error('id parameter is missing', 400);

        $id = $request->id;

        $orderDetail = OrderDetail::find($id);
        if (!isset($orderDetail))
            return $this->error('id parameter is not correct. OrderDetail not found', 400);

        $orderDetail->delete();

        return $this->success('success', 200);
    }

    /* ========================= OrderCode delete start ========================= */
    // public function delete(Request $request)
    // {
    //     // Check if 'id' parameter is provided
    //     if (!$request->has('id')) {
    //         return $this->error('id parameter is missing', 400);
    //     }

    //     $id = $request->input('id');
        
    //     // Find the OrderDetail by its ID
    //     $orderDetail = OrderDetail::find($id);
        
    //     // Check if the OrderDetail exists
    //     if (!$orderDetail) {
    //         return $this->error('OrderDetail not found for the given id', 400);
    //     }

    //     // Delete the OrderDetail
    //     $orderDetail->delete();

    //     // Return a success response
    //     return $this->success('Success', 200);
    // }
    /* ========================= OrderCode delete end ========================= */


    public function searchClients(Request $request)
    {
        // $request = $request->validate([
        //     'from_id'=>'required',
        //     'to_id'=>'required',
        //     'date'=>'required'
        // ]);


        $came_date=Carbon::parse($request->start_date)->format('Y-m-d');
        $tomorrow=Carbon::parse($came_date)->addDays(1)->format('Y-m-d');

        $list=[]; 
        // $order_details = DB::table('yy_order_details')
            // ->where('order_id', null)
            // ->where('from_id', $request->from_id)
            // ->where('to_id', $request->to_id)
            // ->select(DB::raw('DATE(start_date) as start_date'),'client_id','seats_count')
            // ->where('start_date','>=',$came_date)
            // ->where('start_date','<',$tomorrow)
            // ->get();

        $order_details = OrderDetail::select(DB::raw('DATE(start_date) as start_date'),'client_id','seats_count')
            ->where('order_id', null)
            ->where('from_id', $request->from_id)
            ->where('to_id', $request->to_id)
            ->where('start_date','>=',$came_date)
            ->where('start_date','<',$tomorrow)
            ->get();
            
        $total_trips = DB::table('yy_order_details as dt1')
            ->leftJoin('yy_orders as dt2', 'dt2.id', '=', 'dt1.order_id')
            ->where('dt1.client_id', auth()->id())
            ->where('dt2.status_id', Constants::COMPLETED)
            ->count();

        foreach ($order_details as $order_detail) {
            $odFrom = $order_detail->from;
            $odTo = $order_detail->to;
            $personalInfo = PersonalInfo::where('id',User::where('id', $order_detail->client_id)->first()->personal_info_id)->first();

            // $distance = $this->getDistanceAndKm((($odFrom) ? $odFrom->lng : ''), (($odFrom) ? $odFrom->lat : ''), (($odTo) ? $odTo->lng : ''), (($odTo) ? $odTo->lat : ''));
            $distance = ['km' => '0', 'time' => '0', 'distance_value' => 0];

            $data = [
                'id' => $order_detail->id ,
                'start_date' => date('d.m.Y H:i', strtotime($order_detail->start_date)),
                'isYour' => ($order_detail->client_id == auth()->id()) ? true : false,
                'avatar' => $personalInfo->avatar,
                'rating' => 4,
                'name' => $personalInfo->first_name .' '. $personalInfo->last_name .' '. $personalInfo->middle_name,
                'total_trips' => $total_trips,
                'count_pleace' => $order_detail->seats_count,

                'from' => ($odFrom) ? $odFrom->name : '',
                'from_lng' => ($odFrom) ? $odFrom->lng : '',
                'from_lat' => ($odFrom) ? $odFrom->lat : '',
                'to' => ($odTo) ? $odTo->name : '',
                'to_lng' => ($odTo) ? $odTo->lng : '',
                'to_lat' => ($odTo) ? $odTo->lat : '',

                'distance_km' => $distance['km'],
                'distance' => $distance['time'],
                'arrived_date' => date('d.m.Y H:i', strtotime($order_detail->start_date. ' +' . $distance['time'])),
            ];
            
            array_push($list,$data);
        }       

        return $this->success('success', 200, $list);

        // return response()->json([
        //     'data' => $list,
        //     'status' => true,
        //     'message' => 'success',

        // ], 200);

    }

    public function searchHistory()
    {
        $model = DB::table('yy_order_details as yyo')
            ->leftJoin('yy_cities as yyF', 'yyF.id', '=', 'yyo.from_id')
            ->leftJoin('yy_cities as yyT', 'yyT.id', '=', 'yyo.to_id')
            ->where('yyo.client_id', auth()->id())
            ->select('yyo.id', 'yyF.name as from', 'yyF.id as from_id', 'yyF.lng as from_lng', 'yyF.lat as from_lat', 'yyT.name as to', 'yyT.id as to_id', 'yyT.lng as to_lng', 'yyT.lat as to_lat')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        return $this->success('success', 200, $model);
    }

    public function history(Request $request)
    {
        if ($request->page)
            $page = $request->page;
        else
            return $this->error('page parameter is missing', 400);

        $offers = $this->getClientOffers();

        if (!$offers)
            return $this->success('success', 200, []);
        
        $data = $this->makeDataToArray($offers);
            
        return $this->success('success', 201, $data);
    }

    private function getClientOffers()
    {
        $offers = DB::table('yy_order_details as od')
            ->join('yy_offers as of', 'od.id', '=', 'of.order_detail_id')
            ->leftJoin('yy_orders as or', 'or.id', '=', 'of.order_id')
            ->leftJoin('yy_cities as from', 'from.id', '=', 'or.from_id')
            ->leftJoin('yy_cities as to', 'to.id', '=', 'or.to_id')
            ->leftJoin('yy_order_details as orod', 'orod.order_id', '=', 'or.id')
            ->leftJoin('yy_users as usC', 'usC.id', '=', 'orod.client_id')
            ->leftJoin('yy_personal_infos as piC', 'piC.id', '=', 'usC.personal_info_id')
            ->leftJoin('yy_users as us', 'us.id', '=', 'or.driver_id')
            ->leftJoin('yy_drivers as dr', 'dr.user_id', '=', 'us.id')
            ->leftJoin('yy_personal_infos as pi', 'pi.id', '=', 'us.personal_info_id')
            ->leftJoin('yy_cars as car', 'car.id', '=', 'or.car_id')
            ->leftJoin('yy_car_lists as cl', 'cl.id', '=', 'car.car_list_id')
            ->leftJoin('yy_color_lists as col', 'col.id', '=', 'car.color_list_id')
            ->leftJoin('yy_class_lists as class', 'class.id', '=', 'car.class_list_id')
            ->leftJoin('yy_statuses as status', 'status.id', '=', 'or.status_id')
            ->where('od.client_id',auth()->id())
            // ->where('of.status', )
            ->select('or.id', 'od.id as order_detail_id', 'or.start_date', 'or.price', 'of.status as offer_status', 'or.seats as seats_count', 'or.booking_place as booking_count', 'usC.id as client_id', 'piC.last_name as c_last_name', 'piC.first_name as c_first_name', 'piC.middle_name as c_middle_name', 'piC.phone_number as c_phone_number', 'piC.avatar as c_avatar', 'usC.rating as c_rating', 'pi.last_name', 'pi.first_name', 'pi.middle_name', 'pi.phone_number', 'pi.avatar as dImg', 'us.rating', 'car.id as car_id', 'cl.name as car_name', 'col.name as color_name', 'col.code as color_code', 'car.production_date', 'class.name as class_name', 'car.reg_certificate', 'car.reg_certificate_image', 'car.images as car_images', 'or.options', 'from.name as from', 'from.lng as from_lng', 'from.lat as from_lat', 'to.name as to', 'to.lng as to_lng', 'to.lat as to_lat', 'status.name as status_name', 'us.id as driver_id', 'dr.id as dr_id', 'dr.doc_status as driver_doc_status')
            ->orderBy('od.id', 'desc')
            ->get();

        return $offers;
    }

    private function makeDataToArray($offers)
    {
        $arr = [];
        $n = -1;
        $c = -1;
        $inArray = [];
        $clientIdArr = [];
        foreach ($offers as $offer) {

            if (!in_array($offer->client_id, $clientIdArr, true)) {
                $clientIdArr[] = $offer->client_id;
                $c++;
            }

            if (!in_array($offer->id, $inArray, true)) {
                $inArray[] = $offer->id;
                $n++;
            }

            $arrImgs = [];
            if ($offer->car_images != null) {
                $imgs = json_decode(json_decode($offer->car_images));

                foreach ($imgs as $img) {
                    $arrImgs[] = asset('storage/cars/' . $img);
                }
            }

            $arr[$n]['id'] = $offer->id;
            $arr[$n]['order_detail_id'] = $offer->order_detail_id;
            $arr[$n]['start_date'] = date('d.m.Y H:i', strtotime($offer->start_date));
            $arr[$n]['price'] = (double)$offer->price;
            $arr[$n]['offer'] = $offer->offer_status;
            $arr[$n]['seats_count'] = $offer->seats_count;
            $arr[$n]['booking_count'] = $offer->booking_count ?? 0;
            $arr[$n]['is_full'] = ($offer->seats_count == $offer->booking_count) ? true : false;
            $arr[$n]['clients_list'][$c]['full_name'] = $offer->c_last_name . ' ' . $offer->c_first_name . ' ' . $offer->c_middle_name;
            $arr[$n]['clients_list'][$c]['phone_number'] = $offer->c_phone_number;
            $arr[$n]['clients_list'][$c]['img'] = ($offer->c_avatar) ? asset('storage/avatar/' . $offer->c_avatar) : '';
            $arr[$n]['clients_list'][$c]['rating'] = $offer->c_rating;
            $arr[$n]['driver'] = [
                'id' => $offer->driver_id,
                'full_name' => $offer->last_name . ' ' . $offer->first_name . ' ' . $offer->middle_name,
                'phone_number' => $offer->phone_number,
                'img' => ($offer->dImg) ? asset('storage/avatar/' . $offer->dImg) : '',
                'rating' => $offer->rating,
                'doc_status' => (int)$offer->driver_doc_status
            ];
            $arr[$n]['car'] = [
                'id' => $offer->car_id,
                'name' => $offer->car_name,
                'color' => [
                    'name' => $offer->color_name,
                    'code' => $offer->color_code
                ],
                'production_date' => date('Y', strtotime($offer->production_date)),
                'class' => $offer->class_name,
                'reg_certificate' => $offer->reg_certificate,
                'reg_certificate_img' => ($offer->reg_certificate_image) ? asset('storage/cars/' . $offer->reg_certificate_image) : '',
                'images' => $arrImgs,
            ];
            $arr[$n]['options'] = json_decode($offer->options);
            $arr[$n]['from'] = $offer->from;
            $arr[$n]['from_lng'] = $offer->from_lng;
            $arr[$n]['from_lat'] = $offer->from_lat;
            $arr[$n]['to'] = $offer->to;
            $arr[$n]['to_lng'] = $offer->to_lng;
            $arr[$n]['to_lat'] = $offer->to_lat;

            $distance = $this->getDistanceAndKm($offer->from_lng, $offer->from_lat, $offer->to_lng, $offer->to_lat);

            $arr[$n]['distance_km'] = $distance['km'];
            $arr[$n]['distance'] = $distance['time'];
            $arr[$n]['arrived_date'] = date('d.m.Y H:i', strtotime($offer->start_date. ' +' . $distance['time']));
            
            $arr[$n]['status'] = $offer->status_name;
        }

        return $arr;
    }


    /**
     * Display the specified resource.
     */
    // public function show(OrderDetails $orderDetails)
    // {
    //     //
    // }

    /**
     * Show the form for editing the specified resource.
     */
    // public function edit(OrderDetails $orderDetails)
    // {
    //     //
    // }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, OrderDetails $orderDetails)
    // {
    //     //
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(OrderDetails $orderDetails)
    // {
    //     //
    // }
}
