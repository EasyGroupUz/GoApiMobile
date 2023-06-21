<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    const MALE = 1;
    const FEMALE = 0;
    
    use HasFactory, SoftDeletes;

    protected $table = 'yy_drivers';

    protected $fillable = [
        'personal_info_id',
        'serial_number',
        'issued_by',
        'passport_image',
        'passport_expired_date',
        'license_number',
        'license_expired_date',
        'license_image',
        'status_id',
        'balance'
    ];

    public function personalInfo(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function carLists(): BelongsTo
    {
        return $this->belongsTo(CarList::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'driver_id', 'id');
    }

    public function commentScores()
    {
        return $this->hasMany(CommentScore::class, 'driver_id', 'id');
    }

    public function cars()
    {
        return $this->hasMany(Cars::class, 'driver_id', 'id');
    }

    public function balanceHistory()
    {
        return $this->hasMany(BalanceHistory::class, 'user_id', 'id')->where('type', 1);
    }
}
