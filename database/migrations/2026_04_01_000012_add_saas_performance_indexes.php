<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'scheduled_at'], 'facebook_posts_user_status_sched_idx');
            $table->index(['page_id', 'status', 'created_at'], 'facebook_posts_page_status_created_idx');
        });

        Schema::table('scheduled_posts', function (Blueprint $table) {
            $table->index(['user_id', 'scheduled_for'], 'scheduled_posts_user_scheduled_for_idx');
            $table->index(['page_id', 'status', 'scheduled_for'], 'scheduled_posts_page_status_sched_idx');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['user_id', 'current_period_end'], 'subscriptions_user_period_end_idx');
            $table->index(['plan_id', 'status'], 'subscriptions_plan_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            $table->dropIndex('facebook_posts_user_status_sched_idx');
            $table->dropIndex('facebook_posts_page_status_created_idx');
        });

        Schema::table('scheduled_posts', function (Blueprint $table) {
            $table->dropIndex('scheduled_posts_user_scheduled_for_idx');
            $table->dropIndex('scheduled_posts_page_status_sched_idx');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_user_period_end_idx');
            $table->dropIndex('subscriptions_plan_status_idx');
        });
    }
};
