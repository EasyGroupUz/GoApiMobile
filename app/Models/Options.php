<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Options extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'yy_options';

    protected $fillable = [
        'name',
        'icon',
        'class_list_id',
    ];

    public function class_list(){
        return $this->hasOne(ClassList::class, 'id', 'class_list_id');
    }
}
