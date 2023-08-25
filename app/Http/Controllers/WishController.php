<?php

namespace App\Http\Controllers;

use App\Models\Wish;
use Illuminate\Http\Request;

class WishController extends Controller
{
    public function store(Request $request)
    {
        if (!isset($request['name']) || $request['name'] == '')
            return $this->error('name parameter is missing', 400);

        if (!isset($request['email']) || $request['email'] == '')
            return $this->error('email parameter is missing', 400);

        if (!isset($request['message']) || $request['message'] == '')
            return $this->error('message parameter is missing', 400);

        $newWish = new Wish();
        $newWish->create($request->all());

        return $this->success('success', 200);
    }
}
