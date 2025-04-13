<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Reviews;

class Property extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price_per_night', 'user_id', 'property_type', 'number_of_rooms', 'city', 'municipality', 'district', 'status', 'slug', 'features'];

    public function images()
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function firstImage()
    {
        return $this->images()->orderBy('id')->first(); // Récupère la première image
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Reviews::class);
    }

    protected $casts = [
        'features' => 'array',
    ];
}
