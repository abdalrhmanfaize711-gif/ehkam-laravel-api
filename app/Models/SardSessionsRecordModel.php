<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SardSessionsRecordModel extends Model
{
    protected $table = 'sard_sessions_records';

   
    protected $fillable = [
        'student_id',
        'record_sard_day_id',
        'total_mistakes',
        'total_melodies',
        'hesitiations_state',
        'serd_day',
        'from_surah',
        'from_ayah',
        'to_surah',
        'to_ayah',
        'session_state',
        'session_number',
        'sard_date',
        'insert_date',
    ];
}