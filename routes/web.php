<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashFlowCategoryController;
use App\Http\Controllers\DiscountTypeController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerRoleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
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
    Route::resource('attributes', AttributeController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('cash-flow-categories', CashFlowCategoryController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('users', UserController::class);
});
