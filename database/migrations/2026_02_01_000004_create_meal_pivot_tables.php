<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('category_meal')) {
            Schema::create('category_meal', function (Blueprint $table) {
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->foreignId('meal_id')->constrained('meals')->cascadeOnDelete();
                $table->primary(['category_id', 'meal_id']);
                $table->index('meal_id');
            });
        }

        if (!Schema::hasTable('meal_offer')) {
            Schema::create('meal_offer', function (Blueprint $table) {
                $table->foreignId('meal_id')->constrained('meals')->cascadeOnDelete();
                $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
                $table->primary(['meal_id', 'offer_id']);
                $table->index('offer_id');
            });
        }

        if (!Schema::hasTable('ingredient_meal')) {
            Schema::create('ingredient_meal', function (Blueprint $table) {
                $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
                $table->foreignId('meal_id')->constrained('meals')->cascadeOnDelete();
                $table->decimal('quantity', 10, 2)->nullable();
                $table->boolean('allow_print')->default(true);
                $table->boolean('is_main_ingredient')->default(false);
                $table->primary(['ingredient_id', 'meal_id']);
                $table->index('meal_id');
            });
        }

        if (!Schema::hasTable('meal_meal_tag')) {
            Schema::create('meal_meal_tag', function (Blueprint $table) {
                $table->foreignId('meal_id')->constrained('meals')->cascadeOnDelete();
                $table->foreignId('meal_tag_id')->constrained('meal_tags')->cascadeOnDelete();
                $table->primary(['meal_id', 'meal_tag_id']);
                $table->index('meal_tag_id');
            });
        }

        if (!Schema::hasTable('meal_meal_group')) {
            Schema::create('meal_meal_group', function (Blueprint $table) {
                $table->foreignId('meal_id')->constrained('meals')->cascadeOnDelete();
                $table->foreignId('meal_group_id')->constrained('meal_groups')->cascadeOnDelete();
                $table->primary(['meal_id', 'meal_group_id']);
                $table->index('meal_group_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_meal');
        Schema::dropIfExists('meal_offer');
        Schema::dropIfExists('ingredient_meal');
        Schema::dropIfExists('meal_meal_tag');
        Schema::dropIfExists('meal_meal_group');
    }
};
