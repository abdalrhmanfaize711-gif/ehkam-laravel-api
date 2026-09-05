<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AdminModel extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'admin';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'username',
        'password',
    ];
}