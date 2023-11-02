<?php

namespace App\Http\Controllers;

use App\Constants;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClientControler extends Controller
{
    
    public function placedOrder(Request $request)
    {
        $language = $request->header('language');

        if (!$request->order_id)
            return $this->error(translate_api('order_id parameter is missing', $language), 400);

        // if (!$request->to_id)
        //     return $this->error(translate_api('to_id parameter is missing', $language), 400);

        // if (!$request->date)
        //     return $this->error(translate_api('date parameter is missing', $language), 400);

        $order = Order::where('id', $request->order_id)->first();

        if (!$order)
            return $this->error(translate_api('No information was found for the order_id you provided', $language), 400);

        // return $order;
        $from_id = $order->from_id;
        $to_id = $order->to_id;
        $date = date('Y-m-d', strtotime($order->start_date));

        $query = DB::select("
            SELECT
                yod.id, ypi.last_name, ypi.first_name, ypi.middle_name, CONCAT(ypi.last_name, ' ', ypi.first_name, ' ', ypi.middle_name) AS full_name, ypi.avatar, yod.seats_count, yod.start_date, yu.rating, yod.from_id, yfrom.name AS from, yod.to_id, yto.name AS to, con.count AS count_trips
            FROM yy_order_details AS yod 
            INNER JOIN yy_users AS yu ON yu.id = yod.client_id
            INNER JOIN yy_personal_infos AS ypi ON ypi.id = yu.personal_info_id
            INNER JOIN yy_cities AS yfrom ON yfrom.id = yod.from_id
            INNER JOIN yy_cities AS yto ON yto.id = yod.to_id
            LEFT JOIN (
                SELECT 
                    yod.client_id, COUNT(yod.id) FROM yy_order_details AS yod
                INNER JOIN yy_orders AS yo ON yo.id = yod.order_id
                WHERE yod.type = " . Constants::CREATED_ORDER_DETAIL . " AND yod.end_date IS NULL
                GROUP BY yod.client_id
            ) AS con ON yod.client_id = con.client_id
            WHERE yod.from_id = " . $from_id . " AND yod.to_id = " . $to_id . " AND yod.type = " . Constants::CREATED_ORDER_DETAIL . " AND yod.end_date IS NULL AND yod.start_date::DATE = '" . $date . "'
        ");
        
        $message = translate_api('success', $language);
        if (!$query) {
            $date_today = date("Y-m-d");
            $query = DB::select("
                SELECT * FROM (
                    SELECT * FROM (
                        SELECT
                            yod.id, ypi.last_name, ypi.first_name, ypi.middle_name, CONCAT(ypi.last_name, ' ', ypi.first_name, ' ', ypi.middle_name) AS full_name, ypi.avatar, yod.seats_count, yod.start_date, yu.rating, yod.from_id, yfrom.name AS from, yod.to_id, yto.name AS to, con.count AS count_trips
                        FROM yy_order_details AS yod 
                        INNER JOIN yy_users AS yu ON yu.id = yod.client_id
                        INNER JOIN yy_personal_infos AS ypi ON ypi.id = yu.personal_info_id
                        INNER JOIN yy_cities AS yfrom ON yfrom.id = yod.from_id
                        INNER JOIN yy_cities AS yto ON yto.id = yod.to_id
                        LEFT JOIN (
                            SELECT 
                                yod.client_id, COUNT(yod.id) FROM yy_order_details AS yod
                            INNER JOIN yy_orders AS yo ON yo.id = yod.order_id
                            WHERE yod.type = " . Constants::CREATED_ORDER_DETAIL . " AND yod.end_date IS NULL
                            GROUP BY yod.client_id
                        ) AS con ON yod.client_id = con.client_id
                        WHERE yod.from_id = " . $from_id . " AND yod.to_id = " . $to_id . " AND yod.type = " . Constants::CREATED_ORDER_DETAIL . " AND yod.end_date IS NULL AND yod.start_date::DATE < '" . $date . "' 
                            AND yod.start_date::DATE > '" . $date_today . "'
                        ORDER BY yod.start_date DESC
                        LIMIT 5
                    ) AS A
                    
                    UNION
                    
                    SELECT * FROM (
                        SELECT
                            yod.id, ypi.last_name, ypi.first_name, ypi.middle_name, CONCAT(ypi.last_name, ' ', ypi.first_name, ' ', ypi.middle_name) AS full_name, ypi.avatar, yod.seats_count, yod.start_date, yu.rating, yod.from_id, yfrom.name AS from, yod.to_id, yto.name AS to, con.count AS count_trips
                        FROM yy_order_details AS yod 
                        INNER JOIN yy_users AS yu ON yu.id = yod.client_id
                        INNER JOIN yy_personal_infos AS ypi ON ypi.id = yu.personal_info_id
                        INNER JOIN yy_cities AS yfrom ON yfrom.id = yod.from_id
                        INNER JOIN yy_cities AS yto ON yto.id = yod.to_id
                        LEFT JOIN (
                            SELECT 
                                yod.client_id, COUNT(yod.id) FROM yy_order_details AS yod
                            INNER JOIN yy_orders AS yo ON yo.id = yod.order_id
                            WHERE yod.type = " . Constants::CREATED_ORDER_DETAIL . " AND yod.end_date IS NULL
                            GROUP BY yod.client_id
                        ) AS con ON yod.client_id = con.client_id
                        WHERE yod.from_id = " . $from_id . " AND yod.to_id = " . $to_id . " AND yod.type = " . Constants::CREATED_ORDER_DETAIL . " AND yod.end_date IS NULL AND yod.start_date::DATE > '" . $date . "' 
                        ORDER BY yod.start_date ASC
                        LIMIT 5
                    ) AS B
                ) AS tab
                ORDER BY tab.start_date ASC
            ");

            if ($query)
                $message = 'isEmpty';

        }

        $data = [];
        if (!empty($query)) {
            $i = 0;
            foreach ($query as $value) {
                $data['count'] = $i;
                $data['list'][$i]['id'] = $value->id;
                $data['list'][$i]['last_name'] = $value->last_name;
                $data['list'][$i]['first_name'] = $value->first_name;
                $data['list'][$i]['middle_name'] = $value->middle_name;
                $data['list'][$i]['full_name'] = $value->full_name;
                $data['list'][$i]['avatar'] = $value->avatar ? asset('storage/avatar/' . $value->avatar) : '';
                $data['list'][$i]['start_date'] = date("d.m.Y H:i", strtotime($value->start_date));
                $data['list'][$i]['seats_count'] = $value->seats_count;
                $data['list'][$i]['rating'] = (INT)$value->rating;
                $data['list'][$i]['from_id'] = $value->from_id;
                $data['list'][$i]['from'] = $value->from;
                $data['list'][$i]['to_id'] = $value->to_id;
                $data['list'][$i]['to'] = $value->to;
                $data['list'][$i]['count_trips'] = $value->count_trips;
                $i++;
            }
        }

        return $this->success($message, 200, $data);
    }

    public function createOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_detail_id' => 'required|integer',
            'from_id' => 'required|integer',
            'to_id' => 'required|integer',
            'seats' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $language = $request->header('language');
        $data = $request->all();
        
        $order = $this->getOrder(auth()->id(), $data['from_id'], $data['to_id']);
        if (!$order) {
            return $this->error(translate_api('order_id parameter is missing', $language), 400);
        }

        $getOffer = Offer::where('order_id', $order->id)->where('order_detail_id', $data['order_detail_id'])->where('create_type', Constants::ORDER)->where('status', Constants::NEW)->first();

        if ($getOffer) {
            return $this->error(translate_api('You cannot submit two consecutive offers', $language), 400);
        }

        $offer = new Offer();
        $offer->order_id = $order->id;
        $offer->seats = $data['seats'];
        $offer->order_detail_id = $data['order_detail_id'];
        $offer->create_type = Constants::ORDER;
        $offer->status = Constants::NEW;
        $offer->save();

        $orderDetail = OrderDetail::where('id', $data['order_detail_id'])->first();

        $device = ($orderDetail->client) ? json_decode($orderDetail->client->device_id) : [];
        $title = 'You have a new offer';
        $message = ': ' . (($order && $order->from) ? $order->from->name : '') . ' - ' . (($order && $order->to) ? $order->to->name : '');
        $user_id = ($orderDetail->client) ? $orderDetail->client->id : 0;
        $entity_id = $orderDetail->id;

        $this->sendNotificationOrder($device, $user_id, $entity_id, $title, $message);
        
        return $this->success('success', 200);

    }

    private function getOrder($driver_id, $from_id, $to_id)
    {
        $order = Order::where('driver_id', $driver_id)->where('from_id', $from_id)->where('to_id', $to_id)->first();

        return $order;
    }

}
