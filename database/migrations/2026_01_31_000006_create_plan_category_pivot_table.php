<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_category', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_category_id')->constrained()->cascadeOnDelete();

            $table->primary(['plan_id', 'plan_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_category');
    }
};
