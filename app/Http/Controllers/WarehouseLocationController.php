<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseLocationRequest;
use App\Http\Requests\UpdateWarehouseLocationRequest;
use App\Http\Resources\WarehouseLocationResource;
use App\Models\WarehouseLocation;
use App\Repositories\WarehouseLocationRepository;
use App\Repositories\WarehouseRepository;
use App\Services\WarehouseLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseLocationController extends Controller
{
    public function __construct(
        private readonly WarehouseLocationRepository $warehouseLocations,
        private readonly WarehouseRepository $warehouses,
        private readonly WarehouseLocationService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('warehouse-locations/index', [
            'records' => WarehouseLocationResource::collection($this->warehouseLocations->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [
                'warehouses' => $this->warehouses->options()->map(fn ($warehouse) => ['label' => $warehouse->name, 'value' => $warehouse->id]),
                'parents' => $this->warehouseLocations->options()->map(fn ($location) => ['label' => $location->name, 'value' => $location->id]),
            ],
            'config' => [
                'title' => 'Warehouse Locations',
                'singular' => 'Warehouse Location',
                'route' => '/warehouse-locations',
                'searchPlaceholder' => 'Cari lokasi gudang',
                'defaults' => ['type' => 'rack', 'is_active' => true],
                'fields' => [
                    ['name' => 'warehouse_id', 'label' => 'Warehouse', 'type' => 'select', 'optionKey' => 'warehouses', 'required' => true, 'table' => true, 'displayKey' => 'warehouse.name'],
                    ['name' => 'parent_id', 'label' => 'Parent Lokasi', 'type' => 'select', 'optionKey' => 'parents', 'displayKey' => 'parent.name'],
                    ['name' => 'type', 'label' => 'Tipe Lokasi', 'type' => 'select', 'required' => true, 'table' => true, 'displayKey' => 'type_label', 'options' => [
                        ['label' => 'Rak Susun (Rack)', 'value' => 'rack'],
                        ['label' => 'Ambalan / Tingkat Rak (Shelf)', 'value' => 'shelf'],
                        ['label' => 'Kotak Part / Laci Baut (Bin)', 'value' => 'bin'],
                        ['label' => 'Etalase Kaca (Showcase)', 'value' => 'showcase'],
                        ['label' => 'Rak Gantung (Ban / Belt / Rantai)', 'value' => 'hanging'],
                        ['label' => 'Lantai / Palet (Drum Oli / Part Berat)', 'value' => 'floor'],
                        ['label' => 'Lemari / Locker (Alat Khusus / SST)', 'value' => 'cabinet'],
                        ['label' => 'Zona / Area Gudang', 'value' => 'zone'],
                        ['label' => 'Area Transit / Bongkar Muat', 'value' => 'staging'],
                    ]],
                    ['name' => 'code', 'label' => 'Kode', 'required' => true, 'table' => true],
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'table' => true],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('warehouse-locations.index');
    }

    public function store(StoreWarehouseLocationRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('warehouse-locations.index')->with('success', 'Warehouse location berhasil dibuat.');
    }

    public function show(WarehouseLocation $warehouseLocation): RedirectResponse
    {
        return redirect()->route('warehouse-locations.index');
    }

    public function edit(WarehouseLocation $warehouseLocation): RedirectResponse
    {
        return redirect()->route('warehouse-locations.index');
    }

    public function update(UpdateWarehouseLocationRequest $request, WarehouseLocation $warehouseLocation): RedirectResponse
    {
        $this->service->update($warehouseLocation, $request->validated());

        return redirect()->route('warehouse-locations.index')->with('success', 'Warehouse location berhasil diperbarui.');
    }

    public function destroy(WarehouseLocation $warehouseLocation): RedirectResponse
    {
        $this->service->delete($warehouseLocation);

        return redirect()->route('warehouse-locations.index')->with('success', 'Warehouse location berhasil dihapus.');
    }
}
