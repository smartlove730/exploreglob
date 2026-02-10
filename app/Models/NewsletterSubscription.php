<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscription extends Model
{
    protected $fillable = [
        'name',
        'email',
        'ip_address',
        'user_agent',
        'blog_id',
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
