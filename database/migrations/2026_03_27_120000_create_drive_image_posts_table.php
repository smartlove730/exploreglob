<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drive_image_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->string('drive_file_id');
            $table->string('drive_folder_id')->nullable();
            $table->text('image_url');
            $table->text('caption')->nullable();
            $table->json('platforms');
            $table->string('facebook_post_id')->nullable();
            $table->string('instagram_media_id')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'drive_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_image_posts');
    }
};
