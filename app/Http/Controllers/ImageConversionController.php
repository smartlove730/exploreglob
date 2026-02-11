<?php

namespace App\Http\Controllers;

use FilesystemIterator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ImageConversionController extends Controller
{
    public function convertCategoryImages(Request $request): JsonResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $baseQuality = (int) $request->query('quality', 70);
        $baseQuality = max(1, min(100, $baseQuality));

        $targetRatio = (float) $request->query('target_ratio', 0.70);
        $targetRatio = max(0.1, min(1.0, $targetRatio));

        $deleteOriginal = (bool) ((int) $request->query('delete_original', 0));

        $baseDirectory = public_path('storage/categories');

        if (!is_dir($baseDirectory)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Directory not found: ' . $baseDirectory,
            ], 404);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp'];

        $results = [
            'status' => 'ok',
            'quality' => $baseQuality,
            'target_ratio' => $targetRatio,
            'base_directory' => $baseDirectory,
            'converted' => [],
            'skipped' => [],
            'failed' => [],
            'summary' => [],
        ];

        $beforeBytes = 0;
        $afterBytes = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDirectory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $sourcePath = $fileInfo->getPathname();
            $extension = strtolower($fileInfo->getExtension());

            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $sourceSize = $fileInfo->getSize() ?: 0;
            $beforeBytes += $sourceSize;

            if ($extension === 'webp') {
                $results['skipped'][] = [
                    'file' => $sourcePath,
                    'reason' => 'already_webp',
                ];
                $afterBytes += $sourceSize;
                continue;
            }

            $pathWithoutExtension = preg_replace('/\.[^.]+$/', '', $sourcePath);
            $targetPath = $pathWithoutExtension . '.webp';

            $image = $this->createImageResource($sourcePath, $extension);
            if ($image === null) {
                $results['failed'][] = [
                    'file' => $sourcePath,
                    'reason' => 'unsupported_or_corrupt_image',
                ];
                continue;
            }

            $bestOutput = null;
            $quality = $baseQuality;
            $targetBytes = (int) floor($sourceSize * $targetRatio);

            while ($quality >= 10) {
                if (!imagewebp($image, $targetPath, $quality)) {
                    break;
                }

                clearstatcache(true, $targetPath);
                $newSize = is_file($targetPath) ? (filesize($targetPath) ?: 0) : 0;

                if ($newSize > 0) {
                    $bestOutput = [
                        'size' => $newSize,
                        'quality' => $quality,
                    ];
                }

                if ($newSize <= $targetBytes) {
                    break;
                }

                $quality -= 5;
            }

            imagedestroy($image);

            if ($bestOutput === null || !is_file($targetPath)) {
                $results['failed'][] = [
                    'file' => $sourcePath,
                    'reason' => 'conversion_failed',
                ];
                continue;
            }

            $afterBytes += $bestOutput['size'];

            $results['converted'][] = [
                'source' => $sourcePath,
                'target' => $targetPath,
                'used_quality' => $bestOutput['quality'],
                'before_kb' => round($sourceSize / 1024, 2),
                'after_kb' => round($bestOutput['size'] / 1024, 2),
                'compression_ratio' => $sourceSize > 0 ? round($bestOutput['size'] / $sourceSize, 4) : 0,
            ];

            if ($deleteOriginal && is_file($sourcePath)) {
                @unlink($sourcePath);
            }
        }

        $results['summary'] = [
            'converted_count' => count($results['converted']),
            'skipped_count' => count($results['skipped']),
            'failed_count' => count($results['failed']),
            'total_before_mb' => round($beforeBytes / 1024 / 1024, 2),
            'total_after_mb' => round($afterBytes / 1024 / 1024, 2),
            'saved_mb' => round(($beforeBytes - $afterBytes) / 1024 / 1024, 2),
            'overall_ratio' => $beforeBytes > 0 ? round($afterBytes / $beforeBytes, 4) : 0,
        ];

        return response()->json($results);
    }

    /**
     * @return resource|null
     */
    private function createImageResource(string $sourcePath, string $extension)
    {
        return match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
            'png' => @imagecreatefrompng($sourcePath),
            'gif' => @imagecreatefromgif($sourcePath),
            'bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($sourcePath) : @imagecreatefromstring((string) @file_get_contents($sourcePath)),
            'tif', 'tiff' => @imagecreatefromstring((string) @file_get_contents($sourcePath)),
            default => @imagecreatefromstring((string) @file_get_contents($sourcePath)),
        };
    }
}
