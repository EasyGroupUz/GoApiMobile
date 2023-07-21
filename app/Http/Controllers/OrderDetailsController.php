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
        $request = $request->validate([
           'from_id'=>'required',
           'to_id'=>'required',
           'seats_type'=>'required',
           'seats_count'=>'required',
           'date'=>'required'
       ]);
        //    dd($request);

        $order_details= OrderDetail::create([
            'client_id'=>auth()->id(),
            'status_id'=>Constants::ACTIVE,
            'from_id'=>$request['from_id'],
            'to_id'=>$request['to_id'],
            'seats_type'=>$request['seats_type'],
            'seats_count'=>$request['seats_count'],
            'start_date'=>date('Y-m-d', strtotime($request['date']))
        ]);
        

        $timezone = 'Asia/Tashkent';
        $date_time = Carbon::now($timezone)->format('Y-m-d H:i:s');
        $date = Carbon::now($timezone)->format('Y-m-d');
        $three_day_after=Carbon::parse($date)->addDays(3)->format('Y-m-d');

        $came_date=date('Y-m-d', strtotime($request['date']));
        $came_date_time=date('Y-m-d H:i:s', strtotime($request['date']));
        $startDate=Carbon::parse($came_date)->subDays(3)->format('Y-m-d');
        $endDate=Carbon::parse($came_date)->addDays(3)->format('Y-m-d');

        if ($came_date < $three_day_after) {
            $startDate=$date;
            $endDate=Carbon::parse($startDate)->addDays(6)->format('Y-m-d');
        }
        
        if ( $came_date >= $date) {
            $orders = DB::table('yy_orders')
            ->where('status_id', Constants::ORDERED)
            ->where('start_date', '>=', $date)
            ->select(DB::raw('DATE(start_date) as start_date'))
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orderBy('start_date', 'asc')
            ->distinct()
            ->take(5)
            ->get();

            $list=[];
            foreach ($orders as $key => $value) {
                // dd($value->start_date);
                $list[$key]=$value->start_date;
            }
            // dd($list);
            return response()->json([
                'status' => true,
                'message' => 'success',
                'list' => $list,

            ], 200);
           
        }
        else {
            return response()->json([
                'status' => false,
                'message' => 'error',
                // 'orders' => $orders,

            ], 200);
        }


       

    }

    public function searchClients(Request $request)
    {
        // $request = $request->validate([
        //     'from_id'=>'required',
        //     'to_id'=>'required',
        //     'date'=>'required'
        // ]);
        // dd($request->all());


            $date=Carbon::parse($request->date)->format('Y-m-d');
            $list=[]; 
                $order_details = DB::table('yy_order_details')
                ->where('order_id', null)
                ->where('from_id', $request->from_id)
                ->where('to_id', $request->to_id)
                ->select(DB::raw('DATE(start_date) as start_date'),'client_id','seats_count')
                ->where('start_date','=',$date)
                ->get();
                $total_trips = DB::table('yy_order_details as dt1')
                ->leftJoin('yy_orders as dt2', 'dt2.id', '=', 'dt1.order_id')
                ->where('dt1.client_id', auth()->id())
                ->where('dt2.status_id', Constants::COMPLETED)
                ->count();

                foreach ($order_details as $order_detail) {
                    $personalInfo=PersonalInfo::where('id',User::where('id',$order_detail->client_id)->first()->personal_info_id)->first();
                    $data=[
                        'start_date'=>$order_detail->start_date ,
                        'avatar'=>$personalInfo->avatar,
                        'rating'=>4,
                        'name'=>$personalInfo->first_name .' '. $personalInfo->last_name .' '. $personalInfo->middle_name,
                        'total_trips'=>$total_trips,
                        'count_pleace'=>$order_detail->seats_count,
                    ];
                    array_push($list,$data);
                }       
        return response()->json([
            'status' => true,
            'message' => 'success',
            'data' => $list,

        ], 200);

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
    public function edit(OrderDetails $orderDetails)
    {
        //
    }

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
