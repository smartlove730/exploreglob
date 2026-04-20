<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posted_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_config_id')->constrained('automation_configs')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->string('drive_file_id');
            $table->string('platform', 32);
            $table->string('status', 32)->default('processing');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'drive_file_id', 'platform'], 'posted_media_unique_file_platform');
            $table->index(['automation_config_id', 'status']);
            $table->index(['user_id', 'platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posted_media');
    }
};
