<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SardRecordsModel extends Model
{
    use HasFactory;

    protected $table = 'sard_records';

    protected $fillable = [
        'student_id',
        'teacher_id',
        'total_assigned_juz',
        'num_of_days',
        'serd_schedule_id',
        'insert_date',
    ];

    protected $casts = [
        'insert_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(TeacherModel::class, 'teacher_id');
    }
}