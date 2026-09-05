<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HalaqatModel extends Model
{
    protected $table = 'halaqats';
    protected $fillable = [
        'max_students',
        'teacher_id',
        'insert_date',
        'halaqa_type'  
    ];
    public function user()
{
    return $this->belongsTo(TeacherModel::class);
}



}
