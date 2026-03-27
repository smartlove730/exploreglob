<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('facebook_posts', 'google_post_name')) {
                $table->string('google_post_name')->nullable()->after('instagram_media_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (Schema::hasColumn('facebook_posts', 'google_post_name')) {
                $table->dropColumn('google_post_name');
            }
        });
    }
};
