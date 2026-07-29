<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceOrderRequest;
use App\Http\Requests\UpdateServiceOrderRequest;
use App\Http\Resources\ProductVariantResource;
use App\Http\Resources\ServiceOrderResource;
use App\Http\Resources\UserResource;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\User;
use App\Repositories\ServiceOrderRepository;
use App\Repositories\StoreRepository;
use App\Services\ServiceOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderController extends Controller
{
    public function __construct(
        private readonly ServiceOrderRepository $repository,
        private readonly ServiceOrderService $service,
        private readonly StoreRepository $stores
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $storeId = $request->string('store_id')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $serviceOrders = $this->repository->paginate($search, $status, $storeId, $startDate, $endDate);
        $activeOrders = $this->repository->getActiveOrders($storeId);

        $mechanics = $this->getScopedMechanics($request, $storeId);

        $summary = [
            'total_count' => ServiceOrder::count(),
            'checkin_count' => ServiceOrder::where('status', 'checkin')->count(),
            'in_progress_count' => ServiceOrder::where('status', 'in_progress')->count(),
            'waiting_parts_count' => ServiceOrder::where('status', 'waiting_parts')->count(),
            'ready_count' => ServiceOrder::where('status', 'ready')->count(),
            'total_estimated' => (float) ServiceOrder::sum('estimated_total'),
        ];

        return Inertia::render('services/index', [
            'serviceOrders' => ServiceOrderResource::collection($serviceOrders),
            'activeOrders' => ServiceOrderResource::collection($activeOrders),
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'store_id' => $storeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'mechanics' => UserResource::collection($mechanics),
            ],
        ]);
    }

    public function tvDisplay(Request $request): Response
    {
        $storeId = $request->string('store_id')->toString();
        $activeOrders = $this->repository->getActiveOrders($storeId);

        $store = null;
        if ($storeId) {
            $store = Store::find($storeId);
        } else {
            $store = Store::first();
        }

        return Inertia::render('services/display', [
            'activeOrders' => ServiceOrderResource::collection($activeOrders),
            'store' => $store ? ['id' => $store->id, 'name' => $store->name] : null,
        ]);
    }

    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:checkin,in_progress,waiting_parts,ready,invoiced,cancelled'],
            'mechanic_id' => ['nullable', 'exists:users,id'],
        ]);

        $serviceOrder = ServiceOrder::findOrFail($id);
        $this->service->updateStatus($serviceOrder, $validated['status'], $validated['mechanic_id'] ?? null);

        return back()->with('success', 'Status SPK Servis berhasil diperbarui.');
    }

    public function create(Request $request): Response
    {
        $mechanics = $this->getScopedMechanics($request);

        $categories = ProductCategory::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['label' => $c->name, 'value' => $c->id]);

        $variants = ProductVariant::query()
            ->with(['product.category', 'product.brand', 'product.unit', 'media', 'product.media'])
            ->latest()
            ->get();

        return Inertia::render('services/create', [
            'options' => [
                'stores' => $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'mechanics' => UserResource::collection($mechanics),
                'categories' => $categories,
            ],
            'variants' => ProductVariantResource::collection($variants),
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        $serviceOrder = $this->repository->findWithRelations($id);

        $mechanics = $this->getScopedMechanics($request, $serviceOrder->store_id);

        $categories = ProductCategory::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['label' => $c->name, 'value' => $c->id]);

        $variants = ProductVariant::query()
            ->with(['product.category', 'product.brand', 'product.unit', 'media', 'product.media'])
            ->latest()
            ->get();

        return Inertia::render('services/edit', [
            'serviceOrder' => new ServiceOrderResource($serviceOrder),
            'options' => [
                'stores' => $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'mechanics' => UserResource::collection($mechanics),
                'categories' => $categories,
            ],
            'variants' => ProductVariantResource::collection($variants),
        ]);
    }

    private function getScopedMechanics(Request $request, ?string $storeId = null)
    {
        $userStoreId = $request->user()?->store_id;
        $targetStoreId = $userStoreId ?: ($storeId ?: null);

        return User::query()
            ->select(['id', 'name', 'email', 'store_id'])
            ->when($targetStoreId, function ($q) use ($targetStoreId) {
                $q->where(function ($sq) use ($targetStoreId) {
                    $sq->where('store_id', $targetStoreId)
                        ->orWhereNull('store_id');
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function show(string $id): Response
    {
        $serviceOrder = $this->repository->findWithRelations($id);

        return Inertia::render('services/show', [
            'serviceOrder' => new ServiceOrderResource($serviceOrder),
        ]);
    }

    public function store(StoreServiceOrderRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('services.index')->with('success', 'Surat Perintah Kerja (SPK) Servis berhasil dibuat.');
    }

    public function update(UpdateServiceOrderRequest $request, string $id): RedirectResponse
    {
        $serviceOrder = ServiceOrder::findOrFail($id);
        $this->service->update($serviceOrder, $request->validated());

        return redirect()->route('services.index')->with('success', 'Surat Perintah Kerja (SPK) Servis berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $serviceOrder = ServiceOrder::findOrFail($id);
        $this->service->delete($serviceOrder);

        return redirect()->route('services.index')->with('success', 'Surat Perintah Kerja (SPK) Servis berhasil dihapus.');
    }
}
