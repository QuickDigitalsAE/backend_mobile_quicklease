<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ProductRelatedCoverage extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'product_related_coverages';

    protected $fillable = [
        "coverage_id",
        "product_id",
        "less_30_days_price",
        "more_30_days_price"
    ];
}
