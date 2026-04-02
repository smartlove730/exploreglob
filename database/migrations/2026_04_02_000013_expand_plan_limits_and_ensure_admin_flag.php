<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'posts_per_day_limit')) {
                $table->unsignedInteger('posts_per_day_limit')->nullable()->after('post_limit');
            }
            if (!Schema::hasColumn('plans', 'posts_per_week_limit')) {
                $table->unsignedInteger('posts_per_week_limit')->nullable()->after('posts_per_day_limit');
            }
            if (!Schema::hasColumn('plans', 'posts_per_month_limit')) {
                $table->unsignedInteger('posts_per_month_limit')->nullable()->after('posts_per_week_limit');
            }
            if (!Schema::hasColumn('plans', 'automation_limit')) {
                $table->unsignedInteger('automation_limit')->nullable()->after('posts_per_month_limit');
            }
            if (!Schema::hasColumn('plans', 'connected_apps_limit')) {
                $table->unsignedInteger('connected_apps_limit')->nullable()->after('automation_limit');
            }
            if (!Schema::hasColumn('plans', 'synced_pages_limit')) {
                $table->unsignedInteger('synced_pages_limit')->nullable()->after('connected_apps_limit');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            foreach ([
                'posts_per_day_limit',
                'posts_per_week_limit',
                'posts_per_month_limit',
                'automation_limit',
                'connected_apps_limit',
                'synced_pages_limit',
            ] as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
