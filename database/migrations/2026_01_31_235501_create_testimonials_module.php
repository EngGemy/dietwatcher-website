<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('testimonials')) {
            Schema::create('testimonials', function (Blueprint $table) {
                $table->id();
                $table->string('author_image')->nullable();
                $table->tinyInteger('rating')->default(5);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('order_column')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'order_column']);
                $table->index('rating');
            });
        }

        if (!Schema::hasTable('testimonial_translations')) {
            Schema::create('testimonial_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('testimonial_id')->constrained('testimonials')->cascadeOnDelete();
                $table->string('locale')->index();
                $table->string('author_name')->nullable();
                $table->string('author_title')->nullable();
                $table->text('content'); // required text
                $table->timestamps();

                $table->unique(['testimonial_id', 'locale']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonial_translations');
        Schema::dropIfExists('testimonials');
    }
};
