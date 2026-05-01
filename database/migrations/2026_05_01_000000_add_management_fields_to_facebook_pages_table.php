<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('facebook_pages', 'category')) {
                $table->string('category')->nullable()->after('page_name');
            }

            if (!Schema::hasColumn('facebook_pages', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            if (Schema::hasColumn('facebook_pages', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }

            if (Schema::hasColumn('facebook_pages', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
