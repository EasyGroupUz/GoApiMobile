<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarTypes extends Model
{
    use HasFactory;

    protected $table = 'yy_car_types';

    protected $fillable = [
        'name',
        'status'
    ];
    
    public function status(){
        return $this->hasOne(Status::class, 'id', 'status_id');
    }
}
