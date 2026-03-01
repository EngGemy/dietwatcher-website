<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Offer;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $offers = [
            [
                'name' => 'New Year Discount',
                'discount_percentage' => 15.00,
                'discount_amount' => 0,
                'is_active' => true,
                'start_date' => now(),
                'end_date' => now()->addMonths(2),
            ],
            [
                'name' => 'Summer Special',
                'discount_percentage' => 20.00,
                'discount_amount' => 0,
                'is_active' => false,
                'start_date' => null,
                'end_date' => null,
            ],
            [
                'name' => 'Fixed 100 SAR Off',
                'discount_percentage' => 0,
                'discount_amount' => 100.00,
                'is_active' => true,
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ],
        ];

        foreach ($offers as $offer) {
            Offer::create($offer);
        }

        $this->command->info('Offers seeded successfully!');
    }
}
