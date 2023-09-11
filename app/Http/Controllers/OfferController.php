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
        $language = $request->header('language');
        // dd($language);
        if(!isset($request->order_id)){
            return $this->error(translate_api('order id is not entered', $language), 400);
        }
        if(!isset($request->order_detail_id)){
            return $this->error(translate_api('order detail id is not entered', $language), 400);
        }
        // if(!isset($request->price)){
        //     return $this->error(translate_api('price is not entered', $language), 400);
        // }
        // if(!isset($request->create_type)){
        //     return $this->error(translate_api('price is not entered', $language), 400);
        // }
        // if(!isset($request->comment)){
        //     return $this->error(translate_api('comment is not entered', $language), 400);
        // }
        $field = $request->validate([
            'order_id'=>'required|integer',
            'order_detail_id'=>'required|integer',
            'seats'=>'required|integer',
            // 'comment'=>'nullable|string',
        ]); 
        $order_detail = OrderDetail::find($field['order_detail_id']);
        $order = Order::find($field['order_id']);
        if(!isset($order_detail)){
            return $this->error(translate_api('Order detail not found', $language), 400);
        }

        if(!isset($order)){
            return $this->error(translate_api('Order not found', $language), 400);
        }

        // $old_offer=
        $seats_count = ($order->seats) - ($order->booking_place);
        if ($old_offer = Offer::where('order_id',$order->id)->where('order_detail_id',$order_detail->id)->first()) {
            // dd($old_offer);
            if ($old_offer->status==Constants::NEW) 
            {
                // dd('dfawdawdaw');
                return $this->success(translate_api('Your old offer was not accepted please wait', $language), 200);
            }
            elseif($old_offer->status==Constants::CANCEL && $old_offer->cancel_type==Constants::ORDER_DETAIL)
            {
                return $this->success(translate_api('Sorry, but you cannot make another offer for this order', $language), 200);
            }
            else 
            {
                if ($order->status_id==Constants::ORDERED) {
                    
                    if ($seats_count==0) {
                        return $this->success(translate_api('Sorry, seats are full', $language), 200);
                    }
                    if ($seats_count >= $field['seats'] ) {
                        $offer = new Offer();
                        $id=auth()->id();
                        $create_type = ($id==$order_detail->client_id) ? 0 : 1;
                        $offer->order_id = $order->id;
                        $offer->order_detail_id = $order_detail->id;
                        $offer->seats = $field['seats'];
                        $offer->create_type = $create_type;
                        $offer->status = Constants::NEW;
                        $offer->save();

                        $device = ($order->driver) ? json_decode($order->driver->device_type) : [];
                        $title = translate_api('You have a new offer', $language);
                        $message = translate_api('Route', $language) . ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
                        $user_id = ($order->driver) ? $order->driver->id : 0;

                        $this->sendNotification($device, $user_id, "Offer", $title, $message);

                        return $this->success(translate_api('Offer created', $language), 201);

                        // $offer->comment = $field['comment'] ?? '';
                    }
                    else {

                        return $this->success(translate_api('sorry we only have '. $seats_count .' spaces available', $language), 200);
                    }

                }
                
            }
        }
        if ($seats_count >= $field['seats']) {
            $offer = new Offer();
            $id=auth()->id();
            $create_type = ($id==$order_detail->client_id) ? 0 : 1;
            $offer->order_id = $order->id;
            $offer->order_detail_id = $order_detail->id;
            $offer->seats = $field['seats'];
            $offer->create_type = $create_type;
            $offer->status = Constants::NEW;
        }
        else {
            return $this->success(translate_api('sorry we only have '. $order->seats .' spaces available', $language), 200);
        }
        $offer->save();
        // if ($offer->save()) {
            $device = ($order->driver) ? json_decode($order->driver->device_type) : [];
            $title = translate_api('You have a new offer', $language);
            $message = translate_api('Route', $language) . ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
            $user_id = ($order->driver) ? $order->driver->id : 0;

            $this->sendNotification($device, $user_id, "Offer", $title, $message);
        // }

        return $this->success(translate_api('Offer created', $language), 201);
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
        ->where('dt1.status','!=',Constants::CANCEL)
        ->orWhere('dt2.client_id', auth()->id())
        ->select('dt1.id as offer_id','dt1.order_id', 'dt1.order_detail_id','dt3.from_id' ,'dt3.to_id',DB::raw('DATE(dt2.start_date) as start_date'),'dt2.client_id as client_id','dt2.seats_count as seats_count','dt4.name as status','dt5.rating','dt6.first_name','dt6.middle_name','dt6.last_name','dt6.avatar')
        ->get();
        // ->toArray();
        dd($offers);


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

            if ($offer->client_id==auth()->id()) {
                $is_your=true;
            }else {
                $is_your=false;
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
                'avatar'=>$offer->avatar,
                'seats_count'=>$offer->seats_count,
                'is_your'=>$is_your
            ];
            array_push($data , $list);
        }
        // dd($data);
        // $data=$data->toArray();
        // foreach ($offers as $key => $offer) {
        //     $data
            

        // }


        return $this->success('Success', 200, $data);

        // if($data){
        //     return $this->success('Success', 200, $data);
        // }else{
        //     return $this->error(translate_api('Offer not found', $language), 400);
        // }
    }


}
