<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MediaHistoryUser;
use App\Models\MediaHistory;
use App\Models\User;

class MediaHistoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/media/history",
     *     tags={"Media"},
     *     summary="Get all media",
     *     description="Multiple status values can be provided with comma separated string",
     *     operationId="mediaHistory",
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
    public function mediaHistory(){
        $medias = MediaHistory::select('id', 'url_small', 'url_big', 'created_at', 'updated_at')->get();
        $data = [];
        foreach($medias as $media){
            $data['id'][] = $media->id;
            if(isset($media->url_small)){
                $media->url_small = 'https://api.easygo.uz/storage/media/thumb/'.$media->url_small;
                $data['url_small'][] = $media->url_small;
            }
            if(isset($media->url_big)){
                $url_bigs = json_decode($media->url_big);
                foreach ($url_bigs as $url_big){
                    $url_big_array[] = 'https://api.easygo.uz/storage/media/'.$url_big;
                }
                $data['url_big'][] = $url_big_array;
                $url_big_array = [];
            }
            $data['created_at'][] = $media->id;
            $data['updated_at'][] = $media->id;
        }
        $response = [
            'data'=>$data,
            'status'=>true,
            'message'=>'Success',
        ];
        return response()->json($response);
    }
    /**
     * @OA\Get(
     *     path="/api/media/history/user",
     *     tags={"Media"},
     *     summary="get media history user",
     *     description="Multiple status values can be provided with comma separated string",
     *     operationId="getHistoryUser",
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
    public function getHistoryUser(){
        $media_users = MediaHistoryUser::select('id', 'user_id', 'media_history_id', 'created_at', 'updated_at')->get();
        $response = [
            'data'=>$media_users,
            'status'=>true,
            'message'=>'Success',
        ];
        return response()->json($response);
    }

    /**
     * @OA\Post(
     *     path="/api/media/history/user",
     *     tags={"Media"},
     *     summary="give user id and media history id",
     *     operationId="postHistoryUser",
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
     *                     property="user_id",
     *                     description="write user_id",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="media_history_id",
     *                     description="write your media_history_id",
     *                     type="integer",
     *                 )
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function postHistoryUser(Request $request){
        $media_user = new MediaHistoryUser();
        $user = User::find($request->user_id);
        $media = MediaHistory::find($request->media_history_id);
        if(isset($user) && isset($media)){
            $status = true;
            $message = 'Success';
            $media_user->user_id = $request->user_id;
            $media_user->media_history_id = $request->media_history_id;
            $media_user->save();
        }else{
            $status = false;
            $message = 'User or media not found';
        }
        $response = [
            'status'=>$status,
            'message'=>$message,
        ];
        return response()->json($response);
    }
}
