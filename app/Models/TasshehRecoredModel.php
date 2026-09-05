<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasshehRecoredModel extends Model
{
    protected $table = 'tassheh_record';
    protected $fillable = [
        'student_id',
        'halaqa_id',
        'Is_corrected',
        'insert_date'
    ];
}
