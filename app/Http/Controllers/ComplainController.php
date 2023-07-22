<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complain;
use App\Http\Requests\ComplainRequest;

class ComplainController extends Controller
{
    /**
     * Update the specified resource in storage.
     */
    public function create(ComplainRequest $request)
    {
        $data = $request->validated();
        // $token = $request->header()['token'];

        $order = new Complain();
        $order->create($data);
        
        return [
            "status" => true,
            "message" => "success"
        ];
    }
}
