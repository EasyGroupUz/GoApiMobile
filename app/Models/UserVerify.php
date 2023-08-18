<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserVerify extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'yy_user_verifies';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'user_id',
        'verify_code',
        'verify_date',
        'status_id',
    ];

    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }

}
