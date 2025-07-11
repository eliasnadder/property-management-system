<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'requests';


    public function requestable()
    {
        return $this->morphTo();
    }
}
