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

            // 3. Define Permissions based on menu domains with descriptions
            $permissions = [
                // Master Data - Stores
                'stores.view' => 'Melihat daftar dan detail cabang bengkel / toko',
                'stores.create' => 'Menambahkan toko / cabang bengkel baru',
                'stores.edit' => 'Mengubah data toko / cabang bengkel',
                'stores.delete' => 'Menghapus toko / cabang bengkel',

                // Master Data - Partners
                'partners.view' => 'Melihat daftar mitra (supplier / pelanggan / mekanik)',
                'partners.create' => 'Menambahkan data mitra baru',
                'partners.edit' => 'Mengubah data mitra',
                'partners.delete' => 'Menghapus data mitra',

                // Master Data - Partner Roles
                'partner-roles.view' => 'Melihat daftar peran mitra',
                'partner-roles.create' => 'Menambahkan peran mitra baru',
                'partner-roles.edit' => 'Mengubah peran mitra',
                'partner-roles.delete' => 'Menghapus peran mitra',

                // Master Data - Discount Types
                'discount-types.view' => 'Melihat jenis-jenis diskon',
                'discount-types.create' => 'Menambahkan jenis diskon baru',
                'discount-types.edit' => 'Mengubah jenis diskon',
                'discount-types.delete' => 'Menghapus jenis diskon',

                // Master Data - Brands
                'brands.view' => 'Melihat daftar merek barang / suku cadang',
                'brands.create' => 'Menambahkan merek baru',
                'brands.edit' => 'Mengubah data merek',
                'brands.delete' => 'Menghapus merek',

                // Master Data - Units
                'units.view' => 'Melihat satuan barang (Pcs, Set, Liter, dll)',
                'units.create' => 'Menambahkan satuan barang baru',
                'units.edit' => 'Mengubah satuan barang',
                'units.delete' => 'Menghapus satuan barang',

                // Master Data - Payments
                'payments.view' => 'Melihat metode pembayaran',
                'payments.create' => 'Menambahkan metode pembayaran baru',
                'payments.edit' => 'Mengubah metode pembayaran',
                'payments.delete' => 'Menghapus metode pembayaran',

                // Master Data - Cash Flow Categories
                'cash-flow-categories.view' => 'Melihat kategori arus kas',
                'cash-flow-categories.create' => 'Menambahkan kategori arus kas baru',
                'cash-flow-categories.edit' => 'Mengubah kategori arus kas',
                'cash-flow-categories.delete' => 'Menghapus kategori arus kas',

                // Catalog & Products
                'product-categories.view' => 'Melihat kategori produk / suku cadang',
                'product-categories.create' => 'Menambahkan kategori produk baru',
                'product-categories.edit' => 'Mengubah kategori produk',
                'product-categories.delete' => 'Menghapus kategori produk',
                'products.view' => 'Melihat katalog produk / barang',
                'products.create' => 'Menambahkan produk baru ke katalog',
                'products.edit' => 'Mengubah data produk',
                'products.delete' => 'Menghapus produk dari katalog',
                'product-variants.view' => 'Melihat varian produk',
                'product-variants.create' => 'Menambahkan varian produk baru',
                'product-variants.edit' => 'Mengubah varian produk',
                'product-variants.delete' => 'Menghapus varian produk',
                'product-attributes.view' => 'Melihat atribut spesifikasi produk',
                'product-attributes.create' => 'Menambahkan atribut produk baru',
                'product-attributes.edit' => 'Mengubah atribut produk',
                'product-attributes.delete' => 'Menghapus atribut produk',
                'product-discounts.view' => 'Melihat promo & diskon produk',
                'product-discounts.create' => 'Menambahkan promo / diskon produk baru',
                'product-discounts.edit' => 'Mengubah data promo / diskon',
                'product-discounts.delete' => 'Menghapus promo / diskon produk',
                'product-prices.view' => 'Melihat daftar harga jual produk',
                'product-prices.create' => 'Menentukan harga jual produk',
                'product-prices.edit' => 'Mengubah harga jual produk',
                'product-prices.delete' => 'Menghapus penetapan harga produk',
                'product-stocks.view' => 'Melihat stok barang per cabang / gudang',
                'product-stocks.create' => 'Input penyesuaian / penambahan stok',
                'product-stocks.edit' => 'Mengubah data stok barang',
                'product-stocks.delete' => 'Menghapus catatan stok',

                // Warehouses, Purchases, & Printers
                'warehouses.view' => 'Melihat daftar gudang',
                'warehouses.create' => 'Menambahkan gudang baru',
                'warehouses.edit' => 'Mengubah data gudang',
                'warehouses.delete' => 'Menghapus gudang',
                'warehouse-locations.view' => 'Melihat lokasi rak / sekat gudang',
                'warehouse-locations.create' => 'Menambahkan lokasi rak gudang baru',
                'warehouse-locations.edit' => 'Mengubah lokasi rak gudang',
                'warehouse-locations.delete' => 'Menghapus lokasi rak gudang',
                'stock-adjustments.view' => 'Melihat dokumen penyesuaian stok',
                'stock-adjustments.create' => 'Membuat draft penyesuaian stok',
                'stock-adjustments.edit' => 'Mengubah draft penyesuaian stok',
                'stock-adjustments.delete' => 'Menghapus draft penyesuaian stok',
                'stock-adjustments.post' => 'Memposting penyesuaian stok ke ledger persediaan',
                'stock-adjustments.cancel' => 'Membatalkan draft penyesuaian stok',
                'stock-transfers.view' => 'Melihat dokumen transfer stok antar gudang',
                'stock-transfers.create' => 'Membuat draft transfer stok',
                'stock-transfers.edit' => 'Mengubah draft transfer stok',
                'stock-transfers.delete' => 'Menghapus draft transfer stok',
                'stock-transfers.post' => 'Memposting transfer stok antar gudang',
                'stock-transfers.cancel' => 'Membatalkan draft transfer stok',
                'purchases.view' => 'Melihat daftar dan detail transaksi pembelian supplier',
                'purchases.create' => 'Membuat transaksi pembelian baru dari supplier',
                'purchases.edit' => 'Mengubah data transaksi pembelian',
                'purchases.delete' => 'Menghapus / membatalkan transaksi pembelian',
                'printers.view' => 'Melihat daftar dan konfigurasi printer toko',
                'printers.create' => 'Menambahkan konfigurasi printer toko baru',
                'printers.edit' => 'Mengubah konfigurasi printer toko',
                'printers.delete' => 'Menghapus konfigurasi printer toko',

                // POS & Work Orders / Services
                'pos.view' => 'Mengakses menu kasir / POS',
                'pos.create' => 'Melakukan transaksi penjualan kasir',
                'services.view' => 'Melihat daftar Surat Perintah Kerja (SPK) / Servis',
                'services.create' => 'Membuat SPK perbaikan / servis kendaraan baru',
                'services.edit' => 'Mengubah data SPK / menambah suku cadang & jasa servis',
                'services.delete' => 'Membatalkan / menghapus SPK servis',
                'work-orders.view' => 'Melihat daftar Surat Perintah Kerja (SPK) / Perbaikan',
                'work-orders.create' => 'Membuat SPK perbaikan kendaraan baru',
                'work-orders.edit' => 'Mengubah data SPK / menambah suku cadang servis',
                'work-orders.delete' => 'Batalkan / hapus SPK perbaikan',

                // User & Role Access Management
                'roles.view' => 'Melihat daftar peran / Hak Akses Pengguna',
                'roles.create' => 'Menambahkan peran pengguna baru',
                'roles.edit' => 'Mengatur izin / permission pada peran',
                'roles.delete' => 'Menghapus peran pengguna',
                'permissions.view' => 'Melihat daftar hak izin sistem (permissions)',
                'permissions.create' => 'Menambahkan hak izin sistem baru',
                'permissions.edit' => 'Mengubah hak izin sistem',
                'permissions.delete' => 'Menghapus hak izin sistem',
                'users.view' => 'Melihat daftar pengguna / karyawan',
                'users.create' => 'Menambahkan pengguna / akun karyawan baru',
                'users.edit' => 'Mengubah akun pengguna & penetapan peran/cabang',
                'users.delete' => 'Menghapus / menonaktifkan pengguna',
            ];

            foreach ($permissions as $permissionName => $description) {
                $perm = Permission::findOrCreate($permissionName, 'web');
                $perm->update(['description' => $description]);
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
                'stock-adjustments.view',
                'stock-transfers.view',
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
                'stock-adjustments.view',
                'stock-transfers.view',
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
