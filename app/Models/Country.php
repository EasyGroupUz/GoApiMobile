<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'yy_countries';

    protected $fillable = [
        'code',
        'name',
        'status'
    ];
}
