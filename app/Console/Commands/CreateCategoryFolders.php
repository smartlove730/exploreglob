<?php

namespace App\Console\Commands;

use Illuminate\Console\Command; 
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use Illuminate\Support\Str;

class CreateCategoryFolders extends Command
{
    protected $signature = 'categories:create-folders';
    protected $description = 'Create folders for each unique category';

    public function handle()
    {
        // Fetch unique category names
        $categories = Category::select('name')->distinct()->get();

        foreach ($categories as $category) {

            // Safer folder name (slug)
            $folderName = $category->name;

            $folderPath = 'categories/' . $folderName;

            if (!Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->makeDirectory($folderPath);
                $this->info("Created: {$folderPath}");
            } else {
                $this->line("Exists: {$folderPath}");
            }
        }

        $this->info('Category folder sync completed.');
        return Command::SUCCESS;
    } 
}
