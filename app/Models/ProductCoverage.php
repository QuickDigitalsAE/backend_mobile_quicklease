<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ProductCoverage extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'product_coverages';

    protected $fillable = [
        "less_30_days_price",
        "more_30_days_price",
        "prices_by_locations",
        "field_required",
        "checked_by_default",
        "coverage_status",
        "countable_value",
        "per_day_price",
        "address_is_required",
        "vat_is_applicable",
        "created_by",
    ];
}
