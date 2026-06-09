<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingNotification extends Model
{
    use HasFactory;
    
    protected $table = 'booking_notifications';

    protected $fillable = [
        "booking_id",
        "description",
        "booking_status"
    ];
}
