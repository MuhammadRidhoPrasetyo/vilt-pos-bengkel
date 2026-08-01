<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashFlowCategoryController;
use App\Http\Controllers\DatabaseBackupController;
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
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseLocationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('home') : redirect()->route('login');
});

Route::get('/home', function () {
    return Inertia::render('Portal');
})->middleware(['auth'])->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth'])->name('dashboard');

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
    Route::get('services/display', [ServiceOrderController::class, 'tvDisplay'])->name('services.display');
    Route::patch('services/{service}/status', [ServiceOrderController::class, 'updateStatus'])->name('services.status.update');
    Route::resource('services', ServiceOrderController::class);
    Route::get('transactions/{transaction}/print', [TransactionController::class, 'print'])->name('transactions.print');
    Route::post('transactions/{transaction}/payment-attempts', [TransactionController::class, 'storePaymentAttempt'])->name('transactions.payment-attempts.store');
    Route::resource('transactions', TransactionController::class);
    Route::get('printers/{printer}/test', [PrinterController::class, 'test'])->name('printers.test');
    Route::resource('printers', PrinterController::class);
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('warehouse-locations', WarehouseLocationController::class);
    Route::post('stock-adjustments/{stockAdjustment}/post', [StockAdjustmentController::class, 'post'])->name('stock-adjustments.post');
    Route::post('stock-adjustments/{stockAdjustment}/cancel', [StockAdjustmentController::class, 'cancel'])->name('stock-adjustments.cancel');
    Route::resource('stock-adjustments', StockAdjustmentController::class);
    Route::post('stock-transfers/{stockTransfer}/post', [StockTransferController::class, 'post'])->name('stock-transfers.post');
    Route::post('stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
    Route::resource('stock-transfers', StockTransferController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('users', UserController::class);

    // Database Backup & Restore Routes
    Route::get('settings/database', [DatabaseBackupController::class, 'index'])->name('settings.database.index');
    Route::get('settings/database/export', [DatabaseBackupController::class, 'export'])->name('settings.database.export');
    Route::post('settings/database/import', [DatabaseBackupController::class, 'import'])->name('settings.database.import');
});
