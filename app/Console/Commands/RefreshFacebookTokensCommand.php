<?php

namespace App\Console\Commands;

use App\Models\FacebookAccount;
use App\Services\FacebookGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshFacebookTokensCommand extends Command
{
    protected $signature = 'facebook:refresh-tokens';

    protected $description = 'Refresh expiring Facebook long-lived user tokens';

    public function handle(FacebookGraphService $facebookGraphService): int
    {
        $accounts = FacebookAccount::query()
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', now()->addDays(7))
            ->get();

        foreach ($accounts as $account) {
            try {
                $tokenData = $facebookGraphService->refreshLongLivedToken($account->long_lived_user_token);

                $account->update([
                    'long_lived_user_token' => $tokenData['access_token'],
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                ]);

                $pages = $facebookGraphService->fetchManagedPages($tokenData['access_token']);
                $facebookGraphService->upsertPages($account, $pages);
            } catch (Throwable $exception) {
                Log::error('Facebook token refresh failed', [
                    'facebook_account_id' => $account->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info('Facebook token refresh completed.');

        return self::SUCCESS;
    }
}
