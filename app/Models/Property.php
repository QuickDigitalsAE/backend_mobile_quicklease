<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Property extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'properties';

    protected $fillable = [
        "property_image",
        "type",
        "property_status",
        "property_field_type",
        "created_by",
    ];
}
