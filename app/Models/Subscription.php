<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'subscription_type',
        'status',
        'price',
        'starts_at',
        'expires_at',
    ];

    public function office()
    {
        return $this->belongsTo(\App\Models\Office::class);
    }
}
