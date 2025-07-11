<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;


class Office extends  Authenticatable implements JWTSubject
{
    protected $fillable = [
        'name',
        'phone',
        'password', // ✅ تأكد أنه موجود
        'description',
        'location',
        'document_path',
        'status',
        'free_ads',
        'followers_count',
        'views',
    ];
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoriteable');
    }
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now());
    }
    public function requests()
    {
        return $this->hasMany(Requestt::class, 'office_id');
    }





    public function properties(): MorphMany
    {
        return $this->morphMany(Property::class, 'owner');
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
    public function wantedProperties()
    {
        return $this->morphMany(WantedProperty::class, 'wanted_Pable');
    }
    public function followers()
    {
        return $this->belongsToMany(User::class, 'office_followers', 'office_id', 'user_id');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    public function pendingProperties()
    {
        return $this->properties()->where('status', 'pending');
    }
}
