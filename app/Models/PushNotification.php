<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    use SoftDeletes;
    
    protected $table = 'notifications';

    protected $fillable = [
        'image',
        'title',
        'notification',
        'data',
        'status'
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
