<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'yy_cities';

    protected $fillable = [
        'id', 
        'name', 
        'country_id', 
        'type', 
        'parent_id', 
        'distance', 
        'inside_price', 
        'has_express', 
        'is_selected'
    ];
}
