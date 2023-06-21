<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    // use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'chat';
    protected $fillable = [
        'user_from_id',
        'user_to_id',
        'deal_id',
        'text'
    ];

    public function userTo()
    {
        return $this->belongsTo(User::class, 'user_to_id', 'id');
    }

}
