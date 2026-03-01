<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Menu Item Seeder
 *
 * Seeds the menu_items table with header menu structure including:
 * - Meal Plans dropdown with sub-items
 * - Regular links (Market, Blog, FAQs)
 * - Header actions (cart icon, CTA button)
 */
class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing menu items
        MenuItem::truncate();

        // 1. Meal Plans Dropdown (Parent)
        $mealPlans = MenuItem::create([
            'location' => 'header',
            'parent_id' => null,
            'type' => 'dropdown',
            'label_en' => 'Meal Plans',
            'label_ar' => 'خطط الوجبات',
            'url' => null,
            'icon' => 'chevron-down',
            'order' => 1,
            'is_active' => true,
        ]);

        // Meal Plans Sub-items
        MenuItem::create([
            'location' => 'header',
            'parent_id' => $mealPlans->id,
            'type' => 'link',
            'label_en' => 'Weight Loss',
            'label_ar' => 'إنقاص الوزن',
            'url' => '/#plans',
            'order' => 1,
            'is_active' => true,
        ]);

        MenuItem::create([
            'location' => 'header',
            'parent_id' => $mealPlans->id,
            'type' => 'link',
            'label_en' => 'Muscle Gain',
            'label_ar' => 'بناء العضلات',
            'url' => '/#plans',
            'order' => 2,
            'is_active' => true,
        ]);

        MenuItem::create([
            'location' => 'header',
            'parent_id' => $mealPlans->id,
            'type' => 'link',
            'label_en' => 'Healthy Lifestyle',
            'label_ar' => 'نمط حياة صحي',
            'url' => '/#plans',
            'order' => 3,
            'is_active' => true,
        ]);

        // 2. Market Link (Store for meals)
        MenuItem::create([
            'location' => 'header',
            'parent_id' => null,
            'type' => 'link',
            'label_en' => 'Market',
            'label_ar' => 'المتجر',
            'url' => '/store',
            'order' => 2,
            'is_active' => true,
        ]);

        // 3. Blog Link
        MenuItem::create([
            'location' => 'header',
            'parent_id' => null,
            'type' => 'link',
            'label_en' => 'Blog',
            'label_ar' => 'المدونة',
            'url' => '/blog',
            'order' => 3,
            'is_active' => true,
        ]);

        // 4. FAQs Link
        MenuItem::create([
            'location' => 'header',
            'parent_id' => null,
            'type' => 'link',
            'label_en' => 'FAQs',
            'label_ar' => 'الأسئلة الشائعة',
            'url' => '/#faq',
            'order' => 4,
            'is_active' => true,
        ]);

        // 5. Header Actions - Cart Button
        MenuItem::create([
            'location' => 'header_actions',
            'parent_id' => null,
            'type' => 'icon_button',
            'label_en' => 'Cart',
            'label_ar' => 'السلة',
            'url' => '/cart',
            'icon' => 'bag',
            'order' => 1,
            'is_active' => true,
        ]);

        // 6. Header Actions - Primary CTA Button
        MenuItem::create([
            'location' => 'header_actions',
            'parent_id' => null,
            'type' => 'button',
            'label_en' => 'Choose Your Meal Plan',
            'label_ar' => 'اختر خطة وجباتك',
            'url' => '/#plans',
            'order' => 2,
            'is_active' => true,
            'meta' => [
                'classes' => 'btn btn--primary',
            ],
        ]);

        $this->command->info('✅ Menu items seeded successfully!');
        $this->command->info('   - 4 main menu items (1 dropdown with 3 sub-items, 3 links)');
        $this->command->info('   - 2 header action buttons');
    }
}
