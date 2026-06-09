<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class BlogTranslation extends Model
{
    use HasFactory, LogsActivity;

    public function blog()
    {
        return $this->belongsTo(Blog::class, 'blog_id');
    }
}
