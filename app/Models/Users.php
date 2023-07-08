<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Users extends Model
{

    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'yy_staffs';
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'login',
        'password',
        'role_id',
        'token',
        'status_id',
        'company_id',
        'personal_info_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function personalInfo(){
        return $this->hasOne(PersonalInfo::class, 'id', 'personal_info_id');
    }
    public function role(){
        return $this->hasOne(Role::class, 'id', 'role_id');
    }
    public function company(){
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}
