<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'yy_companies';

    protected $fillable = [
        'name',
        'status_id',
        'address',
        'info'
    ];
}
