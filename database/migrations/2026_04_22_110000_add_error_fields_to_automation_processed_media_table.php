<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_processed_media', function (Blueprint $table) {
            $table->string('platform')->nullable()->after('status');
            $table->text('last_error')->nullable()->after('platform');
            $table->timestamp('failed_at')->nullable()->after('last_error');
            $table->index('failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('automation_processed_media', function (Blueprint $table) {
            $table->dropIndex(['failed_at']);
            $table->dropColumn(['platform', 'last_error', 'failed_at']);
        });
    }
};
