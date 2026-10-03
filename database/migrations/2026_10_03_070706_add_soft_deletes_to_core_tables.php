<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    protected array $tables = [
        'transactions',
        'transaction_items',
        'transaction_payment_attempts',
        'purchases',
        'purchase_items',
        'cash_flows',
        'service_orders',
        'service_order_items',
        'customer_vehicles',
        'stock_transfers',
        'stock_transfer_items',
        'stock_adjustments',
        'stock_adjustment_items',
        'stock_opname_items',
        'products',
        'product_variants',
        'partners',
        'stores',
        'users',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
