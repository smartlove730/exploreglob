<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'is_active']);
        });

        Schema::table('facebook_posts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'status', 'scheduled_at']);
        });

        Schema::table('post_images', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'post_id']);
        });

        Schema::table('drive_image_posts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'page_id', 'drive_file_id']);
        });

        Schema::table('automation_posts_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'automation_config_id', 'created_at'], 'automation_logs_user_config_created_idx');
        });

        Schema::table('drive_api_keys', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'is_active']);
        });

        Schema::table('drive_folders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'is_active']);
        });

        DB::table('facebook_pages')
            ->join('facebook_accounts', 'facebook_accounts.id', '=', 'facebook_pages.facebook_account_id')
            ->update(['facebook_pages.user_id' => DB::raw('facebook_accounts.user_id')]);

        DB::table('facebook_posts')
            ->join('facebook_pages', 'facebook_pages.id', '=', 'facebook_posts.page_id')
            ->update(['facebook_posts.user_id' => DB::raw('facebook_pages.user_id')]);

        DB::table('post_images')
            ->join('facebook_posts', 'facebook_posts.id', '=', 'post_images.post_id')
            ->update(['post_images.user_id' => DB::raw('facebook_posts.user_id')]);

        DB::table('drive_image_posts')
            ->join('facebook_pages', 'facebook_pages.id', '=', 'drive_image_posts.page_id')
            ->update(['drive_image_posts.user_id' => DB::raw('facebook_pages.user_id')]);

        DB::table('automation_posts_logs')
            ->join('automation_configs', 'automation_configs.id', '=', 'automation_posts_logs.automation_config_id')
            ->update(['automation_posts_logs.user_id' => DB::raw('automation_configs.user_id')]);

        DB::table('drive_folders')
            ->join('drive_api_keys', 'drive_api_keys.id', '=', 'drive_folders.drive_api_key_id')
            ->whereNull('drive_folders.user_id')
            ->update(['drive_folders.user_id' => DB::raw('drive_api_keys.user_id')]);
    }

    public function down(): void
    {
        Schema::table('drive_folders', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('drive_api_keys', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('automation_posts_logs', function (Blueprint $table) {
            $table->dropIndex('automation_logs_user_config_created_idx');
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('drive_image_posts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'page_id', 'drive_file_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('post_images', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'post_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('facebook_posts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'scheduled_at']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
