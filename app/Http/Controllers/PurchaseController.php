<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Partner;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Repositories\PurchaseRepository;
use App\Repositories\StoreRepository;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseRepository $repository,
        private readonly PurchaseService $service,
        private readonly StoreRepository $stores
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $storeId = $request->string('store_id')->toString();
        $supplierId = $request->string('supplier_id')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $purchases = $this->repository->paginate($search, $storeId, $supplierId, $startDate, $endDate);

        $suppliers = Partner::query()
            ->whereHas('roles', function ($q) {
                $q->where('name', 'like', '%supplier%')
                    ->orWhere('name', 'like', '%vendor%');
            })
            ->orWhereDoesntHave('roles')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['label' => $p->name, 'value' => $p->id]);

        $summary = [
            'total_count' => Purchase::count(),
            'total_amount' => (float) Purchase::sum('price'),
            'month_amount' => (float) Purchase::whereMonth('purchase_date', now()->month)
                ->whereYear('purchase_date', now()->year)
                ->sum('price'),
        ];

        return Inertia::render('purchases/index', [
            'purchases' => PurchaseResource::collection($purchases),
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'store_id' => $storeId,
                'supplier_id' => $supplierId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'suppliers' => $suppliers,
            ],
        ]);
    }

    public function create(): Response
    {
        $suppliers = Partner::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['label' => $p->name, 'value' => $p->id]);

        $categories = ProductCategory::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['label' => $c->name, 'value' => $c->id]);

        $variants = ProductVariant::with([
            'product:id,name,product_category_id,brand_id,unit_id',
            'product.category:id,name',
            'product.brand:id,name',
            'product.unit:id,name,symbol',
            'media',
            'product.media',
        ])
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->display_receipt_name,
                'product_name' => $v->product?->name ?? '',
                'category_id' => $v->product?->product_category_id,
                'category_name' => $v->product?->category?->name ?? '-',
                'brand_name' => $v->product?->brand?->name ?? '-',
                'unit_name' => $v->product?->unit?->name ?? 'Pcs',
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'image_url' => $v->getFirstMediaUrl('images', 'thumb')
                    ?: $v->product?->getFirstMediaUrl('images', 'thumb')
                    ?: null,
                'default_purchase_price' => (float) ($v->default_purchase_price ?? 0),
            ]);

        return Inertia::render('purchases/create', [
            'options' => [
                'stores' => $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'suppliers' => $suppliers,
                'categories' => $categories,
                'variants' => $variants,
            ],
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), (int) auth()->id());

        return redirect()->route('purchases.index')->with('success', 'Transaksi pembelian berhasil disimpan.');
    }

    public function show(string $id): Response
    {
        $purchase = $this->repository->findWithRelations($id);

        return Inertia::render('purchases/show', [
            'purchase' => new PurchaseResource($purchase),
        ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        $purchase = $this->repository->findWithRelations($id);
        $this->service->delete($purchase);

        return redirect()->route('purchases.index')->with('success', 'Transaksi pembelian berhasil dibatalkan dan stok dikembalikan.');
    }
}
