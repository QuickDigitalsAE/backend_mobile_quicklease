<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'user_name', 'model_type', 'table_name', 'changes', 'action'
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}
