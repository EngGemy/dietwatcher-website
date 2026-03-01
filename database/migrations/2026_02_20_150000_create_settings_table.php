<?php

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
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->default('general');
                $table->string('type')->default('string'); // string, boolean, json, integer
                $table->timestamps();
                
                $table->index('group');
            });
        } else {
            // Ensure columns exist
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'group')) {
                    $table->string('group')->default('general');
                }
                if (!Schema::hasColumn('settings', 'type')) {
                    $table->string('type')->default('string');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
