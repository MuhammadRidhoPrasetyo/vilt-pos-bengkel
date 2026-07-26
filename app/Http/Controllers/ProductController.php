<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Repositories\AttributeRepository;
use App\Repositories\BrandRepository;
use App\Repositories\DiscountTypeRepository;
use App\Repositories\ProductCategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\StoreRepository;
use App\Repositories\UnitRepository;
use App\Repositories\WarehouseLocationRepository;
use App\Repositories\WarehouseRepository;
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
        private readonly AttributeRepository $attributes,
        private readonly StoreRepository $stores,
        private readonly DiscountTypeRepository $discountTypes,
        private readonly WarehouseRepository $warehouses,
        private readonly WarehouseLocationRepository $warehouseLocations,
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
                'attributes' => $this->attributes->options()->map(fn ($attribute) => ['label' => $attribute->name, 'value' => $attribute->id]),
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
                    ['name' => 'receipt_name', 'label' => 'Nama Struk', 'table' => true],
                    ['name' => 'item_type', 'label' => 'Tipe Item', 'type' => 'select', 'required' => true, 'table' => true, 'options' => [['label' => 'Part', 'value' => 'part'], ['label' => 'Labor', 'value' => 'labor']]],
                    ['name' => 'has_variants', 'label' => 'Punya Varian', 'type' => 'checkbox', 'table' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'images', 'label' => 'Gambar Produk (Galeri)', 'type' => 'image_gallery', 'description' => 'Upload foto galeri produk.'],
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

    public function show(Product $product): Response
    {
        $product->load([
            'category:id,name',
            'brand:id,name',
            'unit:id,name',
            'media',
            'attributes',
            'attributes.options:id,attribute_id,value',
            'variants',
            'variants.media',
            'variants.attributeOptions:id,attribute_id,value',
            'variants.attributeOptions.attribute:id,name',
            'variants.discounts',
            'variants.discounts.store:id,name',
            'variants.discounts.discountType:id,name',
            'variants.stocks',
            'variants.stocks.warehouse:id,name',
            'variants.stocks.warehouseLocation:id,name,full_path,warehouse_id',
        ]);

        return Inertia::render('products/show', [
            'product' => ProductResource::make($product),
            'variants' => ProductVariantResource::collection($product->variants),
            'options' => [
                'productCategories' => $this->productCategories->options()->map(fn ($category) => ['label' => $category->name, 'value' => $category->id]),
                'brands' => $this->brands->options()->map(fn ($brand) => ['label' => $brand->name, 'value' => $brand->id]),
                'units' => $this->units->options()->map(fn ($unit) => ['label' => $unit->name, 'value' => $unit->id]),
                'stores' => $this->stores->options()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
                'discountTypes' => $this->discountTypes->options()->map(fn ($discountType) => ['label' => $discountType->name, 'value' => $discountType->id]),
                'warehouses' => $this->warehouses->options()->map(fn ($wh) => ['label' => $wh->name, 'value' => $wh->id]),
                'warehouseLocations' => $this->warehouseLocations->options()->map(fn ($loc) => [
                    'label' => ($loc->full_path ?? $loc->name),
                    'value' => $loc->id,
                    'warehouse_id' => $loc->warehouse_id,
                ]),
            ],
            'attributes' => $this->attributes->options()->map(fn ($attribute) => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'options' => $attribute->options->map(fn ($option) => [
                    'id' => $option->id,
                    'value' => $option->value,
                ])->values(),
            ])->values(),
        ]);
    }

    public function edit(Product $product): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->update($product, $request->validated());

        return redirect()->to(url()->previous(route('products.index')))->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->service->delete($product);

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
