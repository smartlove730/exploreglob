<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $travelSubcategories = Category::TRAVEL_SUBCATEGORY_NAMES;

        $otherCategories = [
            'Technology',
            'Business',
            'Health',
            'Education',
            'Lifestyle',
            'Finance',
            'Digital Marketing',
            'Startups',
            'Entrepreneurship',
            'Artificial Intelligence',
            'Web Development',
            'Mobile Apps',
            'E-commerce',
            'Cyber Security',
            'Software Reviews',
            'Productivity',
            'Personal Development',
            'Fitness & Wellness',
            'Nutrition',
            'Fashion',
            'Beauty',
            'Food & Recipes',
            'Home & Living',
            'Real Estate',
            'Automobile',
            'Gaming',
            'Entertainment',
            'Movies & TV',
            'Music',
            'Photography',
            'Design & Creativity',
            'Social Media',
            'News & Trends',
            'Science',
            'Environment',
            'Politics',
            'Spirituality',
            'Career & Jobs',
            'Freelancing',
            'Remote Work',
            'Parenting',
            'Finance Tips',
            'Investing & Crypto',
        ];

        foreach (Country::all() as $country) {
            $travelRoot = Category::create([
                'name' => Category::TRAVEL_NAME,
                'slug' => Str::slug(Category::TRAVEL_NAME) . '-' . strtolower($country->code),
                'country_id' => $country->id,
                'description' => 'Travel related blogs in ' . $country->name,
                'parent_id' => null,
            ]);

            foreach ($travelSubcategories as $cat) {
                Category::create([
                    'name' => $cat,
                    'slug' => Str::slug($cat) . '-' . strtolower($country->code),
                    'country_id' => $country->id,
                    'description' => "{$cat} related blogs in {$country->name}",
                    'parent_id' => $travelRoot->id,
                ]);
            }

            foreach ($otherCategories as $cat) {
                Category::create([
                    'name' => $cat,
                    'slug' => Str::slug($cat) . '-' . strtolower($country->code),
                    'country_id' => $country->id,
                    'description' => "{$cat} related blogs in {$country->name}",
                    'parent_id' => null,
                ]);
            }
        }
    }
}
