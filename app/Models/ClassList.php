<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassList extends Model
{
    use HasFactory;

    protected $table = 'yy_class_lists';

    protected $fillable = [
        'name',
        'status_id'
    ];
    
    public function status(){
        return $this->hasOne(Status::class, 'id', 'status_id');
    }
}
