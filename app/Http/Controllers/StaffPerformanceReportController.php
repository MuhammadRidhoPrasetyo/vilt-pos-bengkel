<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrderItem;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StaffPerformanceReportController extends Controller
{
    /**
     * Display staff performance and productivity report.
     */
    public function index(Request $request): Response
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $storeId = $request->input('store_id');
        $roleFilter = $request->input('role', 'all');
        $search = $request->input('search');

        // Stores for filter dropdown
        $stores = Store::select('id', 'name', 'code')->orderBy('name')->get();

        // 1. CASHIERS PERFORMANCE
        $cashiersData = [];
        if ($roleFilter === 'all' || $roleFilter === 'kasir') {
            $cashierQuery = User::query()
                ->with(['store:id,name,code'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereHas('roles', fn ($rq) => $rq->where('name', 'kasir'))
                        ->orWhereHas('transactions', fn ($tq) => $tq->whereBetween('transaction_date', [$startDate, $endDate]));
                })
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId));

            $cashierUsers = $cashierQuery->get();

            // Aggregated transactions per user in the period
            $txStats = Transaction::query()
                ->select(
                    'user_id',
                    DB::raw('COUNT(id) as total_transactions'),
                    DB::raw('COALESCE(SUM(grand_total), 0) as total_sales'),
                    DB::raw('COALESCE(AVG(grand_total), 0) as average_sales')
                )
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', '!=', 'void')
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $cashiersData = $cashierUsers->map(function ($user) use ($txStats) {
                $stat = $txStats->get($user->id);
                $count = (int) ($stat->total_transactions ?? 0);
                $total = (float) ($stat->total_sales ?? 0);
                $avg = (float) ($stat->average_sales ?? 0);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nik' => $user->nik,
                    'phone' => $user->phone,
                    'active' => (bool) $user->active,
                    'store' => $user->store ? [
                        'id' => $user->store->id,
                        'name' => $user->store->name,
                        'code' => $user->store->code,
                    ] : null,
                    'total_transactions' => $count,
                    'total_sales' => $total,
                    'average_sales' => $avg,
                ];
            })->sortByDesc('total_sales')->values()->all();
        }

        // 2. MECHANICS PERFORMANCE
        $mechanicsData = [];
        if ($roleFilter === 'all' || $roleFilter === 'mekanik') {
            $mechanicQuery = User::query()
                ->with(['store:id,name,code'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereHas('roles', fn ($rq) => $rq->where('name', 'mekanik'))
                        ->orWhereHas('serviceOrderItems', fn ($siq) => $siq->whereBetween(
                            DB::raw('COALESCE(service_order_items.assigned_at, service_order_items.created_at)'),
                            [$startDate, $endDate]
                        ));
                })
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId));

            $mechanicUsers = $mechanicQuery->get();

            // Aggregated service items per mechanic in the period
            $itemStats = ServiceOrderItem::query()
                ->select(
                    'mechanic_id',
                    DB::raw('COUNT(service_order_items.id) as total_jobs'),
                    DB::raw('COUNT(DISTINCT service_order_items.service_order_id) as total_vehicles'),
                    DB::raw('COALESCE(SUM(service_order_items.line_total), 0) as total_revenue'),
                    DB::raw('COALESCE(AVG(service_order_items.line_total), 0) as average_job')
                )
                ->join('service_orders', 'service_orders.id', '=', 'service_order_items.service_order_id')
                ->whereBetween(DB::raw('COALESCE(service_order_items.assigned_at, service_order_items.created_at)'), [$startDate, $endDate])
                ->whereNull('service_order_items.deleted_at')
                ->whereNull('service_orders.deleted_at')
                ->when($storeId, fn ($q) => $q->where('service_orders.store_id', $storeId))
                ->groupBy('mechanic_id')
                ->get()
                ->keyBy('mechanic_id');

            $mechanicsData = $mechanicUsers->map(function ($user) use ($itemStats) {
                $stat = $itemStats->get($user->id);
                $count = (int) ($stat->total_jobs ?? 0);
                $vehicles = (int) ($stat->total_vehicles ?? 0);
                $total = (float) ($stat->total_revenue ?? 0);
                $avg = (float) ($stat->average_job ?? 0);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nik' => $user->nik,
                    'phone' => $user->phone,
                    'active' => (bool) $user->active,
                    'store' => $user->store ? [
                        'id' => $user->store->id,
                        'name' => $user->store->name,
                        'code' => $user->store->code,
                    ] : null,
                    'total_jobs' => $count,
                    'total_vehicles' => $vehicles,
                    'total_revenue' => $total,
                    'average_job' => $avg,
                ];
            })->sortByDesc('total_revenue')->values()->all();
        }

        // Summary KPI Metrics
        $totalCashierSales = array_sum(array_column($cashiersData, 'total_sales'));
        $totalCashierTransactions = array_sum(array_column($cashiersData, 'total_transactions'));
        $totalMechanicRevenue = array_sum(array_column($mechanicsData, 'total_revenue'));
        $totalMechanicJobs = array_sum(array_column($mechanicsData, 'total_jobs'));

        return Inertia::render('reports/staff-performance', [
            'cashiers' => $cashiersData,
            'mechanics' => $mechanicsData,
            'stores' => $stores,
            'summary' => [
                'total_cashier_sales' => $totalCashierSales,
                'total_cashier_transactions' => $totalCashierTransactions,
                'total_mechanic_revenue' => $totalMechanicRevenue,
                'total_mechanic_jobs' => $totalMechanicJobs,
            ],
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'store_id' => $storeId,
                'role' => $roleFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Get detailed recent transactions for a cashier within the period.
     */
    public function cashierDetails(Request $request, User $user): JsonResponse
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $storeId = $request->input('store_id');

        $transactions = Transaction::with(['customer:id,name,phone', 'payment:id,name'])
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', '!=', 'void')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->orderByDesc('transaction_date')
            ->limit(50)
            ->get()
            ->map(fn ($tx) => [
                'id' => $tx->id,
                'number' => $tx->number,
                'transaction_date' => $tx->transaction_date?->format('d/m/Y H:i'),
                'customer_name' => $tx->customer?->name ?? 'Pelanggan Umum',
                'payment_name' => $tx->payment?->name ?? 'Tunai',
                'grand_total' => (float) $tx->grand_total,
                'status' => $tx->status,
                'payment_status' => $tx->payment_status,
            ]);

        return response()->json([
            'cashier' => [
                'id' => $user->id,
                'name' => $user->name,
                'nik' => $user->nik,
            ],
            'transactions' => $transactions,
        ]);
    }

    /**
     * Get detailed recent service jobs for a mechanic within the period.
     */
    public function mechanicDetails(Request $request, User $user): JsonResponse
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $storeId = $request->input('store_id');

        $items = ServiceOrderItem::with(['serviceOrder.customer:id,name'])
            ->where('mechanic_id', $user->id)
            ->whereBetween(DB::raw('COALESCE(service_order_items.assigned_at, service_order_items.created_at)'), [$startDate, $endDate])
            ->whereHas('serviceOrder', fn ($q) => $q->when($storeId, fn ($sq) => $sq->where('store_id', $storeId)))
            ->orderByDesc(DB::raw('COALESCE(service_order_items.assigned_at, service_order_items.created_at)'))
            ->limit(50)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'date' => ($item->assigned_at ?? $item->created_at)?->format('d/m/Y H:i'),
                'spk_number' => $item->serviceOrder?->number,
                'plate_number' => $item->serviceOrder?->plate_number,
                'vehicle' => trim(($item->serviceOrder?->vehicle_brand ?? '').' '.($item->serviceOrder?->vehicle_model ?? '')),
                'customer_name' => $item->serviceOrder?->customer?->name ?? $item->serviceOrder?->customer_name ?? '-',
                'description' => $item->description,
                'quantity' => $item->quantity,
                'line_total' => (float) $item->line_total,
            ]);

        return response()->json([
            'mechanic' => [
                'id' => $user->id,
                'name' => $user->name,
                'nik' => $user->nik,
            ],
            'items' => $items,
        ]);
    }
}
