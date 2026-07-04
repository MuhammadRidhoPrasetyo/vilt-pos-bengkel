<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignUuid('linked_store_id')->nullable()->index()->constrained('stores')->nullOnDelete();
            $table->string('code')->index();
            $table->string('name');
            $table->enum('kind', ['person', 'organization']);
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('npwp')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'code']);
        });

        Schema::create('partner_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_role_partner', function (Blueprint $table) {
            $table->foreignUuid('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('partner_role_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['partner_id', 'partner_role_id']);
        });

        Schema::create('discount_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('provider_code')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_flow_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['income', 'expense']);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable()->index()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->enum('pricing_mode', ['fixed', 'editable'])->default('fixed');
            $table->timestamps();
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_category_id')->constrained();
            $table->foreignUuid('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('item_type', ['part', 'labor']);
            $table->boolean('has_variants')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name_suffix')->nullable();
            $table->decimal('default_purchase_price', 12, 2)->default(0);
            $table->decimal('default_selling_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('attribute_option_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_variant_id', 'attribute_option_id']);
        });

        Schema::create('warehouses', function (Blueprint $table) {
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

        Schema::create('location_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignUuid('location_type_id')->constrained()->restrictOnDelete();
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

        Schema::create('product_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->enum('price_type', ['toko', 'distributor'])->default('toko');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('markup', 12, 2)->default(0);
            $table->enum('markup_type', ['percent', 'amount'])->nullable();
            $table->decimal('selling_price', 12, 2);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('product_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_location_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->boolean('is_hidden')->default(false);
            $table->integer('minimum_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('product_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('discount_type_id')->constrained();
            $table->enum('type', ['percent', 'amount']);
            $table->decimal('value', 12, 2);
            $table->timestamps();
        });

        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_price_id')->constrained()->cascadeOnDelete();
            $table->dateTime('date');
            $table->timestamps();
        });

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

        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('invoice_number')->nullable();
            $table->date('purchase_date');
            $table->enum('discount_type', ['percent', 'amount'])->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->enum('price_type', ['toko', 'distributor'])->default('toko');
            $table->unsignedInteger('quantity_ordered');
            $table->decimal('unit_purchase_price', 12, 2);
            $table->enum('item_discount_type', ['percent', 'amount'])->nullable();
            $table->decimal('item_discount_value', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('initial_quantity');
            $table->integer('current_quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->timestamp('received_at');
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inventory_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type');
            $table->uuid('reference_id');
            $table->enum('type', ['in', 'out']);
            $table->integer('quantity');
            $table->integer('balance_after');
            $table->timestamps();
        });

        Schema::create('product_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained();
            $table->foreignUuid('store_id')->constrained();
            $table->enum('movement_type', ['in', 'out']);
            $table->unsignedInteger('quantity');
            $table->string('movementable_type')->nullable();
            $table->uuid('movementable_id')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('plate_number');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->year('year')->nullable();
            $table->string('color')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'plate_number']);
        });

        Schema::create('service_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUuid('store_id')->constrained();
            $table->foreignUuid('customer_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->enum('status', ['checkin', 'in_progress', 'waiting_parts', 'ready', 'invoiced', 'cancelled'])->default('checkin');
            $table->dateTime('checkin_at');
            $table->dateTime('completed_at')->nullable();
            $table->text('general_complaint')->nullable();
            $table->decimal('estimated_total', 15, 2)->default(0);
            $table->uuid('transaction_id')->nullable();
            $table->timestamps();
        });

        Schema::create('service_order_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_order_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_vehicle_id')->constrained();
            $table->enum('status', ['checkin', 'diagnosis', 'in_progress', 'waiting_parts', 'ready', 'invoiced', 'cancelled'])->default('checkin');
            $table->dateTime('checkin_at');
            $table->dateTime('completed_at')->nullable();
            $table->string('plate_number');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->text('complaint')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('work_done')->nullable();
            $table->decimal('estimated_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('service_order_unit_mechanics', function (Blueprint $table) {
            $table->foreignUuid('service_order_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('mechanic_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['leader', 'assistant'])->default('leader');
            $table->decimal('work_portion', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['service_order_unit_id', 'mechanic_id']);
        });

        Schema::create('service_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_order_unit_id')->index()->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['part', 'labor']);
            $table->foreignUuid('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('service_order_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUuid('store_id')->index()->constrained();
            $table->foreignUuid('user_id')->index()->constrained();
            $table->foreignUuid('customer_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('transaction_date')->index();
            $table->enum('type', ['retail', 'service', 'internal', 'warranty'])->default('retail');
            $table->foreignUuid('service_order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('item_discount_total', 15, 2)->default(0);
            $table->decimal('subtotal_after_item_discount', 15, 2)->default(0);
            $table->enum('universal_discount_mode', ['percent', 'amount'])->nullable();
            $table->decimal('universal_discount_value', 12, 2)->nullable();
            $table->decimal('universal_discount_amount', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('paid');
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->enum('status', ['draft', 'completed', 'void'])->default('completed');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->foreignUuid('store_id')->constrained();
            $table->foreignUuid('product_stock_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->enum('item_discount_mode', ['percent', 'amount'])->nullable();
            $table->decimal('item_discount_value', 12, 2)->nullable();
            $table->decimal('item_discount_amount', 15, 2)->default(0);
            $table->decimal('final_unit_price', 12, 2);
            $table->decimal('line_subtotal', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->foreignUuid('discount_type_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('line_cost_total', 15, 2)->default(0);
            $table->decimal('line_profit', 15, 2)->default(0);
            $table->boolean('price_edited')->default(false);
            $table->string('pricing_mode')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('amount_given', 15, 2)->nullable();
            $table->decimal('change', 15, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_item_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inventory_batch_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained();
            $table->enum('adjustment_type', ['increase', 'decrease']);
            $table->unsignedInteger('quantity');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('from_store_id')->constrained('stores');
            $table->foreignUuid('to_store_id')->constrained('stores');
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->string('reference_number')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->foreignUuid('product_price_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cash_flows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained();
            $table->foreignUuid('category_id')->constrained('cash_flow_categories');
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('date');
            $table->enum('type', ['income', 'expense']);
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->timestamps();
        });

        Schema::create('printers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained();
            $table->string('name');
            $table->enum('connection_type', ['usb', 'network', 'bluetooth']);
            $table->string('address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->foreignUuid('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('sequence')->default(0);
            $table->integer('year')->nullable();
            $table->timestamps();
            $table->unique(['type', 'store_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('printers');
        Schema::dropIfExists('cash_flows');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('transaction_item_batches');
        Schema::dropIfExists('transaction_payment_attempts');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('service_order_customers');
        Schema::dropIfExists('service_order_items');
        Schema::dropIfExists('service_order_unit_mechanics');
        Schema::dropIfExists('service_order_units');
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('customer_vehicles');
        Schema::dropIfExists('product_movements');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('product_labels');
        Schema::dropIfExists('product_price_histories');
        Schema::dropIfExists('product_discounts');
        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('location_types');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('product_variant_attributes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('cash_flow_categories');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('discount_types');
        Schema::dropIfExists('partner_role_partner');
        Schema::dropIfExists('partner_roles');
        Schema::dropIfExists('partners');
    }
};
