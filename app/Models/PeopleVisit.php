<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    /**
     * Get people visit count by slug.
     *
     * @param string $slug
     * @return int
     */
    public static function getVisitCount(string $slug): int
    {
        return self::where('slug', $slug)
            ->whereDate('visit_datetime', Carbon::today())
            ->count();
    }
}
