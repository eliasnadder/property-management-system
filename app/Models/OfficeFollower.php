<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeFollower extends Model
{
    public function user()
    {
        // return $this->belongsTo(User::class,'user_id');
        return $this->belongsTo(User::class,);
    }
}
