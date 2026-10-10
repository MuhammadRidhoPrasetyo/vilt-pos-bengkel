<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionPaymentAttemptRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\ProductVariantResource;
use App\Http\Resources\ServiceOrderResource;
use App\Http\Resources\TransactionResource;
use App\Models\DiscountType;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Repositories\StoreRepository;
use App\Repositories\TransactionRepository;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionRepository $repository,
        private readonly TransactionService $service,
        private readonly StoreRepository $stores
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isOwner = (bool) $user?->hasRole('owner');
        $canFilterStore = $isOwner;

        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        if ($type === 'all') {
            $type = '';
        }
        $paymentStatus = $request->string('payment_status')->toString();
        if ($paymentStatus === 'all') {
            $paymentStatus = '';
        }
        $storeId = $request->string('store_id')->toString();
        if ($storeId === 'all') {
            $storeId = '';
        }
        if (! $canFilterStore) {
            $storeId = $user?->store_id ?? '';
        }
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $transactions = $this->repository->paginate($search, $type, $paymentStatus, $storeId, $startDate, $endDate);

        $canViewSummary = $user?->can('transactions.summary.view') ?? false;
        $canViewProfit = $user?->can('transactions.profit.view') ?? false;

        $baseQuery = Transaction::query()->when($storeId, fn ($q) => $q->where('store_id', $storeId));
        $summary = [
            'total_count' => (clone $baseQuery)->count(),
            'paid_count' => (clone $baseQuery)->where('payment_status', 'paid')->count(),
            'total_grand_total' => $canViewSummary ? (float) (clone $baseQuery)->where('status', 'completed')->sum('grand_total') : null,
            'total_profit' => $canViewProfit ? (float) (clone $baseQuery)->where('status', 'completed')->sum('total_profit') : null,
            'total_unpaid' => $canViewSummary ? (float) (clone $baseQuery)->where('payment_status', 'unpaid')->sum('grand_total') : null,
            'retail_count' => (clone $baseQuery)->where('type', 'retail')->count(),
            'service_count' => (clone $baseQuery)->where('type', 'service')->count(),
        ];

        if ($canFilterStore) {
            $storeOptions = $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]);
        } else {
            $userStore = $user?->store;
            $storeOptions = $userStore
                ? collect([['label' => $userStore->name, 'value' => $userStore->id]])
                : collect();
        }

        return Inertia::render('transactions/index', [
            'transactions' => TransactionResource::collection($transactions),
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'payment_status' => $paymentStatus,
                'store_id' => $storeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'canFilterStore' => $canFilterStore,
            'options' => [
                'stores' => $storeOptions,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $isOwner = (bool) $user?->hasRole('owner');
        $canFilterStore = $isOwner;

        if ($canFilterStore) {
            $storeOptions = $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]);
            $storeId = $request->string('store_id')->toString() ?: ($user?->store_id ?: $this->stores->options()->first()?->id);
        } else {
            $userStore = $user?->store;
            $storeOptions = $userStore
                ? collect([['label' => $userStore->name, 'value' => $userStore->id]])
                : collect();
            $storeId = $user?->store_id;
        }

        $payments = Payment::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['label' => $p->name, 'value' => $p->id, 'type' => $p->type]);

        $customers = Partner::query()
            ->with('vehicles')
            ->select(['id', 'name', 'phone', 'email'])
            ->orderBy('name')
            ->get();

        $discountTypes = DiscountType::query()
            ->select(['id', 'name', 'description'])
            ->orderBy('name')
            ->get();

        $readyServiceOrders = ServiceOrder::query()
            ->with(['store', 'customer', 'vehicle', 'items.productVariant.product', 'items.mechanic'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereIn('status', ['ready', 'in_progress'])
            ->whereNull('transaction_id')
            ->orderBy('checkin_at', 'asc')
            ->get();

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with(['product.category', 'product.brand', 'product.unit', 'media', 'product.media', 'stocks.warehouse', 'discounts.discountType'])
            ->latest()
            ->get();

        return Inertia::render('transactions/create', [
            'activeStoreId' => $storeId,
            'isStoreLocked' => ! $canFilterStore,
            'options' => [
                'stores' => $storeOptions,
                'payments' => $payments,
                'customers' => $customers,
                'discountTypes' => $discountTypes,
            ],
            'readyServiceOrders' => ServiceOrderResource::collection($readyServiceOrders),
            'variants' => ProductVariantResource::collection($variants),
            'preselectedServiceOrderId' => $request->string('service_order_id')->toString(),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $transaction = $this->service->create($request->validated(), $request->user()->id);

        return redirect()->route('transactions.show', $transaction->id)
            ->with('success', 'Transaksi POS berhasil diproses dan disimpan.')
            ->with('print_transaction_id', $transaction->id);
    }

    public function show(string $id): Response
    {
        $transaction = $this->repository->findWithRelations($id, true);

        return Inertia::render('transactions/show', [
            'transaction' => new TransactionResource($transaction),
            'paymentOptions' => Payment::query()
                ->orderBy('name')
                ->get()
                ->map(fn ($payment) => [
                    'label' => $payment->name,
                    'value' => $payment->id,
                    'type' => $payment->type,
                ]),
        ]);
    }

    public function storePaymentAttempt(StoreTransactionPaymentAttemptRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->service->recordPaymentAttempt($transaction, $request->validated(), $request->user()->id);

        return redirect()->route('transactions.show', $transaction->id)
            ->with('success', 'Pembayaran transaksi berhasil dicatat.');
    }

    public function print(string $id): Response
    {
        $transaction = $this->repository->findWithRelations($id, true);

        return Inertia::render('transactions/print', [
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        $transaction = Transaction::findOrFail($id);
        $this->service->delete($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dibatalkan dan stok dikembalikan.');
    }
}
