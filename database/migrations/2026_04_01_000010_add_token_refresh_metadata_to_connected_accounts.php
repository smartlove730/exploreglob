<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('facebook_accounts', 'token_last_refreshed_at')) {
                $table->timestamp('token_last_refreshed_at')->nullable()->after('token_expires_at');
            }
            if (!Schema::hasColumn('facebook_accounts', 'reauthorization_required')) {
                $table->boolean('reauthorization_required')->default(false)->after('token_last_refreshed_at');
            }
            if (!Schema::hasColumn('facebook_accounts', 'reauthorization_reason')) {
                $table->string('reauthorization_reason')->nullable()->after('reauthorization_required');
            }
        });

        Schema::table('google_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('google_accounts', 'token_last_refreshed_at')) {
                $table->timestamp('token_last_refreshed_at')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('google_accounts', 'reauthorization_required')) {
                $table->boolean('reauthorization_required')->default(false)->after('token_last_refreshed_at');
            }
            if (!Schema::hasColumn('google_accounts', 'reauthorization_reason')) {
                $table->string('reauthorization_reason')->nullable()->after('reauthorization_required');
            }
        });

        Schema::table('drive_api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('drive_api_keys', 'oauth_token_last_refreshed_at')) {
                $table->timestamp('oauth_token_last_refreshed_at')->nullable()->after('oauth_expires_at');
            }
            if (!Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_required')) {
                $table->boolean('oauth_reauthorization_required')->default(false)->after('oauth_token_last_refreshed_at');
            }
            if (!Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_reason')) {
                $table->string('oauth_reauthorization_reason')->nullable()->after('oauth_reauthorization_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facebook_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('facebook_accounts', 'reauthorization_reason')) {
                $table->dropColumn('reauthorization_reason');
            }
            if (Schema::hasColumn('facebook_accounts', 'reauthorization_required')) {
                $table->dropColumn('reauthorization_required');
            }
            if (Schema::hasColumn('facebook_accounts', 'token_last_refreshed_at')) {
                $table->dropColumn('token_last_refreshed_at');
            }
        });

        Schema::table('google_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('google_accounts', 'reauthorization_reason')) {
                $table->dropColumn('reauthorization_reason');
            }
            if (Schema::hasColumn('google_accounts', 'reauthorization_required')) {
                $table->dropColumn('reauthorization_required');
            }
            if (Schema::hasColumn('google_accounts', 'token_last_refreshed_at')) {
                $table->dropColumn('token_last_refreshed_at');
            }
        });

        Schema::table('drive_api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_reason')) {
                $table->dropColumn('oauth_reauthorization_reason');
            }
            if (Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_required')) {
                $table->dropColumn('oauth_reauthorization_required');
            }
            if (Schema::hasColumn('drive_api_keys', 'oauth_token_last_refreshed_at')) {
                $table->dropColumn('oauth_token_last_refreshed_at');
            }
        });
    }
};
