<?php

namespace App\Helpers;

class ImageOptimizer
{
    public static function optimize(?string $url, int $width = 1200, int $quality = 75): string
    {
        if (blank($url)) {
            return asset('images/default-category.webp');
        }

        if (str_starts_with($url, 'data:')) {
            return $url;
        }

        $parsed = parse_url($url);
        $host = strtolower($parsed['host'] ?? '');

        if (str_contains($host, 'pexels.com') || str_contains($host, 'images.pexels.com')) {
            return self::buildUrl($url, [
                'auto' => 'compress',
                'cs' => 'tinysrgb',
                'fit' => 'crop',
                'w' => $width,
                'fm' => 'webp',
                'q' => $quality,
            ]);
        }

        if (str_contains($host, 'unsplash.com')) {
            return self::buildUrl($url, [
                'auto' => 'format',
                'fit' => 'max',
                'w' => $width,
                'q' => $quality,
            ]);
        }

        if (str_contains($url, '/storage/') && preg_match('/\.(jpe?g|png)$/i', $url)) {
            $webpUrl = preg_replace('/\.(jpe?g|png)$/i', '.webp', $url);
            $publicPath = public_path(ltrim(parse_url($webpUrl, PHP_URL_PATH) ?? '', '/'));

            if (is_file($publicPath)) {
                return $webpUrl;
            }
        }

        return $url;
    }

    private static function buildUrl(string $url, array $params): string
    {
        $parts = parse_url($url);

        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query = array_merge($query, $params);
        $parts['query'] = http_build_query($query);

        return self::unparseUrl($parts);
    }

    private static function unparseUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $pass = ($user || $pass) ? $pass . '@' : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
    }
}
