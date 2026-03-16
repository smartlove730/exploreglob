<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public const TRAVEL_NAME = 'Travel';

    public const TRAVEL_SUBCATEGORY_NAMES = [
        'Travel Guides',
        'Destinations',
        'Things To Do',
        'Travel Itineraries',
        'Budget Travel',
        'Travel Tips',
        'Adventure Travel',
        'Food & Culture',
        'Solo Travel',
        'Luxury Travel',
    ];

    public const TRAVEL_NAV_CATEGORY_NAMES = [
        'Travel Guides',
        'Destinations',
        'Things To Do',
        'Travel Itineraries',
        'Budget Travel',
        'Travel Tips',
    ];
   
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',  
        'country_id',
        'parent_id',
        'status'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public static function travelRoot(?int $countryId = null): ?self
    {
        return static::query()
            ->where('name', self::TRAVEL_NAME)
            ->when($countryId, fn (Builder $query) => $query->where('country_id', $countryId))
            ->first();
    }

    public function scopeTravelVisible(Builder $query, ?int $countryId = null): Builder
    {
        $travelRootIds = static::query()
            ->select('id')
            ->where('name', self::TRAVEL_NAME)
            ->when($countryId, fn (Builder $q) => $q->where('country_id', $countryId));

        return $query->where(function (Builder $q) use ($travelRootIds) {
            $q->whereIn('id', $travelRootIds)
                ->orWhereIn('parent_id', $travelRootIds);
        });
    }

    public function scopeTravelSubcategories(Builder $query, ?int $countryId = null): Builder
    {
        $travelRootIds = static::query()
            ->select('id')
            ->where('name', self::TRAVEL_NAME)
            ->when($countryId, fn (Builder $q) => $q->where('country_id', $countryId));

        return $query->whereIn('parent_id', $travelRootIds);
    }
}
