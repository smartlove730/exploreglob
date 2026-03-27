<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('facebook_posts', 'media_type')) {
                $table->enum('media_type', ['image', 'video'])->default('image')->after('message');
            }

            if (!Schema::hasColumn('facebook_posts', 'video_path')) {
                $table->string('video_path')->nullable()->after('image_url');
            }

            if (!Schema::hasColumn('facebook_posts', 'video_url')) {
                $table->string('video_url')->nullable()->after('video_path');
            }

            if (Schema::hasColumn('facebook_posts', 'status')) {
                $table->string('status')->default('processing')->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (Schema::hasColumn('facebook_posts', 'video_url')) {
                $table->dropColumn('video_url');
            }

            if (Schema::hasColumn('facebook_posts', 'video_path')) {
                $table->dropColumn('video_path');
            }

            if (Schema::hasColumn('facebook_posts', 'media_type')) {
                $table->dropColumn('media_type');
            }
        });
    }
};
