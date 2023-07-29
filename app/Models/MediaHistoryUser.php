<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaHistoryUser extends Model
{
    use HasFactory;

    public $table='yy_media_history_for_users';

    protected $fillable = [
        'user_id',
        'media_history_id'
    ];

    public function media_history(){
        return $this->hasOne(MediaHistory::class, 'id', 'media_history_id');
    }
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
