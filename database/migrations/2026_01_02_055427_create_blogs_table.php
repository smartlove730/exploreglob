<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('blogs', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->longText('content');
    $table->text('excerpt')->nullable();
    $table->string('featured_image')->nullable();

    $table->foreignId('category_id')->constrained()->onDelete('cascade');
    $table->foreignId('country_id')->constrained()->onDelete('cascade');

    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->string('seo_keywords')->nullable();

    $table->timestamp('published_at')->nullable();
    $table->boolean('status')->default(1);

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
