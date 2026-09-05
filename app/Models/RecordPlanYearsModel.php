<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordPlanYearsModel extends Model
{
       protected $table = 'record_plan_years';
    protected $fillable = [
        'student_id',
        'start_date',
        'min_juz_target',
        'min_from_surah',
        'min_from_ayah',
        'min_to_surah',
        'min_to_ayah',
        'ideal_juz_target',
        'ideal_from_surah',
        'ideal_from_ayah',
        'ideal_to_surah',
        'ideal_to_ayah',
        'insert_date',
        
    ];
}
