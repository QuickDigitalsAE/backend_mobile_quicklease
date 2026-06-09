<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OurLocation extends Model
{
    use HasFactory;
    
    protected $table = 'our_locations';

    protected $fillable = [
        "slug",
        "banner_image",
        "car_ids",
        "location_status",
        "created_by"
    ];
}
