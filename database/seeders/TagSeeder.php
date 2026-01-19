<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    public function run()
    {
        $tags = ['AI', 'Laravel', 'Startup', 'Marketing', 'SEO', 'Design'];

        foreach ($tags as $tag) {
            Tag::create([
                'name' => $tag,
                'slug' => strtolower($tag),
            ]);
        }
    }
}
