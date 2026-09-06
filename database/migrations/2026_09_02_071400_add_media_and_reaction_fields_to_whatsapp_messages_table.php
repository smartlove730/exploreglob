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
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('media_url')->nullable();
            $table->string('media_mime_type', 50)->nullable();
            $table->string('media_filename')->nullable();
            $table->text('media_caption')->nullable();
            $table->string('reaction_emoji', 20)->nullable();
            $table->string('reaction_whatsapp_message_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn([
                'media_url',
                'media_mime_type',
                'media_filename',
                'media_caption',
                'reaction_emoji',
                'reaction_whatsapp_message_id',
            ]);
        });
    }
};
