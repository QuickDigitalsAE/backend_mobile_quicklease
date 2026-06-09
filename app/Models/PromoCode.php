<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PromoCode extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'promo_codes';

    protected $fillable = [
        "page_id",
        "target_type",
        "promotion_id",
        "code_title",
        "code_type",
        "code_value",
        "code",
        "code_status",
        "expires_at",
        "created_by",
    ];
}
