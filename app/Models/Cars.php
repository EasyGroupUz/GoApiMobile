<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cars extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'yy_cars';

    protected $fillable = [
        'car_list_id',
        'driver_id',
        'status_id',
        'reg_certificate',
        'reg_certificate_image',
        'images',
        'color_list_id',
        'class_list_id',
        'production_date',
        'wheel_side'  // 0 - left  1 - right
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(CarList::class, 'car_list_id', 'id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(ColorList::class, 'color_list_id', 'id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassList::class, 'class_list_id', 'id');
    }
    
    protected $casts = [
      'images'=>'array'
    ];
    public function carList(){
        return $this->hasOne(CarList::class, 'id', 'car_list_id');
    }
    public function driver(){
        return $this->hasOne(Drivers::class, 'id', 'driver_id');
    }
    public function status(){
        return $this->hasOne(Status::class, 'id', 'status_id');
    }
    public function colorList(){
        return $this->hasOne(ColorList::class, 'id', 'color_list_id');
    }
    public function classList(){
        return $this->hasOne(ClassList::class, 'id', 'class_list_id');
    }
}
