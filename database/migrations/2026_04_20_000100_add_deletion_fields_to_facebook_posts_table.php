<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('facebook_posts', 'deletion_status')) {
                $table->string('deletion_status')->nullable()->after('status');
                $table->index('deletion_status');
            }

            if (!Schema::hasColumn('facebook_posts', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('deletion_status');
                $table->index('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            if (Schema::hasColumn('facebook_posts', 'deleted_at')) {
                $table->dropIndex(['deleted_at']);
                $table->dropColumn('deleted_at');
            }

            if (Schema::hasColumn('facebook_posts', 'deletion_status')) {
                $table->dropIndex(['deletion_status']);
                $table->dropColumn('deletion_status');
            }
        });
    }
};
