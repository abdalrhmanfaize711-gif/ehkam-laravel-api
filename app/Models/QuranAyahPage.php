<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranAyahPage extends Model
{
    protected $table = 'quran_ayah_pages';

    protected $fillable = [
        'surah_number',
        'ayah_number',
        'page_number',
    ];

    protected $casts = [
        'surah_number' => 'integer',
        'ayah_number' => 'integer',
        'page_number' => 'integer',
    ];
}