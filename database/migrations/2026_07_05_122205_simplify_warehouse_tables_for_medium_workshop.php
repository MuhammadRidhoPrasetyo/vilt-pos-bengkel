<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses_tmp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['store_id', 'code']);
        });

        DB::table('warehouses_tmp')->insertUsing(
            ['id', 'store_id', 'code', 'name', 'description', 'phone', 'is_active', 'deleted_at', 'created_at', 'updated_at'],
            DB::table('warehouses')->select(['id', 'store_id', 'code', 'name', 'description', 'phone', 'is_active', 'deleted_at', 'created_at', 'updated_at'])
        );

        Schema::drop('warehouses');
        Schema::rename('warehouses_tmp', 'warehouses');

        Schema::create('warehouse_locations_tmp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('warehouse_locations_tmp')->nullOnDelete();
            $table->string('type')->default('shelf');
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });

        DB::table('warehouse_locations_tmp')->insertUsing(
            ['id', 'warehouse_id', 'parent_id', 'type', 'code', 'name', 'description', 'is_active', 'deleted_at', 'created_at', 'updated_at'],
            DB::table('warehouse_locations')->selectRaw("id, warehouse_id, parent_id, 'shelf' as type, code, name, description, is_active, deleted_at, created_at, updated_at")
        );

        Schema::drop('warehouse_locations');
        Schema::rename('warehouse_locations_tmp', 'warehouse_locations');

        Schema::dropIfExists('location_types');
    }

    public function down(): void
    {
        Schema::create('location_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $locationTypeId = (string) str()->uuid();

        DB::table('location_types')->insert([
            'id' => $locationTypeId,
            'name' => 'General',
            'description' => 'Default location type',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('warehouses_tmp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->foreignUuid('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('max_capacity', 15, 2)->nullable();
            $table->string('capacity_uom')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['store_id', 'code']);
        });

        DB::table('warehouses_tmp')->insertUsing(
            ['id', 'store_id', 'code', 'name', 'description', 'address', 'city', 'state', 'zip_code', 'latitude', 'longitude', 'phone', 'manager_id', 'max_capacity', 'capacity_uom', 'is_active', 'sort', 'created_by', 'updated_by', 'deleted_at', 'created_at', 'updated_at'],
            DB::table('warehouses')->selectRaw('id, store_id, code, name, description, null as address, null as city, null as state, null as zip_code, null as latitude, null as longitude, phone, null as manager_id, null as max_capacity, null as capacity_uom, is_active, null as sort, null as created_by, null as updated_by, deleted_at, created_at, updated_at')
        );

        Schema::drop('warehouses');
        Schema::rename('warehouses_tmp', 'warehouses');

        Schema::create('warehouse_locations_tmp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('warehouse_locations_tmp')->nullOnDelete();
            $table->foreignUuid('location_type_id')->constrained('location_types')->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('full_path')->nullable();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->integer('position_z')->nullable();
            $table->decimal('max_weight', 15, 2)->nullable();
            $table->decimal('max_volume', 15, 2)->nullable();
            $table->boolean('is_scrap')->default(false);
            $table->boolean('is_quarantine')->default(false);
            $table->boolean('is_return')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });

        DB::table('warehouse_locations_tmp')->insertUsing(
            ['id', 'warehouse_id', 'parent_id', 'location_type_id', 'code', 'name', 'full_path', 'barcode', 'description', 'position_x', 'position_y', 'position_z', 'max_weight', 'max_volume', 'is_scrap', 'is_quarantine', 'is_return', 'is_active', 'sort', 'created_by', 'updated_by', 'deleted_at', 'created_at', 'updated_at'],
            DB::table('warehouse_locations')->selectRaw("id, warehouse_id, parent_id, '{$locationTypeId}' as location_type_id, code, name, null as full_path, null as barcode, description, null as position_x, null as position_y, null as position_z, null as max_weight, null as max_volume, 0 as is_scrap, 0 as is_quarantine, 0 as is_return, is_active, null as sort, null as created_by, null as updated_by, deleted_at, created_at, updated_at")
        );

        Schema::drop('warehouse_locations');
        Schema::rename('warehouse_locations_tmp', 'warehouse_locations');
    }
};
