<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'yy_users';
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'token',
        'balance',
        'personal_account',
        'type',
        'about_me',
        'company_id',
        'personal_info_id',
        'rating',
        'device_type',
        'device_id',
        'language',
        'created_at',
        'deleted_at',
    ];

    public function personalInfo()
    {
        return $this->hasOne(PersonalInfo::class, 'id', 'personal_info_id');
    }
    
    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function commentScores()
    {
        return $this->hasMany(CommentScore::class, 'driver_id', 'id')->where('type', 1);
    }
    
    public function userVerify()
    {
        return $this->hasOne(UserVerify::class, 'user_id', 'id');
    }

    public function driver()
    {
        return $this->hasOne(Driver::class, 'user_id', 'id');
    }
}
