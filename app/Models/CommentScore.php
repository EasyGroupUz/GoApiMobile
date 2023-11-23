<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

class CommentScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'yy_comment_scores';

    protected $fillable = [
        'client_id',
        'driver_id',
        'order_id',
        'type', // 1 - for driver, 0 - for client
        'date',
        'text',
        'score',
        'to_whom'
    ];

    public function driver()
    {
        return $this->hasOne(Driver::class, 'user_id', 'driver_id');
    }

    public function ToWhom(){
        return $this->hasOne(User::class, 'to_whom', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
