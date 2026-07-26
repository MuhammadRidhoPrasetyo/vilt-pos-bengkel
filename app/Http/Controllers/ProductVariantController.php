<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use App\Repositories\AttributeRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Services\ProductVariantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductVariantRepository $productVariants,
        private readonly ProductRepository $products,
        private readonly AttributeRepository $attributes,
        private readonly ProductVariantService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('product-variants/index', [
            'records' => ProductVariantResource::collection($this->productVariants->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('product-variants/create', [
            'products' => $this->products->options()->map(fn ($product) => ['label' => $product->name, 'value' => $product->id])->values(),
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

    public function store(StoreProductVariantRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->to(url()->previous(route('product-variants.index')))->with('success', 'Varian produk berhasil dibuat.');
    }

    public function show(ProductVariant $productVariant): Response
    {
        $productVariant->load([
            'product:id,name,receipt_name,product_category_id,brand_id,unit_id',
            'product.category:id,name',
            'product.brand:id,name',
            'product.unit:id,name',
            'product.media',
            'attributeOptions:id,attribute_id,value',
            'attributeOptions.attribute:id,name',
            'media',
        ]);

        return Inertia::render('product-variants/show', [
            'productVariant' => ProductVariantResource::make($productVariant),
            'products' => $this->products->options()->map(fn ($product) => ['label' => $product->name, 'value' => $product->id])->values(),
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

    public function edit(ProductVariant $productVariant): Response
    {
        return Inertia::render('product-variants/edit', [
            'productVariant' => ProductVariantResource::make($productVariant->load(['product:id,name', 'attributeOptions:id,attribute_id,value', 'attributeOptions.attribute:id,name'])),
            'products' => $this->products->options()->map(fn ($product) => ['label' => $product->name, 'value' => $product->id])->values(),
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

    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): RedirectResponse
    {
        $this->service->update($productVariant, $request->validated());

        return redirect()->to(url()->previous(route('product-variants.index')))->with('success', 'Varian produk berhasil diperbarui.');
    }

    public function destroy(ProductVariant $productVariant): RedirectResponse
    {
        $this->service->delete($productVariant);

        return redirect()->route('product-variants.index')->with('success', 'Varian produk berhasil dihapus.');
    }
}
