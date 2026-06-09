<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PartnerTranslation extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'partner_translations';

    protected $fillable = [
        "field_values",
        "language",
        "partner_id"
    ];
    
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
