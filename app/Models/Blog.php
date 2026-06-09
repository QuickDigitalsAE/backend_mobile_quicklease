<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Blog extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'blogs';

    protected $fillable = [
        "slug",
        "image",
        "created_by",
        "blog_status",
        "blog_schedule",
        "updated_by"
    ];

    public function translations()
    {
        return $this->hasMany(BlogTranslation::class, 'blog_id');
    }
}
