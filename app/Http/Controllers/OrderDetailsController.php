<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use App\Models\PersonalInfo;
use App\Models\User;
use Illuminate\Http\Request;
use App\Constants;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



class OrderDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $language = $request->header('language');

        $request = $request->validate([
           'from_id'=>'required',
           'to_id'=>'required',
           'seats_type'=>'required',
           'seats_count'=>'required',
           'date'=>'required'
       ]);
        //    dd($request);
        // $language=$request->header('language');
        // dd($language);

        $order_detail= OrderDetail::create([
            'client_id'=>auth()->id(),
            'status_id'=>Constants::ACTIVE,
            'from_id'=>$request['from_id'],
            'to_id'=>$request['to_id'],
            'seats_type'=>$request['seats_type'],
            'seats_count'=>$request['seats_count'],
            'start_date'=>date('Y-m-d', strtotime($request['date']))
        ]);
        // dd($order_detail);

        $timezone = 'Asia/Tashkent';
        $date_time = Carbon::now($timezone)->format('Y-m-d H:i:s');
        $date = Carbon::now($timezone)->format('Y-m-d');
        // $date=Carbon::parse($request->date)->format('Y-m-d');
        $three_day_after=Carbon::parse($date)->addDays(3)->format('Y-m-d');
        
        $came_date=date('Y-m-d', strtotime($request['date']));
        $tomorrow=Carbon::parse($came_date)->addDays(1)->format('Y-m-d');
        $came_date_time=date('Y-m-d H:i:s', strtotime($request['date']));
        $startDate=Carbon::parse($came_date)->subDays(3)->format('Y-m-d');
        $endDate=Carbon::parse($came_date)->addDays(3)->format('Y-m-d');

        if ($came_date < $three_day_after) {
            $startDate=$date;
            $endDate=Carbon::parse($startDate)->addDays(6)->format('Y-m-d');
        }
        
       
        if ( $came_date >= $date) {


            $from_to_name=table_translate($order_detail,'city',$language);


            $order_detail = [
                'id'=>$order_detail->id,
                'seats_count'=>$order_detail->seats_count,
                'start_date'=>$order_detail->start_date,
                'from_id'=>$order_detail->from_id,
                'to_id'=>$order_detail->to_id,
                'from_name'=>$from_to_name['from_name'],
                'to_name'=>$from_to_name['to_name'],
                'long'=>null,
                'lat'=>null
            ];

            if ($order_information = DB::table('yy_orders')
                ->where('status_id', Constants::ORDERED)
                ->where('from_id', $request['from_id'])
                ->where('to_id', $request['to_id'])
                ->select(DB::raw('DATE(start_date) as start_date'),'driver_id','price','booking_place')
                ->where('start_date','>=',$came_date)
                ->where('start_date','<',$tomorrow)
                ->where('status_id', Constants::ORDERED)
                ->get() == []) {
                //   dd('fsef');
                    $list=[]; 

                    $total_trips=DB::table('yy_orders')->where('driver_id',auth()->id())
                        ->where('status_id', Constants::COMPLETED)
                        ->count();
        
                    foreach ($order_information as $order) {

                        $personalInfo=PersonalInfo::where('id',User::where('id',$order->driver_id)->first()->personal_info_id)->first();
                        $data=[
                            'start_date'=>$order->start_date ,
                            'avatar'=>$personalInfo->avatar,
                            'rating'=>4,
                            'price'=>$order->price,
                            'name'=>$personalInfo->first_name .' '. $personalInfo->last_name .' '. $personalInfo->middle_name,
                            'total_trips'=>$total_trips,
                            'count_pleace'=>$order->booking_place,
                        ];
                        array_push($list,$data);
                    }
                                    
            } else {
                $order_dates = DB::table('yy_orders')
                ->where('status_id', Constants::ORDERED)
                ->where('from_id', $request['from_id'])
                ->where('to_id', $request['to_id'])
                ->where('start_date', '>=', $date)
                ->select(DB::raw('DATE(start_date) as start_date'))
                ->whereBetween('start_date', [$startDate, $endDate])
                ->orderBy('start_date', 'asc')
                ->distinct()
                ->take(5)
                ->get();

                $list=[];
                foreach ($order_dates as $key => $value) {
                    $list[$key]=$value->start_date;
                }
               

            }
            $message=translate_api('success',$language);
            return response()->json([
                'data' => $list,
                'order_detail'=>$order_detail,
                'status' => true,
                'message' => $message,
            ], 200);
        }
        else {
            $message=translate_api('Sorry, you must enter a date greater than or equal to today',$language);

            return response()->json([
                'status' => false,
                'message' => $message,
                // 'orders' => $orders,

            ], 500);
        }
    }

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
        
        //    dd($request);
        // $language=$request->header('language');
        // dd($language);

        // $order_detail= OrderDetail::create([
        //     'client_id'=>auth()->id(),
        //     'status_id'=>Constants::ACTIVE,
        //     'from_id'=>$request['from_id'],
        //     'to_id'=>$request['to_id'],
        //     'seats_type'=>$request['seats_type'],
        //     'seats_count'=>$request['seats_count'],
        //     'start_date'=>date('Y-m-d', strtotime($request['date']))
        // ]);
        // // dd($order_detail);

        // $timezone = 'Asia/Tashkent';
        // $date_time = Carbon::now($timezone)->format('Y-m-d H:i:s');
        // $date = Carbon::now($timezone)->format('Y-m-d');
        // // $date=Carbon::parse($request->date)->format('Y-m-d');
        // $three_day_after=Carbon::parse($date)->addDays(3)->format('Y-m-d');
        
        // $came_date=date('Y-m-d', strtotime($request['date']));
        // $tomorrow=Carbon::parse($came_date)->addDays(1)->format('Y-m-d');
        // $came_date_time=date('Y-m-d H:i:s', strtotime($request['date']));
        // $startDate=Carbon::parse($came_date)->subDays(3)->format('Y-m-d');
        // $endDate=Carbon::parse($came_date)->addDays(3)->format('Y-m-d');

        // if ($came_date < $three_day_after) {
        //     $startDate=$date;
        //     $endDate=Carbon::parse($startDate)->addDays(6)->format('Y-m-d');
        // }
        
       
        // if ( $came_date >= $date) {


        //     $from_to_name=table_translate($order_detail,'city',$language);


        //     $order_detail = [
        //         'seats_count'=>$order_detail->seats_count,
        //         'start_date'=>$order_detail->start_date,
        //         'from_id'=>$order_detail->from_id,
        //         'to_id'=>$order_detail->to_id,
        //         'from_name'=>$from_to_name['from_name'],
        //         'to_name'=>$from_to_name['to_name'],
        //         'long'=>null,
        //         'lat'=>null
        //     ];

        //     if ($order_information = DB::table('yy_orders')
        //         ->where('status_id', Constants::ORDERED)
        //         ->where('from_id', $request['from_id'])
        //         ->where('to_id', $request['to_id'])
        //         ->select(DB::raw('DATE(start_date) as start_date'),'driver_id','price','booking_place')
        //         ->where('start_date','>=',$came_date)
        //         ->where('start_date','<',$tomorrow)
        //         ->where('status_id', Constants::ORDERED)
        //         ->get() == []) {
        //         //   dd('fsef');
        //             $list=[]; 

        //             $total_trips=DB::table('yy_orders')->where('driver_id',auth()->id())
        //                 ->where('status_id', Constants::COMPLETED)
        //                 ->count();
        
        //             foreach ($order_information as $order) {

        //                 $personalInfo=PersonalInfo::where('id',User::where('id',$order->driver_id)->first()->personal_info_id)->first();
        //                 $data=[
        //                     'start_date'=>$order->start_date ,
        //                     'avatar'=>$personalInfo->avatar,
        //                     'rating'=>4,
        //                     'price'=>$order->price,
        //                     'name'=>$personalInfo->first_name .' '. $personalInfo->last_name .' '. $personalInfo->middle_name,
        //                     'total_trips'=>$total_trips,
        //                     'count_pleace'=>$order->booking_place,
        //                 ];
        //                 array_push($list,$data);
        //             }
                                    
        //     } else {
        //         $order_dates = DB::table('yy_orders')
        //         ->where('status_id', Constants::ORDERED)
        //         ->where('from_id', $request['from_id'])
        //         ->where('to_id', $request['to_id'])
        //         ->where('start_date', '>=', $date)
        //         ->select(DB::raw('DATE(start_date) as start_date'))
        //         ->whereBetween('start_date', [$startDate, $endDate])
        //         ->orderBy('start_date', 'asc')
        //         ->distinct()
        //         ->take(5)
        //         ->get();

        //         $list=[];
        //         foreach ($order_dates as $key => $value) {
        //             $list[$key]=$value->start_date;
        //         }
               

        //     }
        //     $message=translate_api('success',$language);
        //     return response()->json([
        //         'data' => $list,
        //         'order_detail'=>$order_detail,
        //         'status' => true,
        //         'message' => $message,
        //     ], 200);
        // }
        // else {
        //     $message=translate_api('Sorry, you must enter a date greater than or equal to today',$language);

        //     return response()->json([
        //         'status' => false,
        //         'message' => $message,
        //         // 'orders' => $orders,

        //     ], 500);
        // }
    }

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

        return response()->json([
            'data' => $list,
            'status' => true,
            'message' => 'success',

        ], 200);

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


    /**
     * Display the specified resource.
     */
    public function show(OrderDetails $orderDetails)
    {
        //
    }

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
    public function update(Request $request, OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderDetails $orderDetails)
    {
        //
    }
}
