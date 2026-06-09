<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ProductTranslation extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'product_translations';

    protected $fillable = [
        "field_values",
        "language",
        "product_id"
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
