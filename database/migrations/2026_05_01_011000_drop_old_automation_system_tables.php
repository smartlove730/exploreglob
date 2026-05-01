<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('posted_media');
        Schema::dropIfExists('automation_processed_media');
        Schema::dropIfExists('automation_posts_logs');
        Schema::dropIfExists('automation_configs');
    }

    public function down(): void
    {
        // The old automation system has been intentionally removed.
    }
};
