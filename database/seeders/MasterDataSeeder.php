<?php

namespace Database\Seeders;

use App\Models\CashFlowCategory;
use App\Models\PartnerRole;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Seed Units
            $units = [
                ['name' => 'Pcs', 'symbol' => 'pcs'],
                ['name' => 'Botol', 'symbol' => 'btl'],
                ['name' => 'Liter', 'symbol' => 'L'],
                ['name' => 'Set', 'symbol' => 'set'],
                ['name' => 'Pack', 'symbol' => 'pck'],
                ['name' => 'Box', 'symbol' => 'box'],
                ['name' => 'Roll', 'symbol' => 'roll'],
                ['name' => 'Meter', 'symbol' => 'm'],
            ];

            foreach ($units as $unit) {
                Unit::query()->firstOrCreate(
                    ['name' => $unit['name']],
                    ['symbol' => $unit['symbol']]
                );
            }

            // 2. Seed Partner Roles
            $partnerRoles = [
                ['name' => 'Pelanggan / Customer', 'description' => 'Pelanggan Bengkel / Pembeli Retail'],
                ['name' => 'Supplier / Vendor', 'description' => 'Pemasok Barang, Sparepart, dan Oli'],
                ['name' => 'Sales Representative', 'description' => 'Sales Representatif Supplier'],
                ['name' => 'Subkontraktor / Mitra Kerja', 'description' => 'Mitra Bengkel Luar / Jasa Bubut / Pihak Ketiga'],
            ];

            foreach ($partnerRoles as $role) {
                PartnerRole::query()->firstOrCreate(
                    ['name' => $role['name']],
                    ['description' => $role['description']]
                );
            }

            // 3. Seed Cash Flow Categories
            $cashFlowCategories = [
                // Pemasukan (Income)
                [
                    'name' => 'Penjualan Sparepart & Oli',
                    'type' => 'income',
                    'description' => 'Pemasukan Kasir dari Penjualan Produk',
                    'is_active' => true,
                    'is_system' => true,
                ],
                [
                    'name' => 'Jasa Servis & Perbaikan',
                    'type' => 'income',
                    'description' => 'Pemasukan dari Layanan Mekanik / Jasa Bengkel',
                    'is_active' => true,
                    'is_system' => true,
                ],
                [
                    'name' => 'Pendapatan Lain-lain',
                    'type' => 'income',
                    'description' => 'Pemasukan di Luar Transaksi Utama Bengkel',
                    'is_active' => true,
                    'is_system' => false,
                ],

                // Pengeluaran (Expense)
                [
                    'name' => 'Gaji & Tunjangan Karyawan',
                    'type' => 'expense',
                    'description' => 'Gaji dan Komisi Staf / Mekanik Bengkel',
                    'is_active' => true,
                    'is_system' => true,
                ],
                [
                    'name' => 'Pembelian Sparepart & Stok',
                    'type' => 'expense',
                    'description' => 'Pengeluaran Belanja Barang dari Supplier',
                    'is_active' => true,
                    'is_system' => true,
                ],
                [
                    'name' => 'Tagihan Listrik, Air & Internet',
                    'type' => 'expense',
                    'description' => 'Biaya Utilitas Operasional Bengkel',
                    'is_active' => true,
                    'is_system' => false,
                ],
                [
                    'name' => 'Sewa Bangunan Bengkel',
                    'type' => 'expense',
                    'description' => 'Biaya Sewa Ruko / Tempat Bengkel',
                    'is_active' => true,
                    'is_system' => false,
                ],
                [
                    'name' => 'Operasional & Peralatan',
                    'type' => 'expense',
                    'description' => 'Biaya Kebersihan, Tools, & Operasional',
                    'is_active' => true,
                    'is_system' => false,
                ],
                [
                    'name' => 'Pengeluaran Lain-lain',
                    'type' => 'expense',
                    'description' => 'Pengeluaran Tidak Terduga Lainnya',
                    'is_active' => true,
                    'is_system' => false,
                ],
            ];

            foreach ($cashFlowCategories as $cat) {
                CashFlowCategory::query()->firstOrCreate(
                    ['name' => $cat['name']],
                    [
                        'type' => $cat['type'],
                        'description' => $cat['description'],
                        'is_active' => $cat['is_active'],
                        'is_system' => $cat['is_system'],
                    ]
                );
            }
        });
    }
}
