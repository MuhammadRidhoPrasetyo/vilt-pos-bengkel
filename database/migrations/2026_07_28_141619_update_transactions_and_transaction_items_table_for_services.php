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
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->nullable()->default(0)->after('universal_discount_amount');
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->enum('item_type', ['part', 'labor'])->default('part')->after('transaction_id');
            $table->string('description')->nullable()->after('product_variant_id');
            $table->foreignUuid('product_variant_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'description']);
            $table->foreignUuid('product_variant_id')->nullable(false)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });
    }
};
