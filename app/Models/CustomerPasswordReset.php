<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CustomerPasswordReset extends Model
{
    protected $table = 'customer_password_resets';
    
    protected $fillable = ['email', 'otp', 'expires_at'];
    
    public $timestamps = true;

    protected $dates = ['expires_at'];
    
    public function isExpired()
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }
}
