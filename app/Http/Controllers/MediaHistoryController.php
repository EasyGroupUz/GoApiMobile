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
        $data = null;
        foreach($medias as $media){
            if(isset($media->url_small)){
                $media->url_small = 'http://admin.easygo.uz/storage/thumb/'.$media->url_small;
                $url_small = $media->url_small;
            }
            $url_big_array = [];
            if(isset($media->url_big)){
                $url_bigs = json_decode($media->url_big);
                foreach ($url_bigs as $url_big){
                    $url_big_array[] = 'http://admin.easygo.uz/storage/media/'.$url_big;
                }
            }
            $data[] = [
                'id' =>$media->id,
                'url_small'=>$url_small??'',
                'url_big'=>$url_big_array,
                'created_at'=>$media->created_at??'',
                'updated_at'=>$media->updated_at??'',
            ];
        }
        if($data != null){
            return $this->success('Success', 200, $data);
        }else{
            return $this->error('No media history', 400);
        }
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
        $media_users = MediaHistoryUser::select('id', 'user_id', 'media_history_id', 'created_at', 'updated_at')->get()->toArray();
        if(count($media_users)>0){
            return $this->success('Success', 200, $media_users);
        }else{
            return $this->error('No media history guest', 400);
        }
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
        if(isset($user->id) && isset($media->id)){
            $media_user->user_id = $request->user_id;
            $media_user->media_history_id = $request->media_history_id;
            $media_user->save();
            return $this->success('Success', 201);
        }elseif(!isset($user->id) && isset($media->id)){
            return $this->error('User not found', 400);
        }elseif(!isset($media->id) && isset($user->id)){
            return $this->error('Media not found', 400);
        }else{
            return $this->error('User and media not found', 400);
        }
    }
}
