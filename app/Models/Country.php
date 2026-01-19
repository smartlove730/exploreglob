<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'status'
    ];

    // Country has many categories
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    // Country has many blogs
    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }
}
