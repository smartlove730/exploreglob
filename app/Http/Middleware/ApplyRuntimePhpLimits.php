<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApplyRuntimePhpLimits
{
    /**
     * @var array<string, string>
     */
    private array $limits = [
        'memory_limit' => '512M',
        'max_execution_time' => '300',
        'upload_max_filesize' => '512M',
        'post_max_size' => '512M',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $applied = [];
        $warnings = [];

        foreach ($this->limits as $key => $value) {
            $applied[$key] = $this->applyIniSetting($key, $value, $warnings);
        }

        $applied['set_time_limit'] = $this->applyTimeLimit($warnings);

        Log::info('Runtime PHP limits check completed.', [
            'path' => $request->path(),
            'method' => $request->method(),
            'applied_limits' => $applied,
            'warnings' => $warnings,
        ]);

        return $next($request);
    }

    /**
     * @param array<int, string> $warnings
     *
     * @return array<string, string|bool>
     */
    private function applyIniSetting(string $key, string $desiredValue, array &$warnings): array
    {
        $before = (string) ini_get($key);

        if (!function_exists('ini_set')) {
            $warnings[] = "ini_set is unavailable; cannot set {$key}.";

            return [
                'before' => $before,
                'desired' => $desiredValue,
                'after' => $before,
                'changed' => false,
            ];
        }

        $result = @ini_set($key, $desiredValue);
        $after = (string) ini_get($key);
        $changed = $result !== false;

        if (!$changed) {
            $warnings[] = "Unable to set {$key} to {$desiredValue}; hosting configuration may enforce it.";
        }

        return [
            'before' => $before,
            'desired' => $desiredValue,
            'after' => $after,
            'changed' => $changed,
        ];
    }

    /**
     * @param array<int, string> $warnings
     */
    private function applyTimeLimit(array &$warnings): array
    {
        if (!function_exists('set_time_limit')) {
            $warnings[] = 'set_time_limit is unavailable on this host.';

            return [
                'desired' => 300,
                'applied' => false,
            ];
        }

        try {
            @set_time_limit(300);

            return [
                'desired' => 300,
                'applied' => true,
            ];
        } catch (\Throwable $exception) {
            $warnings[] = 'set_time_limit failed: '.$exception->getMessage();

            return [
                'desired' => 300,
                'applied' => false,
            ];
        }
    }
}
