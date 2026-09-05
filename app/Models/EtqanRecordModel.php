<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtqanRecordModel extends Model
{
    protected $table = 'etqan_record';
    protected $fillable = [
        'student_id',
        'num_of_sheets',
        'from_surah',
        'from_ayah',
        'to_surah',
        'to_ayah',
        'memorization_state',
        'general_revision',
        'addition_date',
    ];
}
