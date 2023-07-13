<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use Illuminate\Http\Request;

class OrderDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        dd(auth()->user());
        

        $coupon= OrderDetails::create([
            'user_id'
            'from_id'=>$request->from_id,
            'to_id'=>$request->to_id,
            'seats_type'=>$request->seats_type,
            'seats_count'=>$request->seats_count,
            'seats_type'=>$request->seats_type,

        ]);

        // return '';
       

    }

    /**
     * Display the specified resource.
     */
    public function show(OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderDetails $orderDetails)
    {
        //
    }
}
