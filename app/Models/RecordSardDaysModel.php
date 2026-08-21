<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordSardDaysModel extends Model
{
protected $table = 'record_sard_days';

protected $fillable = [
    'student_id',
    'num_of_session',
    'sard_record_id',
    'sard_day',
    'num_of_remaining_sheets',
];
}
