<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PopupBanner extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'popup_banners';

    protected $fillable = [
        'title',
        'status',
        'from_date',
        'to_date',
        'attachment',
        'redirect_link',
    ];

    protected $casts = [
        'status' => 'integer',
        'from_date' => 'date:Y-m-d',
        'to_date' => 'date:Y-m-d',
    ];
}
