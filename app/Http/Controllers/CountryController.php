<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\City;

class CountryController extends Controller
{
    public function index()
    {
        $model = City::select('id', 'name', 'type', 'parent_id')->where('type', 'region')->get();

        $arr = [];
        if (isset($model) && count($model) > 0) {
            foreach ($model as $value) {
                $cities = City::select('id', 'name', 'type', 'parent_id')->where('parent_id', $value->id)->get();
                
                if (!(isset($cities) && count($cities) > 0))
                    return $this->success('Cities not found', 204);

                $arrCity = [];
                foreach ($cities as $valueCity) {
                    $arrCity[] = [
                        'id' => $valueCity->id,
                        'name' => $valueCity->name,
                        'lng' => $valueCity->lng,
                        'lat' => $valueCity->lat
                    ];
                }
                
                $arr[] = [
                    'id' => $value->id,
                    'name' => $value->name ?? '',
                    'list' => $arrCity,
                ];
            }

            return $this->success('success', 200, $arr);
        } else {
            return $this->success('Cities not found', 204);
        }
        
        // $response = [
        //     'data' => $arr,
        //     'status' => true,
        //     'message' => 'success',
        // ];

        // return response()->json($response);
    }
}
