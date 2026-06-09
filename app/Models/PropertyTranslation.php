<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PropertyTranslation extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'property_translations';

    protected $fillable = [
        "field_values",
        "language",
        "property_id"
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
