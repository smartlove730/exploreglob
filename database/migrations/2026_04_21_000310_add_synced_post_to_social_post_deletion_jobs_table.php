<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('social_post_deletion_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('social_post_deletion_jobs', 'synced_social_post_id')) {
                $table->foreignId('synced_social_post_id')
                    ->nullable()
                    ->after('facebook_page_id')
                    ->constrained('synced_social_posts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('social_post_deletion_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('social_post_deletion_jobs', 'synced_social_post_id')) {
                $table->dropConstrainedForeignId('synced_social_post_id');
            }
        });
    }
};
