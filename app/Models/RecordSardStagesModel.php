<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordSardStagesModel extends Model
{
    protected $table = 'record_sard_stages';

    protected $fillable = [
        'student_id',
        'sard_type',
        'total_melodies',
        'total_mistakes',
        'hesitation_state',
        'memorization_state',
        'repeat_times',
        'sard_duration',
        'insert_date',
    ];
}