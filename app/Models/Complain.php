<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complain extends Model
{
    use HasFactory;

    protected $table = 'yy_complains';

    protected $fillable = [
        'order_id',
        'text',
        'type'  // 0 - clientga jaloba   1- haydovchiga jaloba
    ];
}
