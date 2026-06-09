<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Promotion extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'promotions';

    protected $fillable = [
        "slug",
        "image",
        "banner_image",
        "promotion_status",
        "brand_logo",
        "car_ids",
        "schedule_date",
        "page_type",
        "created_by",
    ];
}
