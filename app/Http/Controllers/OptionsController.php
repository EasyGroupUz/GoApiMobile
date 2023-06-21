<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OptionsRequest;
use App\Models\Options;
use App\Models\ClassList;

class OptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $options = Options::all();
        return view('options.index', ['options'=>$options]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $class_lists = ClassList::all();
        return view('options.create', ['class_lists'=> $class_lists]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OptionsRequest $request)
    {
        $model = new Options();
        $model->name = $request->name;
        $letters = range('a', 'z');
        $random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
        $random = implode("", $random_array);
        $file = $request->file('icon');
        if(isset($file)) {
            $image_name = $random . '' . date('Y-m-dh-i-s') . '.' . $file->extension();
            $file->storeAs('public/option/', $image_name);
            $model->icon = $image_name;
        }
        $model->class_list_id = $request->class_list_id;
        $model->save();
        return redirect()->route('option.index')->with('status', translate('Successfully created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = Options::find($id);
        return view('options.show', ['model'=>$model]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Options::find($id);
        $class_lists = ClassList::all();
        return view('options.edit', ['class_lists'=> $class_lists, 'model'=>$model]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OptionsRequest $request, string $id)
    {
        $model = Options::find($id);
        $model->name = $request->name;
        $letters = range('a', 'z');
        $random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
        $random = implode("", $random_array);
        $file = $request->file('icon');
        if(isset($file)) {
            $sms_avatar = storage_path('app/public/option/'.$model->icon);
            if(file_exists($sms_avatar)){
                unlink($sms_avatar);
            }
            $image_name = $random . '' . date('Y-m-dh-i-s') . '.' . $file->extension();
            $file->storeAs('public/option/', $image_name);
            $model->icon = $image_name;
        }
        $model->class_list_id = $request->class_list_id;
        $model->save();
        return redirect()->route('option.index')->with('status', translate('Successfully updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = Options::find($id);
        $sms_avatar = storage_path('app/public/option/'.$model->icon);
        if(file_exists($sms_avatar)){
            unlink($sms_avatar);
        }
        $model->delete();
        return redirect()->route('option.index')->with('status', translate('Successfully deleted'));
    }
}
