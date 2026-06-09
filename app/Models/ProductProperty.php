<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ProductProperty extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'product_properties';

    protected $fillable = [
        "property_id",
        "product_id",
        "property_type",
        "property_values",
        "language",
    ];
}
