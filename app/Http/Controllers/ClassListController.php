<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassList;
use App\Models\Status;
use App\Http\Requests\ClassListRequest;

class ClassListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classLists = ClassList::all();
        return view('class-lists.index', ['classLists'=>$classLists]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $statuses = Status::all();
        return view('class-lists.create', ['statuses'=>$statuses]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClassListRequest $request)
    {
        $model = new ClassList();
        $model->name = $request->name;
        $model->status_id = $request->status_id;
        $model->save();
        return redirect()->route('classList.index')->with('status', translate('Successfully created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = ClassList::find($id);
        return view('class-lists.show', ['model'=>$model]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $statuses = Status::all();
        $classList = ClassList::find($id);
        return view('class-lists.edit', ['statuses'=>$statuses, 'classList'=>$classList]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $model = ClassList::find($id);
        $model->name = $request->name;
        $model->status_id = $request->status_id;
        $model->save();
        return redirect()->route('classList.index')->with('status', translate('Successfully updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = ClassList::find($id);
        $model->delete();
        return redirect()->route('classList.index')->with('status', translate('Successfully deleted'));
    }
}
