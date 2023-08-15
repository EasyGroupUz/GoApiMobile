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

    public function getOffer(Request $request){
        $language = $request->header('language');
        $offer = Offer::select('driver_id', 'client_id', 'order_id',
            'order_detail_id', 'price', 'status', 'comment')->get()->toArray();
        if(count($offer)>0){
            return $this->success('Success', 200, $offer);
        }else{
            return $this->error(translate_api('Offer not found', $language), 400);
        }
    }

    public function destroy(Request $request)
    {
        $language = $request->header('language');
        $offer = Offer::find($request->id);
        if(isset($offer)){
            $offer->delete();
            return $this->success('Success', 200);
        }else{
            return $this->error(translate_api('Offer not found', $language), 400);
        }
    }
}
