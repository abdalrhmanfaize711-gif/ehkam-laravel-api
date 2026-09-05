<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoredExamsModel extends Model
{
    protected $table = 'record_exams';
    protected $fillable = [
        'student_id',
        'teacher_id',
        'num_of_questions',
        'total_mistakes',
        'total_melodies',
        'total_hesitiations',
        'final_percentage',
        'exam_type',
        'insert_date'
    ];
}
