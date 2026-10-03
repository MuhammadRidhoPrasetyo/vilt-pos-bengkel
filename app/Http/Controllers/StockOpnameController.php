<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockOpnameRequest;
use App\Http\Requests\UpdateStockOpnameRequest;
use App\Http\Resources\StockOpnameResource;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\Store;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\StockOpnameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function __construct(private readonly StockOpnameService $service) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        if ($status === 'all') {
            $status = '';
        }
        $storeId = $request->string('store_id')->toString();
        if ($storeId === 'all') {
            $storeId = '';
        }
        $warehouseId = $request->string('warehouse_id')->toString();
        if ($warehouseId === 'all') {
            $warehouseId = '';
        }

        $query = StockOpname::query()
            ->with(['store:id,name,code', 'warehouse:id,name,code', 'postedBy:id,name', 'createdBy:id,name'])
            ->withCount('items')
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('opname_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return Inertia::render('stock-opnames/index', [
            'records' => StockOpnameResource::collection($query->paginate(15)->withQueryString()),
            'summary' => [
                'draft' => StockOpname::where('status', 'draft')->count(),
                'posted' => StockOpname::where('status', 'posted')->count(),
                'cancelled' => StockOpname::where('status', 'cancelled')->count(),
            ],
            'filters' => compact('search', 'status', 'storeId', 'warehouseId'),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('stock-opnames/create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreStockOpnameRequest $request): RedirectResponse
    {
        $opname = $this->service->create($request->validated(), (string) auth()->id());

        return redirect()->route('stock-opnames.show', $opname)->with('success', 'Draft sesi stock opname berhasil dibuat.');
    }

    public function show(StockOpname $stockOpname): Response
    {
        $stockOpname->load([
            'store:id,name,code',
            'warehouse:id,name,code',
            'createdBy:id,name',
            'postedBy:id,name',
            'items.productVariant.product:id,name',
            'items.warehouseLocation:id,name,full_path',
            'movements.productVariant.product:id,name',
            'movements.warehouse:id,name',
            'movements.inventoryBatch:id,unit_cost,received_at',
        ]);

        return Inertia::render('stock-opnames/show', [
            'record' => new StockOpnameResource($stockOpname),
        ]);
    }

    public function edit(StockOpname $stockOpname): Response
    {
        $stockOpname->load([
            'store:id,name,code',
            'warehouse:id,name,code',
            'items.productVariant.product:id,name',
            'items.warehouseLocation:id,name,full_path',
        ]);

        return Inertia::render('stock-opnames/edit', [
            'record' => new StockOpnameResource($stockOpname),
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateStockOpnameRequest $request, StockOpname $stockOpname): RedirectResponse
    {
        $this->service->update($stockOpname, $request->validated());

        return redirect()->route('stock-opnames.show', $stockOpname)->with('success', 'Draft stock opname berhasil diperbarui.');
    }

    public function destroy(StockOpname $stockOpname): RedirectResponse
    {
        $this->service->delete($stockOpname);

        return redirect()->route('stock-opnames.index')->with('success', 'Draft stock opname berhasil dihapus.');
    }

    public function post(StockOpname $stockOpname): RedirectResponse
    {
        $this->service->post($stockOpname, (string) auth()->id());

        return redirect()->route('stock-opnames.show', $stockOpname)->with('success', 'Hasil stock opname berhasil diposting ke kartu stok persediaan.');
    }

    public function cancel(StockOpname $stockOpname): RedirectResponse
    {
        $this->service->cancel($stockOpname);

        return redirect()->route('stock-opnames.show', $stockOpname)->with('success', 'Draft stock opname berhasil dibatalkan.');
    }

    public function warehouseStock(Warehouse $warehouse): JsonResponse
    {
        $items = $this->service->getWarehouseStockItems($warehouse->id);

        return response()->json([
            'warehouse_id' => $warehouse->id,
            'items' => $items,
        ]);
    }

    private function options(): array
    {
        return [
            'stores' => Store::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
            'warehouses' => Warehouse::query()->select(['id', 'store_id', 'name', 'code'])->orderBy('name')->get()->map(fn ($warehouse) => ['label' => "{$warehouse->name} ({$warehouse->code})", 'value' => $warehouse->id, 'store_id' => $warehouse->store_id]),
            'warehouseLocations' => WarehouseLocation::query()->select(['id', 'warehouse_id', 'name', 'full_path'])->orderBy('full_path')->get()->map(fn ($location) => ['label' => $location->full_path ?? $location->name, 'value' => $location->id, 'warehouse_id' => $location->warehouse_id]),
            'variants' => ProductVariant::with([
                'product:id,name,product_category_id,brand_id,unit_id',
                'product.category:id,name',
                'product.brand:id,name',
                'product.unit:id,name,symbol',
                'media',
                'product.media',
            ])->orderBy('sku')->get()->map(fn ($variant) => [
                'id' => $variant->id,
                'label' => $variant->display_receipt_name,
                'value' => $variant->id,
                'name' => $variant->display_receipt_name,
                'product_name' => $variant->product?->name,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'category_id' => $variant->product?->product_category_id,
                'category_name' => $variant->product?->category?->name ?? '-',
                'brand_name' => $variant->product?->brand?->name ?? '-',
                'unit_name' => $variant->product?->unit?->symbol ?? $variant->product?->unit?->name ?? 'Pcs',
                'image_url' => $variant->getFirstMediaUrl('images', 'thumb')
                    ?: $variant->product?->getFirstMediaUrl('images', 'thumb')
                    ?: null,
                'default_purchase_price' => (float) $variant->default_purchase_price,
            ]),
        ];
    }
}
