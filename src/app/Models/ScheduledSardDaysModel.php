<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SerdSchedulesModel;
class ScheduledSardDaysModel extends Model
{
     protected $table = 'scheduled_sard_days';

    protected $fillable = [

        'student_id',

        'serd_schedule_id',

        'sard_day',

        'teacher_id',

        'num_of_sessions',

        'from_surah',
        'from_ayah',

        'to_surah',
        'to_ayah',

        'time_assigned',

        'sard_date',

        'insert_date'

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function schedule()
    {
        return $this->belongsTo(
            SerdSchedulesModel::class,
            'serd_schedule_id'
        );
    }

    public function student()
    {
        return $this->belongsTo(
            StudentModel::class,
            'student_id'
        );
    }

    public function teacher()
    {
        return $this->belongsTo(
            TeacherModel::class,
            'teacher_id'
        );
    }
}
