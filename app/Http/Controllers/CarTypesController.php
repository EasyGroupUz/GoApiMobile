<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarTypes;
use App\Models\Status;
use App\Http\Requests\CarTypesRequest;

class CarTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $carTypes = CarTypes::all();
        return view('car-types.index', ['carTypes'=>$carTypes]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $statuses = Status::all();
        return view('car-types.create', ['statuses'=>$statuses]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CarTypesRequest $request)
    {
        $model = new CarTypes();
        $model->name = $request->name;
        $model->status_id = $request->status_id;
        $model->save();
        return redirect()->route('carTypes.index')->with('status', translate('Successfully created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = CarTypes::find($id);
        return view('car-types.show', ['model'=>$model]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $statuses = Status::all();
        $carType = CarTypes::find($id);
        return view('car-types.edit', ['statuses'=>$statuses, 'carType'=>$carType]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CarTypesRequest $request, string $id)
    {
        $model = CarTypes::find($id);
        $model->name = $request->name;
        $model->status_id = $request->status_id;
        $model->save();
        return redirect()->route('carTypes.index')->with('status', translate('Successfully updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = CarTypes::find($id);
        $model->delete();
        return redirect()->route('carTypes.index')->with('status', translate('Successfully deleted'));
    }
}
