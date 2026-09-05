<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationsModel extends Model
{
      protected $table = 'notifications';
    protected $fillable = [
        'student_id',
        'halaqa_id',
        'title',
        'notification_time',
        'insert_date'
    ];
}
