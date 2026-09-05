<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotsModel extends Model
{
    protected $table = 'nots';
    protected $fillable = [
        'text_nots',
        'teacher_id',
        'student_id'
    ];
}
