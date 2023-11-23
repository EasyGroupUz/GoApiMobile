<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public $table = 'yy_notifications';

    protected $fillable = [
        'title',
        'text',
        'date',
        'read_at'
    ];
}
