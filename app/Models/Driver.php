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
        'user_id',
        'status_id',
        'license_number',
        'license_expired_date',
        'license_image',
        'personal_account',
        'balance',
        'doc_status',  //1 Not accepted, 2 Accept, 3 Expectations, 4 Canceled
        'license_image_back',
        'from_admin',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $this->hasMany(Cars::class, 'driver_id', 'user_id');
    }

    public function balanceHistory()
    {
        return $this->hasMany(BalanceHistory::class, 'user_id', 'id')->where('type', 1);
    }
}
