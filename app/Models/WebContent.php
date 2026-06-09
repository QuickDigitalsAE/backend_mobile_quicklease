<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class WebContent extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'web_contents';

    protected $fillable = [
        "slug",
        "banner",
        "car_ids",
        "sec_one_image",
        "sec_two_image",
        "sec_three_image",
        "sec_four_image",
        "sec_five_image",
        "sec_six_image",
        "sec_seven_image",
        "sec_eight_image",
        "created_by"
    ];


    public function translations()
    {
        return $this->hasMany(WebContentTranslation::class);
    }
}
