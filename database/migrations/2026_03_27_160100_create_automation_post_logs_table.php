<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_posts_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_config_id')->constrained('automation_configs')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('facebook_pages')->nullOnDelete();
            $table->string('drive_file_id')->nullable();
            $table->string('drive_file_name')->nullable();
            $table->text('image_url')->nullable();
            $table->text('caption')->nullable();
            $table->json('platforms')->nullable();
            $table->string('facebook_post_id')->nullable();
            $table->string('instagram_media_id')->nullable();
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
            $table->text('message')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['automation_config_id', 'drive_file_id']);
            $table->index(['automation_config_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_posts_logs');
    }
};
