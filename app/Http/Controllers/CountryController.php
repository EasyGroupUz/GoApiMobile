<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\City;

class CountryController extends Controller
{
    public function index()
    {
        $regions = City::select('id', 'name', 'type', 'parent_id')
            ->where('type', 'region')
            ->orderBy('created_at', 'ASC')
            ->get();

        if ($regions->isEmpty()) {
            return $this->success('Regions not found', 204);
        }

        $data = [];
        foreach ($regions as $region) {
            $cities = City::select('id', 'name', 'lng', 'lat')
                ->where('parent_id', $region->id)
                ->get();

            $cityData = [];
            foreach ($cities as $city) {
                $cityData[] = [
                    'id' => $city->id,
                    'name' => $city->name,
                    'lng' => $city->lng,
                    'lat' => $city->lat
                ];
            }

            $data[] = [
                'id' => $region->id,
                'name' => $region->name ?? '',
                'list' => $cityData,
            ];
        }

        return $this->success('Success', 200, $data);
    }
    
    // public function index()
    // {
    //     $model = City::select('id', 'name', 'type', 'parent_id')->where('type', 'region')->get();

    //     $arr = [];
    //     if (isset($model) && count($model) > 0) {
    //         foreach ($model as $value) {
    //             $cities = City::select('id', 'name', 'type', 'parent_id')->where('parent_id', $value->id)->get();
                
    //             if (!(isset($cities) && count($cities) > 0))
    //                 return $this->success('Cities not found', 204);

    //             $arrCity = [];
    //             foreach ($cities as $valueCity) {
    //                 $arrCity[] = [
    //                     'id' => $valueCity->id,
    //                     'name' => $valueCity->name,
    //                     'lng' => $valueCity->lng,
    //                     'lat' => $valueCity->lat
    //                 ];
    //             }
                
    //             $arr[] = [
    //                 'id' => $value->id,
    //                 'name' => $value->name ?? '',
    //                 'list' => $arrCity,
    //             ];
    //         }

    //         return $this->success('success', 200, $arr);
    //     } else {
    //         return $this->success('Cities not found', 204);
    //     }
        
    //     // $response = [
    //     //     'data' => $arr,
    //     //     'status' => true,
    //     //     'message' => 'success',
    //     // ];

    //     // return response()->json($response);
    // }
}
