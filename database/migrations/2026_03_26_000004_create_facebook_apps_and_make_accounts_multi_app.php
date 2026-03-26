<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facebook_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('app_id')->unique();
            $table->string('app_secret');
            $table->string('redirect_uri');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaultAppId = DB::table('facebook_apps')->insertGetId([
            'name' => 'Default Facebook App',
            'app_id' => config('services.facebook.app_id') ?? 'placeholder-app-id',
            'app_secret' => config('services.facebook.app_secret') ?? 'placeholder-app-secret',
            'redirect_uri' => config('services.facebook.redirect_uri') ?? url('/auth/facebook/callback'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('facebook_accounts', function (Blueprint $table) use ($defaultAppId) {
            $table->foreignId('facebook_app_id')
                ->default($defaultAppId)
                ->after('user_id')
                ->constrained('facebook_apps')
                ->cascadeOnDelete();

            $table->dropUnique('facebook_accounts_user_id_unique');
            $table->unique(['user_id', 'facebook_app_id']);
        });

        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->dropUnique('facebook_pages_page_id_unique');
            $table->unique(['facebook_account_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->dropUnique('facebook_pages_facebook_account_id_page_id_unique');
            $table->unique('page_id');
        });

        Schema::table('facebook_accounts', function (Blueprint $table) {
            $table->dropUnique('facebook_accounts_user_id_facebook_app_id_unique');
            $table->unique('user_id');
            $table->dropConstrainedForeignId('facebook_app_id');
        });

        Schema::dropIfExists('facebook_apps');
    }
};
