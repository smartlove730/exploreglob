<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('facebook_posts', 'last_error')) {
                $table->text('last_error')->nullable()->after('status');
            }

            if (!Schema::hasColumn('facebook_posts', 'google_location_id')) {
                $table->foreignId('google_location_id')->nullable()->after('platforms')->constrained('google_locations')->nullOnDelete();
            }
        });

        DB::table('facebook_posts')
            ->where('status', 'draft')
            ->update(['status' => 'pending']);

        DB::table('facebook_posts')
            ->whereNull('status')
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE facebook_posts MODIFY status VARCHAR(255) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (Schema::hasColumn('facebook_posts', 'google_location_id')) {
                $table->dropConstrainedForeignId('google_location_id');
            }

            if (Schema::hasColumn('facebook_posts', 'last_error')) {
                $table->dropColumn('last_error');
            }
        });
    }
};
