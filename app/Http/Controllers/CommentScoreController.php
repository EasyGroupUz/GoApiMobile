<?php

namespace App\Http\Controllers;

use App\Models\CommentScore;
use App\Models\Order;
use App\Models\User;
use App\Models\Driver;
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
        $language = $request->header('language');
        $user = Auth::user();
        $comment = new CommentScore();
        if(isset($request->client_id)){
            $comment->client_id = $request->client_id;
            $client = User::find($request->client_id);
            if(!isset($client->id)){
                return $this->error(translate_api('Client is not exist', $language), 400);
            }
        }
        if(isset($request->driver_id)){
            $comment->driver_id = $request->driver_id;
            $driver = User::find($request->driver_id);
            if(!isset($driver->id)){
                return $this->error(translate_api('Driver is not exist', $language), 400);
            }
        }
        if(isset($request->order_id)){
            $comment->order_id = $request->order_id;
            $order = Order::find($request->order_id);
            if(!isset($order->id)){
                return $this->error(translate_api('Order is not exist', $language), 400);
            }
        }
        if(isset($request->text)){
            $comment->text = $request->text;
        }
        if(isset($request->score)){
            $comment->score = $request->score;
        }
        $comment->date = date("Y-m-d");
        if($user->id == $request->client_id){
            $comment->to_whom = $request->driver_id;
            $comment->type = 1;
        }elseif($user->id == $request->driver_id){
            $comment->to_whom = $request->client_id;
            $comment->type = 0;
        }else{
            return $this->error(translate_api('one of client id or driver id must be yours', $language), 400);
        }
        $order = Order::find($request->order_id);
        if(!isset($order)){
            return $this->error(translate_api('Order is not exist', $language), 400);
        }
        $comment->save();
        return $this->success(translate_api('Success', $language), 400);
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
        $language = $request->header('language');
        $personal_info = '';
        $ratings_list = [];
        $comments_list = [];
        $comment = CommentScore::where('driver_id', $request->driver_id)->first();
        $driver = Driver::where('user_id', $request->driver_id)->first();
        if(isset($driver->id)){
            return $this->error(translate_api('Driver not found', $language), 400);
        }
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
                        "id" => $com->id,
                        "rating" => $com->score,
                        "percent" => $percent.' %',
                        "comment_count" => count($comm)
                    ];
                }
            }
            $first_name = $comment->driver->personalInfo?$comment->driver->personalInfo->first_name.' ':'';
            $last_name = $comment->driver->personalInfo?strtoupper($comment->driver->personalInfo->last_name[0].'. '):'';
            $middle_name = $comment->driver->personalInfo?strtoupper($comment->driver->personalInfo->middle_name[0].'.'):'';
            $personal_info = [
                'id'=>$comment->id,
                'img'=>$comment->driver->personalInfo?asset('storage/avatar/'.$comment->driver->personalInfo->avatar):'',
                'full_name'=>$first_name.''.strtoupper($last_name).''.strtoupper($middle_name),
                'rating'=>$average_score/count($comments),
                'comment_count'=>count($comments)
            ];
            foreach ($getComments as $getComment){
                $date = explode(" ", $getComment->date);
                $comments_list[] = [
                    'id'=>$getComment->id,
                    "img" => $getComment->driver->personalInfo?asset('storage/avatar/'.$getComment->driver->personalInfo->avatar):'',
                    "full_name" => $first_name.''.strtoupper($last_name).''.strtoupper($middle_name),
                    "date" => $date[0],
                    "rating" => $getComment->score,
                    "comment" => $getComment->text
                ];
            }
            return $this->success(translate_api('Success', $language), 400, [
                'personal_info'=>$personal_info,
                'ratings_list'=>$ratings_list,
                'comments_list'=>$comments_list,
            ]);
        }else{
            if(isset($driver->user->personalInfo)){
                $first_name = $driver->user->personalInfo->first_name?$driver->user->personalInfo->first_name.' ':'';
                $last_name = $driver->user->personalInfo->last_name?strtoupper($driver->user->personalInfo->last_name[0].'. '):'';
                $middle_name = $driver->user->personalInfo->middle_name?strtoupper($driver->user->personalInfo->middle_name[0].'.'):'';
                $image_driver = asset('storage/avatar/'.$driver->user->personalInfo->avatar);
                $personal_info = [
                    'id'=>$driver->user->personalInfo->id,
                    'img'=>$driver->user->personalInfo->avatar?asset('storage/avatar/'.$driver->user->personalInfo->avatar):'no image',
                    'full_name'=>$first_name.''.strtoupper($last_name).''.strtoupper($middle_name),
                    'rating'=>'no score',
                    'comment_count'=> 0
                ];
            }else{
                $personal_info = [];
            }
            return $this->success(translate_api('No comment', $language), 400, [
                'personal_info'=>$personal_info,
                'ratings_list'=> 0,
                'comments_list'=> 0,
            ]);
        }
    }
}
