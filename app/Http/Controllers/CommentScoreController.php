<?php

namespace App\Http\Controllers;

use App\Models\CommentScore;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentScoreController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/comment/create",
     *     tags={"Users"},
     *     summary="Write your comment",
     *     operationId="commentCreate",
     *     @OA\Response(
     *         response=405,
     *         description="Invalid input"
     *     ),
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="client_id",
     *                     description="write your client id(select option)",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="driver_id",
     *                     description="write your driver id(select option)",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="order_id",
     *                     description="write your order id(select option)",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="text",
     *                     description="write your text message",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="score",
     *                     description="write your score",
     *                     type="integer",
     *                 ),
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function commentCreate(Request $request)
    {
        $user = Auth::user();
        $comment = new CommentScore();
        $comment->client_id = $request->client_id;
        $comment->driver_id = $request->driver_id;
        $comment->order_id = $request->order_id;
        $comment->date = date("Y-m-d");
        $comment->text = $request->text;
        $comment->score = $request->score;
        if($user->id == $request->client_id){
            $comment->to_whom = $request->driver_id;
            $comment->type = 1;
        }elseif($user->id == $request->driver_id){
            $comment->to_whom = $request->client_id;
            $comment->type = 0;
        }else{
            $response = [
                'status'=>false,
                'message'=> translate('Client or driver not exist')
            ];
            return response()->json($response);
        }
        $order = Order::find($request->order_id);
        if(!isset($order)){
            $response = [
                'status'=>false,
                'message'=> translate('Order is not exist')
            ];
            return response()->json($response);
        }
        $comment->save();
        $response = [
            'status'=>true,
            'message'=>'Success'
        ];
        return response()->json($response);
    }
    /**
     * @OA\Get(
     *     path="/api/comment/my-comments",
     *     tags={"Users"},
     *     summary="Finds Pets by status",
     *     description="Multiple status values can be provided with comma separated string",
     *     operationId="myComments",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status values that needed to be considered for filter",
     *         required=true,
     *         explode=true,
     *         @OA\Schema(
     *             default="available",
     *             type="string",
     *             enum={"available", "pending", "sold"},
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid status value"
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function myComments()
    {

        $comments = CommentScore::all();
        foreach ($comments as $comment){

            return response()->json($comment->client);
        }
        return response()->json('good');
    }


}
