<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Catalog extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'catalogs';

    protected $fillable = [
        "parent_id",
        "slug",
        "banner_image",
        "brand_logo",
        "car_ids",
        "sec_one_slider_status",
        "sec_two_slider_status",
        "sec_three_slider_status",
        "catalog_status",
        "type",
        "new_style_page_type",
        "created_by",
        "updated_by"
    ];
}
