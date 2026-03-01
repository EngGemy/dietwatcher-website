<?php

declare(strict_types=1);

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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('location')->default('header'); // header, footer, etc.
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->onDelete('cascade');
            $table->string('type')->default('link'); // link, dropdown, button
            $table->string('label_en');
            $table->string('label_ar');
            $table->string('url')->nullable();
            $table->string('icon')->nullable(); // SVG sprite icon name
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_external')->default(false);
            $table->string('target')->default('_self'); // _self, _blank
            $table->json('meta')->nullable(); // Additional data (classes, attributes, etc.)
            $table->timestamps();
            
            $table->index(['location', 'parent_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
