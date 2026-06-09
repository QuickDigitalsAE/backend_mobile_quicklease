<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class GoogleReview extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    
    protected $table = 'google_reviews';

    protected $fillable = [
        "rating",
        "image",
        "redirect_url"
    ];
}
