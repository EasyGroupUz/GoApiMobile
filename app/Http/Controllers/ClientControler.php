<?php

namespace App\Http\Controllers;

use App\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientControler extends Controller
{
    
    public function placedOrder(Request $request)
    {
        $language = $request->header('language');

        if (!$request->from_id)
            return $this->error(translate_api('from_id parameter is missing', $language), 400);

        if (!$request->to_id)
            return $this->error(translate_api('to_id parameter is missing', $language), 400);

        if (!$request->date)
            return $this->error(translate_api('date parameter is missing', $language), 400);

        $from_id = $request->from_id;
        $to_id = $request->to_id;
        $date = date('Y-m-d', strtotime($request->date));

        $query = DB::select("
            SELECT
                yod.id, ypi.last_name, ypi.first_name, ypi.middle_name, CONCAT(ypi.last_name, ' ', ypi.first_name, ' ', ypi.middle_name) AS full_name, ypi.avatar, yod.seats_count, yod.start_date, yu.rating, yod.from_id, yod.to_id, con.count AS count_trips
            FROM yy_order_details AS yod 
            INNER JOIN yy_users AS yu ON yu.id = yod.client_id
            INNER JOIN yy_personal_infos AS ypi ON ypi.id = yu.personal_info_id
            LEFT JOIN (
                SELECT yod.client_id, COUNT(yod.id) FROM yy_order_details AS yod
            INNER JOIN yy_orders AS yo ON yo.id = yod.order_id
            WHERE yod.type = " . Constants::CREATED_ORDER_DETAIL . " AND yod.end_date IS NULL
            GROUP BY yod.client_id
            ) AS con ON yod.client_id = con.client_id
            WHERE yod.from_id = " . $from_id . " AND yod.to_id = " . $to_id . " AND yod.type = " . Constants::CREATED_ORDER_DETAIL . " AND yod.end_date IS NULL AND yod.start_date::DATE = '" . $date . "'
        ");

        $data = [];
        if (!empty($query)) {
            $i = 0;
            foreach ($query as $value) {
                // return $value;
                $data['count'] = $i;
                $data['list'][$i]['id'] = $value->id;
                $data['list'][$i]['last_name'] = $value->last_name;
                $data['list'][$i]['first_name'] = $value->first_name;
                $data['list'][$i]['middle_name'] = $value->middle_name;
                $data['list'][$i]['full_name'] = $value->full_name;
                $data['list'][$i]['avatar'] = $value->avatar ? asset('storage/avatar/' . $value->avatar) : '';
                $data['list'][$i]['seats_count'] = $value->seats_count;
                $data['list'][$i]['rating'] = (INT)$value->rating;
                $data['list'][$i]['from_id'] = $value->from_id;
                $data['list'][$i]['to_id'] = $value->to_id;
                $data['list'][$i]['count_trips'] = $value->count_trips;
                $i++;
            }
        }

        $message = translate_api('success', $language);
        return $this->success($message, 200, $data);
    }

}
