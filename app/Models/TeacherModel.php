<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class TeacherModel extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'teachers';

    protected $fillable = [
        'user_id',
        'username',
        'password',
        'canSurpriseTestStudents',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}