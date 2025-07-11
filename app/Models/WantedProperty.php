<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WantedProperty extends Model
{

    protected $fillable = [
        'wanted_Pable_id',
        'wanted_Pable_type',
        'buy_or_rent',
        'governorate',
        'area',
        'budget',
        'description',
    ];

    public function wantedPropertyable()
    {
        return $this->morphTo();
    }
    public function requests()
    {
        return $this->morphMany(Request::class, 'requestable');
    }
}
