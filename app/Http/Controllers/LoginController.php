<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\Staff;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
//    public function registerPage(){
//        return view('auth.register');
//    }
//
//    public function loginPage(){
//        return view('auth.login');
//    }

    public function authentificate(Request $request){
        
        $model = Staff::where('login', $request->email)->first();
        
        if(isset($model) && Hash::check($request->password, $model->password)){
            return redirect()->route('dashboard');
        }else{
            return redirect()->route('loginPage')->with('status', translate("Your password or Email is incorrect"));
            
        }
    }
}
