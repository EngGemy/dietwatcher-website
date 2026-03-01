<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('meal_images')) {
            Schema::create('meal_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('meal_id')->constrained('meals')->cascadeOnDelete();
                $table->string('image_path');
                $table->boolean('is_cover')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('order_column')->default(0)->index();
                $table->timestamps();

                $table->index(['meal_id', 'is_active']);
                $table->index(['meal_id', 'is_cover']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_images');
    }
};
