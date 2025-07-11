<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requestt extends Model
{
    protected $table = 'requests';
    protected $fillable = [
        'office_id',
        'requestable_id',
        'requestable_type',
        'status',
    ];

    public function requestable()
    {
        return $this->morphTo();
    }
}
