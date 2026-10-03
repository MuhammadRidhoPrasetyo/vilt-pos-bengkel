<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('opname_number')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->timestamp('occurred_at')->useCurrent();
            $table->integer('total_system_qty')->default(0);
            $table->integer('total_physical_qty')->default(0);
            $table->integer('total_difference_qty')->default(0);
            $table->decimal('total_difference_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_location_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('system_quantity')->default(0);
            $table->integer('physical_quantity')->default(0);
            $table->integer('difference_quantity')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('difference_value', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};
