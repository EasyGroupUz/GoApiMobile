<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use DB;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index()
    {
        $model = Notification::select('id', 'title', 'text', 'date')->whereNull('read_at')->get()->toArray();

        $arr = [];
        if (isset($model) && count($model) > 0) {
            $i = 0;
            foreach ($model as $value) {
                $arr[$i]['id'] = $value['id'];
                $arr[$i]['title'] = $value['title'];
                $arr[$i]['text'] = $value['text'];
                $arr[$i]['date'] = date('d.m.Y H:i', strtotime($value['date']));

                $i++;
            }

            return $this->success('success', 200, $arr);
        } else {
            return $this->success('Notification not found', 200, $arr);
        }
    }
    
    public function read(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $model = Notification::find($request->id);
        if (!($model)) {
            return $this->error('Notification not found', 400);
        }

        if (!is_null($model->read_at)) {
            return $this->error('Notification is red already', 200);
        }
        
        $model->read_at = date('Y-m-d H:i:s');
        $model->save();
        
        return $this->success('success', 200);
    }
}
