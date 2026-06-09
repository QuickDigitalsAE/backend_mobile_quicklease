<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebContentTranslation extends Model
{
    use HasFactory;
    
    protected $table = 'web_content_translations';

    protected $fillable = [
        "translated_value",
        "language",
        "web_content_id",
    ];

    public function webContent()
    {
        return $this->belongsTo(WebContent::class);
    }

    public function web_content()
    {
        return $this->belongsTo(WebContent::class, 'web_content_id');
    }
}
