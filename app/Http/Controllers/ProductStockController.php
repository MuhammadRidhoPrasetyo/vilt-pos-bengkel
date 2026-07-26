<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Repositories\StoreRepository;
use App\Repositories\WarehouseLocationRepository;
use App\Repositories\WarehouseRepository;
use App\Services\ProductStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductStockController extends Controller
{
    public function __construct(
        private readonly ProductStockService $service,
        private readonly WarehouseRepository $warehouses,
        private readonly WarehouseLocationRepository $warehouseLocations,
        private readonly StoreRepository $stores
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $warehouseId = $request->string('warehouse_id')->toString();

        $query = ProductStock::query()
            ->with([
                'productVariant',
                'productVariant.product:id,name',
                'warehouse:id,name',
                'warehouseLocation:id,name,full_path,warehouse_id',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('productVariant', function ($vq) use ($search) {
                    $vq->where('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('receipt_name', 'like', "%{$search}%")
                        ->orWhere('name_suffix', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%");
                        });
                })
                    ->orWhereHas('warehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $user = auth()->user();
        if ($user && $user->store_id) {
            $query->whereHas('warehouse', function ($wq) use ($user) {
                $wq->where('store_id', $user->store_id);
            });
        }

        $records = $query->latest()->paginate(15)->withQueryString();

        // Summary Statistics
        $totalQuantity = (int) ProductStock::sum('quantity');
        $lowStockCount = (int) ProductStock::whereColumn('quantity', '<=', 'minimum_stock')->count();

        $variants = ProductVariant::with('product:id,name')->get()->map(fn ($v) => [
            'label' => $v->display_receipt_name,
            'value' => $v->id,
            'default_purchase_price' => (float) $v->default_purchase_price,
            'default_selling_price' => (float) $v->default_selling_price,
        ]);

        return Inertia::render('product-stocks/index', [
            'records' => $records->through(fn ($stock) => [
                'id' => $stock->id,
                'product_variant_id' => $stock->product_variant_id,
                'variant_display_name' => $stock->productVariant?->display_receipt_name ?? '-',
                'sku' => $stock->productVariant?->sku ?? '-',
                'barcode' => $stock->productVariant?->barcode ?? '-',
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse?->name ?? '-',
                'warehouse_location_id' => $stock->warehouse_location_id,
                'warehouse_location_name' => $stock->warehouseLocation?->full_path ?? $stock->warehouseLocation?->name ?? '-',
                'quantity' => (int) $stock->quantity,
                'minimum_stock' => (int) $stock->minimum_stock,
                'is_hidden' => (bool) $stock->is_hidden,
                'created_at' => $stock->created_at?->toDateTimeString(),
            ]),
            'summary' => [
                'total_quantity' => $totalQuantity,
                'total_items' => ProductStock::count(),
                'low_stock_count' => $lowStockCount,
            ],
            'filters' => [
                'search' => $search,
                'warehouse_id' => $warehouseId,
            ],
            'options' => [
                'warehouses' => $this->warehouses->options()->map(fn ($wh) => ['label' => $wh->name, 'value' => $wh->id]),
                'warehouseLocations' => $this->warehouseLocations->options()->map(fn ($loc) => [
                    'label' => ($loc->full_path ?? $loc->name),
                    'value' => $loc->id,
                    'warehouse_id' => $loc->warehouse_id,
                ]),
                'stores' => $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'variants' => $variants,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'warehouse_id' => ['required', 'uuid', Rule::exists('warehouses', 'id')],
            'warehouse_location_id' => ['nullable', 'uuid', Rule::exists('warehouse_locations', 'id')],
            'quantity' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'is_hidden' => ['boolean'],
        ]);

        $this->service->setStock($validated);

        return redirect()->back()->with('success', 'Stok gudang berhasil diinisialisasi.');
    }

    public function update(Request $request, ProductStock $productStock): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'warehouse_id' => ['required', 'uuid', Rule::exists('warehouses', 'id')],
            'warehouse_location_id' => ['nullable', 'uuid', Rule::exists('warehouse_locations', 'id')],
            'quantity' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'is_hidden' => ['boolean'],
        ]);

        $this->service->setStock($validated, $productStock->id);

        return redirect()->back()->with('success', 'Stok gudang berhasil diperbarui.');
    }

    public function destroy(ProductStock $productStock): RedirectResponse
    {
        $this->service->deleteStock($productStock);

        return redirect()->back()->with('success', 'Stok gudang berhasil dihapus.');
    }
}
