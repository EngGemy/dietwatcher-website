<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_calorie_macros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_calorie_id')->constrained('plan_calories')->cascadeOnDelete();
            $table->unsignedInteger('calories')->nullable();
            $table->decimal('protein_g', 8, 2)->default(0);
            $table->decimal('carbs_g', 8, 2)->default(0);
            $table->decimal('fat_g', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_calorie_macros');
    }
};
