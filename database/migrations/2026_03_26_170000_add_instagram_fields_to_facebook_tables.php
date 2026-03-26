<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->string('instagram_business_account_id')->nullable()->after('page_access_token');
            $table->index('instagram_business_account_id');
        });

        Schema::table('facebook_posts', function (Blueprint $table) {
            $table->json('platforms')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            $table->dropColumn('platforms');
        });

        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->dropIndex(['instagram_business_account_id']);
            $table->dropColumn('instagram_business_account_id');
        });
    }
};
