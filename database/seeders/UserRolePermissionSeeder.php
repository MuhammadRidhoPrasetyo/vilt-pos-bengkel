<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::transaction(function () {
            // 2. Create 3 Bengkel / Toko
            $storePusat = Store::query()->firstOrCreate([
                'code' => 'PUSAT',
            ], [
                'name' => 'Bengkel Utama Pusat',
                'phone' => '081234567891',
                'address' => 'Jl. Raya Utama No. 1, Jakarta Pusat',
            ]);

            $storeJakarta = Store::query()->firstOrCreate([
                'code' => 'JKT-01',
            ], [
                'name' => 'Bengkel Cabang Jakarta',
                'phone' => '081234567892',
                'address' => 'Jl. Sudirman No. 10, Jakarta Selatan',
            ]);

            $storeBandung = Store::query()->firstOrCreate([
                'code' => 'BDG-01',
            ], [
                'name' => 'Bengkel Cabang Bandung',
                'phone' => '081234567893',
                'address' => 'Jl. Asia Afrika No. 20, Bandung',
            ]);

            // 3. Define Permissions based on menu domains
            $permissions = [
                // Master Data
                'stores.view',
                'stores.create',
                'stores.edit',
                'stores.delete',
                'partners.view',
                'partners.create',
                'partners.edit',
                'partners.delete',
                'partner-roles.view',
                'partner-roles.create',
                'partner-roles.edit',
                'partner-roles.delete',
                'discount-types.view',
                'discount-types.create',
                'discount-types.edit',
                'discount-types.delete',
                'brands.view',
                'brands.create',
                'brands.edit',
                'brands.delete',
                'units.view',
                'units.create',
                'units.edit',
                'units.delete',
                'payments.view',
                'payments.create',
                'payments.edit',
                'payments.delete',
                'cash-flow-categories.view',
                'cash-flow-categories.create',
                'cash-flow-categories.edit',
                'cash-flow-categories.delete',

                // Catalog & Products
                'product-categories.view',
                'product-categories.create',
                'product-categories.edit',
                'product-categories.delete',
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'product-variants.view',
                'product-variants.create',
                'product-variants.edit',
                'product-variants.delete',
                'product-attributes.view',
                'product-attributes.create',
                'product-attributes.edit',
                'product-attributes.delete',
                'product-discounts.view',
                'product-discounts.create',
                'product-discounts.edit',
                'product-discounts.delete',
                'product-prices.view',
                'product-prices.create',
                'product-prices.edit',
                'product-prices.delete',
                'product-stocks.view',
                'product-stocks.create',
                'product-stocks.edit',
                'product-stocks.delete',

                // Warehouses
                'warehouses.view',
                'warehouses.create',
                'warehouses.edit',
                'warehouses.delete',
                'warehouse-locations.view',
                'warehouse-locations.create',
                'warehouse-locations.edit',
                'warehouse-locations.delete',

                // POS & Work Orders
                'pos.view',
                'pos.create',
                'work-orders.view',
                'work-orders.create',
                'work-orders.edit',
                'work-orders.delete',

                // User & Role Access Management
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
                'permissions.view',
                'permissions.create',
                'permissions.edit',
                'permissions.delete',
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
            ];

            foreach ($permissions as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            // 4. Create Roles and Assign Permissions
            $roleOwner = Role::findOrCreate('owner', 'web');
            $roleOwner->syncPermissions(Permission::all());

            $roleKasir = Role::findOrCreate('kasir', 'web');
            $roleKasir->syncPermissions([
                'pos.view',
                'pos.create',
                'products.view',
                'product-variants.view',
                'product-stocks.view',
                'product-prices.view',
                'product-discounts.view',
                'payments.view',
                'work-orders.view',
            ]);

            $roleMekanik = Role::findOrCreate('mekanik', 'web');
            $roleMekanik->syncPermissions([
                'work-orders.view',
                'work-orders.create',
                'work-orders.edit',
                'products.view',
                'product-variants.view',
                'product-stocks.view',
            ]);

            // 5. Create 4 Users and Assign Roles
            // User 1: Owner (Global / store_id = null)
            $ownerUser = User::query()->firstOrCreate([
                'email' => 'owner@viltpos.com',
            ], [
                'name' => 'Owner POS / Bengkel',
                'password' => bcrypt('password'),
                'nik' => 'OWN-001',
                'phone' => '081111111111',
                'address' => 'Jl. Owner Utama No. 88',
                'store_id' => null,
                'active' => true,
            ]);
            $ownerUser->syncRoles([$roleOwner]);

            // User 2: Kasir 1 (Bengkel Cabang Jakarta)
            $kasir1User = User::query()->firstOrCreate([
                'email' => 'kasir1@viltpos.com',
            ], [
                'name' => 'Kasir Cabang Jakarta',
                'password' => bcrypt('password'),
                'nik' => 'KAS-001',
                'phone' => '082222222222',
                'address' => 'Jl. Sudirman No. 10',
                'store_id' => $storeJakarta->id,
                'active' => true,
            ]);
            $kasir1User->syncRoles([$roleKasir]);

            // User 3: Kasir 2 (Bengkel Cabang Bandung)
            $kasir2User = User::query()->firstOrCreate([
                'email' => 'kasir2@viltpos.com',
            ], [
                'name' => 'Kasir Cabang Bandung',
                'password' => bcrypt('password'),
                'nik' => 'KAS-002',
                'phone' => '083333333333',
                'address' => 'Jl. Asia Afrika No. 20',
                'store_id' => $storeBandung->id,
                'active' => true,
            ]);
            $kasir2User->syncRoles([$roleKasir]);

            // User 4: Mekanik (Bengkel Cabang Jakarta)
            $mekanikUser = User::query()->firstOrCreate([
                'email' => 'mekanik@viltpos.com',
            ], [
                'name' => 'Mekanik Senior',
                'password' => bcrypt('password'),
                'nik' => 'MEK-001',
                'phone' => '084444444444',
                'address' => 'Jl. Sudirman No. 12',
                'store_id' => $storeJakarta->id,
                'active' => true,
            ]);
            $mekanikUser->syncRoles([$roleMekanik]);
        });
    }
}
