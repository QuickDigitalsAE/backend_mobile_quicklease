<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestForm extends Model
{
    use HasFactory;
    
    protected $table = 'request_forms';

    protected $fillable = [
        "client_name",
        "client_contract_number",
        "client_email",
        "service_name",
        "message"
    ];
}
