<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use DB;

class NotificationController extends Controller
{
    public function index()
    {
        $model = Notification::select('id', 'title', 'text', 'date')->whereNull('read_at')->get()->toArray();

        if (isset($model) && count($model) > 0) {
            foreach ($model as $value) 
                $value['date'] = date('d.m.Y H:i', strtotime($value['date']));

            return $this->success('success', 200, (array)$model);
        } else {
            return $this->success('Notification not found', 204);
        }
    }
}
