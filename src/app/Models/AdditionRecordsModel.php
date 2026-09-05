<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionRecordsModel extends Model
{
    protected $table = 'addition_records';
    protected $fillable = [
        'student_id',
        'num_of_pages',
        'from_surah',
        'to_surah',
        'from_ayah',
        'to_ayah',
        'insert_date',
        'repeated_times',
        'memorization_state',
        'addition_date',
        'general_revision',
        'daily_revision'                                 
    ];
}
