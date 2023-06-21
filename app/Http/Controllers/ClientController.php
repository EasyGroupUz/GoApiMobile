<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Status;
use App\Models\PersonalInfo;

use App\Http\Requests\ClientRequest;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $model = Client::all();

        return view('client.index', [
            'model' => $model
        ]);
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $model = Client::find($id);
        // dd($model->commentScores);

        return view('client.show', [
            'model' => $model,
            'orderDetails' => $model->orderDetails,
            'commentScores' => $model->commentScores,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Client::findOrfail($id);
        $modelStatus = Status::all();
        $modelPersonalInfo = PersonalInfo::where(['id' => $model->personal_info_id])->first();

        return view('client.edit', [
            'model' => $model,
            'modelStatus' => $modelStatus,
            'modelPersonalInfo' => $modelPersonalInfo,
            'male' => Client::MALE,
            'female' => Client::FEMALE,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClientRequest $request, string $id)
    {
        $data = $request->validated();

        $client = Client::findOrFail($id);
        $client->update($request->all());
        $client->save();

        $personalInfo = PersonalInfo::where(['id' => $client->personal_info_id])->first();
        $personalInfo->first_name = $data['first_name'];
        $personalInfo->last_name = $data['last_name'];
        $personalInfo->middle_name = $data['middle_name'];
        $personalInfo->phone_number = $data['phone_number'];
        $personalInfo->gender = $data['gender'];
        $personalInfo->birth_date = $data['birth_date'];
        $personalInfo->save();
        
        return redirect()->route('client.index'); // ->with('updated', translate('Data successfully updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        $personalInfo = PersonalInfo::where(['id' => $client->personal_info_id])->first();
        $personalInfo->delete();
        
        return redirect()->route('client.index'); // ->with('updated', translate('Data successfully updated'));
    }
}
