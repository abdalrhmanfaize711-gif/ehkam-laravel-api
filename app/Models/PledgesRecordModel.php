<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PledgesRecordModel extends Model
{
     protected $table = 'pledges_record';
    protected $fillable = [
        'student_id',
        'pledge_type',
        'insert_date'
    ];
}
