<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashFlowCategoryController;
use App\Http\Controllers\DiscountTypeController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerRoleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDiscountController;
use App\Http\Controllers\ProductPriceController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseLocationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/home', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::resource('stores', StoreController::class);
    Route::resource('partners', PartnerController::class);
    Route::resource('partner-roles', PartnerRoleController::class);
    Route::resource('discount-types', DiscountTypeController::class);
    Route::resource('brands', BrandController::class);
    Route::resource('units', UnitController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('cash-flow-categories', CashFlowCategoryController::class);
    Route::resource('product-categories', ProductCategoryController::class);
    Route::resource('products', ProductController::class);
    Route::post('products/{product}/attributes', [ProductAttributeController::class, 'store'])->name('products.attributes.store');
    Route::put('product-attributes/{attribute}', [ProductAttributeController::class, 'update'])->name('product-attributes.update');
    Route::delete('product-attributes/{attribute}', [ProductAttributeController::class, 'destroy'])->name('product-attributes.destroy');
    Route::resource('product-variants', ProductVariantController::class);
    Route::resource('product-discounts', ProductDiscountController::class);
    Route::resource('product-stocks', ProductStockController::class);
    Route::resource('product-prices', ProductPriceController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::resource('services', ServiceOrderController::class);
    Route::get('printers/{printer}/test', [PrinterController::class, 'test'])->name('printers.test');
    Route::resource('printers', PrinterController::class);
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('warehouse-locations', WarehouseLocationController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('users', UserController::class);
});
