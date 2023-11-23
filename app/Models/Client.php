<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    const MALE = 1;
    const FEMALE = 0;

    use HasFactory, SoftDeletes;

    protected $table = 'yy_clients';

    protected $fillable = [
        'personal_info_id',
        'status_id',
        'company_id'
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function personalInfo(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'client_id', 'id');
    }

    public function commentScores()
    {
        return $this->hasMany(CommentScore::class, 'client_id', 'id');
    }
}
