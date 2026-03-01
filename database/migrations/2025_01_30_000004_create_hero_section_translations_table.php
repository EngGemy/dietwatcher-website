<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_section_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_section_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12)->index();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_secondary_text')->nullable();
            $table->timestamps();

            $table->unique(['hero_section_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_section_translations');
    }
};
