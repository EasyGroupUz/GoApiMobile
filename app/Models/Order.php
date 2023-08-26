<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'yy_orders';

    protected $fillable = [
        'status_id',
        'driver_id',
        'car_id',
        'company_id',
        'from_id',
        'to_id',
        'price',
        'price_type',
        'title',
        'start_date',
        'seats',
        'options',
        'booking_place'
    ];

    public static function boot()
    {
        parent::boot();
     
        self::deleted(function($model) {
            $orderDetails = OrderDetail::where('order_id', $model->id)->first();

            if ($orderDetails)
                $orderDetails->delete();
        });
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id', 'id');
    }

    // public function carList(): BelongsTo
    // {
    //     return $this->belongsTo(CarList::class);
    // }

    public function car()
    {
        return $this->hasOne(Cars::class, 'id', 'car_id');
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function to(): BelongsTo
    {   
        return $this->belongsTo(City::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'order_id', 'id');
    }

    public function commentScores()
    {
        return $this->hasMany(CommentScore::class, 'order_id', 'id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

    // public function personalInfo(): BelongsTo
    // {
    //     return $this->belongsTo(PersonalInfo::class);
    // }

    // public function status(): HasOne
    // {
    //     return $this->hasOne(Status::class);
    // }
}
