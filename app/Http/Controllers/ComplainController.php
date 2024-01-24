<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complain;
use App\Models\OrderDetail;
use App\Models\ComplainReason;
use App\Models\Order;
use App\Http\Requests\ComplainRequest;
use Illuminate\Support\Facades\Auth;

class ComplainController extends Controller
{
    public function getComplain(){
        $complains = Complain::select('id', 'type', 'order_id', 'order_detail_id', 'text', 'complain_reason', 'created_at')->get();
        $getComplain = null;
        foreach($complains as $complain){
            if(!is_array($complain->complain_reason)){
                $complain_reason = json_decode($complain->complain_reason);
            }elseif(!isset($complain->complain_reason)){
                $complain_reason = null;
            }else{
                $complain_reason = $complain->complain_reason;
            }
            $getComplain[] = [
                'id'=>$complain->id??null,
                'type'=>$complain->type??null,
                'order_id'=>$complain->order_id??null,
                'order_detail_id'=>$complain->order_detail_id??null,
                'text'=>$complain->text??null,
                'complain_reason'=>$complain_reason,
                'created_at'=>$complain->created_at??null,
            ];
        }
        if($getComplain != null){
            return $this->success('Success', 200);
        }else{
            return $this->error('No complains', 400);
        }
    }

    public function getReason(){
        $complain_reasons = ComplainReason::select('id', 'text')->get()->toArray();
        if(count($complain_reasons)>0){
            return $this->success('Success', 200, $complain_reasons);
        }else{
            return $this->error('No complains', 400);
        }
    }
    
    public function storeReason(ComplainRequest $request)
    {
        $language = $request->header('language');
        $reasons_id = $request->reasons_id;
        $reason = [];
        $complain = new Complain();
        foreach ($reasons_id as $reason_id){
            $complainReason = ComplainReason::find($reason_id);
            if(isset($complainReason->text)){
                $reason[] = $complainReason->text;
            }
        }
        $complain->order_detail_id = Auth::user()->id;
        // if(isset($request->order_detail_id)){
        //     $order_detail = OrderDetail::withTrashed()->find($request->order_detail_id);
        //     $complain->order_detail_id = $request->order_detail_id;
        //     if(!isset($order_detail)){
        //         return $this->error(translate_api("Order detail not found", $language), 400);
        //     }
        // }
        if(isset($request->order_id)) {
            $order = Order::withTrashed()->find($request->order_id);
            $complain->order_id = $request->order_id;
            if (!isset($order)) {
                return $this->error(translate_api("Order not found", $language), 400);
            }
        }
        $complain->complain_reason = json_encode($reason);
        if(!isset($request->text)){
            return $this->error('text is not entered', 400);
        }
        
        if(!isset($request->type)){
            return $this->error('type is not entered', 400);
        }
        $complain->text = $request->text;
        $complain->type = $request->type;
        $complain->save();
        return $this->success("success", 200);
    }

    public function destroy(Request $request){
        $language = $request->header('language');
        $model = Complain::find($request->id);
        if(isset($model->id)){
            $model->delete();
            return $this->success("Success", 200);
        }else{
            return $this->error(translate_api("Complain not found", $language), 400);
        }
    }
}
