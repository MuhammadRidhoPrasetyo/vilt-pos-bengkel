<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\CashFlowCategory;
use App\Models\ProductCategory;
use App\Repositories\ProductCategoryRepository;
use App\Repositories\StoreRepository;
use App\Services\ProductCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function __construct(
        private readonly ProductCategoryRepository $productCategories,
        private readonly StoreRepository $stores,
        private readonly ProductCategoryService $service
    ) {}

    public function index(Request $request): Response
    {
        $incomeCategories = CashFlowCategory::where('is_active', true)->where('type', 'income')->orderBy('name')->get();
        $expenseCategories = CashFlowCategory::where('is_active', true)->where('type', 'expense')->orderBy('name')->get();

        return Inertia::render('product-categories/index', [
            'records' => ProductCategoryResource::collection($this->productCategories->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
                'productCategories' => $this->productCategories->options()->map(fn ($category) => ['label' => $category->name, 'value' => $category->id]),
                'incomeCashFlowCategories' => $incomeCategories->map(fn ($cat) => ['label' => $cat->name, 'value' => $cat->id]),
                'expenseCashFlowCategories' => $expenseCategories->map(fn ($cat) => ['label' => $cat->name, 'value' => $cat->id]),
            ],
            'config' => [
                'title' => 'Product Categories',
                'singular' => 'Product Category',
                'route' => '/product-categories',
                'searchPlaceholder' => 'Cari kategori produk',
                'defaults' => ['pricing_mode' => 'fixed'],
                'fields' => [
                    ['name' => 'store_id', 'label' => 'Store', 'type' => 'select', 'optionKey' => 'stores', 'table' => true, 'displayKey' => 'store.name'],
                    ['name' => 'parent_id', 'label' => 'Parent Category', 'type' => 'select', 'optionKey' => 'productCategories', 'table' => true, 'displayKey' => 'parent.name'],
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'pricing_mode', 'label' => 'Mode Harga', 'type' => 'select', 'required' => true, 'table' => true, 'options' => [['label' => 'Fixed', 'value' => 'fixed'], ['label' => 'Editable', 'value' => 'editable']]],
                    ['name' => 'income_cash_flow_category_id', 'label' => 'Kategori Arus Kas Pemasukan', 'type' => 'select', 'optionKey' => 'incomeCashFlowCategories', 'table' => true, 'displayKey' => 'income_cash_flow_category.name'],
                    ['name' => 'expense_cash_flow_category_id', 'label' => 'Kategori Arus Kas Pengeluaran', 'type' => 'select', 'optionKey' => 'expenseCashFlowCategories', 'table' => true, 'displayKey' => 'expense_cash_flow_category.name'],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('product-categories.index');
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('product-categories.index')->with('success', 'Kategori produk berhasil dibuat.');
    }

    public function show(ProductCategory $productCategory): RedirectResponse
    {
        return redirect()->route('product-categories.index');
    }

    public function edit(ProductCategory $productCategory): RedirectResponse
    {
        return redirect()->route('product-categories.index');
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $this->service->update($productCategory, $request->validated());

        return redirect()->route('product-categories.index')->with('success', 'Kategori produk berhasil diperbarui.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $this->service->delete($productCategory);

        return redirect()->route('product-categories.index')->with('success', 'Kategori produk berhasil dihapus.');
    }
}
