<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('service_orders', 'customer_name')) {
                $table->string('customer_name')->after('customer_id')->nullable();
            }
            if (! Schema::hasColumn('service_orders', 'customer_phone')) {
                $table->string('customer_phone')->after('customer_name')->nullable();
            }
            if (! Schema::hasColumn('service_orders', 'vehicle_id')) {
                $table->uuid('vehicle_id')->after('customer_phone')->nullable();
            }
            if (! Schema::hasColumn('service_orders', 'plate_number')) {
                $table->string('plate_number')->after('vehicle_id')->nullable()->index();
            }
            if (! Schema::hasColumn('service_orders', 'vehicle_brand')) {
                $table->string('vehicle_brand')->after('plate_number')->nullable();
            }
            if (! Schema::hasColumn('service_orders', 'vehicle_model')) {
                $table->string('vehicle_model')->after('vehicle_brand')->nullable();
            }
            if (! Schema::hasColumn('service_orders', 'year')) {
                $table->integer('year')->after('vehicle_model')->nullable();
            }
            if (! Schema::hasColumn('service_orders', 'color')) {
                $table->string('color')->after('year')->nullable();
            }
            if (! Schema::hasColumn('service_orders', 'odometer')) {
                $table->unsignedInteger('odometer')->after('color')->nullable();
            }
            if (! Schema::hasColumn('service_orders', 'diagnosis')) {
                $table->text('diagnosis')->after('general_complaint')->nullable();
            }
        });

        Schema::table('service_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('service_order_items', 'service_order_id')) {
                $table->uuid('service_order_id')->after('id')->nullable()->index();
            }
            if (Schema::hasColumn('service_order_items', 'service_order_unit_id')) {
                $table->uuid('service_order_unit_id')->nullable()->change();
            }
            if (! Schema::hasColumn('service_order_items', 'mechanic_id')) {
                $table->bigInteger('mechanic_id')->after('line_total')->nullable()->index();
            }
            if (! Schema::hasColumn('service_order_items', 'assigned_at')) {
                $table->timestamp('assigned_at')->after('mechanic_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'vehicle_id',
                'plate_number',
                'vehicle_brand',
                'vehicle_model',
                'year',
                'color',
                'odometer',
                'diagnosis',
            ]);
        });

        Schema::table('service_order_items', function (Blueprint $table) {
            $table->dropColumn(['service_order_id', 'mechanic_id', 'assigned_at']);
        });
    }
};
