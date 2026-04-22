<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_failed_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_config_id')->constrained('automation_configs')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('facebook_pages')->nullOnDelete();
            $table->string('drive_folder_id')->nullable();
            $table->string('drive_file_id');
            $table->string('drive_file_name')->nullable();
            $table->string('media_type', 20)->default('image');
            $table->text('source_url')->nullable();
            $table->json('platforms')->nullable();
            $table->text('failure_reason');
            $table->unsignedInteger('fail_count')->default(1);
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();

            $table->unique(['automation_config_id', 'page_id', 'drive_file_id'], 'automation_failed_media_unique');
            $table->index(['automation_config_id', 'last_failed_at'], 'automation_failed_media_config_failed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_failed_media');
    }
};
