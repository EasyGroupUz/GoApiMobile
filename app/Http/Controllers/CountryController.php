<?php

namespace App\Http\Controllers;

use App\Models\City;

class CountryController extends Controller
{
    public function index(){
        $model = City::select('id', 'name', 'type', 'parent_id')->where('type', 'region')->get();

        $arr = [];
        foreach ($model as $value) {
            $cities = City::select('id', 'name', 'type', 'parent_id')->where('parent_id', $value->id)->get();
            $arrCity = [];
            foreach ($cities as $valueCity) {
                $arrCity[] = [
                    'id' => $valueCity->id,
                    'name' => $valueCity->name,
                ];
            }
            $arr[] = [
                'id' => $value->id,
                'name' => $value->name ?? '',
                'list' => $arrCity,
            ];
        }
        
        $response = [
            'data' => $arr,
            'status' => true,
            'message' => 'success',
        ];

        return response()->json($response);
    }
}
