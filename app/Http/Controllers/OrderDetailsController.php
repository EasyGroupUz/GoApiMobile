<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
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



       
       

        // return '';
       

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
