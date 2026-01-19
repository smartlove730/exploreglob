<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Country;

class CategorySeeder extends Seeder
{
    public function run()
    {
       $categories = [
    'Technology',
    'Business',
    'Health',
    'Travel',
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
    'Travel Guides',
    'Finance Tips',
    'Investing & Crypto',
];


        foreach (Country::all() as $country) {
            foreach ($categories as $cat) {
                Category::create([
                    'name' => $cat,
                    'slug' => strtolower($cat) . '-' . strtolower($country->code),
                    'country_id' => $country->id,
                    'description' => "$cat related blogs in {$country->name}",
                ]);
            }
        }
    }
}
