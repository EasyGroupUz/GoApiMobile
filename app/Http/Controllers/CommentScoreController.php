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
        return $this->success(translate_api('Success', $language), 400, ["created_at" => date_format($comment->created_at, 'Y-m-d H:i:s')]);
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
        date_default_timezone_set("Asia/Tashkent");
        $language = $request->header('language');
        $user = Auth::user();
        $personal_info = '';
        $ratings_list = [];
        $comments_list = [];
        $comment = CommentScore::where('to_whom', $request->user_id)->first();
        if(isset($comment)){
            $getComments = CommentScore::where('to_whom', $request->user_id)->get();
            $comments = CommentScore::where('to_whom', $request->user_id)->get()->groupBy('score');
            $average_score = 0;
            foreach ($comments as $key => $comm){
                if(isset($key) && $key>0){
                    foreach ($comm as $com){
                        $average_score = $average_score + $com->score;
                        switch ($key){
                            case 1:
                                $ratings_list_1[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            case 2:
                                $ratings_list_2[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            case 3:
                                $ratings_list_3[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            case 4:
                                $ratings_list_4[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            case 5:
                                $ratings_list_5[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            default:
                        }

                    }
                }
            }
            $to_user = User::find($comment->to_whom);
            if(isset($to_user->personalInfo)){
                if(isset($to_user->personalInfo->first_name)){
                    $first_name = $to_user->personalInfo->first_name.' ';
                }else{
                    $first_name = '';
                }
                if(isset($to_user->personalInfo->last_name)){
                    $last_name = mb_strtoupper($to_user->personalInfo->last_name[0].'. ');
                }else{
                    $last_name = '';
                }
                if(isset($to_user->personalInfo->middle_name)){
                    $middle_name = mb_strtoupper($to_user->personalInfo->middle_name[0].'.');
                }else{
                    $middle_name = '';
                }
                if(isset($to_user->personalInfo->avatar) && $to_user->personalInfo->avatar != ''){
                    $avatar = storage_path('app/public/avatar/'.$to_user->personalInfo->avatar??'no');
                    if(file_exists($avatar)){
                        $img_ = asset('storage/avatar/'.$to_user->personalInfo->avatar);
                    }else{
                        $img_ = '';
                    }
                }else{
                    $img_ = '';
                }
                $full_name = $first_name.''.mb_strtoupper($last_name).''.mb_strtoupper($middle_name);
            }else{
                $img_ = '';
                $full_name = '';
            }
            $personal_info = [
                'user_id'=>$to_user->id,
                'img'=>$img_,
                'full_name'=>$full_name,
                'rating'=>$average_score/count($comments),
                'comment_count'=>count($comments)
            ];
            foreach ($getComments as $getComment){
                if($getComment->to_whom == $getComment->client_id){
                    $from_user = User::find($getComment->driver_id);
                }else{
                    $from_user = User::find($getComment->client_id);
                }
                if(isset($from_user->personalInfo)){
                    if(isset($from_user->personalInfo->first_name)){
                        $user_first_name = $from_user->personalInfo->first_name.' ';
                    }else{
                        $user_first_name = '';
                    }
                    if(isset($from_user->personalInfo->last_name)){
                        $user_last_name = mb_strtoupper($from_user->personalInfo->last_name[0].'. ');
                    }else{
                        $user_last_name = '';
                    }
                    if(isset($from_user->personalInfo->middle_name)){
                        $user_middle_name = mb_strtoupper($from_user->personalInfo->middle_name[0].'.');
                    }else{
                        $user_middle_name = '';
                    }
                    if(isset($from_user->personalInfo->avatar) && $from_user->personalInfo->avatar != ''){
                        $avatar = storage_path('app/public/avatar/'.$from_user->personalInfo->avatar??'no');
                        if(file_exists($avatar)){
                            $user_img = asset('storage/avatar/'.$from_user->personalInfo->avatar);
                        }else{
                            $user_img = '';
                        }
                    }else{
                        $user_img = '';
                    }
                    $user_full_name = $user_first_name.''.mb_strtoupper($user_last_name).''.mb_strtoupper($user_middle_name);
                }else{
                    $user_img = '';
                    $user_full_name = '';
                }
                $date = explode(" ", $getComment->date);
                if(!isset($from_user)){
                    $comments_list[] = [
                        "user"=>'deleted',
                        "date" => $date[0],
                        "rating" => $getComment->score,
                        "comment" => $getComment->text,
                        "created_at" => date_format($getComment->created_at, 'Y-m-d H:i:s')
                    ];
                }else{
                    $comments_list[] = [
                        'id'=>$from_user->id,
                        "img" => $user_img,
                        "full_name" => $user_full_name,
                        "date" => $date[0],
                        "rating" => $getComment->score,
                        "comment" => $getComment->text,
                        "created_at" => date_format($getComment->created_at, 'Y-m-d H:i:s')
                    ];
                }
            }
            return $this->success(translate_api('Success', $language), 400, [
                'personal_info'=>$personal_info,
                'ratings_list_1'=>$ratings_list_1??[],
                'ratings_list_2'=>$ratings_list_2??[],
                'ratings_list_3'=>$ratings_list_3??[],
                'ratings_list_4'=>$ratings_list_4??[],
                'ratings_list_5'=>$ratings_list_5??[],
                'comments_list'=>$comments_list,
            ]);
        }else{
            if(isset($user->personalInfo)){
                if(isset($user->personalInfo->first_name)){
                    $first_name = $user->personalInfo->first_name.' ';
                }else{
                    $first_name = '';
                }
                if(isset($user->personalInfo->last_name)){
                    $last_name = mb_strtoupper($user->personalInfo->last_name[0].'. ');
                }else{
                    $last_name = '';
                }
                if(isset($user->personalInfo->middle_name)){
                    $middle_name = mb_strtoupper($user->personalInfo->middle_name[0].'.');
                }else{
                    $middle_name = '';
                }
                if(isset($user->personalInfo->avatar) && $user->personalInfo->avatar != ''){
                    $avatar = storage_path('app/public/avatar/'.$user->personalInfo->avatar??'no');
                    if(file_exists($avatar)){
                        $img_ = asset('storage/avatar/'.$user->personalInfo->avatar);
                    }else{
                        $img_ = '';
                    }
                }else{
                    $img_ = '';
                }
                $full_name = $first_name.''.mb_strtoupper($last_name).''.mb_strtoupper($middle_name);
                $personal_info = [
                    'id'=>$user->personalInfo->id,
                    'img'=>$img_,
                    'full_name'=>$full_name,
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

    public function getOrderUserId(Request $request)
    {
        $users = User::select('id')->get();
        $orders = Order::select('id')->get();
        return response()->json([
           'users' => $users,
           'orders' => $orders
        ]);
    }
}
