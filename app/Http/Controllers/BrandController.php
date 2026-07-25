<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Repositories\BrandRepository;
use App\Repositories\StoreRepository;
use App\Services\BrandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function __construct(
        private readonly BrandRepository $brands,
        private readonly StoreRepository $stores,
        private readonly BrandService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('brands/index', [
            'records' => BrandResource::collection($this->brands->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
            ],
            'config' => [
                'title' => 'Brands',
                'singular' => 'Brand',
                'route' => '/brands',
                'searchPlaceholder' => 'Cari brand',
                'fields' => [
                    ['name' => 'store_id', 'label' => 'Store', 'type' => 'select', 'optionKey' => 'stores', 'table' => true, 'displayKey' => 'store.name'],
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('brands.index');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('brands.index')->with('success', 'Brand berhasil dibuat.');
    }

    public function show(Brand $brand): RedirectResponse
    {
        return redirect()->route('brands.index');
    }

    public function edit(Brand $brand): RedirectResponse
    {
        return redirect()->route('brands.index');
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->service->update($brand, $request->validated());

        return redirect()->route('brands.index')->with('success', 'Brand berhasil diperbarui.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->service->delete($brand);

        return redirect()->route('brands.index')->with('success', 'Brand berhasil dihapus.');
    }
}
