<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Category;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sitemap.xml for the website.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $urls = collect([
            [
                'loc' => URL::to('/'),
                'lastmod' => now(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            ['loc' => URL::to('/categories'), 'lastmod' => now(), 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => URL::to('/about'), 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => URL::to('/contact'), 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => URL::to('/privacy-policy'), 'lastmod' => now(), 'changefreq' => 'yearly', 'priority' => '0.4'],
            ['loc' => URL::to('/terms'), 'lastmod' => now(), 'changefreq' => 'yearly', 'priority' => '0.4'],
        ]);

        $categoryUrls = Category::query()
            ->where('status', 1)
            ->get(['slug', 'updated_at'])
            ->map(fn (Category $category) => [
                'loc' => URL::to('/category/' . $category->slug),
                'lastmod' => $category->updated_at,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ]);

        $blogUrls = Blog::query()
            ->where('status', 1)
            ->get(['slug', 'updated_at'])
            ->map(fn (Blog $blog) => [
                'loc' => URL::to('/blog/' . $blog->slug),
                'lastmod' => $blog->updated_at,
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ]);

        $allUrls = $urls->merge($categoryUrls)->merge($blogUrls);

        $xml = $this->buildSitemapXml($allUrls->all());
        $path = public_path('sitemap.xml');

        file_put_contents($path, $xml);

        $this->info("Sitemap generated successfully at {$path}");

        return self::SUCCESS;
    }

    /**
     * @param array<int, array{loc: string, lastmod: CarbonInterface|string|null, changefreq: string, priority: string}> $entries
     */
    private function buildSitemapXml(array $entries): string
    {
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($entries as $entry) {
            $lastmod = $entry['lastmod'] instanceof CarbonInterface
                ? $entry['lastmod']->toAtomString()
                : now()->toAtomString();

            $xml[] = '  <url>';
            $xml[] = '    <loc>' . e($entry['loc']) . '</loc>';
            $xml[] = '    <lastmod>' . $lastmod . '</lastmod>';
            $xml[] = '    <changefreq>' . $entry['changefreq'] . '</changefreq>';
            $xml[] = '    <priority>' . $entry['priority'] . '</priority>';
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode(PHP_EOL, $xml) . PHP_EOL;
    }
}
