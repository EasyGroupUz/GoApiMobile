<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complain;
use App\Models\OrderDetail;
use App\Models\ComplainReason;
use App\Models\Order;
use App\Http\Requests\ComplainRequest;

class ComplainController extends Controller
{

    public function getComplain(){
        $complains = Complain::select('id', 'type', 'order_id', 'order_detail_id', 'text', 'complain_reason', 'created_at')->get();
        $getComplain = [];
        foreach($complains as $complain){
            $getComplain[] = [
                'id'=>$complain->id,
                'type'=>$complain->type,
                'order_id'=>$complain->order_id,
                'order_detail_id'=>$complain->order_detail_id,
                'text'=>$complain->text,
                'complain_reason'=>json_decode($complain->complain_reason),
                'created_at'=>$complain->created_at,
            ];
        }
        return response()->json($getComplain);
    }

    public function getReason(){
        $complain_reasons = ComplainReason::select('id', 'text')->get();
        return response()->json($complain_reasons);
    }
    
    public function storeReason(ComplainRequest $request)
    {
        $reasons_id = $request->reasons_id;
        foreach ($reasons_id as $reason_id){
            $complainReason = ComplainReason::find($reason_id);
            if(isset($complainReason->text)){
                $reason[] = $complainReason->text;
            }
        }
        $order_detail = OrderDetail::find($request->order_detail_id);
        if(!isset($order_detail)){
            return response()->json([
                "status" => false,
                "message" => "Order detail not found"
            ]);
        }
        $order = Order::find($request->order_id);
        if(!isset($order)){
            return response()->json([
                "status" => false,
                "message" => "Order not found"
            ]);
        }
        $complain = new Complain();
        $complain->complain_reason = json_encode($reason);
        $complain->text = $request->text;
        $complain->order_detail_id = $request->order_detail_id;
        $complain->order_id = $request->order_id;
        $complain->type = $request->type;
        $complain->save();
        $response = [
            "status" => true,
            "message" => "success"
        ];
        return response()->json($response);
    }
}
