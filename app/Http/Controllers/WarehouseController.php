<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Repositories\StoreRepository;
use App\Repositories\WarehouseRepository;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseRepository $warehouses,
        private readonly StoreRepository $stores,
        private readonly WarehouseService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('warehouses/index', [
            'records' => WarehouseResource::collection($this->warehouses->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
            ],
            'config' => [
                'title' => 'Warehouses',
                'singular' => 'Warehouse',
                'route' => '/warehouses',
                'searchPlaceholder' => 'Cari gudang',
                'defaults' => ['is_active' => true],
                'fields' => [
                    ['name' => 'store_id', 'label' => 'Store', 'type' => 'select', 'optionKey' => 'stores', 'required' => true, 'table' => true, 'displayKey' => 'store.name'],
                    ['name' => 'code', 'label' => 'Kode', 'required' => true, 'table' => true],
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'phone', 'label' => 'Telepon', 'table' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'table' => true],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('warehouses.index');
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('warehouses.index')->with('success', 'Warehouse berhasil dibuat.');
    }

    public function show(Warehouse $warehouse): RedirectResponse
    {
        return redirect()->route('warehouses.index');
    }

    public function edit(Warehouse $warehouse): RedirectResponse
    {
        return redirect()->route('warehouses.index');
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->service->update($warehouse, $request->validated());

        return redirect()->route('warehouses.index')->with('success', 'Warehouse berhasil diperbarui.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->service->delete($warehouse);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse berhasil dihapus.');
    }
}
