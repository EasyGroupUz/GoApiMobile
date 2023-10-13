<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MediaHistoryUser;
use App\Models\MediaHistory;
use App\Models\PersonalInfo;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
    public function mediaHistory()
    {
        $medias = MediaHistory::select('id', 'url_small')->where('is_read', MediaHistory::IS_NOT_READ)->get();
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
            if(isset($media->mediaUser)){
                foreach ($media->mediaUser as $media_user){
                    $user = User::withTrashed()->find($media_user->user_id);
                    $personal_info = PersonalInfo::withTrashed()->find($user->personal_info_id);
                    if(isset($personal_info->first_name)){
                        $first_name = $personal_info->first_name.' ';
                    }else{
                        $first_name = '';
                    }
                    if(isset($personal_info->last_name)){
                        $last_name = strtoupper($personal_info->last_name[0].'. ');
                    }else{
                        $last_name = '';
                    }
                    if(isset($personal_info->middle_name)){
                        $middle_name = strtoupper($personal_info->middle_name[0].'.');
                    }else{
                        $middle_name = '';
                    }
                    if(isset($personal_info->avatar) && $personal_info->avatar != ''){
                        $avatar = storage_path('app/public/avatar/'.$personal_info->avatar??'no');
                        if(file_exists($avatar)){
                            $img_ = asset('storage/avatar/'.$personal_info->avatar);
                        }else{
                            $img_ = null;
                        }
                    }else{
                        $img_ = null;
                    }
                    if($first_name.''.strtoupper($last_name).''.strtoupper($middle_name) != ''){
                        $full_name = $first_name.''.strtoupper($last_name).''.strtoupper($middle_name);
                    }else{
                        $full_name = null;
                    }
                    if(isset($personal_info->deleted_at)) {
                        $mediaUser[] = [
                            'id' => $media_user->id,
                            'full_name' => $full_name,
                            'status'=>'deleted'
                        ];
                    }else{
                        $mediaUser[] = [
                            'id' => $media_user->id,
                            'full_name' => $full_name,
                        ];
                    }
                }
            }
            $data[] = [
                'id' =>$media->id,
                'url_small'=>$url_small??null,
                'url_big'=>$url_big_array??null,
                'is_read'=>$media->is_read??null,
                'count_user'=>count($media->mediaUser),
                'media_user'=>$mediaUser??[],
                'expire_date'=>$media->expire_date??null,
                'created_at'=>$media->created_at??null,
                'updated_at'=>$media->updated_at??null,
            ];
        }
        if($data != null){
            return $this->success('Success', 200, $data);
        }else{
            return $this->error('No media history', 400);
        }
    }


    public function getMedia()
    {
        $model = DB::table('yy_media_histories as dt1')
            ->leftJoin('yy_media_history_for_users as dt2', function($join) {
                $join->on('dt1.id', '=', 'dt2.media_history_id')
                    ->where('dt2.user_id', '=', auth()->user()->id);
            })
            ->select('dt1.id', 'dt1.url_small as media', 'dt1.created_at', 'dt2.id as for_user_id')
            ->orderByDesc('dt2.id')
            ->orderByDesc('dt1.created_at')
            ->whereNull('dt1.created_at')
            ->get()
            ->toArray();

        return $this->success('success', 200, $model);
    }

    public function getMediaDetail(Request $request)
    {
        $model = MediaHistory::where('id', $request->id)->select('id', 'url_big')->first();
        $responce = [];
        if ($model) {
            $details = json_decode($model->url_big);
            $responce['id'] = $model->id;
            if (!empty($details)) {
                $i = 0;
                foreach ($details as $detail) {
                    $responce['details'][$i++] = 'http://admin.easygo.uz/storage/thumb/' . $detail;
                }
            }
        }

        return $this->success('success', 200, $responce);
    }

    public function getMediaHistory(Request $request){
        $media = MediaHistory::select('id', 'url_small', 'url_big', 'is_read', 'expire_date', 'created_at', 'updated_at')->find($request->id);
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
        if(isset($media->mediaUser)){
            foreach ($media->mediaUser as $media_user){
                $user = User::withTrashed()->find($media_user->user_id);
                $personal_info = PersonalInfo::withTrashed()->find($user->personal_info_id);
                if(isset($personal_info->first_name)){
                    $first_name = $personal_info->first_name.' ';
                }else{
                    $first_name = '';
                }
                if(isset($personal_info->last_name)){
                    $last_name = strtoupper($personal_info->last_name[0].'. ');
                }else{
                    $last_name = '';
                }
                if(isset($personal_info->middle_name)){
                    $middle_name = strtoupper($personal_info->middle_name[0].'.');
                }else{
                    $middle_name = '';
                }
                if(isset($personal_info->avatar) && $personal_info->avatar != ''){
                    $avatar = storage_path('app/public/avatar/'.$personal_info->avatar??'no');
                    if(file_exists($avatar)){
                        $img_ = asset('storage/avatar/'.$personal_info->avatar);
                    }else{
                        $img_ = null;
                    }
                }else{
                    $img_ = null;
                }
                if($first_name.''.strtoupper($last_name).''.strtoupper($middle_name) != ''){
                    $full_name = $first_name.''.strtoupper($last_name).''.strtoupper($middle_name);
                }else{
                    $full_name = null;
                }
                if(isset($personal_info->deleted_at)) {
                    $mediaUser[] = [
                        'id' => $media_user->id,
                        'full_name' => $full_name,
                        'status'=>'deleted'
                    ];
                }else{
                    $mediaUser[] = [
                        'id' => $media_user->id,
                        'full_name' => $full_name,
                    ];
                }
            }
        }
        $data = [
            'id' =>$media->id,
            'url_small'=>$url_small??null,
            'url_big'=>$url_big_array??[],
            'is_read'=>$media->is_read??null,
            'count_user'=>count($media->mediaUser),
            'media_user'=>$mediaUser??[],
            'expire_date'=>$media->expire_date??null,
            'created_at'=>$media->created_at??null,
            'updated_at'=>$media->updated_at??null,
        ];
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
        $user = User::find($request->user_id);
        $media = MediaHistory::find($request->media_history_id);
        $mediaUser = MediaHistoryUser::
            where('media_history_id', $request->media_history_id)
            ->where('user_id', $request->user_id)
            ->first();
        
        if (isset($mediaUser))
            return $this->success('Success', 201);

        if (isset($user->id) && isset($media->id)) {
            $media_user = new MediaHistoryUser();
            $media->is_read = 1;
            $media_user->user_id = $request->user_id;
            $media_user->media_history_id = $request->media_history_id;
            $media->save();
            $media_user->save();
            
            return $this->success('Success', 201);
        } elseif(!isset($user->id) && isset($media->id)) {
            return $this->error('User not found', 400);
        } elseif(!isset($media->id) && isset($user->id)) {
            return $this->error('Media not found', 400);
        } else {
            return $this->error('User and media not found', 400);
        }
    }
}
