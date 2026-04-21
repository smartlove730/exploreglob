<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_post_deletion_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facebook_page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->string('platform', 20);
            $table->string('external_post_id');
            $table->timestamp('post_created_at')->nullable();
            $table->text('content_preview')->nullable();
            $table->text('media_preview_url')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('scheduled_for');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for'], 'social_post_deletion_status_sched_idx');
            $table->unique(['facebook_page_id', 'platform', 'external_post_id'], 'social_post_deletion_unique_post_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_deletion_jobs');
    }
};
