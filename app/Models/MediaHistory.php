<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaHistory extends Model
{
    const IS_READ = 1;
    const IS_NOT_READ = 0;

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
