<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'category_id',
        'country_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'published_at',
        'status'
    ];

     protected $casts = [
    'published_at' => 'datetime',
];

    // Blog belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Blog belongs to a country
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    // Blog has many tags
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    // SEO relation (optional)
    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'model');
    }

    public function scopeInTravelSubcategories(Builder $query, ?int $countryId = null): Builder
    {
        $travelRootIds = Category::query()
            ->select('id')
            ->where('name', Category::TRAVEL_NAME)
            ->when($countryId, fn (Builder $q) => $q->where('country_id', $countryId));

        $subcategoryIds = Category::query()
            ->select('id')
            ->whereIn('parent_id', $travelRootIds)
            ->when($countryId, fn (Builder $q) => $q->where('country_id', $countryId));

        return $query->whereIn('category_id', $subcategoryIds);
    }
}
