<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->longText('ingredients')->nullable();
            $table->longText('benefits')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_translations');
    }
};
