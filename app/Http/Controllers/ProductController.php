<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\BrandRepository;
use App\Repositories\ProductCategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UnitRepository;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductCategoryRepository $productCategories,
        private readonly BrandRepository $brands,
        private readonly UnitRepository $units,
        private readonly ProductService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('products/index', [
            'records' => ProductResource::collection($this->products->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [
                'productCategories' => $this->productCategories->options()->map(fn ($category) => ['label' => $category->name, 'value' => $category->id]),
                'brands' => $this->brands->options()->map(fn ($brand) => ['label' => $brand->name, 'value' => $brand->id]),
                'units' => $this->units->options()->map(fn ($unit) => ['label' => $unit->name, 'value' => $unit->id]),
            ],
            'config' => [
                'title' => 'Products',
                'singular' => 'Product',
                'route' => '/products',
                'searchPlaceholder' => 'Cari produk',
                'defaults' => ['item_type' => 'part', 'has_variants' => false],
                'fields' => [
                    ['name' => 'product_category_id', 'label' => 'Kategori', 'type' => 'select', 'optionKey' => 'productCategories', 'required' => true, 'table' => true, 'displayKey' => 'category.name'],
                    ['name' => 'brand_id', 'label' => 'Merek', 'type' => 'select', 'optionKey' => 'brands', 'table' => true, 'displayKey' => 'brand.name'],
                    ['name' => 'unit_id', 'label' => 'Satuan', 'type' => 'select', 'optionKey' => 'units', 'table' => true, 'displayKey' => 'unit.name'],
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'item_type', 'label' => 'Tipe Item', 'type' => 'select', 'required' => true, 'table' => true, 'options' => [['label' => 'Part', 'value' => 'part'], ['label' => 'Labor', 'value' => 'labor']]],
                    ['name' => 'has_variants', 'label' => 'Punya Varian', 'type' => 'checkbox', 'table' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('products.index')->with('success', 'Produk berhasil dibuat.');
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    public function edit(Product $product): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->update($product, $request->validated());

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->service->delete($product);

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
