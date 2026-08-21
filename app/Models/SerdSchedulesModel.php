<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerdSchedulesModel extends Model
{
    protected $table = 'serd_schedules';
    protected $fillable = [
        'student_id',
        'teacher_id',
        'total_assigned_juz',
        'insert_date',
        'num_of_days'
    ];

    public function student()
    {
        return $this->belongsTo(
            StudentModel::class,
            'student_id'
        );
    }

    public function days()
    {
        return $this->hasMany(
            ScheduledSardDaysModel::class,
            'serd_schedule_id'
        );
    }
}
