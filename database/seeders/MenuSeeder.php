<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['name' => 'Classic Menu', 'is_active' => true],
            ['name' => 'Keto Menu', 'is_active' => true],
            ['name' => 'Vegan Menu', 'is_active' => true],
            ['name' => 'Mediterranean Menu', 'is_active' => true],
            ['name' => 'Low Carb Menu', 'is_active' => true],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }

        $this->command->info('Menus seeded successfully!');
    }
}
