<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Testimonial extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'testimonials';

    protected $fillable = [
        "client_id",
        "client_email",
        "client_phone",
        "client_image",
        "car_id",
        "stars",
        "approved_by"
    ];
    
    protected $casts = [
        'client_id' => 'integer',
        'car_id' => 'integer',
        'stars' => 'integer'
    ];
}
