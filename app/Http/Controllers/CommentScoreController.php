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
     *     path="/api/comment/get-comments?driver_id=21",
     *     tags={"Users"},
     *     summary="Finds Pets by status",
     *     description="Multiple status values can be provided with comma separated string",
     *     operationId="getComments",
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
    public function getComments(Request $request)
    {
        $personal_info = '';
        $ratings_list = [];
        $comments_list = [];
        $comment = CommentScore::where('driver_id', $request->driver_id)->first();
        if(isset($comment)){
            $getComments = CommentScore::where('driver_id', $request->driver_id)->get();
            $comments = CommentScore::where('driver_id', $request->driver_id)->get()->groupBy('score');
            $average_score = 0;
            foreach ($comments as $comm){
                foreach ($comm as $com){
                    $average_score = $average_score + $com->score;
                    if(isset($com->score) || $com->score != 0){
                        $percent = 100*($com->score/5);
                    }else{
                        $percent = 0;
                    }
                    $ratings_list[] = [
                        "rating" => $com->score,
                        "percent" => $percent,
                        "comment_count" => count($comm)
                    ];
                }
            }
            $first_name = $comment->driver->personalInfo?$comment->driver->personalInfo->first_name.' ':'';
            $last_name = $comment->driver->personalInfo?strtoupper($comment->driver->personalInfo->last_name[0].'. '):'';
            $middle_name = $comment->driver->personalInfo?strtoupper($comment->driver->personalInfo->middle_name[0].'.'):'';
            $personal_info = [
                'img'=>$comment->driver->personalInfo?$comment->driver->personalInfo->avatar:'',
                'full_name'=>$first_name.''.strtoupper($last_name).''.strtoupper($middle_name),
                'rating'=>$average_score/count($comments),
                'comment_count'=>count($comments)
            ];
            foreach ($getComments as $getComment){
                $date = explode(" ", $getComment->date);
                $comments_list[] = [
                    "img" => $getComment->driver->personalInfo?$getComment->driver->personalInfo->avatar:'',
                    "full_name" => $first_name.''.strtoupper($last_name).''.strtoupper($middle_name),
                    "date" => $date[0],
                    "rating" => $getComment->score,
                    "comment" => $getComment->text
                ];
            }
            $status = true;
            $message = 'Success';
        }else{
            $status = false;
            $message = 'No comment';
        }
        $response = [
            'data'=>[
                'personal_info'=>$personal_info,
                'ratings_list'=>$ratings_list,
                'comments_list'=>$comments_list,
            ],
            'status'=>$status,
            'message'=>$message,
        ];
        return response()->json($response);
    }
}
