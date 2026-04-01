<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('razorpay_subscription_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->unsignedInteger('posts_used')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'current_period_end']);
            $table->unique('razorpay_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
