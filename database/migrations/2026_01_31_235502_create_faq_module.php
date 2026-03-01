<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // FAQ Categories
        if (!Schema::hasTable('faq_categories')) {
            Schema::create('faq_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('order_column')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'order_column']);
            });
        }

        if (!Schema::hasTable('faq_category_translations')) {
            Schema::create('faq_category_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('faq_category_id')->constrained('faq_categories')->cascadeOnDelete();
                $table->string('locale')->index();
                $table->string('name');
                $table->timestamps();

                $table->unique(['faq_category_id', 'locale']);
            });
        }

        // FAQs
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('faq_category_id')->nullable()->constrained('faq_categories')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('order_column')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'order_column']);
                $table->index('faq_category_id');
            });
        }

        if (!Schema::hasTable('faq_translations')) {
            Schema::create('faq_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('faq_id')->constrained('faqs')->cascadeOnDelete();
                $table->string('locale')->index();
                $table->string('question');
                $table->longText('answer');
                $table->timestamps();

                $table->unique(['faq_id', 'locale']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_translations');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_category_translations');
        Schema::dropIfExists('faq_categories');
    }
};
