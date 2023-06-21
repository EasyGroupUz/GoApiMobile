<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cars;
use App\Models\CarList;
use App\Models\Drivers;
use App\Models\Status;
use App\Models\ColorList;
use App\Models\ClassList;
use App\Http\Requests\CarsRequest;

class CarsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cars = Cars::all();
        return view('cars.index', ['cars'=>$cars]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $carLists = CarList::all();
        $drivers = Drivers::all();
        $statuses = Status::all();
        $colorLists = ColorList::all();
        $classLists = ClassList::all();
        return view('cars.create', ['carLists'=>$carLists, 'drivers'=>$drivers, 'statuses'=>$statuses, 'colorLists'=>$colorLists, 'classLists'=>$classLists]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CarsRequest $request)
    {
        $model = new Cars();
        $model->car_list_id = $request->car_list_id;
        $model->driver_id = $request->driver_id;
        $model->status_id = $request->status_id;
        $model->color_list_id = $request->color_list_id;
        $model->class_list_id = $request->class_list_id;
        $model->reg_certificate = $request->reg_certificate;
        $letters = range('a', 'z');
        $certificate_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
        $certificate_random = implode("", $certificate_random_array);
        $certificate_img = $request->file('reg_certificate_image');
        if(isset($certificate_img)) {
            $image_name = $certificate_random . '' . date('Y-m-dh-i-s') . '.' . $certificate_img->extension();
            $certificate_img->storeAs('public/certificate/', $image_name);
            $model->reg_certificate_image = $image_name;
        }

        $images = $request->file('images');
        if(isset($images)){
            foreach ($images as $image){
                $images_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
                $images_random = implode("", $images_random_array);
                $image_name = $images_random . '' . date('Y-m-dh-i-s') . '.' . $image->extension();
                $image->storeAs('public/cars/', $image_name);
                $images_array[] = $image_name;
            }
            $model->images = json_encode($images_array);
        }
        $model->save();
        return redirect()->route('cars.index')->with('status', translate('Successfully created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = Cars::find($id);
        return view('cars.show', ['model'=>$model]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Cars::find($id);
        $carLists = CarList::all();
        $drivers = Drivers::all();
        $statuses = Status::all();
        $colorLists = ColorList::all();
        $classLists = ClassList::all();
        return view('cars.edit', ['carLists'=>$carLists, 'model'=>$model, 'drivers'=>$drivers, 'statuses'=>$statuses, 'colorLists'=>$colorLists, 'classLists'=>$classLists]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CarsRequest $request, string $id)
    {
        $model = Cars::find($id);
        $model->car_list_id = $request->car_list_id;
        $model->driver_id = $request->driver_id;
        $model->status_id = $request->status_id;
        $model->color_list_id = $request->color_list_id;
        $model->class_list_id = $request->class_list_id;
        $model->reg_certificate = $request->reg_certificate;
        $letters = range('a', 'z');
        $certificate_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
        $certificate_random = implode("", $certificate_random_array);
        $certificate_img = $request->file('reg_certificate_image');
        if(isset($certificate_img)) {
            $sms_avatar = storage_path('app/public/certificate/'.$model->reg_certificate_image);
            if(file_exists($sms_avatar)){
                unlink($sms_avatar);
            }
            $image_name = $certificate_random . '' . date('Y-m-dh-i-s') . '.' . $certificate_img->extension();
            $certificate_img->storeAs('public/certificate/', $image_name);
            $model->reg_certificate_image = $image_name;
        }

        $images = $request->file('images');
        if(isset($images)){
            $model_images = json_decode($model->images);
            foreach ($model_images as $model_image){
                $sms_image = storage_path('app/public/cars/'.$model_image);
                if(file_exists($sms_image)){
                    unlink($sms_image);
                }
            }
            foreach ($images as $image){
                $images_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
                $images_random = implode("", $images_random_array);
                $image_name = $images_random . '' . date('Y-m-dh-i-s') . '.' . $image->extension();
                $image->storeAs('public/cars/', $image_name);
                $images_array[] = $image_name;
            }
            $model->images = json_encode($images_array);
        }
        $model->save();
        return redirect()->route('cars.index')->with('status', translate('Successfully updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model= Cars::find($id);
        if(isset($model->reg_certificate_image)){
            $sms_avatar = storage_path('app/public/cars/'.$model->reg_certificate_image);
            if(file_exists($sms_avatar)){
                unlink($sms_avatar);
            }
        }
        if(isset($model->images)){
            $images = json_decode($model->images);
            foreach ($images as $image){
                $sms_image = storage_path('app/public/cars/'.$image);
                if(file_exists($sms_image)){
                    unlink($sms_image);
                }
            }
        }
        $model->delete();
        return redirect()->route('cars.index')->with('status', translate('Successfully deleted'));
    }
}
