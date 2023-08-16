<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\DirectionHistory;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      x={
 *          "logo": {
 *              "url": "https://via.placeholder.com/190x90.png?text=L5-Swagger"
 *          }
 *      },
 *      title="L5 OpenApi",
 *      description="L5 Swagger OpenApi description",
 *      @OA\Contact(
 *          email="darius@matulionis.lt"
 *      ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="https://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 *  @OA\Server(
 *     description="Pitak",
 *     url="http://127.0.0.1:8000"
 * )
 */

class Controller extends BaseController
{

    public function __construct(){
        date_default_timezone_set("Asia/Tashkent");
    }

    use AuthorizesRequests, ValidatesRequests;

    public function error(string $message, int $error_type, array $data = null)
    {
        return response()->json([
            'data' => $data ?? [],
            'status' => false,
            'message' => $message ?? 'error occured'
        ], $error_type);
    }
    public function success(string $message, int $error_type, array $data = null)
    {
        return response()->json([
            'data' => $data ?? [],
            'status' => true,
            'message' => $message ?? 'success'
        ], 200); // $error_type
    }

    public function validateByToken($request)
    {
        $token = $request->header()['token'];
        $user = User::where('token', $token)->first();

        return $user;
    }

    public function getDistanceAndKm($fromLng, $fromLat, $toLng, $toLat)
    {
        $directionHistory = DB::table('yy_direction_histories')
            ->where(['from_lng' => $fromLng, 'from_lat' => $fromLat, 'to_lng' => $toLng, 'to_lat' => $toLat])
            ->first();

        if ($directionHistory) {
            return [
                'new' => false,
                'km' => $directionHistory->distance_text,
                'distance_value' => $directionHistory->distance_value,
                'time' => $directionHistory->duration_text,
                'duration_value' => $directionHistory->duration_value
            ];
        } else {
            $apiUrl = 'https://api.distancematrix.ai/maps/api/distancematrix/json?origins=' . $fromLng . ', ' . $fromLat . '&destinations=' . $toLng . ', ' . $toLat . '&key=7Q0lMsRgFBBSTgcFtBvQAMk3Qfe5O';

            $response = file_get_contents($apiUrl);

            if ($response !== false) {
                $data = json_decode($response, true);

                if ($data !== null && isset($data['rows'][0]['elements'][0]['distance']['text']) && isset($data['rows'][0]['elements'][0]['duration']['text'])) {
                    $dataElements = $data['rows'][0]['elements'][0];

                    $newDirectionHistory = new DirectionHistory();
                    $newDirectionHistory->from_lng = $fromLng;
                    $newDirectionHistory->from_lat = $fromLat;
                    $newDirectionHistory->to_lng = $toLng;
                    $newDirectionHistory->to_lat = $toLat;
                    $newDirectionHistory->distance_text = $dataElements['distance']['text'];
                    $newDirectionHistory->distance_value = $dataElements['distance']['value'];
                    $newDirectionHistory->duration_text = $dataElements['duration']['text'];
                    $newDirectionHistory->duration_value = $dataElements['duration']['value'];
                    $newDirectionHistory->save();

                    $newDirectionHistory = new DirectionHistory();
                    $newDirectionHistory->from_lng = $toLng;
                    $newDirectionHistory->from_lat = $toLat;
                    $newDirectionHistory->to_lng = $fromLng;
                    $newDirectionHistory->to_lat = $fromLat;
                    $newDirectionHistory->distance_text = $dataElements['distance']['text'];
                    $newDirectionHistory->distance_value = $dataElements['distance']['value'];
                    $newDirectionHistory->duration_text = $dataElements['duration']['text'];
                    $newDirectionHistory->duration_value = $dataElements['duration']['value'];
                    $newDirectionHistory->save();

                    return [
                        'new' => true,
                        'km' => $dataElements['distance']['text'],
                        'distance_value' => $dataElements['distance']['value'],
                        'time' => $dataElements['duration']['text'],
                        'duration_value' => $dataElements['duration']['value']
                    ];
                }
                return ['new' => true, 'km' => '0', 'distance_value' => '0', 'time' => '0', 'duration_value' => '0'];
            } else {
                return ['new' => true, 'km' => '0', 'distance_value' => '0', 'time' => '0', 'duration_value' => '0'];
            }
        }

    }
}
