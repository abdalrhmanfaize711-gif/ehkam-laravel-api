<?php

namespace App\Models;

use App\Models\HalaqatModel;
use App\Models\User;
use App\Models\SerdSchedulesModel;
use App\Models\ScheduledSardDaysModel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class StudentModel extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'students';

    protected $fillable = [
        'user_id',
        'halaqa_id',
        'tassheh_halaqa_id',
        'stage',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function halaqa()
    {
        return $this->belongsTo(
            HalaqatModel::class,
            'halaqa_id',
            'id'
        );
    }

    public function serdSchedules()
    {
        return $this->hasMany(
            SerdSchedulesModel::class,
            'student_id'
        );
    }

    public function scheduledDays()
    {
        return $this->hasMany(
            ScheduledSardDaysModel::class,
            'student_id'
        );
    }
}