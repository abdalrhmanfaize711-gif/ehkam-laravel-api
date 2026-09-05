<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledExamsModel extends Model
{
    protected $table = 'scheduled_exams';
    protected $fillable = [
        'student_id',
        'teacher_id',
        'start_date',
        'end_date',
        'num_of_juz',
        'from_surah',
        'from_ayah',
        'to_surah',
        'to_ayah',
        'num_of_questions'
    ];
}
