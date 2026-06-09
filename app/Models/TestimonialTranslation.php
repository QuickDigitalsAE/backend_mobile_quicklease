<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class TestimonialTranslation extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'testimonial_translations';

    protected $fillable = [
        "field_values",
        "language",
        "testimonial_id"
    ];

    public function testimonial()
    {
        return $this->belongsTo(Testimonial::class, 'testimonial_id');
    }
}
