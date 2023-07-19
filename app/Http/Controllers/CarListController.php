<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CarListRequest;
use App\Models\Carlist;
use App\Models\Status;
use App\Models\CarTypes;

class CarListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $carLists = CarList::all();
        return view('car-lists.index', ['carLists'=>$carLists]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $carTypes = CarTypes::all();
        $statuses = Status::all();
        return view('car-lists.create', ['statuses'=>$statuses, 'carTypes'=>$carTypes]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CarListRequest $request)
    {
        $model = new Carlist();
        $model->status_id = $request->status_id;
        $model->car_type_id = $request->car_type_id;
        $model->name = $request->name;
        $model->save();
        return redirect()->route('carList.index')->with('status', translate('Successfully created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = Carlist::find($id);
        return view('car-lists.show', ['model'=>$model]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Carlist::find($id);
        $statuses = Status::all();
        $carTypes = CarTypes::all();
        return view('car-lists.edit', ['statuses'=>$statuses, 'carTypes'=>$carTypes, 'model'=>$model]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CarListRequest $request, string $id)
    {
        $model = Carlist::find($id);
        $model->status_id = $request->status_id;
        $model->car_type_id = $request->car_type_id;
        $model->name = $request->name;
        $model->save();
        return redirect()->route('carList.index')->with('status', translate('Successfully updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = Carlist::find($id);
        $model->delete();
        return redirect()->route('carList.index')->with('status', translate('Successfully deleted'));
    }

}
