<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class SidebarBanner extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    
    protected $table = 'sidebar_banners';

    protected $fillable = [
        "title",
        "image",
        "redirect_url",
        "sort_order",
        "language",
        "status"
    ];
    
}
