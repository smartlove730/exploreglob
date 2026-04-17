<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('drive_api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('drive_api_keys', 'oauth_access_token')) {
                $table->text('oauth_access_token')->nullable()->after('redirect_url');
            }

            if (!Schema::hasColumn('drive_api_keys', 'oauth_refresh_token')) {
                $column = $table->text('oauth_refresh_token')->nullable();
                if (Schema::hasColumn('drive_api_keys', 'oauth_access_token')) {
                    $column->after('oauth_access_token');
                }
            }

            if (!Schema::hasColumn('drive_api_keys', 'oauth_expires_at')) {
                $column = $table->timestamp('oauth_expires_at')->nullable();
                if (Schema::hasColumn('drive_api_keys', 'oauth_refresh_token')) {
                    $column->after('oauth_refresh_token');
                }
            }

            if (!Schema::hasColumn('drive_api_keys', 'oauth_token_last_refreshed_at')) {
                $column = $table->timestamp('oauth_token_last_refreshed_at')->nullable();
                if (Schema::hasColumn('drive_api_keys', 'oauth_expires_at')) {
                    $column->after('oauth_expires_at');
                }
            }

            if (!Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_required')) {
                $column = $table->boolean('oauth_reauthorization_required')->default(false);
                if (Schema::hasColumn('drive_api_keys', 'oauth_token_last_refreshed_at')) {
                    $column->after('oauth_token_last_refreshed_at');
                }
            }

            if (!Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_reason')) {
                $column = $table->string('oauth_reauthorization_reason')->nullable();
                if (Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_required')) {
                    $column->after('oauth_reauthorization_required');
                }
            }
        });
    }

    public function down(): void
    {
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

            if (Schema::hasColumn('drive_api_keys', 'oauth_expires_at')) {
                $table->dropColumn('oauth_expires_at');
            }

            if (Schema::hasColumn('drive_api_keys', 'oauth_refresh_token')) {
                $table->dropColumn('oauth_refresh_token');
            }

            if (Schema::hasColumn('drive_api_keys', 'oauth_access_token')) {
                $table->dropColumn('oauth_access_token');
            }
        });
    }
};
