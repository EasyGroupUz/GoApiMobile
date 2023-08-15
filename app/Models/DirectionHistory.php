<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectionHistory extends Model
{
    use HasFactory;

    protected $table = 'yy_direction_histories';

    protected $fillable = [
        'from_lng',
        'from_lat',
        'to_lng',
        'to_lat',
        'distance_text',
        'distance_value',
        'duration_text',
        'duration_value'
    ];
}
