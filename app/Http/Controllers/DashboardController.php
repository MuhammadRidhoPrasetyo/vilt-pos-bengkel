<?php

namespace App\Http\Controllers;

use App\Http\Resources\CashFlowResource;
use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use App\Models\ProductStock;
use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Check if user is Owner/Super-admin or has no assigned store
        $canFilterStore = $user->hasRole('owner') || $user->hasRole('super-admin') || $user->store_id === null;

        $stockStoreId = $request->string('stock_store_id')->toString() ?: null;
        if (! $canFilterStore) {
            $stockStoreId = $user->store_id;
        }

        $totalIncome = (float) CashFlow::where('type', 'income')->sum('amount');
        $totalExpense = (float) CashFlow::where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $recentCashFlows = CashFlow::query()
            ->with(['store:id,name', 'user:id,name', 'category:id,name,type'])
            ->latest('date')
            ->latest('created_at')
            ->take(10)
            ->get();

        $stores = Store::orderBy('name')->get(['id', 'name']);
        $categories = CashFlowCategory::where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']);

        $totalTransactions = Transaction::count();
        $totalRevenue = (float) Transaction::where('status', 'completed')->sum('grand_total');

        // Product Stock Alert Query
        $stockQuery = ProductStock::query()
            ->with([
                'productVariant' => fn ($q) => $q->with('product:id,name'),
                'warehouse.store:id,name',
            ])
            ->where('is_hidden', false)
            ->when($stockStoreId, fn ($q) => $q->whereHas('warehouse', fn ($whQuery) => $whQuery->where('store_id', $stockStoreId)));

        $outOfStockCount = (clone $stockQuery)->where('quantity', '<=', 0)->count();
        $belowMinCount = (clone $stockQuery)->where('quantity', '>', 0)->whereColumn('quantity', '<', 'minimum_stock')->count();
        $approachingMinCount = (clone $stockQuery)
            ->whereColumn('quantity', '>=', 'minimum_stock')
            ->whereRaw('quantity <= minimum_stock + 3')
            ->where('minimum_stock', '>', 0)
            ->count();

        $alertsQuery = (clone $stockQuery)->where(function ($q) {
            $q->where('quantity', '<=', 0)
                ->orWhereColumn('quantity', '<', 'minimum_stock')
                ->orWhere(function ($subQ) {
                    $subQ->whereColumn('quantity', '>=', 'minimum_stock')
                        ->whereRaw('quantity <= minimum_stock + 3')
                        ->where('minimum_stock', '>', 0);
                });
        });

        $stockAlertRecords = $alertsQuery->orderBy('quantity', 'asc')->take(20)->get()->map(function ($stock) {
            $qty = (int) $stock->quantity;
            $minStock = (int) $stock->minimum_stock;

            $alertStatus = 'out_of_stock';
            if ($qty > 0 && $qty < $minStock) {
                $alertStatus = 'below_min';
            } elseif ($qty >= $minStock && $qty <= $minStock + 3 && $minStock > 0) {
                $alertStatus = 'approaching_min';
            }

            return [
                'id' => $stock->id,
                'product_variant_id' => $stock->product_variant_id,
                'variant_display_name' => $stock->productVariant?->display_receipt_name ?? '-',
                'sku' => $stock->productVariant?->sku ?? '-',
                'barcode' => $stock->productVariant?->barcode ?? '-',
                'warehouse_name' => $stock->warehouse?->name ?? '-',
                'store_id' => $stock->warehouse?->store_id,
                'store_name' => $stock->warehouse?->store?->name ?? '-',
                'quantity' => $qty,
                'minimum_stock' => $minStock,
                'alert_status' => $alertStatus,
            ];
        });

        return inertia('Dashboard', [
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $netBalance,
                'total_transactions' => $totalTransactions,
                'total_revenue' => $totalRevenue,
            ],
            'recentCashFlows' => CashFlowResource::collection($recentCashFlows),
            'stockAlerts' => $stockAlertRecords,
            'stockSummary' => [
                'out_of_stock_count' => $outOfStockCount,
                'below_min_count' => $belowMinCount,
                'approaching_min_count' => $approachingMinCount,
                'total_alert_count' => $outOfStockCount + $belowMinCount + $approachingMinCount,
            ],
            'canFilterStore' => $canFilterStore,
            'stockStoreId' => $stockStoreId,
            'options' => [
                'stores' => $stores->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'incomeCategories' => $categories->filter(fn ($c) => $c->type === 'income')->values()->map(fn ($c) => ['label' => $c->name, 'value' => $c->id]),
                'expenseCategories' => $categories->filter(fn ($c) => $c->type === 'expense')->values()->map(fn ($c) => ['label' => $c->name, 'value' => $c->id]),
            ],
        ]);
    }
}
