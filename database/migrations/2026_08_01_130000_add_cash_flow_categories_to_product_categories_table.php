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
        Schema::table('product_categories', function (Blueprint $table) {
            $table->foreignUuid('income_cash_flow_category_id')
                ->nullable()
                ->after('pricing_mode')
                ->constrained('cash_flow_categories')
                ->nullOnDelete();

            $table->foreignUuid('expense_cash_flow_category_id')
                ->nullable()
                ->after('income_cash_flow_category_id')
                ->constrained('cash_flow_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign(['income_cash_flow_category_id']);
            $table->dropForeign(['expense_cash_flow_category_id']);
            $table->dropColumn(['income_cash_flow_category_id', 'expense_cash_flow_category_id']);
        });
    }
};
