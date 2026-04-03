<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('automation_posts_logs', function (Blueprint $table) {
            $table->string('status')->default('success')->change();
            $table->timestamp('scheduled_for')->nullable()->after('message');
            $table->timestamp('started_at')->nullable()->after('scheduled_for');
            $table->timestamp('completed_at')->nullable()->after('started_at');

            $table->index(['status', 'scheduled_for'], 'automation_logs_status_scheduled_idx');
            $table->index(['automation_config_id', 'status'], 'automation_logs_config_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('automation_posts_logs', function (Blueprint $table) {
            $table->dropIndex('automation_logs_status_scheduled_idx');
            $table->dropIndex('automation_logs_config_status_idx');

            $table->dropColumn(['scheduled_for', 'started_at', 'completed_at']);
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success')->change();
        });
    }
};
