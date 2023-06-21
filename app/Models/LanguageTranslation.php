<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LanguageTranslation extends Model
{
    use HasFactory;
    protected $table = 'yy_language_translations';

     protected $fillable = [
        'name',
        'language_id',
        'lang',

    ];
}
