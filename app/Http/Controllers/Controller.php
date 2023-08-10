<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;

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
            'data' => $data,
            'status' => false,
            'message' => $message ?? 'error occured'
        ], $error_type);
    }

    public function success(string $message, int $error_type, array $data = null)
    {
        return response()->json([
            'data' => $data,
            'status' => true,
            'message' => $message ?? 'success'
        ], $error_type);
    }

    public function validateByToken($request)
    {
        $token = $request->header()['token'];
        $user = User::where('token', $token)->first();

        return $user;
    }
}
