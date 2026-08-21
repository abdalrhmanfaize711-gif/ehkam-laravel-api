<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class AttendancesModel extends Model
{  
    protected $table = 'attendances';
    protected $fillable = [
        'user_id',
        'role',
        'attendance_state',
        'insert_date'
    ];
 public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
