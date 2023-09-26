<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\City;
use Illuminate\Support\Facades\DB;


class CountryController extends Controller
{
    public function index(Request $request)
    {

        $language = $request->header('language');

        $regions = DB::table('yy_cities as dt1')
                ->leftJoin('yy_city_translations as dt2', 'dt2.city_id', '=', 'dt1.id')
                ->where('dt1.type', 'region')
                ->where('dt2.lang', $language)
                ->select('dt1.id', 'dt2.name', 'dt1.created_at')
                ->orderBy('created_at', 'ASC')
                ->get();
                // ->first();
            // dd($regions);

        // DB::table('yy_city_translations')->where('order_id',$id)->exists()
        // $regions = City::select('id', 'name', 'type', 'parent_id')
        //     ->where('type', 'region')
        //     ->orderBy('created_at', 'ASC')
        //     ->get();

        if ($regions->isEmpty()) {
            return $this->success('Regions not found', 204);
        }

        $data = [];
        foreach ($regions as $region) {
            $cities = DB::table('yy_cities as dt1')
            ->leftJoin('yy_city_translations as dt2', 'dt2.city_id', '=', 'dt1.id')
            ->where('parent_id', $region->id)
            ->where('dt2.lang', $language)
            ->select('dt1.id', 'dt2.name', 'dt1.lng', 'dt1.lat')
            ->get();
            // $cities = City::select('id', 'name', 'lng', 'lat')
            //     ->where('parent_id', $region->id)
            //     ->get();

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
