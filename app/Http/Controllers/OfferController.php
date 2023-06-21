<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Driver;
use App\Models\Client;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $offers = Offer::all();

        return view('offer.index', compact('offers'));
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
        $offer = Offer::findOrFail($id);

        return view('offer.show', compact('offer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $model = Offer::findOrFail($id);
        $driver = Driver::all();
        $client = Client::all();

        return view('offer.edit', [
            'model' => $model, 
            'driver' => $driver,
            'client' => $client
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // $data = $request->validated();

        $client = Offer::findOrFail($id);
        $client->update($request->all());
        $client->save();

        return redirect(route('offer.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Offer::destroy($id);

        return redirect(route('offer.index'));
    }
}
