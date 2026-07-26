<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('receipt_name')->nullable()->after('name');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('receipt_name')->nullable()->after('name_suffix');
        });

        Schema::dropIfExists('product_labels');
    }

    public function down(): void
    {
        Schema::create('product_labels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_category_id')->constrained();
            $table->foreignUuid('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('label_sku')->nullable();
            $table->boolean('label_category')->nullable();
            $table->boolean('label_brand')->nullable();
            $table->boolean('label_type')->nullable();
            $table->boolean('label_unit')->nullable();
            $table->boolean('label_size')->nullable();
            $table->boolean('label_keyword')->nullable();
            $table->boolean('label_compatibility')->nullable();
            $table->boolean('label_description')->nullable();
            $table->string('separator')->nullable();
            $table->timestamps();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('receipt_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('receipt_name');
        });
    }
};
