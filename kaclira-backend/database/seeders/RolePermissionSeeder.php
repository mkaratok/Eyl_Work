<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds for Kaçlıra.com roles and permissions.
     */
    public function run(): void
    {
        // Create comprehensive permissions for Kaçlıra.com
        $permissions = [
            // Product Management
            'product.create',
            'product.edit',
            'product.delete',
            'product.view',
            'product.approve',
            'product.manage_all',
            
            // Category Management
            'category.create',
            'category.edit',
            'category.delete',
            'category.view',
            'category.manage',
            
            // User Management
            'user.create',
            'user.edit',
            'user.delete',
            'user.view',
            'user.manage',
            'user.activate',
            'user.deactivate',
            
            // Seller Management
            'seller.create',
            'seller.edit',
            'seller.delete',
            'seller.view',
            'seller.manage',
            'seller.approve',
            
            // Price Management
            'price.create',
            'price.edit',
            'price.delete',
            'price.view',
            'price.manage',
            
            // Analytics & Reports
            'analytics.view',
            'analytics.export',
            'reports.view',
            'reports.generate',
            
            // System Settings
            'settings.view',
            'settings.edit',
            'system.manage',
            
            // Notifications
            'notification.send',
            'notification.manage',
            
            // Own Resource Management
            'own.products.manage',
            'own.prices.manage',
            'own.profile.edit',
            'own.analytics.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles with hierarchy: super_admin > admin > seller > sub_seller > user
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $sellerRole = Role::firstOrCreate(['name' => 'seller']);
        $subSellerRole = Role::firstOrCreate(['name' => 'sub_seller']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Assign permissions to roles
        
        // Super Admin - All permissions
        $superAdminRole->givePermissionTo($permissions);
        
        // Admin - Most permissions except system management
        $adminRole->givePermissionTo([
            'product.create', 'product.edit', 'product.delete', 'product.view', 'product.approve', 'product.manage_all',
            'category.create', 'category.edit', 'category.delete', 'category.view', 'category.manage',
            'user.create', 'user.edit', 'user.delete', 'user.view', 'user.manage', 'user.activate', 'user.deactivate',
            'seller.create', 'seller.edit', 'seller.delete', 'seller.view', 'seller.manage', 'seller.approve',
            'price.create', 'price.edit', 'price.delete', 'price.view', 'price.manage',
            'analytics.view', 'analytics.export', 'reports.view', 'reports.generate',
            'settings.view', 'settings.edit',
            'notification.send', 'notification.manage',
            'own.products.manage', 'own.prices.manage', 'own.profile.edit', 'own.analytics.view',
        ]);
        
        // Seller - Can manage own products and prices
        $sellerRole->givePermissionTo([
            'product.create', 'product.edit', 'product.view',
            'category.view',
            'price.create', 'price.edit', 'price.view',
            'own.products.manage', 'own.prices.manage', 'own.profile.edit', 'own.analytics.view',
        ]);
        
        // Sub Seller - Limited seller permissions
        $subSellerRole->givePermissionTo([
            'product.view',
            'category.view',
            'price.view',
            'own.profile.edit', 'own.analytics.view',
        ]);
        
        // User - Basic permissions
        $userRole->givePermissionTo([
            'product.view',
            'category.view',
            'price.view',
            'own.profile.edit',
        ]);

        // Create default super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@kaclira.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'phone' => '+90 555 000 0001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->assignRole('super_admin');

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@kaclira.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'phone' => '+90 555 123 4567',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');

        // Create default seller user
        $seller = User::firstOrCreate(
            ['email' => 'seller@kaclira.com'],
            [
                'name' => 'Demo Seller',
                'password' => Hash::make('seller123'),
                'phone' => '+90 555 765 4321',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $seller->assignRole('seller');

        // Create default sub seller user
        $subSeller = User::firstOrCreate(
            ['email' => 'subseller@kaclira.com'],
            [
                'name' => 'Sub Seller',
                'password' => Hash::make('subseller123'),
                'phone' => '+90 555 555 5555',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $subSeller->assignRole('sub_seller');

        // Create default regular user
        $user = User::firstOrCreate(
            ['email' => 'user@kaclira.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('user123'),
                'phone' => '+90 555 999 8888',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('user');
    }
}
