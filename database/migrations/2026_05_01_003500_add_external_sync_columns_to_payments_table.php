<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return substr($digits, -9);
    }

    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('customer_phone_normalized', 16)->nullable()->index()->after('customer_phone');
            $table->json('checkout_payload')->nullable()->after('cart_items');
            $table->unsignedBigInteger('external_order_id')->nullable()->index()->after('raw_response');
            $table->string('external_order_number')->nullable()->index()->after('external_order_id');
            $table->string('external_sync_status')->nullable()->after('external_order_number');
            $table->text('external_sync_message')->nullable()->after('external_sync_status');
            $table->timestamp('external_synced_at')->nullable()->after('external_sync_message');
        });

        DB::table('payments')
            ->select(['id', 'customer_phone'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $normalized = $this->normalizePhone((string) ($row->customer_phone ?? ''));
                    if ($normalized !== '') {
                        DB::table('payments')
                            ->where('id', $row->id)
                            ->update(['customer_phone_normalized' => $normalized]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'customer_phone_normalized',
                'checkout_payload',
                'external_order_id',
                'external_order_number',
                'external_sync_status',
                'external_sync_message',
                'external_synced_at',
            ]);
        });
    }
};

