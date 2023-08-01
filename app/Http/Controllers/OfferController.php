<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Driver;
use App\Models\Client;
use Illuminate\Http\Request;

class OfferController extends Controller
{

    public function postOffer(Request $request){
        $field = $request->validate([
            'driver_id'=>'required|integer',
            'client_id'=>'required|integer',
            'order_id'=>'required|integer',
            'order_detail_id'=>'required|integer',
            'price'=>'required|integer',
            'comment'=>'required|string',
        ]);
        $offer = new Offer();
        $offer->driver_id = $field['driver_id'];
        $offer->client_id = $field['client_id'];
        $offer->order_id = $field['order_id'];
        $offer->order_detail_id = $field['order_detail_id'];
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
