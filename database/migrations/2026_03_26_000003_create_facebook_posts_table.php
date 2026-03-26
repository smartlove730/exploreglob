<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->text('message');
            $table->string('image_url')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('response_json')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_posts');
    }
};
