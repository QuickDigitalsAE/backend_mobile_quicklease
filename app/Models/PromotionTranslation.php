<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PromotionTranslation extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'promotion_translations';

    protected $fillable = [
        "field_values",
        "language",
        "promotion_id"
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }
}
