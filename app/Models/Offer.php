<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'yy_offers';

    protected $fillable = [
        'driver_id',
        'client_id',
        'order_id',
        'order_detail_id',
        'price',
        'status',
        'comment',
    ];

//    public function driver()
//    {
//        return $this->hasOne(Driver::class, 'user_id', 'driver_id');
//    }

    public function client()
    {
        return $this->belongsTo(User::class, 'id', 'client_id');
    }
    
    public function driver()
    {
        return $this->belongsTo(User::class, 'id', 'driver_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }
}
