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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 60)->unique();
            $table->decimal('price', 10, 2);
            $table->enum('interval', ['monthly', 'yearly'])->default('monthly');
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->json('limits')->comment('JSON object e.g. {"max_users": 5}');
            $table->json('features')->comment('JSON array e.g. ["reports","api_access"]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
