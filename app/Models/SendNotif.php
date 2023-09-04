<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SendNotif extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'yy_send_notifications';

    protected $fillable = [
        'user_id',
        'entity_id',
        'entity_type',
        'title',
        'body',
        'largeIcon',
        'registration_ids'
    ];
}
