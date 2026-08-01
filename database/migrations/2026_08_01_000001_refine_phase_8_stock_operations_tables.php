<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_adjustments', 'status')) {
                $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft')->after('store_id');
            }
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_adjustment_items', 'product_variant_id')) {
                $table->foreignUuid('product_variant_id')->nullable()->after('stock_adjustment_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'warehouse_id')) {
                $table->foreignUuid('warehouse_id')->nullable()->after('product_variant_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'warehouse_location_id')) {
                $table->foreignUuid('warehouse_location_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0)->after('quantity');
            }
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transfer_items', 'from_warehouse_id')) {
                $table->foreignUuid('from_warehouse_id')->nullable()->after('product_variant_id')->constrained('warehouses')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_transfer_items', 'from_warehouse_location_id')) {
                $table->foreignUuid('from_warehouse_location_id')->nullable()->after('from_warehouse_id')->constrained('warehouse_locations')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_transfer_items', 'to_warehouse_id')) {
                $table->foreignUuid('to_warehouse_id')->nullable()->after('from_warehouse_location_id')->constrained('warehouses')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_transfer_items', 'to_warehouse_location_id')) {
                $table->foreignUuid('to_warehouse_location_id')->nullable()->after('to_warehouse_id')->constrained('warehouse_locations')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_transfer_items', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0)->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('from_warehouse_id');
            $table->dropConstrainedForeignId('from_warehouse_location_id');
            $table->dropConstrainedForeignId('to_warehouse_id');
            $table->dropConstrainedForeignId('to_warehouse_location_id');
            $table->dropColumn('unit_cost');
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('warehouse_location_id');
            $table->dropColumn('unit_cost');
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
