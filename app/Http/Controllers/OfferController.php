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
        if(!isset($request->comment)){
            return $this->error(translate_api('comment is not entered', $language), 400);
        }
        $field = $request->validate([
            'order_id'=>'required|integer',
            'order_detail_id'=>'required|integer',
            'price'=>'required|integer',
            'comment'=>'required|string',
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
        $create_type=$id = ($order_detail->client_id) ? 0 : 1;
        // dd($create_type);
        // $offer->driver_id = $order->driver_id;
        // $offer->client_id = $order_detail->client_id;
        $offer->order_id = $order->id;
        $offer->order_detail_id = $order_detail->id;
        $offer->price = $field['price'];
        $offer->create_type = $create_type;
        $offer->status = Constants::NEW;
        $offer->comment = $field['comment'];
        
        
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
        // $offer = Offer::select('order_id',
        //     'order_detail_id', 'price', 'status', 'comment')
        //     ->where('')
        //     ->get()
        //     ->toArray();
        // dd(auth()->id());
        $offer = DB::table('yy_offers as dt1')
        ->Leftjoin('yy_order_details as dt2', 'dt2.id', '=', 'dt1.order_detail_id')
        ->Leftjoin('yy_orders as dt3', 'dt3.id', '=', 'dt1.order_id')
        ->Leftjoin('yy_statuses as dt4', 'dt4.type_id', '=', 'dt1.status')
        ->where('dt3.driver_id', auth()->id())
        ->orWhere('dt2.client_id', auth()->id())
        ->select('dt1.id as offer_id','dt1.order_id', 'dt1.order_detail_id','dt4.name as status',)
        ->get()
        ->toArray();
        // dd($offer);

        if(count($offer)>0){
            return $this->success('Success', 200, $offer);
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
