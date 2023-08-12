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
            return $this->error('Order detail not found', 400);
        }
        if(!isset($order)){
            return $this->error('Order not found', 400);
        }
        $offer->driver_id = $order->driver_id;
        $offer->client_id = $order_detail->client_id;
        $offer->order_id = $order->id;
        $offer->order_detail_id = $order_detail->id;
        $offer->price = $field['price'];
        $offer->status = 1;
        $offer->comment = $field['comment'];
        $offer->save();
        return $this->success('Success', 201);
    }

    public function getOffer(){
        $offer = Offer::select('driver_id', 'client_id', 'order_id',
            'order_detail_id', 'price', 'status', 'comment')->get()->toArray();
        if(count($offer)>0){
            return $this->success('Success', 200, $offer);
        }else{
            return $this->error('Offer not found', 400);
        }
    }

    public function destroy(Request $request)
    {
        $offer = Offer::find($request->id);
        if(isset($offer)){
            $offer->delete();
            return $this->success('Success', 200);
        }else{
            return $this->error('Offer not found', 400);
        }
    }
}
