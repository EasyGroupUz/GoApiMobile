<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalInfo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'yy_personal_infos';

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'phone_number',
        'avatar',
        'gender',
        'birth_date',
        'email',
        'passport_serial_number',
        'passport_images',
        'passport_issued_by',
        'passport_expired_date',
        'phone_history'
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'personal_info_id');
    }

}
