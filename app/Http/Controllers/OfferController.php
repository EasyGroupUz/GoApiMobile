<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Driver;
use App\Models\OrderDetail;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;

class OfferController extends Controller
{

    public function postOffer(Request $request){
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
            $response = [
                'status'=>false,
                'message'=>'Order detail not found'
            ];
            return response()->json($response);
        }
        if(!isset($order)){
            $response = [
                'status'=>false,
                'message'=>'Order not found'
            ];
            return response()->json($response);
        }
        $offer->driver_id = $order->driver_id;
        $offer->client_id = $order_detail->client_id;
        $offer->order_id = $order->id;
        $offer->order_detail_id = $order_detail->id;
        $offer->price = $field['price'];
        $offer->status = 1;
        $offer->comment = $field['comment'];
        $offer->save();
        $response = [
          'status'=>true,
          'message'=>'Success'
        ];
        return response()->json($response);
    }

    public function getOffer(){
        $offer = Offer::select('driver_id', 'client_id', 'order_id',
            'order_detail_id', 'price', 'status', 'comment')->get();
        $response = [
            'data'=>$offer,
            'status'=>true,
            'message'=>'Success'
        ];
        return response()->json($response);
    }

    public function destroy($id)
    {
        Offer::destroy($id);
        return redirect(route('offer.index'));
    }
}
