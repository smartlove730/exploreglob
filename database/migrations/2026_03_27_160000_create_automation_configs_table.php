<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('prompt');
            $table->text('drive_link');
            $table->foreignId('app_id')->constrained('facebook_apps')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->enum('platforms', ['facebook', 'instagram', 'both'])->default('both');
            $table->unsignedSmallInteger('runs_per_day')->default(1);
            $table->unsignedSmallInteger('post_limit_per_day')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_configs');
    }
};
