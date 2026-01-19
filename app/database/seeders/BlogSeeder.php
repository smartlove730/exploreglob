<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Blog, Category, Tag};
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run()
    {
        $categories = Category::all();
        $tags = Tag::all();

        foreach ($categories as $category) {
            for ($i = 1; $i <= 8; $i++) {
                $blog = Blog::create([
                    'title' => "Sample Blog {$i} for {$category->name}",
                      'slug' => Str::slug($category->name) . '-' . uniqid(),
                    'content' => "<p>This is a sample blog content for {$category->name}. Generated for demo purpose.</p>",
                    'excerpt' => "This is a short excerpt for {$category->name}",
                    'category_id' => $category->id,
                    'country_id' => $category->country_id,
                    'status' => 1,
                    'published_at' => now(),
                ]);

                $blog->tags()->sync(
                    $tags->random(2)->pluck('id')->toArray()
                );
            }
        }
    }
}
