<?php

namespace App\Http\Controllers;
use App\Models\Role;
use App\Constants;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles=Role::get();
        // dd($roles);
        // dd(Constants::MYCONST);
        return view('role.index',[
            'roles' => $roles
        ] );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('role.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd(Constants::ACTIVE);
        $coupon= Role::create([
            'name'=>$request->role_name,
            'status_id'=>Constants::ACTIVE
        ]);
        // Role::create(
        //     ['name'=>$request->role_name]

        // );

        return redirect()->route('role.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return view('role.show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // dd($id);

        $role=Role::where('id',$id)->first();
        // dd($role);
        // dd($roles);
        return view('role.edit',[
            'role' => $role
        ] );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all());
        // dd($id);
        Role::where('id', $id)
            ->update(['name' => $request->role_name]);
        return redirect()->route('role.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // dd($id);
        $role = Role::findOrFail($id);
        $role->delete();
        return redirect()->route('role.index');
    }
}
