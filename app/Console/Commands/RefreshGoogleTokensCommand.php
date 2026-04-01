<?php

namespace App\Console\Commands;

use App\Exceptions\ReauthorizationRequiredException;
use App\Models\DriveApiKey;
use App\Models\GoogleAccount;
use App\Services\GoogleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshGoogleTokensCommand extends Command
{
    protected $signature = 'google:refresh-tokens';

    protected $description = 'Refresh expiring Google OAuth tokens';

    public function handle(GoogleService $googleService): int
    {
        $accounts = GoogleAccount::query()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '<=', now()->addDay());
            })
            ->where('reauthorization_required', false)
            ->get();

        foreach ($accounts as $account) {
            try {
                $googleService->ensureValidGoogleAccountToken($account);
            } catch (ReauthorizationRequiredException $exception) {
                $account->update([
                    'reauthorization_required' => true,
                    'reauthorization_reason' => $exception->getMessage(),
                ]);
            } catch (Throwable $exception) {
                Log::error('Google account token refresh failed', [
                    'google_account_id' => $account->id,
                    'user_id' => $account->user_id,
                    'expires_at' => optional($account->expires_at)?->toDateTimeString(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $driveKeys = DriveApiKey::query()
            ->whereNotNull('oauth_access_token')
            ->where(function ($query) {
                $query->whereNull('oauth_expires_at')
                    ->orWhere('oauth_expires_at', '<=', now()->addDay());
            })
            ->where(function ($query) {
                $query->whereNull('oauth_reauthorization_required')
                    ->orWhere('oauth_reauthorization_required', false);
            })
            ->get();

        foreach ($driveKeys as $driveKey) {
            try {
                $googleService->ensureValidDriveToken($driveKey);
            } catch (ReauthorizationRequiredException $exception) {
                $driveKey->update([
                    'oauth_reauthorization_required' => true,
                    'oauth_reauthorization_reason' => $exception->getMessage(),
                ]);
            } catch (Throwable $exception) {
                Log::error('Google Drive token refresh failed', [
                    'drive_api_key_id' => $driveKey->id,
                    'user_id' => $driveKey->user_id,
                    'expires_at' => optional($driveKey->oauth_expires_at)?->toDateTimeString(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info('Google token refresh completed.');

        return self::SUCCESS;
    }
}
