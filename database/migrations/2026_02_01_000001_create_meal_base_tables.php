<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('meal_groups')) {
            Schema::create('meal_groups', function (Blueprint $table) {
                $table->id();
                $table->json('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('meal_tags')) {
            Schema::create('meal_tags', function (Blueprint $table) {
                $table->id();
                $table->json('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ingredients')) {
            Schema::create('ingredients', function (Blueprint $table) {
                $table->id();
                $table->json('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // General categories table (different from PlanCategory)
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->json('name');
                $table->string('type')->nullable()->index(); // e.g., 'meal', 'blog'
                $table->string('icon')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['type', 'is_active']);
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_groups');
        Schema::dropIfExists('meal_tags');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('categories');
    }
};
