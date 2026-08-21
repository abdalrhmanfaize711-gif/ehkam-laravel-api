<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherModel extends Model
{
   protected $table = 'teachers';
   protected $fillable = [
    'user_id',
    'username',
    'password',
    'canSurpriseTestStudents'
   ];
public function user()
{
    return $this->belongsTo(User::class);
}
}
