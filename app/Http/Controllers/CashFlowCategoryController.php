<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashFlowCategoryRequest;
use App\Http\Requests\UpdateCashFlowCategoryRequest;
use App\Http\Resources\CashFlowCategoryResource;
use App\Models\CashFlowCategory;
use App\Repositories\CashFlowCategoryRepository;
use App\Services\CashFlowCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashFlowCategoryController extends Controller
{
    public function __construct(
        private readonly CashFlowCategoryRepository $cashFlowCategories,
        private readonly CashFlowCategoryService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('cash-flow-categories/index', [
            'records' => CashFlowCategoryResource::collection($this->cashFlowCategories->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'config' => [
                'title' => 'Cash Flow Categories',
                'singular' => 'Cash Flow Category',
                'route' => '/cash-flow-categories',
                'searchPlaceholder' => 'Cari kategori cash flow',
                'defaults' => ['type' => 'expense', 'is_active' => true, 'is_system' => false],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'type', 'label' => 'Tipe', 'type' => 'select', 'required' => true, 'table' => true, 'options' => [['label' => 'Income', 'value' => 'income'], ['label' => 'Expense', 'value' => 'expense']]],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'table' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'table' => true],
                    ['name' => 'is_system', 'label' => 'System', 'type' => 'checkbox', 'table' => true],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('cash-flow-categories.index');
    }

    public function store(StoreCashFlowCategoryRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('cash-flow-categories.index')->with('success', 'Kategori cash flow berhasil dibuat.');
    }

    public function show(CashFlowCategory $cashFlowCategory): RedirectResponse
    {
        return redirect()->route('cash-flow-categories.index');
    }

    public function edit(CashFlowCategory $cashFlowCategory): RedirectResponse
    {
        return redirect()->route('cash-flow-categories.index');
    }

    public function update(UpdateCashFlowCategoryRequest $request, CashFlowCategory $cashFlowCategory): RedirectResponse
    {
        $this->service->update($cashFlowCategory, $request->validated());

        return redirect()->route('cash-flow-categories.index')->with('success', 'Kategori cash flow berhasil diperbarui.');
    }

    public function destroy(CashFlowCategory $cashFlowCategory): RedirectResponse
    {
        $this->service->delete($cashFlowCategory);

        return redirect()->route('cash-flow-categories.index')->with('success', 'Kategori cash flow berhasil dihapus.');
    }
}
