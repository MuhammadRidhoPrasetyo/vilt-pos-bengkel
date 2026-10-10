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
            'reports.view' => 'Mengakses menu laporan dan analitik',
            'reports.staff.view' => 'Melihat laporan kinerja dan produktivitas karyawan (kasir & mekanik)',
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

        Permission::whereIn('name', ['reports.view', 'reports.staff.view'])->delete();
    }
};
