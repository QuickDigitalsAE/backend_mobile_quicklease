<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeopleVisit extends Model
{
    use HasFactory;

    protected $table = 'people_visits';

    protected $fillable = [
        'slug',
        'visit_datetime',
    ];

    protected $casts = [
        'visit_datetime' => 'datetime',
    ];
}
