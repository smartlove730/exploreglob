<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('synced_social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facebook_page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->string('platform', 20);
            $table->string('external_post_id');
            $table->text('content')->nullable();
            $table->text('media_preview_url')->nullable();
            $table->text('permalink')->nullable();
            $table->dateTime('external_created_at')->nullable();
            $table->dateTime('last_synced_at');
            $table->timestamps();

            $table->unique(['facebook_page_id', 'platform', 'external_post_id'], 'synced_social_posts_unique_idx');
            $table->index(['user_id', 'platform', 'external_created_at'], 'synced_social_posts_user_platform_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synced_social_posts');
    }
};
