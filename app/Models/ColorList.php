<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorList extends Model
{
    use HasFactory;

    protected $table = 'yy_color_lists';

    protected $fillable = [
        'name'
    ];
}
