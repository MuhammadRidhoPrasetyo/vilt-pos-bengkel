<?php

namespace App\Http\Controllers;

use App\Http\Resources\CashFlowResource;
use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use App\Models\Store;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function __invoke()
    {
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

        return inertia('Dashboard', [
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $netBalance,
                'total_transactions' => $totalTransactions,
                'total_revenue' => $totalRevenue,
            ],
            'recentCashFlows' => CashFlowResource::collection($recentCashFlows),
            'options' => [
                'stores' => $stores->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'incomeCategories' => $categories->filter(fn ($c) => $c->type === 'income')->values()->map(fn ($c) => ['label' => $c->name, 'value' => $c->id]),
                'expenseCategories' => $categories->filter(fn ($c) => $c->type === 'expense')->values()->map(fn ($c) => ['label' => $c->name, 'value' => $c->id]),
            ],
        ]);
    }
}
