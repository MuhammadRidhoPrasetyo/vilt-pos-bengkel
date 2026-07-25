<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscountTypeRequest;
use App\Http\Requests\UpdateDiscountTypeRequest;
use App\Http\Resources\DiscountTypeResource;
use App\Models\DiscountType;
use App\Repositories\DiscountTypeRepository;
use App\Repositories\StoreRepository;
use App\Services\DiscountTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountTypeController extends Controller
{
    public function __construct(
        private readonly DiscountTypeRepository $discountTypes,
        private readonly StoreRepository $stores,
        private readonly DiscountTypeService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('discount-types/index', [
            'records' => DiscountTypeResource::collection($this->discountTypes->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
            ],
            'config' => [
                'title' => 'Discount Types',
                'singular' => 'Discount Type',
                'route' => '/discount-types',
                'searchPlaceholder' => 'Cari discount type',
                'fields' => [
                    ['name' => 'store_id', 'label' => 'Store', 'type' => 'select', 'optionKey' => 'stores', 'table' => true, 'displayKey' => 'store.name'],
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'table' => true],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('discount-types.index');
    }

    public function store(StoreDiscountTypeRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('discount-types.index')->with('success', 'Discount type berhasil dibuat.');
    }

    public function show(DiscountType $discountType): RedirectResponse
    {
        return redirect()->route('discount-types.index');
    }

    public function edit(DiscountType $discountType): RedirectResponse
    {
        return redirect()->route('discount-types.index');
    }

    public function update(UpdateDiscountTypeRequest $request, DiscountType $discountType): RedirectResponse
    {
        $this->service->update($discountType, $request->validated());

        return redirect()->route('discount-types.index')->with('success', 'Discount type berhasil diperbarui.');
    }

    public function destroy(DiscountType $discountType): RedirectResponse
    {
        $this->service->delete($discountType);

        return redirect()->route('discount-types.index')->with('success', 'Discount type berhasil dihapus.');
    }
}
