<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OurLocationsTranslation extends Model
{
    use HasFactory;
    
    protected $table = 'our_locations_translations';

    protected $fillable = [
        "field_values",
        "language",
        "location_id"
    ];
}
