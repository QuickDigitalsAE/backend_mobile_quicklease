<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Partner extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'partners';

    protected $fillable = [
        "slug",
        "image",
        "created_by",
        "updated_by",
        "partner_status"
    ];
}
