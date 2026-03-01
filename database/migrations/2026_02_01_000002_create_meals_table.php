<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('meals')) {
            Schema::create('meals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('meal_group_id')->nullable()->constrained('meal_groups')->nullOnDelete();
                $table->json('name');
                $table->json('description')->nullable();
                $table->decimal('price', 10, 2)->default(0)->index();
                $table->unsignedSmallInteger('calories')->nullable()->index();
                $table->decimal('protein', 8, 2)->default(0);
                $table->decimal('carbs', 8, 2)->default(0);
                $table->decimal('fat', 8, 2)->default(0);
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_store_product')->default(false);
                $table->timestamps();

                $table->index(['is_active', 'is_store_product']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
