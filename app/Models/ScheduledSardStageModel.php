<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledSardStageModel extends Model
{
    protected $table = 'scheduled_sard_stage';

    protected $fillable = [
        'student_id',
        'teacher_id',
        'sard_type',
        'sard_date',
        'insert_date',
    ];
}