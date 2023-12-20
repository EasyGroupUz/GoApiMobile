<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarList extends Model
{
    use HasFactory;

    protected $table = 'yy_car_lists';

    protected $fillable = [
        'name',
        'status_id',
        'car_type_id'
    ];


    public function status()
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }

    public function type()
    {
        return $this->hasOne(CarTypes::class, 'id', 'car_type_id');
    }
}
