<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, HasApiTokens, SoftDeletes;
    
    protected $table = 'customers';
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'profile_image',
        'password',
        'is_active',
        'fcm_token'
    ];
    
    protected $casts = [
        'is_active' => 'int'
    ];
    
    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password'
    ];

}
