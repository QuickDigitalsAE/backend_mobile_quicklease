<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasFactory;
    
    protected $table = 'enquiries';

    protected $fillable = [
        "company_name",
        "car_name",
        "client_name",
        "client_last_name",
        "client_phone",
        "client_email",
        "from_datetime",
        "to_datetime",
        "form_type",
        "referer_page_slug",
        "country",
        "city",
        "form_status",
        "period",
        "lease_to_own",
        "client_comments",
        "language"
    ];
}
