<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarTypes extends Model
{
    use HasFactory;

    protected $table = 'yy_car_types';

    protected $fillable = [
        'id',
        'name',
        'status_id'
    ];
    
    public function status(){
        return $this->hasOne(Status::class, 'id', 'status_id');
    }

    public function carList(){
        return $this->hasMany(CarList::class, 'car_type_id', 'id');
    }
}
