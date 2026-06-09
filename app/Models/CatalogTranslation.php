<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class CatalogTranslation extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'catalog_translations';

    protected $fillable = [
        "field_values",
        "language",
        "catalog_id"
    ];

    public function catalog()
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }
}
