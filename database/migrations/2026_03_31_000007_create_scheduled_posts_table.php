<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->text('message');
            $table->enum('media_type', ['image', 'video'])->default('image');
            $table->string('image_url')->nullable();
            $table->string('video_path')->nullable();
            $table->string('video_url')->nullable();
            $table->json('platforms');
            $table->timestamp('scheduled_for');
            $table->string('status')->default('pending');
            $table->json('response_json')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['user_id', 'status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
