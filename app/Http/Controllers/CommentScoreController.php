<?php

namespace App\Http\Controllers;

use App\Models\CommentScore;
use Illuminate\Http\Request;

class CommentScoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function myComments()
    {
        // dd(aut()->user());
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CommentScore $commentScore)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CommentScore $commentScore)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CommentScore $commentScore)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommentScore $commentScore)
    {
        //
    }
}
