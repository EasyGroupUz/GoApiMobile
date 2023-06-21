<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\DriverRequest;
use App\Models\Driver;
use App\Models\Status;
use App\Models\PersonalInfo;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $model = Driver::all();

        return view('drivers.index', ['model' => $model]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        dd('create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        dd('store');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = Driver::find($id);
        // dd($model->balanceHistory);

        return view('drivers.show', [
            'model' => $model,
            'orders' => $model->orders,
            'commentScores' => $model->commentScores,
            'cars' => $model->cars,
            'balanceHistory' => $model->balanceHistory,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Driver::findOrfail($id);
        $modelStatus = Status::all();
        $modelPersonalInfo = PersonalInfo::where(['id' => $model->personal_info_id])->first();

        return view('drivers.edit', [
            'model' => $model,
            'modelStatus' => $modelStatus,
            'modelPersonalInfo' => $modelPersonalInfo,
            'male' => Driver::MALE,
            'female' => Driver::FEMALE,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DriverRequest $request, string $id)
    {
        $data = $request->validated();

        $driver = Driver::findOrFail($id);
        $driver->update($request->all());
        $driver->save();

        $personalInfo = PersonalInfo::where(['id' => $driver->personal_info_id])->first();
        $personalInfo->first_name = $data['first_name'];
        $personalInfo->last_name = $data['last_name'];
        $personalInfo->middle_name = $data['middle_name'];
        $personalInfo->phone_number = $data['phone_number'];
        $personalInfo->gender = $data['gender'];
        $personalInfo->birth_date = $data['birth_date'];
        $personalInfo->save();
        
        return redirect()->route('driver.index'); // ->with('updated', translate('Data successfully updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();

        $personalInfo = PersonalInfo::where(['id' => $driver->personal_info_id])->first();
        $personalInfo->delete();
        
        return redirect()->route('driver.index'); // ->with('updated', translate('Data successfully updated'));
    }
}
