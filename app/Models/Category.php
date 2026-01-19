<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
   
    protected $fillable = [
        'name',
        'slug',
        'description',
        'country_id',
        'status'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }
}
