<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'transactions.summary.view' => 'Melihat ringkasan total omzet dan statistik penjualan POS',
            'transactions.profit.view' => 'Melihat total profit / laba kotor pada ringkasan transaksi',
            'dashboard.finance.view' => 'Melihat widget arus kas dan saldo keuangan toko pada Beranda',
        ];

        foreach ($permissions as $permissionName => $description) {
            $perm = Permission::findOrCreate($permissionName, 'web');
            $perm->update(['description' => $description]);
        }

        // Assign to owner and admin if they exist
        $owner = Role::where('name', 'owner')->first();
        if ($owner) {
            $owner->givePermissionTo(array_keys($permissions));
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(array_keys($permissions));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'transactions.summary.view',
            'transactions.profit.view',
            'dashboard.finance.view',
        ])->delete();
    }
};
