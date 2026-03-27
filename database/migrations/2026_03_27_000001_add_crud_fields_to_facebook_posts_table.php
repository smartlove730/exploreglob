<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('facebook_posts', 'facebook_post_id')) {
                $table->string('facebook_post_id')->nullable()->after('image_url');
            }

            if (!Schema::hasColumn('facebook_posts', 'instagram_media_id')) {
                $table->string('instagram_media_id')->nullable()->after('facebook_post_id');
            }

            if (Schema::hasColumn('facebook_posts', 'status')) {
                $table->string('status')->default('draft')->change();
            } else {
                $table->string('status')->default('draft')->after('instagram_media_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (Schema::hasColumn('facebook_posts', 'facebook_post_id')) {
                $table->dropColumn('facebook_post_id');
            }

            if (Schema::hasColumn('facebook_posts', 'instagram_media_id')) {
                $table->dropColumn('instagram_media_id');
            }
        });
    }
};
