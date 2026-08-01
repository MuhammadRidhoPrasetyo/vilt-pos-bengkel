<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\UpdateStockAdjustmentRequest;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\Store;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockAdjustmentService $service) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $storeId = $request->string('store_id')->toString();

        $query = StockAdjustment::query()
            ->with(['store:id,name', 'postedBy:id,name'])
            ->withCount('items')
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return Inertia::render('stock-adjustments/index', [
            'records' => StockAdjustmentResource::collection($query->paginate(15)->withQueryString()),
            'summary' => [
                'draft' => StockAdjustment::where('status', 'draft')->count(),
                'posted' => StockAdjustment::where('status', 'posted')->count(),
                'cancelled' => StockAdjustment::where('status', 'cancelled')->count(),
            ],
            'filters' => compact('search', 'status', 'storeId'),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('stock-adjustments/create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        $adjustment = $this->service->create($request->validated(), (string) auth()->id());

        return redirect()->route('stock-adjustments.show', $adjustment)->with('success', 'Stock adjustment draft berhasil dibuat.');
    }

    public function show(StockAdjustment $stockAdjustment): Response
    {
        $stockAdjustment->load([
            'store:id,name',
            'postedBy:id,name',
            'items.productVariant.product:id,name',
            'items.warehouse:id,name',
            'items.warehouseLocation:id,name,full_path',
            'movements.productVariant.product:id,name',
            'movements.warehouse:id,name',
            'movements.inventoryBatch:id,unit_cost,received_at',
        ]);

        return Inertia::render('stock-adjustments/show', [
            'record' => new StockAdjustmentResource($stockAdjustment),
        ]);
    }

    public function edit(StockAdjustment $stockAdjustment): Response
    {
        $stockAdjustment->load(['items.productVariant.product:id,name', 'items.warehouse', 'items.warehouseLocation']);

        return Inertia::render('stock-adjustments/edit', [
            'record' => new StockAdjustmentResource($stockAdjustment),
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateStockAdjustmentRequest $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->service->update($stockAdjustment, $request->validated());

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('success', 'Stock adjustment draft berhasil diperbarui.');
    }

    public function destroy(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->service->delete($stockAdjustment);

        return redirect()->route('stock-adjustments.index')->with('success', 'Stock adjustment draft berhasil dihapus.');
    }

    public function post(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->service->post($stockAdjustment, (string) auth()->id());

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('success', 'Stock adjustment berhasil diposting.');
    }

    public function cancel(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->service->cancel($stockAdjustment);

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('success', 'Stock adjustment draft berhasil dibatalkan.');
    }

    private function options(): array
    {
        return [
            'stores' => Store::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
            'warehouses' => Warehouse::query()->select(['id', 'store_id', 'name'])->orderBy('name')->get()->map(fn ($warehouse) => ['label' => $warehouse->name, 'value' => $warehouse->id, 'store_id' => $warehouse->store_id]),
            'warehouseLocations' => WarehouseLocation::query()->select(['id', 'warehouse_id', 'name', 'full_path'])->orderBy('full_path')->get()->map(fn ($location) => ['label' => $location->full_path ?? $location->name, 'value' => $location->id, 'warehouse_id' => $location->warehouse_id]),
            'variants' => ProductVariant::with('product:id,name')->orderBy('sku')->get()->map(fn ($variant) => [
                'label' => $variant->display_receipt_name,
                'value' => $variant->id,
                'sku' => $variant->sku,
                'default_purchase_price' => (float) $variant->default_purchase_price,
            ]),
        ];
    }
}
