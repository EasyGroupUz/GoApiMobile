<?php

namespace App\Http\Controllers;

use App\Constants;
use App\Models\Cars;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    public function accept(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            // 'driver_id' => 'required',
            'license_number' => 'required',
            'license_expired_date' => 'required',
            'reg_certificate_number' => 'required',
            'car_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $id = auth()->id();
        // Find user and check if driver exists
        $model = User::find($id);
        if (!$model) {
            return $this->error('Driver not found', 400);
        }

        // Check if the user is already a registered driver
        $modelDriver = Driver::where('user_id', $model->id)->first();
        if ($modelDriver) {
            return $this->error('You are already registered as a driver', 400);
        }

        // Find the associated car
        $modelCars = Cars::find($request->car_id);
        if (!$modelCars) {
            return $this->error('Cars not found', 400);
        }
        $modelCars->reg_certificate = $request->reg_certificate_number;
        $modelCars->save();

        // Create and save a new driver
        $newDriver = new Driver();
        $newDriver->user_id = $model->id;
        $newDriver->license_number = $request->license_number;
        $newDriver->license_expired_date = $request->license_expired_date;
        $newDriver->doc_status = Constants::WAITING;
        $newDriver->save();

        // Handle images
        $this->handleImageUpload($request, $newDriver, 'license_image', 'certificate');
        $this->handleImageUpload($request, $newDriver, 'license_image_back', 'certificate');
        $this->handleImageUpload($request, $modelCars, 'reg_certificate_image', 'cars');
        $this->handleImageUpload($request, $modelCars, 'reg_certificate_image_back', 'cars');

        return $this->success('Success', 200);
    }

    private function handleImageUpload($request, $model, $imageName, $folderName)
    {
        $letters = range('a', 'z');
        if ($request->hasFile($imageName)) {
            $randomArray = [];
            for ($i = 0; $i < 5; $i++) {
                $randomArray[] = $letters[rand(0, 25)];
            }
            $randomString = implode("", $randomArray);

            $image = $request->file($imageName);
            $imageNameGen = $randomString . '' . now()->format('Y-m-dh-i-s') . '.' . $image->extension();
            $image->storeAs('public/' . $folderName . '/', $imageNameGen);

            $model->{$imageName} = $imageNameGen;
            $model->save();
        }
    }
    

    // public function accept(Request $request)
    // {
    //     if (!isset($request['driver_id']) || $request['driver_id'] == '')
    //         return $this->error('driver_id parameter is missing', 400);

    //     if (!isset($request['license_number']) || $request['license_number'] == '')
    //         return $this->error('license_number parameter is missing', 400);

    //     if (!isset($request['license_expired_date']) || $request['license_expired_date'] == '')
    //         return $this->error('license_expired_date parameter is missing', 400);

    //     $model = User::find($request['driver_id']);

    //     if (!isset($model))
    //         return $this->error('driver_id not correct. Driver not found', 400);
        
    //     $modelDriver = Driver::where('user_id', $model->id)->first();

    //     if (isset($modelDriver))
    //         return $this->error('You are registered, please wait for admin confirmation', 400);

    //     $modelCars = Cars::where('driver_id', $model->id)->first();

    //     if (!isset($modelCars))
    //         return $this->error('driver_id not correct. Cars not found', 400);

    //     $newDriver = new Driver();
    //     $newDriver->user_id = $model->id;
    //     $newDriver->license_number = $request['license_number'];
    //     $newDriver->license_expired_date = $request['license_expired_date'];
    //     $newDriver->doc_status = 1;

    //     $letters = range('a', 'z');
    //     if (isset($request->license_image)) {
    //         $certificate_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
    //         $certificate_random = implode("", $certificate_random_array);
    //         $certificate_img = $request->file('license_image');
    //         if (isset($certificate_img)) {
    //             $image_name = $certificate_random . '' . date('Y-m-dh-i-s') . '.' . $certificate_img->extension();
    //             $certificate_img->storeAs('public/certificate/', $image_name);
    //             $newDriver->license_image = $image_name;
    //         }
    //     }
        
    //     if (isset($request->license_image_back)) {
    //         $certificate_random_array_back = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
    //         $certificate_random_back = implode("", $certificate_random_array_back);
    //         $certificate_img_back = $request->file('license_image_back');
    //         if (isset($certificate_img_back)) {
    //             $image_name_back = $certificate_random_back . '' . date('Y-m-dh-i-s') . '.' . $certificate_img_back->extension();
    //             $certificate_img_back->storeAs('public/certificate/', $image_name_back);
    //             $newDriver->license_image_back = $image_name_back;
    //         }
    //     }
        
    //     if (isset($request->reg_certificate_image)) {
    //         $certificate_random_array = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
    //         $certificate_random = implode("", $certificate_random_array);
    //         $certificate_img = $request->file('reg_certificate_image');
    //         if (isset($certificate_img)) {
    //             $image_name = $certificate_random . '' . date('Y-m-dh-i-s') . '.' . $certificate_img->extension();
    //             $certificate_img->storeAs('public/cars/', $image_name);
    //             $modelCars->reg_certificate_image = $image_name;
    //         }
    //     }
        
    //     if (isset($request->reg_certificate_image_back)) {
    //         $certificate_random_array_back = [$letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)], $letters[rand(0,25)]];
    //         $certificate_random_back = implode("", $certificate_random_array_back);
    //         $certificate_img_back = $request->file('reg_certificate_image_back');
    //         if (isset($certificate_img_back)) {
    //             $image_name_back = $certificate_random_back . '' . date('Y-m-dh-i-s') . '.' . $certificate_img_back->extension();
    //             $certificate_img_back->storeAs('public/cars/', $image_name_back);
    //             $modelCars->reg_certificate_image_back = $image_name_back;
    //         }
    //     }

    //     $modelCars->save();
    //     $newDriver->save();

    //     return $this->success('success', 200);
    // }
}
