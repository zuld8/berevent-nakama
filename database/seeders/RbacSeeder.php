<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $perms = [
            'manage users',
            'manage roles',
            'manage permissions',
            'view campaign',
            'create campaign',
            'update campaign',
            'delete campaign',
            // CMS: News permissions (Filament Shield style)
            'view_any_news', 'view_news', 'create_news', 'update_news', 'delete_news', 'delete_any_news',
            // CMS: Page permissions
            'view_any_page', 'view_page', 'create_page', 'update_page', 'delete_page', 'delete_any_page',
            // CMS: Menu permissions
            'view_any_menu', 'view_menu', 'create_menu', 'update_menu', 'delete_menu', 'delete_any_menu',
            // CMS: Menu Item permissions (relation manager)
            'view_any_menu_item', 'view_menu_item', 'create_menu_item', 'update_menu_item', 'delete_menu_item', 'delete_any_menu_item',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
        $admin->givePermissionTo($perms);


        $user = \App\Models\User::where('email', 'admin@myapp.test')->first();
        $user?->assignRole('admin');
    }
}
