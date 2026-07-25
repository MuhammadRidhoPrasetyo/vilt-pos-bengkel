<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_types', function (Blueprint $table) {
            $table->foreignUuid('store_id')->nullable()->after('id')->index()->constrained('stores')->nullOnDelete();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->foreignUuid('store_id')->nullable()->after('id')->index()->constrained('stores')->nullOnDelete();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->foreignUuid('store_id')->nullable()->after('id')->index()->constrained('stores')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('store_id')->nullable()->after('id')->index()->constrained('stores')->nullOnDelete();
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->foreignUuid('store_id')->nullable()->after('id')->index()->constrained('stores')->nullOnDelete();
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->foreignUuid('store_id')->nullable()->after('id')->index()->constrained('stores')->nullOnDelete();
        });

        Schema::table('cash_flow_categories', function (Blueprint $table) {
            $table->foreignUuid('store_id')->nullable()->after('id')->index()->constrained('stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_flow_categories', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('discount_types', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
