<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_documents';

    protected $fillable = [
        'customer_id',
        'title',
        'status',
        'expiry_date',
        'attachment',
        'comment',
        'type',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'status' => 'integer',
        'expiry_date' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(Customer::class);
    }
}
