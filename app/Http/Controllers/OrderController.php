<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Status;
use App\Models\CarList;
use App\Models\Country;
use App\Models\City;
use App\Models\Driver;
use App\Models\PersonalInfo;
use App\Models\User;
use Carbon\Carbon;
use App\Constants;
use Illuminate\Support\Facades\DB;



use App\Http\Requests\OrderRequest;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $model = Order::all();

        return view('order.index', [
            'model' => $model
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = Order::find($id);

        return view('order.show', [
            'order' => $order,
            'offers' => $order->offers,
            'commentScores' => $order->commentScores
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Order::findOrfail($id);
        $modelStatus = Status::all();
        $modelCarsList = CarList::all();
        $modelCity = City::where(['country_id' => 234, 'type' => 'city'])->get();

        return view('order.edit', [
            'model' => $model,
            'modelStatus' => $modelStatus,
            'modelCarsList' => $modelCarsList,
            'modelCity' => $modelCity,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OrderRequest $request, string $id)
    {
        $data = $request->validated();

        $order = Order::findOrFail($id);
        $order->update($request->all());
        if (isset($data['seats']))
            $order->seats = json_encode($data['seats']);
            
        $order->save();
        
        return redirect()->route('order.index'); // ->with('updated', translate('Data successfully updated'));
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        
        return redirect()->route('order.index'); // ->with('updated', translate('Data successfully updated'));
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
                ->where('from_id', $request->to_id)
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
                    // dd();
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
        return response()->json([
            'status' => true,
            'message' => 'success',
            'data' => $list,

        ], 200);

    }

    public function orderShow(Request $request){
        
        // $order_id=$request->order_id;
         $order_id=2;
         $order=Orders::where('id',$order_id)->first();
        //  dd($order);
         $driver=Driver::where('id',$order->driver_id)->first();
         $car_list=CarList::where('id',$order->cars_list_id)->first();
        //  dd($car_list);
         $car=Cars::where('car_list_id',$car_list->id)->first();
         $driver_information=[
            'name'=>$driver->first_name,
            'avatar'=>$driver->avatar
         ];
         $car_information=[
            'name'=>$car_list->name,
            'avater'=>$car->images

             
         ];
         $list=[
          'price'=>$order->price,
          'price_type'=>$order->price_type,
          'seats'=>$order->seats,
          'driver_information'=>$driver_information,
          'car_information'=>$car_information
         ];
        //  dd($list);

    }
}
