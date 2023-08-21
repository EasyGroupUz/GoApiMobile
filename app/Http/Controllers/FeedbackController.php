<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        if (!isset($request['name']) || $request['name'] == '')
            return $this->error('name parameter is missing', 400);

        if (!isset($request['phone']) || $request['phone'] == '')
            return $this->error('phone parameter is missing', 400);

        if (!isset($request['message']) || $request['message'] == '')
            return $this->error('message parameter is missing', 400);

        if (!isset($request['type']) || $request['type'] == '') // 1 - from site, 2 - from app
            return $this->error('type parameter is missing', 400);

        $newPersonalInfo = new Feedback();
        $newPersonalInfo->create($request->all());

        return $this->success('success', 200);
    }
}
