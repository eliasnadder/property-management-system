<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyPayment extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
