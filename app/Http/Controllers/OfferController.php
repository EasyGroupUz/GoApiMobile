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

    public function postOffer(Request $request)
    {
        $language = $request->header('language');

        if (!isset($request->order_id)) {
            return $this->error(translate_api('order id is not entered', $language), 400);
        }

        if (!isset($request->order_detail_id)) {
            return $this->error(translate_api('order detail id is not entered', $language), 400);
        }

        $field = $request->validate([
            'order_id'=>'required|integer',
            'order_detail_id'=>'required|integer',
            'seats'=>'required|integer',
        ]); 

        $order_detail = OrderDetail::find($field['order_detail_id']);
        $order = Order::find($field['order_id']);

        if (!isset($order_detail)) {
            return $this->error(translate_api('Order detail not found', $language), 400);
        }

        if (!isset($order)) {
            return $this->error(translate_api('Order not found', $language), 400);
        }

        $seats_count = ($order->seats) - ($order->booking_place);
        $old_offer = Offer::where('order_id',$order->id)->where('order_detail_id',$order_detail->id)->first();
        if ($old_offer) {
            if ($old_offer->status == Constants::NEW) {
                return $this->error(translate_api('Your old offer was not accepted please wait', $language), 400);
            } elseif ($old_offer->accepted == Constants::OFFER_ACCEPTED && $old_offer->status==Constants::CANCEL) {
                return $this->error(translate_api('Sorry, you cannot make another offer for this order', $language), 400);
            } else {
                if ($order->status_id == Constants::ORDERED) {
                    if ($seats_count == 0) {
                        return $this->error(translate_api('Sorry, seats are full', $language), 400);
                    }

                    if ($seats_count >= $field['seats'] ) {
                        if ($old_offer->accepted == Constants::NOT_ACCEPTED && $old_offer->status == Constants::CANCEL) {
                            $old_offer->update([
                                'status' => Constants::NEW,
                                'seats' => $field['seats'],
                                'cancel_type' => NULL,
                                'cancel_date' => NULL,
                                'create_type' => Constants::ORDER_DETAIL
                            ]);

                            $device = ($order->driver) ? json_decode($order->driver->device_id) : [];
                            $title = 'You have a new offer';
                            $message = ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
                            $user_id = ($order->driver) ? $order->driver->id : 0;
                            $entity_id = $order->id;
    
                            $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
                            return $this->success(translate_api('Offer updates', $language), 201);
                        }
                    } else {
                        return $this->error(translate_api('Sorry we only have', $language) ." ". $seats_count ." ". translate_api('Spaces available', $language), 400);
                    }
                } else {
                    return $this->error(translate_api('Sorry, this  order status is not Ordered', $language), 400);
                }
            }
        }

        if ($seats_count >= $field['seats']) {
            $offer = new Offer();
            // $id = auth()->id();
            // $create_type = ($id == $order_detail->client_id) ? 0 : 1;
            $offer->order_id = $order->id;
            $offer->order_detail_id = $order_detail->id;
            $offer->seats = $field['seats'];
            $offer->create_type = Constants::ORDER_DETAIL;
            $offer->status = Constants::NEW;
        }
        else {
            // return $this->success(translate_api('sorry we only have '. $order->seats .' spaces available', $language), 200);
            return $this->error(translate_api('Sorry we only have', $language) ." ".$seats_count ." ". translate_api('Spaces available', $language), 400);

        }
        $offer->save();
        // if ($offer->save()) {
            $device = ($order->driver) ? json_decode($order->driver->device_id) : [];
            $title = 'You have a new offer';
            $message = ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
            $user_id = ($order->driver) ? $order->driver->id : 0;
            $entity_id = $order->id;
    
            $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
        // }

        return $this->success(translate_api('Offer created', $language), 201);
    }

    public function storeByOrder(Request $request)
    {
        $language = $request->header('language');

        $field = $request->validate([
            'order_id' => 'required|integer',
            'order_detail_id' => 'required|integer',
        ]); 

        $order_detail = OrderDetail::find($field['order_detail_id']);
        if (!isset($order_detail)) {
            return $this->error(translate_api('Order detail not found', $language), 400);
        }
        
        $order = Order::find($field['order_id']);
        if (!isset($order)) {
            return $this->error(translate_api('Order not found', $language), 400);
        }

        $old_offer = Offer::where('order_id', $order->id)->where('order_detail_id', $order_detail->id)->first();

        if ($old_offer) {
            if ($old_offer->status == Constants::NEW) {
                return $this->error(translate_api('Your old offer was not accepted please wait', $language), 400);
            } elseif ($old_offer->accepted == Constants::OFFER_ACCEPTED && $old_offer->status == Constants::CANCEL) {
                return $this->error(translate_api('Sorry, you cannot make another offer for this order', $language), 400);
            } else {
                if ($order->status_id == Constants::ORDERED) {
                    if ($old_offer->accepted == Constants::NOT_ACCEPTED && $old_offer->status == Constants::CANCEL) {
                        $old_offer->update([
                            'status' => Constants::NEW,
                            'create_type' => Constants::ORDER,
                            'seats' => $order_detail->seats_count,
                            'cancel_type' => NULL,
                            'cancel_date' => NULL
                        ]);

                        $device = ($order_detail->client) ? json_decode($order_detail->client->device_id) : [];
                        $title = 'You have a new offer';
                        $message = ': ' . (($order_detail && $order_detail->from) ? $order_detail->from->name : '') . ' - ' . (($order_detail && $order_detail->to) ? $order_detail->to->name : '');
                        $user_id = ($order_detail->client) ? $order_detail->client->id : 0;
                        $entity_id = $order_detail->id;

                        $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);

                        return $this->success(translate_api('Offer updates', $language), 201);
                    }
                } else {
                    return $this->error(translate_api('Sorry, this  order status is not Ordered', $language), 400);
                }                
            }
        } else {
            $offer = new Offer();
            $offer->order_id = $order->id;
            $offer->order_detail_id = $order_detail->id;
            $offer->seats = $order_detail->seats_count;
            $offer->create_type = Constants::ORDER;
            $offer->status = Constants::NEW;
            $offer->save();
            
            $device = ($order_detail->client) ? json_decode($order_detail->client->device_id) : [];
            $title = 'You have a new offer';
            $message = ': ' . (($order_detail && $order_detail->from) ? $order_detail->from->name : '') . ' - ' . (($order_detail && $order_detail->to) ? $order_detail->to->name : '');
            $user_id = ($order_detail->client) ? $order_detail->client->id : 0;
            $entity_id = $order_detail->id;

            $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
        }

        return $this->success(translate_api('Offer created', $language), 201);
    }

    public function getOffer(Request $request)
    {
        $language = $request->header('language');
        $offers = DB::table('yy_offers as dt1')
            ->Leftjoin('yy_order_details as dt2', 'dt2.id', '=', 'dt1.order_detail_id')
            ->Leftjoin('yy_orders as dt3', 'dt3.id', '=', 'dt1.order_id')
            ->Leftjoin('yy_statuses as dt4', 'dt4.type_id', '=', 'dt1.status')
            ->Leftjoin('yy_users as dt5', 'dt5.id', '=', 'dt2.client_id')
            ->Leftjoin('yy_personal_infos as dt6', 'dt6.id', '=', 'dt5.personal_info_id')
            ->Leftjoin('yy_drivers as dt7', 'dt7.user_id', '=', 'dt5.id')
            ->where('dt3.driver_id', auth()->id())
            ->where('dt1.create_type', Constants::ORDER_DETAIL)
            ->select('dt1.id as offer_id','dt1.order_id','dt1.seats as seats_count', 'dt1.order_detail_id','dt1.status as status_id','dt3.from_id' ,'dt3.to_id',DB::raw('DATE(dt2.start_date) as start_date'),'dt2.client_id as client_id','dt4.name as status','dt5.rating','dt6.first_name','dt6.middle_name','dt6.last_name','dt6.avatar','dt7.doc_status')
            ->get();

        $data=[];
        foreach ($offers as $key => $offer) {
            $from_to_name = table_translate($offer,'city',$language);

            if (isset($offer->avatar)) {
                $avatar = storage_path('app/public/avatar/'.$offer->avatar);
                if (file_exists($avatar)) {
                    $offer->avatar = asset('storage/avatar/'.$offer->avatar);
                } else {
                    $offer->avatar=null;
                }
            }

            if ($offer->client_id == auth()->id()) {
                $is_your=true;
            } else {
                $is_your=false;
            }

            if ($offer->status_id == Constants::NEW) {
                $list = [
                    'offer_id' => $offer->offer_id,
                    'order_id' => $offer->order_id,
                    'order_detail_id' => $offer->order_detail_id,
                    'start_date' => $offer->start_date,
                    'status' => $offer->status,
                    'rating' => $offer->rating,
                    'from_name' => $from_to_name['from_name'],
                    'to_name' => $from_to_name['to_name'],
                    'full_name' => ((isset($offer->first_name)) ? $offer->first_name : ''). '.' . ((isset($offer->last_name)) ? $offer->last_name[0] : ''),
                    'doc_status' => (int)$offer->doc_status,
                    'avatar' => $offer->avatar,
                    'seats_count' => $offer->seats_count,
                    'is_your' => $is_your
                ];
                array_push($data , $list);
            }
            
        }

        return $this->success('Success', 200, $data);
    }

    public function getByClient(Request $request)
    {
        $language = $request->header('language');
        
        $offers = DB::select("
            SELECT
                yof.id AS offer_id, yo.id AS order_id, yod.id AS order_detail_id, yo.start_date::DATE AS start_date, yof.created_at AS created_at, yfrom.id as from_id, yfrom.name AS from_name, yto.id as to_id, yto.name AS to_name, ypi.last_name AS last_name, ypi.first_name AS first_name, ypi.avatar, concat(ypi.last_name, ' ', ypi.first_name) AS full_name, ypi.phone_number, yu.rating AS rating, ycl.name AS model, ycol.id as color_id, ycol.name AS color, yc.production_date AS production_date, yo.seats AS seats_count, (yo.seats - yof.seats) AS empty_seats, yo.price
            FROM yy_offers AS yof
            INNER JOIN yy_order_details AS yod ON yod.id = yof.order_detail_id AND yod.order_id IS NULL AND yod.client_id = " . auth()->id() . "
            INNER JOIN yy_orders AS yo ON yo.id = yof.order_id
            INNER JOIN yy_cities AS yfrom ON yfrom.id = yod.from_id
            INNER JOIN yy_cities AS yto ON yto.id = yod.to_id
            INNER JOIN yy_users AS yu ON yu.id = yo.driver_id
            INNER JOIN yy_personal_infos AS ypi ON ypi.id = yu.personal_info_id
            INNER JOIN yy_cars AS yc ON yc.id = yo.car_id
            INNER JOIN yy_car_lists AS ycl ON ycl.id = yc.car_list_id
            INNER JOIN yy_color_lists AS ycol ON ycol.id = yc.color_list_id
            WHERE yof.status = " . Constants::NEW . " AND yof.create_type = " . Constants::ORDER . " AND yof.cancel_type IS NULL AND yof.accepted = " . Constants::NOT_ACCEPTED . "
        ");
        
        $data=[];
        foreach ($offers as $key => $offer) {
            $from_to_name = table_translate($offer,'city',$language);
            $color_name = table_translate($offer,'color',$language);

            $avatar = NULL;
            if (isset($offer->avatar)) {
                $avatar_path = storage_path('app/public/avatar/' . $offer->avatar);
                if (file_exists($avatar_path)) {
                    $avatar = asset('storage/avatar/' . $offer->avatar);
                }
            }

            $list = [
                'offer_id' => $offer->offer_id,
                'order_id' => $offer->order_id,
                'order_detail_id' => $offer->order_detail_id,
                'start_date' => date('d.m.Y', strtotime($offer->start_date)),
                'created_at' => date('d.m.Y H:i', strtotime($offer->created_at)),
                'from_name' => $from_to_name['from_name'],
                'to_name' => $from_to_name['to_name'],
                'last_name' => $offer->last_name,
                'first_name' => $offer->first_name,
                'full_name' => $offer->full_name,
                'phone_number' => $offer->phone_number,
                'avatar' => $avatar,
                'rating' => $offer->rating,
                'car' => [
                    'model' => $offer->model,
                    'color' => $color_name->color_translation_name,
                    'production_date' => date('d.m.Y H:i', strtotime($offer->production_date)),
                ],
                'empty_seats' => $offer->seats_count,
                'seats_count' => $offer->empty_seats,
                'price' => (double)$offer->price
            ];
            array_push($data , $list);
        }

        return $this->success('Success', 200, $data);
    }


}
