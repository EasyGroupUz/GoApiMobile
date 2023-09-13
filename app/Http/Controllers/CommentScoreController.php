<?php

namespace App\Http\Controllers;

use App\Models\CommentScore;
use App\Models\Order;
use App\Models\User;
use App\Models\PersonalInfo;
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
        $to_user = User::find($request->to_user_id);
        if(isset($request->to_user_id)){
            if(isset($to_user->deleted_at)){
                return $this->error(translate_api('This user was deleted', $language), 400);
            }
            if($user->id == $request->to_user_id){
                return $this->error(translate_api('It is your id. you cannot comment to yourself', $language), 400);
            }
            $is_driver = Driver::Select('id')->where('user_id', $to_user->id)->first();
            $you_driver = Driver::Select('id')->where('user_id', $user->id)->first();
            if(!isset($is_driver->id) && !isset($you_driver->id)){
                return $this->error(translate_api('you or to_user_id must be driver', $language), 400);
            }
            if(isset($to_user->id)){
                $driver = Driver::where('user_id', $to_user->id)->first();
                if(isset($request->score)){
                    $all_comments = CommentScore::select('score')->where('to_whom', $request->to_user_id)->get();
                    $all_score_sum = 0;
                    $count_comment = 0;
                    foreach($all_comments as $all_comment){
                        $count_comment = $count_comment +1;
                        $all_score_sum = $all_score_sum + $all_comment->score;
                    }
                    $to_user->rating = round(($all_score_sum + $request->score)*10/($count_comment+1))/10;
                }
                if(isset($driver->id)){
                    $comment->type = 1;
                    $comment->driver_id = $request->to_user_id;
                    $comment->client_id = $user->id;
                    $comment->to_whom = $request->to_user_id;
                }else{
                    $comment->type = 2;
                    $comment->client_id = $request->to_user_id;
                    $comment->to_whom = $request->to_user_id;
                    $comment->driver_id = $user->id;
                }
            }else{
                return $this->error(translate_api('To user id is not exist', $language), 400);
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
        $order = Order::find($request->order_id);
        if(!isset($order)){
            return $this->error(translate_api('Order is not exist', $language), 400);
        }
        $to_user->save();
        $comment->save();
        return $this->success('Success', 400, ["created_at" => date_format($comment->created_at, 'Y-m-d H:i:s')]);
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
        $user = User::where('id', $request->user_id)->first();
        if(!isset($user)){
            return $this->error(translate_api('This user is not exist', $language), 400);
        }
        $personal_info = null;
        $ratings_list = [];
        $comments_list = [];
        $comment = CommentScore::where('to_whom', $request->user_id)->first();
        if(isset($comment)){
            $getComments = CommentScore::where('to_whom', $request->user_id)->get();
            $comments = CommentScore::where('to_whom', $request->user_id)->get()->groupBy('score');
            $average_score = 0;
            $comment_count = 0;
            foreach ($comments as $key => $comm){
                if(isset($key) && $key>0){
                    foreach ($comm as $com){
                        $average_score = $average_score + $com->score;
                        $comment_count = $comment_count+1;
                        switch ($key){
                            case 1:
                                $ratings_list_1[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score??0,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            case 2:
                                $ratings_list_2[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score??0,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            case 3:
                                $ratings_list_3[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score??0,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            case 4:
                                $ratings_list_4[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score??0,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            case 5:
                                $ratings_list_5[] = [
                                    "id" => $com->id,
                                    "rating" => $com->score??0,
                                    "percent" => 100*count($comm)/count($getComments).' %',
                                    "comment_count" => count($comm)
                                ];
                                break;
                            default:
                        }

                    }
                }
            }
            if(isset($to_user->personalInfo)){
                if(isset($to_user->personalInfo->first_name)){
                    $first_name = $to_user->personalInfo->first_name.' ';
                }else{
                    $first_name = '';
                }
                if(isset($to_user->driver->doc_status)){
                    switch ($to_user->driver->doc_status){
                        case 1:
                            $doc_status = "Not accepted";
                            break;
                        case 2:
                            $doc_status = "Accepted";
                            break;
                        case 3:
                            $doc_status = "Expectations";
                            break;
                        case 4:
                            $doc_status = "Cancelled";
                            break;
                        default;
                    }
                }else{
                    $doc_status = "Not accepted";
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
                        $img_ = null;
                    }
                }else{
                    $img_ = null;
                }
                $full_name = $first_name.''.mb_strtoupper($last_name).''.mb_strtoupper($middle_name);
            }else{
                $img_ = null;
                $full_name = null;
            }
            if($full_name == ''){
                $full_name = null;
            }
            $personal_info = [
                'user_token'=>$to_user->token??null,
                'img'=>$img_,
                'full_name'=>$full_name,
//                'doc_status'=>$doc_status??null,
                'rating'=>round($average_score*10/$comment_count)/10,
                'comment_count'=>$comment_count
            ];
            foreach ($getComments as $getComment){
                if($getComment->to_whom == $getComment->client_id){
                    $from_user = User::withTrashed()->where('id', $getComment->driver_id)->first();
                }else{
                    $from_user = User::withTrashed()->where('id', $getComment->client_id)->first();
                }
                $all_personal_info = PersonalInfo::withTrashed()->find($from_user->personal_info_id);
                if(isset($all_personal_info->id)){
                    if(isset($all_personal_info->first_name)){
                        $user_first_name = $all_personal_info->first_name.' ';
                    }else{
                        $user_first_name = '';
                    }
                    if(isset($all_personal_info->last_name)){
                        $user_last_name = mb_strtoupper($all_personal_info->last_name[0].'. ');
                    }else{
                        $user_last_name = '';
                    }
                    if(isset($all_personal_info->middle_name)){
                        $user_middle_name = mb_strtoupper($all_personal_info->middle_name[0].'.');
                    }else{
                        $user_middle_name = '';
                    }
                    if(isset($all_personal_info->avatar) && $all_personal_info->avatar != ''){
                        $avatar = storage_path('app/public/avatar/'.$all_personal_info->avatar??'no');
                        if(file_exists($avatar)){
                            $user_img = asset('storage/avatar/'.$all_personal_info->avatar);
                        }else{
                            $user_img = null;
                        }
                    }else{
                        $user_img = null;
                    }
                    $user_full_name = $user_first_name.''.mb_strtoupper($user_last_name).''.mb_strtoupper($user_middle_name);
                }else{
                    $user_img = null;
                    $user_full_name = null;
                }
                if($user_full_name == ''){
                    $user_full_name = null;
                }
                $date = explode(" ", $getComment->date);
                if(!isset($all_personal_info->id)){
                    $comments_list[] = [
                        "user"=>'deleted',
                        "date" => $date[0]??null,
                        "rating" => $getComment->score??0,
                        "comment" => $getComment->text??null,
                        "created_at" => date_format($getComment->created_at, 'Y-m-d H:i:s')
                    ];
                }else{
                    $comments_list[] = [
                        'id'=>$from_user->id??null,
                        "img" => $user_img,
                        "full_name" => $user_full_name,
                        "date" => $date[0]??null,
                        "rating" => $getComment->score??0,
                        "comment" => $getComment->text??null,
                        "created_at" => date_format($getComment->created_at, 'Y-m-d H:i:s')
                    ];
                }
            }
            return $this->success('Success', 400, [
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
                if(isset($to_user->driver->doc_status)){
                    switch ($to_user->driver->doc_status){
                        case 1:
                            $doc_status = "Not accepted";
                            break;
                        case 2:
                            $doc_status = "Accepted";
                            break;
                        case 3:
                            $doc_status = "Expectations";
                            break;
                        case 4:
                            $doc_status = "Cancelled";
                            break;
                        default;
                    }
                }else{
                    $doc_status = "Not accepted";
                }
                if(isset($user->personalInfo->avatar) && $user->personalInfo->avatar != ''){
                    $avatar = storage_path('app/public/avatar/'.$user->personalInfo->avatar??'no');
                    if(file_exists($avatar)){
                        $img_ = asset('storage/avatar/'.$user->personalInfo->avatar);
                    }else{
                        $img_ = null;
                    }
                }else{
                    $img_ = null;
                }
                $full_name = $first_name.''.mb_strtoupper($last_name).''.mb_strtoupper($middle_name);
                if($full_name == ''){
                    $full_name = null;
                }
                $personal_info = [
                    'user_token'=>$user->token??null,
                    'img'=>$img_,
                    'full_name'=>$full_name,
//                    'doc_status'=>$doc_status??null,
                    'rating'=>4.5,
                    'comment_count'=> 0
                ];
            }else{
                $personal_info = [];
            }
            return $this->success(translate_api('No comment', $language), 400, [
                'personal_info'=>$personal_info,
                'ratings_list'=> [],
                'comments_list'=> [],
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
