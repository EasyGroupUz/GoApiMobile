<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complain extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'yy_complains';

    protected $fillable = [
        'order_id',
        'order_detail_id',
        'complain_reason_id',
        'text',
        'complain_reason',
        'type',  // 0 - clientga jaloba   1- haydovchiga jaloba
    ];

    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
    
    public function orderDetail()
    {
        return $this->hasOne(OrderDetail::class, 'id', 'order_detail_id');
    }
    
    public function reason()
    {
        return $this->hasOne(ComplainReason::class, 'id', 'complain_reason_id');
    }
}
