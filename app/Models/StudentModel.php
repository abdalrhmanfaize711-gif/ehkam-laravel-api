<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HalaqatModel;
use App\Models\User;
use App\Models\SerdSchedulesModel;
class StudentModel extends Model
{
    protected $table = 'students';
    protected $fillable = [
        'user_id',
        'halaqa_id',
        'tassheh_halaqa_id',
        'stage'
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
