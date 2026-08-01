<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Http\Resources\StockTransferResource;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function __construct(private readonly StockTransferService $service) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $storeId = $request->string('store_id')->toString();

        $query = StockTransfer::query()
            ->with(['fromStore:id,name', 'toStore:id,name', 'createdBy:id,name', 'postedBy:id,name'])
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
            $query->where(fn ($q) => $q->where('from_store_id', $storeId)->orWhere('to_store_id', $storeId));
        }

        return Inertia::render('stock-transfers/index', [
            'records' => StockTransferResource::collection($query->paginate(15)->withQueryString()),
            'summary' => [
                'draft' => StockTransfer::where('status', 'draft')->count(),
                'posted' => StockTransfer::where('status', 'posted')->count(),
                'cancelled' => StockTransfer::where('status', 'cancelled')->count(),
            ],
            'filters' => compact('search', 'status', 'storeId'),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('stock-transfers/create', ['options' => $this->options()]);
    }

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $transfer = $this->service->create($request->validated(), (string) auth()->id());

        return redirect()->route('stock-transfers.show', $transfer)->with('success', 'Stock transfer draft berhasil dibuat.');
    }

    public function show(StockTransfer $stockTransfer): Response
    {
        $stockTransfer->load([
            'fromStore:id,name',
            'toStore:id,name',
            'createdBy:id,name',
            'postedBy:id,name',
            'items.productVariant.product:id,name',
            'items.fromWarehouse:id,name',
            'items.toWarehouse:id,name',
            'items.fromWarehouseLocation:id,name,full_path',
            'items.toWarehouseLocation:id,name,full_path',
            'movements.productVariant.product:id,name',
            'movements.warehouse:id,name',
            'movements.inventoryBatch:id,unit_cost,received_at',
        ]);

        return Inertia::render('stock-transfers/show', ['record' => new StockTransferResource($stockTransfer)]);
    }

    public function edit(StockTransfer $stockTransfer): Response
    {
        $stockTransfer->load(['items.productVariant.product:id,name', 'items.fromWarehouse', 'items.toWarehouse', 'items.fromWarehouseLocation', 'items.toWarehouseLocation']);

        return Inertia::render('stock-transfers/edit', [
            'record' => new StockTransferResource($stockTransfer),
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->service->update($stockTransfer, $request->validated());

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Stock transfer draft berhasil diperbarui.');
    }

    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->service->delete($stockTransfer);

        return redirect()->route('stock-transfers.index')->with('success', 'Stock transfer draft berhasil dihapus.');
    }

    public function post(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->service->post($stockTransfer, (string) auth()->id());

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Stock transfer berhasil diposting.');
    }

    public function cancel(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->service->cancel($stockTransfer);

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Stock transfer draft berhasil dibatalkan.');
    }

    private function options(): array
    {
        return [
            'stores' => Store::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
            'warehouses' => Warehouse::query()->select(['id', 'store_id', 'name'])->orderBy('name')->get()->map(fn ($warehouse) => ['label' => $warehouse->name, 'value' => $warehouse->id, 'store_id' => $warehouse->store_id]),
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
            'prices' => ProductPrice::query()->select(['id', 'product_variant_id', 'store_id', 'purchase_price', 'selling_price'])->get(),
        ];
    }
}
