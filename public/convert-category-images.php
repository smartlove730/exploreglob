<?php

declare(strict_types=1);

/**
 * Convert every image inside /public/storage/categories/** to .webp.
 *
 * URL usage example:
 * /convert-category-images.php?quality=70&delete_original=0
 */

set_time_limit(0);
ini_set('memory_limit', '1024M');

$quality = isset($_GET['quality']) ? (int) $_GET['quality'] : 70;
$quality = max(1, min(100, $quality));
$deleteOriginal = isset($_GET['delete_original']) ? (bool) ((int) $_GET['delete_original']) : false;

$baseDirectory = __DIR__ . '/storage/categories';

if (!is_dir($baseDirectory)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Directory not found: ' . $baseDirectory,
    ], JSON_PRETTY_PRINT);
    exit;
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp'];
$results = [
    'status' => 'ok',
    'quality' => $quality,
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

    $beforeBytes += $fileInfo->getSize();

    $pathWithoutExtension = preg_replace('/\.[^.]+$/', '', $sourcePath);
    $targetPath = $pathWithoutExtension . '.webp';

    if ($extension === 'webp') {
        $results['skipped'][] = [
            'file' => $sourcePath,
            'reason' => 'already_webp',
        ];
        $afterBytes += filesize($sourcePath) ?: 0;
        continue;
    }

    $fileContents = @file_get_contents($sourcePath);
    if ($fileContents === false) {
        $results['failed'][] = [
            'file' => $sourcePath,
            'reason' => 'unable_to_read_file',
        ];
        continue;
    }

    $image = @imagecreatefromstring($fileContents);
    if ($image === false) {
        $results['failed'][] = [
            'file' => $sourcePath,
            'reason' => 'unsupported_or_corrupt_image',
        ];
        continue;
    }

    $converted = @imagewebp($image, $targetPath, $quality);
    imagedestroy($image);

    if (!$converted || !is_file($targetPath)) {
        $results['failed'][] = [
            'file' => $sourcePath,
            'reason' => 'conversion_failed',
        ];
        continue;
    }

    clearstatcache(true, $targetPath);
    $newSize = filesize($targetPath) ?: 0;
    $afterBytes += $newSize;

    $results['converted'][] = [
        'source' => $sourcePath,
        'target' => $targetPath,
        'before_kb' => round(($fileInfo->getSize() ?: 0) / 1024, 2),
        'after_kb' => round($newSize / 1024, 2),
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
];

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
