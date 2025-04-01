<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'image', 'description', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class);
    }
}
