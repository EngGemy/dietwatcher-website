<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->string('identifier'); // email or phone
            $table->timestamp('used_at');

            $table->index(['coupon_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_uses');
    }
};
