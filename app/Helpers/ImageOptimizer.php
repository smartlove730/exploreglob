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

        if ($parsed === false) {
            return $url;
        }

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

        if (self::isStorageImage($url)) {
            $webpUrl = self::buildStorageWebpUrl($url);

            if (!blank($webpUrl)) {
                $webpPath = self::publicPathFromUrl($webpUrl);

                if (!blank($webpPath) && is_file($webpPath)) {
                    return $webpUrl;
                }

                $sourcePath = self::publicPathFromUrl($url);

                if (self::createWebpFromSource($sourcePath, $webpPath, $quality)) {
                    return $webpUrl;
                }
            }
        }

        return $url;
    }

    private static function isStorageImage(string $url): bool
    {
        return str_contains($url, '/storage/') && preg_match('/\.(jpe?g|png)(\?.*)?$/i', $url) === 1;
    }

    private static function buildStorageWebpUrl(string $url): ?string
    {
        return preg_replace('/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $url);
    }

    private static function publicPathFromUrl(string $url): ?string
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            return null;
        }

        $path = $parsed['path'] ?? null;
        if (blank($path)) {
            return null;
        }

        $normalizedPath = rawurldecode(ltrim($path, '/'));

        return public_path($normalizedPath);
    }

    private static function createWebpFromSource(?string $sourcePath, ?string $webpPath, int $quality): bool
    {
        if (blank($sourcePath) || blank($webpPath) || !is_file($sourcePath)) {
            return false;
        }

        if (!extension_loaded('gd')) {
            return false;
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        $sourceImage = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
            'png' => @imagecreatefrompng($sourcePath),
            default => false,
        };

        if ($sourceImage === false) {
            return false;
        }

        $destinationDirectory = dirname($webpPath);
        if (!is_dir($destinationDirectory)) {
            @mkdir($destinationDirectory, 0755, true);
        }

        $quality = max(30, min(90, $quality));
        $written = @imagewebp($sourceImage, $webpPath, $quality);

        imagedestroy($sourceImage);

        return $written && is_file($webpPath);
    }

    private static function buildUrl(string $url, array $params): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

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
