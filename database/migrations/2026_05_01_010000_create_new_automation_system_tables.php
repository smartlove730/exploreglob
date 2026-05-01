<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('facebook_apps')->cascadeOnDelete();
            $table->string('name');
            $table->json('page_ids');
            $table->json('platforms');
            $table->string('media_source_type', 32);
            $table->json('media_source_payload')->nullable();
            $table->unsignedSmallInteger('post_frequency')->default(1);
            $table->json('schedule_times')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->unsignedSmallInteger('daily_limit')->default(3);
            $table->json('caption_templates')->nullable();
            $table->json('hashtag_templates')->nullable();
            $table->string('status', 24)->default('active');
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'next_run_at']);
        });

        Schema::create('automation_queue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->foreignId('facebook_post_id')->nullable()->constrained('facebook_posts')->nullOnDelete();
            $table->string('source_id')->nullable();
            $table->string('media_type', 20)->default('image');
            $table->text('media_url')->nullable();
            $table->text('caption')->nullable();
            $table->json('platforms')->nullable();
            $table->string('status', 24)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('response_json')->nullable();
            $table->string('facebook_post_id_external')->nullable();
            $table->string('instagram_media_id')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['automation_rule_id', 'page_id', 'source_id'], 'automation_queue_unique_source');
            $table->index(['status', 'scheduled_for']);
            $table->index(['page_id', 'status', 'completed_at']);
        });

        Schema::create('automation_run_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->foreignId('automation_queue_item_id')->nullable()->constrained('automation_queue_items')->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('facebook_pages')->nullOnDelete();
            $table->string('status', 24);
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['automation_rule_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_run_logs');
        Schema::dropIfExists('automation_queue_items');
        Schema::dropIfExists('automation_rules');
    }
};
