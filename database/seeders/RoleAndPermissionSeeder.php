<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetCachedRolesAndPermissions();

        $permissions = [
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'view_permissions',
            'view_menu_items',
            'create_menu_items',
            'edit_menu_items',
            'delete_menu_items',
            'view_hero_sections',
            'create_hero_sections',
            'edit_hero_sections',
            'delete_hero_sections',
            'view_features',
            'create_features',
            'edit_features',
            'delete_features',
            'view_blog_posts',
            'create_blog_posts',
            'edit_blog_posts',
            'delete_blog_posts',
            'view_blog_categories',
            'create_blog_categories',
            'edit_blog_categories',
            'delete_blog_categories',
            'view_blog_tags',
            'create_blog_tags',
            'edit_blog_tags',
            'delete_blog_tags',
            'manage_settings',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(
            Permission::whereNotIn('name', ['delete_users', 'delete_roles'])->pluck('name')
        );

        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editor->givePermissionTo([
            'view_menu_items', 'create_menu_items', 'edit_menu_items', 'delete_menu_items',
            'view_hero_sections', 'create_hero_sections', 'edit_hero_sections', 'delete_hero_sections',
            'view_features', 'create_features', 'edit_features', 'delete_features',
            'view_blog_posts', 'create_blog_posts', 'edit_blog_posts', 'delete_blog_posts',
            'view_blog_categories', 'create_blog_categories', 'edit_blog_categories', 'delete_blog_categories',
            'view_blog_tags', 'create_blog_tags', 'edit_blog_tags', 'delete_blog_tags',
            'manage_settings',
        ]);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@dietwatcher.example'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $adminUser->assignRole('super_admin');
    }

    private function resetCachedRolesAndPermissions(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
