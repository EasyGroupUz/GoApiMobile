<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
// use Modules\ForTheBuilder\Entities\Coupon;
use App\Models\Coupon;

use App\Http\Requests\CouponRequest;


// use Modules\ForTheBuilder\Entities\Notification_;
// use Modules\ForTheBuilder\Http\Requests\CouponRequest;

class CouponContoller extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    // public function getNotification(){
    //     $notification = ['Booking', 'BookingPrepayment'];
    //     $all_task = Notification_::where('type', 'Task')->where(['read_at' => NULL,  'user_id' => Auth::user()->id])->orderBy('created_at', 'desc')->get();
    //     $all_booking = Notification_::whereIn('type', $notification)->where('read_at', NULL)->orderBy('created_at', 'desc')->get();
    //     return ['all_task'=>$all_task, 'all_booking'=>$all_booking];
    // }

    public function index()
    {
        $coupons = Coupon::orderBy('id', 'desc')->get();
        return view('coupon.index')->with([
            'coupons' => $coupons,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('coupon.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    // CouponRequest
    public function store(CouponRequest $request)
    {
        // dd($request->all());
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'percent'  => 'required |integer',
        ]);
        //  dd($data['percent']);
        $coupon= Coupon::create([
            'percent'=>$data['percent'],
            'name'=>$data['name']
        ]);
        // dd($coupon);

        return redirect()->route('coupon.index')->with('success', translate('locale.successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $coupon = Coupon::find($id);
        return view('coupon.edit')->with([
            'coupon' => $coupon,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(CouponRequest $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'percent'  => 'required |integer',
        ]);
        // dd($request->all());

        $model = Coupon::find($id);
        $model->name = $data['name'];
        $model->percent = $data['percent'];
        if ($model->save()){

            return redirect()->route('coupon.index')->with('success', translate('locale.successfully'));
        }
            


    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $model = Coupon::find($id);
        $model->delete();
        return redirect()->route('coupon.index')->with('success', translate('locale.successfully'));
    }
}
