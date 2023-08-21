<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaHistory extends Model
{
    use HasFactory;

    public $table='yy_media_histories';

    protected $fillable = [
        'url_small',
        'url_big'
    ];

    public function mediaUser(){
        return $this->hasMany(MediaHistoryUser::class,'media_history_id','id');
    }
}
