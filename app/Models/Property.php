<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'owner_type',
        'ad_number',
        'title',
        'description',
        'price',
        'location',
        'latitude',
        'longitude',
        'area',
        'floor_number',
        'ad_type',
        'type',
        'status',
        'is_offer',
        'offer_expires_at',
        'currency',
        'views',
        'bathrooms',
        'rooms',
        'seller_type',
        'direction',
        'furnishing',
        'features',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo(User::class);
    }
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function video(): MorphOne
    {
        return $this->morphOne(Video::class, 'videoable');
    }


    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoriteable');
    }
    public function requests()
    {
        return $this->morphMany(Request::class, 'requestable');
    }
    public function propertyPayments()
    {
        return $this->hasMany(PropertyPayment::class);
    }
}
