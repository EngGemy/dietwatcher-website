<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('moyasar_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->string('payment_method')->nullable();

            // Amounts (in halalas/cents for precision)
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('SAR');
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('vat_amount')->default(0);

            // Customer info
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');

            // Order details
            $table->json('cart_items');
            $table->string('start_date')->nullable();
            $table->string('duration')->nullable();
            $table->string('delivery_type')->default('home');
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('building')->nullable();
            $table->string('coupon')->nullable();

            // Moyasar response data
            $table->string('description')->nullable();
            $table->string('source_type')->nullable();
            $table->string('card_type')->nullable();
            $table->string('masked_pan')->nullable();
            $table->text('message')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
