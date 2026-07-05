<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Repositories\BrandRepository;
use App\Services\BrandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function __construct(
        private readonly BrandRepository $brands,
        private readonly BrandService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('brands/index', [
            'records' => BrandResource::collection($this->brands->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'config' => [
                'title' => 'Brands',
                'singular' => 'Brand',
                'route' => '/brands',
                'searchPlaceholder' => 'Cari brand',
                'fields' => [
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
