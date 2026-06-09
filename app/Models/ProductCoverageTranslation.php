<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ProductCoverageTranslation extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'product_coverage_translations';

    protected $fillable = [
        "field_values",
        "language",
        "coverage_id"
    ];

    public function  product_coverage()
    {
        return $this->belongsTo(ProductCoverage::class, 'coverage_id');
    }
}
