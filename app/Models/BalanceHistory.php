<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'yy_balance_histories';

    protected $fillable = [
        'user_id',
        'type',
        'price',
        'transaction_id',
        'price_type',
    ];


}
