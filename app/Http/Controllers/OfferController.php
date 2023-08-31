<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Driver;
use App\Models\OrderDetail;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Constants;


class OfferController extends Controller
{

    public function postOffer(Request $request){
        // dd($request->all());
        $language = $request->header('language');
        if(!isset($request->order_id)){
            return $this->error(translate_api('order id is not entered', $language), 400);
        }
        if(!isset($request->order_detail_id)){
            return $this->error(translate_api('order detail id is not entered', $language), 400);
        }
        if(!isset($request->price)){
            return $this->error(translate_api('price is not entered', $language), 400);
        }
        // if(!isset($request->create_type)){
        //     return $this->error(translate_api('price is not entered', $language), 400);
        // }
        // if(!isset($request->comment)){
        //     return $this->error(translate_api('comment is not entered', $language), 400);
        // }
        $field = $request->validate([
            'order_id'=>'required|integer',
            'order_detail_id'=>'required|integer',
            'price'=>'required|integer',
            'comment'=>'nullable|string',
        ]);
        $offer = new Offer();
        $order_detail = OrderDetail::find($field['order_detail_id']);
        $order = Order::find($field['order_id']);
        if(!isset($order_detail)){
            return $this->error(translate_api('Order detail not found', $language), 400);
        }
        if(!isset($order)){
            return $this->error(translate_api('Order not found', $language), 400);
        }
        $id=auth()->id();
        $create_type = ($order_detail->client_id) ? 0 : 1;
        // dd($create_type);
        // $offer->driver_id = $order->driver_id;
        // $offer->client_id = $order_detail->client_id;
        $offer->order_id = $order->id;
        $offer->order_detail_id = $order_detail->id;
        $offer->price = $field['price'];
        $offer->create_type = $create_type;
        $offer->status = Constants::NEW;
        $offer->comment = $field['comment'] ?? '';
        
        
        if ($offer->save()) {
            // dd($offer);    
        }
        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'Action completed successfully',
        //     'data' => $offer,
        // ], 200);

        // dd($offer);
        return $this->success('Success', 201);
    }

    public function getOffer(Request $request){
        $language = $request->header('language');
        $offers = DB::table('yy_offers as dt1')
        ->Leftjoin('yy_order_details as dt2', 'dt2.id', '=', 'dt1.order_detail_id')
        ->Leftjoin('yy_orders as dt3', 'dt3.id', '=', 'dt1.order_id')
        ->Leftjoin('yy_statuses as dt4', 'dt4.type_id', '=', 'dt1.status')
        ->Leftjoin('yy_users as dt5', 'dt5.id', '=', 'dt2.client_id')
        ->Leftjoin('yy_personal_infos as dt6', 'dt6.id', '=', 'dt5.personal_info_id')
        ->where('dt3.driver_id', auth()->id())
        ->orWhere('dt2.client_id', auth()->id())
        ->select('dt1.id as offer_id','dt1.order_id', 'dt1.order_detail_id','dt2.from_id' ,'dt2.to_id',DB::raw('DATE(dt2.start_date) as start_date'),'dt4.name as status','dt5.rating','dt6.first_name','dt6.middle_name','dt6.last_name','dt6.avatar')
        ->get();
        // ->toArray();
        // dd($offer);

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
                'avatar'=>$offer->avatar
            ];
            array_push($data , $list);
        }
        // dd($data);
        // $data=$data->toArray();
        // foreach ($offers as $key => $offer) {
        //     $data
            

        // }


        

        if($data){
            return $this->success('Success', 200, $data);
        }else{
            return $this->error(translate_api('Offer not found', $language), 400);
        }
    }

    // public function destroy(Request $request)
    // {
    //     $language = $request->header('language');
    //     $offer = Offer::find($request->id);
    //     if(isset($offer)){
    //         $offer->delete();
    //         return $this->success('Success', 200);
    //     }else{
    //         return $this->error(translate_api('Offer not found', $language), 400);
    //     }
    // }
}
