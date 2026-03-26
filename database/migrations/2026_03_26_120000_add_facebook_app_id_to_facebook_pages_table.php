<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->foreignId('facebook_app_id')
                ->nullable()
                ->after('facebook_account_id')
                ->constrained('facebook_apps')
                ->cascadeOnDelete();

            $table->index(['facebook_app_id', 'is_active']);
        });

        DB::table('facebook_pages')
            ->join('facebook_accounts', 'facebook_accounts.id', '=', 'facebook_pages.facebook_account_id')
            ->update([
                'facebook_pages.facebook_app_id' => DB::raw('facebook_accounts.facebook_app_id'),
                'facebook_pages.is_active' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->dropIndex('facebook_pages_facebook_app_id_is_active_index');
            $table->dropConstrainedForeignId('facebook_app_id');
        });
    }
};
