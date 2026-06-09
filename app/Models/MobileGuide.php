<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MobileGuide extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'mobile_guides';

    protected $fillable = [
        "title",
        "description",
        "image",
        "button_text",
        "redirect_url",
        "status"
    ];

    protected $casts = [
        'status' => 'integer',
    ];
}
