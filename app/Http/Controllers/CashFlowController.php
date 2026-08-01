<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashFlowRequest;
use App\Http\Resources\CashFlowResource;
use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashFlowController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $categoryId = $request->string('category_id')->toString();
        $storeId = $request->string('store_id')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $query = CashFlow::query()
            ->with(['store:id,name', 'user:id,name', 'category:id,name,type'])
            ->when($search, fn ($q) => $q->where('description', 'like', "%{$search}%"))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($startDate, fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('date', '<=', $endDate));

        // Summary calculations
        $totalIncome = (float) (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (float) (clone $query)->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $cashFlows = (clone $query)->latest('date')->latest('created_at')->paginate(15)->withQueryString();

        $stores = Store::orderBy('name')->get(['id', 'name']);
        $categories = CashFlowCategory::where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']);

        return Inertia::render('cash-flows/index', [
            'records' => CashFlowResource::collection($cashFlows),
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $netBalance,
            ],
            'filters' => [
                'search' => $search,
                'type' => $type,
                'category_id' => $categoryId,
                'store_id' => $storeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'options' => [
                'stores' => $stores->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
                'categories' => $categories->map(fn ($cat) => ['label' => "{$cat->name} (".($cat->type === 'income' ? 'Pemasukan' : 'Pengeluaran').')', 'value' => $cat->id, 'type' => $cat->type]),
                'incomeCategories' => $categories->filter(fn ($cat) => $cat->type === 'income')->values()->map(fn ($cat) => ['label' => $cat->name, 'value' => $cat->id]),
                'expenseCategories' => $categories->filter(fn ($cat) => $cat->type === 'expense')->values()->map(fn ($cat) => ['label' => $cat->name, 'value' => $cat->id]),
            ],
        ]);
    }

    public function store(StoreCashFlowRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        CashFlow::create($validated);

        return redirect()->back()->with('success', 'Catatan arus kas berhasil disimpan.');
    }

    public function destroy(CashFlow $cashFlow): RedirectResponse
    {
        $cashFlow->delete();

        return redirect()->back()->with('success', 'Catatan arus kas berhasil dihapus.');
    }
}
